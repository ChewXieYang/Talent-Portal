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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_service'])) {
        $talent_id = !empty($_POST['talent_id']) ? intval($_POST['talent_id']) : null;
        $service_title = trim($_POST['service_title']);
        $service_description = trim($_POST['service_description']);
        $price = floatval($_POST['price']);
        $service_type = $_POST['service_type'];
        $delivery_time = trim($_POST['delivery_time']);
        $terms_conditions = trim($_POST['terms_conditions']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        if (empty($service_title) || $price <= 0) {
            $message = 'Service title and valid price are required.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO services (user_id, talent_id, service_title, service_description, price, service_type, delivery_time, terms_conditions, is_available) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iissdsssi", $user_id, $talent_id, $service_title, $service_description, $price, $service_type, $delivery_time, $terms_conditions, $is_available);
            
            if ($stmt->execute()) {
                $message = 'Service added successfully!';
                $messageType = 'success';
                
                // Log activity
                $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description) VALUES (?, ?, ?)");
                $action_type = 'service_create';
                $action_desc = 'Created new service: ' . $service_title;
                $log_stmt->bind_param("iss", $user_id, $action_type, $action_desc);
                $log_stmt->execute();
            } else {
                $message = 'Error adding service.';
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['update_service'])) {
        $service_id = intval($_POST['service_id']);
        $talent_id = !empty($_POST['talent_id']) ? intval($_POST['talent_id']) : null;
        $service_title = trim($_POST['service_title']);
        $service_description = trim($_POST['service_description']);
        $price = floatval($_POST['price']);
        $service_type = $_POST['service_type'];
        $delivery_time = trim($_POST['delivery_time']);
        $terms_conditions = trim($_POST['terms_conditions']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        $stmt = $conn->prepare("
            UPDATE services 
            SET talent_id = ?, service_title = ?, service_description = ?, price = ?, service_type = ?, delivery_time = ?, terms_conditions = ?, is_available = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("issdssssii", $talent_id, $service_title, $service_description, $price, $service_type, $delivery_time, $terms_conditions, $is_available, $service_id, $user_id);
        
        if ($stmt->execute()) {
            $message = 'Service updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating service.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['delete_service'])) {
        $service_id = intval($_POST['service_id']);
        
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $service_id, $user_id);
        
        if ($stmt->execute()) {
            $message = 'Service deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting service.';
            $messageType = 'error';
        }
    }
}

// Get user's talents for dropdown
$talents_stmt = $conn->prepare("
    SELECT ut.id, ut.talent_title, tc.category_name 
    FROM user_talents ut
    JOIN talent_categories tc ON ut.category_id = tc.id
    WHERE ut.user_id = ?
    ORDER BY ut.talent_title
");
$talents_stmt->bind_param("i", $user_id);
$talents_stmt->execute();
$talents_result = $talents_stmt->get_result();

// Get user's services
$services_stmt = $conn->prepare("
    SELECT s.*, ut.talent_title, tc.category_name,
           (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.service_id = s.id) as total_orders,
           (SELECT AVG(rating) FROM reviews r JOIN orders o ON r.order_id = o.id WHERE o.seller_id = s.user_id) as avg_rating
    FROM services s
    LEFT JOIN user_talents ut ON s.talent_id = ut.id
    LEFT JOIN talent_categories tc ON ut.category_id = tc.id
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
");
$services_stmt->bind_param("i", $user_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();

// Get service to edit
$editing_service = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $conn->prepare("SELECT * FROM services WHERE id = ? AND user_id = ?");
    $edit_stmt->bind_param("ii", $edit_id, $user_id);
    $edit_stmt->execute();
    $editing_service = $edit_stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
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
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #005eff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .services-grid {
            display: grid;
            gap: 20px;
        }
        
        .service-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
            position: relative;
        }
        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .service-title {
            font-weight: bold;
            color: #333;
            font-size: 1.2em;
            margin-bottom: 5px;
        }
        
        .service-price {
            font-size: 1.5em;
            font-weight: bold;
            color: #28a745;
        }
        
        .service-meta {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .service-description {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .service-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .service-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .service-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        
        .status-unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Manage Your Services</h1>
            <p>Offer your skills and talents to the MMU community</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <!-- Add/Edit Service Form -->
        <div class="section">
            <h2 class="section-title"><?= $editing_service ? 'Edit Service' : 'Add New Service' ?></h2>
            
            <form id="serviceForm" method="POST" action="my_services.php">
                <?php if ($editing_service): ?>
                    <input type="hidden" name="update_service" value="1">
                    <input type="hidden" name="service_id" value="<?= $editing_service['id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="add_service" value="1">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="service_title">Service Title <span class="required">*</span></label>
                    <input type="text" id="service_title" name="service_title" required 
                           value="<?= $editing_service ? htmlspecialchars($editing_service['service_title']) : '' ?>"
                           placeholder="e.g., Custom Logo Design, Guitar Lessons, Photography Session">
                </div>
                
                <div class="form-group">
                    <label for="service_description">Description</label>
                    <textarea id="service_description" name="service_description" rows="4"
                              placeholder="Describe what you offer, your experience, and what clients can expect"><?= $editing_service ? htmlspecialchars($editing_service['service_description']) : '' ?></textarea>
                </div>
                
                <div class="form-row-3">
                    <div class="form-group">
                        <label for="talent_id">Related Talent</label>
                        <select id="talent_id" name="talent_id">
                            <option value="">Select a talent (optional)</option>
                            <?php 
                            $talents_result->data_seek(0);
                            while ($talent = $talents_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $talent['id'] ?>" 
                                        <?= ($editing_service && $editing_service['talent_id'] == $talent['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($talent['talent_title']) ?> (<?= htmlspecialchars($talent['category_name']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_type">Service Type</label>
                        <select id="service_type" name="service_type">
                            <option value="commission" <?= ($editing_service && $editing_service['service_type'] == 'commission') ? 'selected' : '' ?>>Commission</option>
                            <option value="gig" <?= ($editing_service && $editing_service['service_type'] == 'gig') ? 'selected' : '' ?>>Gig/Service</option>
                            <option value="product" <?= ($editing_service && $editing_service['service_type'] == 'product') ? 'selected' : '' ?>>Product</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (RM) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0.01" required 
                               value="<?= $editing_service ? $editing_service['price'] : '' ?>"
                               placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="delivery_time">Delivery Time</label>
                        <input type="text" id="delivery_time" name="delivery_time" 
                               value="<?= $editing_service ? htmlspecialchars($editing_service['delivery_time']) : '' ?>"
                               placeholder="e.g., 3-5 days, 1 week, 2 hours">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_available" name="is_available" value="1"
                                   <?= (!$editing_service || $editing_service['is_available']) ? 'checked' : '' ?>>
                            <label for="is_available">Available for booking</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="terms_conditions">Terms & Conditions</label>
                    <textarea id="terms_conditions" name="terms_conditions" rows="3"
                              placeholder="Any specific terms, requirements, or conditions for this service"><?= $editing_service ? htmlspecialchars($editing_service['terms_conditions']) : '' ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <?= $editing_service ? 'Update Service' : 'Add Service' ?>
                    </button>
                    <?php if ($editing_service): ?>
                        <a href="my_services.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Services List -->
        <div class="section">
            <h2 class="section-title">Your Services</h2>
            
            <?php if ($services_result->num_rows > 0): ?>
                <div class="services-grid">
                    <?php while ($service = $services_result->fetch_assoc()): ?>
                        <div class="service-card">
                            <div class="service-status <?= $service['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                                <?= $service['is_available'] ? 'Available' : 'Unavailable' ?>
                            </div>
                            
                            <div class="service-header">
                                <div>
                                    <div class="service-title"><?= htmlspecialchars($service['service_title']) ?></div>
                                    <div class="service-meta">
                                        <?= ucfirst($service['service_type']) ?>
                                        <?php if ($service['talent_title']): ?>
                                            • <?= htmlspecialchars($service['talent_title']) ?>
                                        <?php endif; ?>
                                        <?php if ($service['delivery_time']): ?>
                                            • ⏱️ <?= htmlspecialchars($service['delivery_time']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="service-price">RM <?= number_format($service['price'], 2) ?></div>
                            </div>
                            
                            <?php if ($service['service_description']): ?>
                                <div class="service-description">
                                    <?= nl2br(htmlspecialchars($service['service_description'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="service-stats">
                                <div class="stat-item">
                                    <span>📦</span>
                                    <span><?= $service['total_orders'] ?> orders</span>
                                </div>
                                <?php if ($service['avg_rating']): ?>
                                    <div class="stat-item">
                                        <span>⭐</span>
                                        <span><?= number_format($service['avg_rating'], 1) ?> rating</span>
                                    </div>
                                <?php endif; ?>
                                <div class="stat-item">
                                    <span>📅</span>
                                    <span>Added <?= date('M j, Y', strtotime($service['created_at'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="service-actions">
                                <a href="service_details.php?id=<?= $service['id'] ?>" class="btn btn-secondary">View</a>
                                <a href="my_services.php?edit=<?= $service['id'] ?>" class="btn btn-warning">Edit</a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this service?')">
                                    <input type="hidden" name="delete_service" value="1">
                                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No services yet</h3>
                    <p>Start offering your skills to the MMU community by creating your first service above.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="profile.php" class="btn btn-secondary">Back to Profile</a>
            <a href="services.php" class="btn btn-primary">View Marketplace</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>