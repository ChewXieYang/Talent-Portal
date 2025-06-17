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
    <title>My Orders - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .view-toggle {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .view-btn {
            padding: 12px 24px;
            border: 2px solid #005eff;
            background: white;
            color: #005eff;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .view-btn.active {
            background: #005eff;
            color: white;
        }
        
        .view-btn:hover {
            background: #005eff;
            color: white;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-form select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .order-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f8f9fa;
        }
        
        .order-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .order-id {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        
        .order-amount {
            font-size: 1.3em;
            font-weight: bold;
            color: #28a745;
        }
        
        .order-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #666;
            flex-wrap: wrap;
        }
        
        .order-content {
            padding: 20px;
        }
        
        .order-items {
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-details h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .item-details p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        
        .item-price {
            font-weight: bold;
            color: #28a745;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-in-progress {
            background: #e7f3ff;
            color: #0c5460;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary {
            background: #005eff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .contact-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .contact-info h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .order-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .order-meta {
                flex-direction: column;
                gap: 5px;
            }
            
            .order-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
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
                            // Get order items
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
                                <!-- Seller Actions -->
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
                                <!-- Buyer Actions -->
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
                            
                            <!-- Common Actions -->
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
</body>
</html>