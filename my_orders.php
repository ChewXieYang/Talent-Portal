<?php
include 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_order_status'])) {
        $order_id = intval($_POST['order_id']);
        $new_status = $_POST['new_status'];
        
        // Verify user owns this order (as seller)
        $verify_stmt = $conn->prepare("SELECT seller_id FROM orders WHERE id = ?");
        $verify_stmt->bind_param("i", $order_id);
        $verify_stmt->execute();
        $order_data = $verify_stmt->get_result()->fetch_assoc();
        
        if ($order_data && $order_data['seller_id'] == $user_id) {
            $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_status, $order_id);
            
            if ($update_stmt->execute()) {
                $message = 'Order status updated successfully!';
                $messageType = 'success';
                
                // Log activity
                $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description) VALUES (?, ?, ?)");
                $action_type = 'order_status_update';
                $action_desc = "Updated order #$order_id status to $new_status";
                $log_stmt->bind_param("iss", $user_id, $action_type, $action_desc);
                $log_stmt->execute();
            } else {
                $message = 'Error updating order status.';
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['cancel_order'])) {
        $order_id = intval($_POST['order_id']);
        
        // Verify user owns this order (as buyer) and it's still pending
        $verify_stmt = $conn->prepare("SELECT buyer_id, status FROM orders WHERE id = ?");
        $verify_stmt->bind_param("i", $order_id);
        $verify_stmt->execute();
        $order_data = $verify_stmt->get_result()->fetch_assoc();
        
        if ($order_data && $order_data['buyer_id'] == $user_id && $order_data['status'] === 'pending') {
            $cancel_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $cancel_stmt->bind_param("i", $order_id);
            
            if ($cancel_stmt->execute()) {
                $message = 'Order cancelled successfully.';
                $messageType = 'success';
            } else {
                $message = 'Error cancelling order.';
                $messageType = 'error';
            }
        }
    }
}

// Get filter parameters
$view = isset($_GET['view']) ? $_GET['view'] : 'purchases';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query based on view
if ($view === 'sales') {
    // Orders where user is the seller
    $sql = "SELECT o.*, u.full_name as buyer_name, u.username as buyer_username, u.contact_email as buyer_email,
                   COUNT(oi.id) as item_count
            FROM orders o
            JOIN users u ON o.buyer_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.seller_id = ?";
} else {
    // Orders where user is the buyer
    $sql = "SELECT o.*, u.full_name as seller_name, u.username as seller_username, u.contact_email as seller_email,
                   COUNT(oi.id) as item_count
            FROM orders o
            JOIN users u ON o.seller_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.buyer_id = ?";
}

$params = [$user_id];
$types = "i";

// Add status filter
if (!empty($status_filter)) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " GROUP BY o.id ORDER BY o.order_date DESC";

// Execute query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/my_orders.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <title>My Orders - MMU Talent Showcase</title>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="container">
                <div class="page-header">
                    <h1>My Orders</h1>
                    <p>Track your purchases and manage your sales</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <!-- View Toggle -->
                <div class="view-toggle">
                    <a href="my_orders.php?view=purchases<?= !empty($status_filter) ? '&status=' . $status_filter : '' ?>" 
                    class="view-btn <?= $view === 'purchases' ? 'active' : '' ?>">
                        My Purchases
                    </a>
                    <a href="my_orders.php?view=sales<?= !empty($status_filter) ? '&status=' . $status_filter : '' ?>" 
                    class="view-btn <?= $view === 'sales' ? 'active' : '' ?>">
                        My Sales
                    </a>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">

                        <label for="status">Filter by Status:</label>
                        <select name="status" id="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>

                        <?php if (!empty($status_filter)): ?>
                            <a href="my_orders.php?view=<?= $view ?>" class="btn btn-secondary">Clear Filter</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Orders List -->
                <?php if ($orders->num_rows > 0): ?>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-title">
                                    <div>
                                        <span class="order-id">Order #<?= $order['id'] ?></span>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                        </span>
                                    </div>
                                    <div class="order-amount">RM <?= number_format($order['total_amount'], 2) ?></div>
                                </div>

                                <div class="order-meta">
                                    <span>📅 <?= date('M j, Y \a\t g:i A', strtotime($order['order_date'])) ?></span>
                                    <span>📦 <?= $order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?></span>
                                    <?php if ($view === 'purchases'): ?>
                                        <span>👤 Seller: <?= htmlspecialchars($order['seller_name']) ?></span>
                                    <?php else: ?>
                                        <span>👤 Buyer: <?= htmlspecialchars($order['buyer_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="order-content">
                                <!-- Order Items -->
                                <div class="order-items">
                                    <?php
                                    $items_stmt = $conn->prepare("
                                        SELECT oi.*, s.service_title, s.service_type
                                        FROM order_items oi
                                        JOIN services s ON oi.service_id = s.id
                                        WHERE oi.order_id = ?
                                    ");
                                    $items_stmt->bind_param("i", $order['id']);
                                    $items_stmt->execute();
                                    $items = $items_stmt->get_result();

                                    while ($item = $items->fetch_assoc()):
                                    ?>
                                        <div class="order-item">
                                            <div class="item-details">
                                                <h4><?= htmlspecialchars($item['service_title']) ?></h4>
                                                <p>
                                                    <?= ucfirst($item['service_type']) ?> • 
                                                    Qty: <?= $item['quantity'] ?> • 
                                                    Unit Price: RM <?= number_format($item['unit_price'], 2) ?>
                                                </p>
                                                <?php if ($item['custom_requirements']): ?>
                                                    <p><strong>Requirements:</strong> <?= htmlspecialchars($item['custom_requirements']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="item-price">
                                                RM <?= number_format($item['unit_price'] * $item['quantity'], 2) ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>

                                <!-- Contact Information -->
                                <div class="contact-info">
                                    <?php if ($view === 'purchases'): ?>
                                        <h4>Seller Contact</h4>
                                        <p><strong><?= htmlspecialchars($order['seller_name']) ?></strong></p>
                                        <p>📧 <?= htmlspecialchars($order['seller_email']) ?></p>
                                        <p>👤 @<?= htmlspecialchars($order['seller_username']) ?></p>
                                    <?php else: ?>
                                        <h4>Buyer Contact</h4>
                                        <p><strong><?= htmlspecialchars($order['buyer_name']) ?></strong></p>
                                        <p>📧 <?= htmlspecialchars($order['buyer_email']) ?></p>
                                        <p>👤 @<?= htmlspecialchars($order['buyer_username']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <!-- Order Actions -->
                                <div class="order-actions">
                                    <?php if ($view === 'sales' && $order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="update_order_status" value="1">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <input type="hidden" name="new_status" value="confirmed">
                                                <button type="submit" class="btn btn-success">Confirm Order</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($order['status'] === 'confirmed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="update_order_status" value="1">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <input type="hidden" name="new_status" value="in_progress">
                                                <button type="submit" class="btn btn-primary">Start Work</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($order['status'] === 'in_progress'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="update_order_status" value="1">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <input type="hidden" name="new_status" value="completed">
                                                <button type="submit" class="btn btn-success">Mark Complete</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php elseif ($view === 'purchases'): ?>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                                <input type="hidden" name="cancel_order" value="1">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <button type="submit" class="btn btn-danger">Cancel Order</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($order['status'] === 'completed'): ?>
                                            <a href="leave_review.php?order_id=<?= $order['id'] ?>" class="btn btn-warning">
                                                Leave Review
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Common Action -->
                                    <a href="mailto:<?= $view === 'sales' ? htmlspecialchars($order['buyer_email']) : htmlspecialchars($order['seller_email']) ?>" 
                                       class="btn btn-secondary">
                                        Send Email
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No orders found</h3>
                        <?php if ($view === 'purchases'): ?>
                            <p>You haven't made any purchases yet.</p>
                            <a href="services.php" class="btn btn-primary">Browse Services</a>
                        <?php else: ?>
                            <p>You haven't received any orders yet.</p>
                            <a href="my_services.php" class="btn btn-primary">Manage Your Services</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="profile.php" class="btn btn-secondary">Back to Profile</a>
                    <?php if ($view === 'purchases'): ?>
                        <a href="services.php" class="btn btn-primary">Browse More Services</a>
                    <?php else: ?>
                        <a href="my_services.php" class="btn btn-primary">Manage Services</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>