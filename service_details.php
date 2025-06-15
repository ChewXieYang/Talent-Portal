<?php
include 'includes/db.php';

// Get service ID from URL
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($service_id <= 0) {
    header('Location: services.php');
    exit;
}

// Get service details with seller information
$stmt = $conn->prepare("
    SELECT s.*, u.full_name, u.username, u.profile_picture_url, u.contact_email, u.short_bio,
           ut.talent_title, tc.category_name,
           (SELECT AVG(rating) FROM reviews r JOIN orders o ON r.order_id = o.id WHERE o.seller_id = s.user_id) as avg_rating,
           (SELECT COUNT(*) FROM reviews r JOIN orders o ON r.order_id = o.id WHERE o.seller_id = s.user_id) as review_count,
           (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.service_id = s.id AND o.status = 'completed') as completed_orders
    FROM services s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN user_talents ut ON s.talent_id = ut.id
    LEFT JOIN talent_categories tc ON ut.category_id = tc.id
    WHERE s.id = ? AND s.is_available = 1
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    header('Location: services.php');
    exit;
}

// Get recent reviews for this seller
$reviews_stmt = $conn->prepare("
    SELECT r.*, u.full_name as reviewer_name, u.username as reviewer_username,
           o.id as order_id, oi.service_id
    FROM reviews r
    JOIN orders o ON r.order_id = o.id
    JOIN users u ON r.reviewer_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.seller_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$reviews_stmt->bind_param("i", $service['user_id']);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result();

// Get other services by the same seller
$other_services_stmt = $conn->prepare("
    SELECT s.*, ut.talent_title
    FROM services s
    LEFT JOIN user_talents ut ON s.talent_id = ut.id
    WHERE s.user_id = ? AND s.id != ? AND s.is_available = 1
    ORDER BY s.created_at DESC
    LIMIT 4
");
$other_services_stmt->bind_param("ii", $service['user_id'], $service_id);
$other_services_stmt->execute();
$other_services = $other_services_stmt->get_result();

$message = '';
$messageType = '';

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $message = 'Please login to add items to cart.';
        $messageType = 'error';
    } elseif ($_SESSION['user_id'] == $service['user_id']) {
        $message = 'You cannot purchase your own service.';
        $messageType = 'error';
    } else {
        $quantity = intval($_POST['quantity']);
        $custom_requirements = trim($_POST['custom_requirements']);
        
        if ($quantity < 1) $quantity = 1;
        if ($quantity > 10) $quantity = 10;
        
        // Check if item already in cart
        $check_stmt = $conn->prepare("SELECT id, quantity FROM shopping_cart WHERE user_id = ? AND service_id = ?");
        $check_stmt->bind_param("ii", $_SESSION['user_id'], $service_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing item
            $new_quantity = $existing['quantity'] + $quantity;
            if ($new_quantity > 10) $new_quantity = 10;
            
            $update_stmt = $conn->prepare("UPDATE shopping_cart SET quantity = ?, custom_requirements = ? WHERE id = ?");
            $update_stmt->bind_param("isi", $new_quantity, $custom_requirements, $existing['id']);
            $success = $update_stmt->execute();
        } else {
            // Add new item
            $insert_stmt = $conn->prepare("INSERT INTO shopping_cart (user_id, service_id, quantity, custom_requirements, price) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("iiisd", $_SESSION['user_id'], $service_id, $quantity, $custom_requirements, $service['price']);
            $success = $insert_stmt->execute();
        }
        
        if ($success) {
            $message = 'Service added to cart successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error adding service to cart.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($service['service_title']) ?> - MMU Talent Showcase</title>
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
        
        .service-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .service-main {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .service-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .service-header {
            padding: 30px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .service-title {
            font-size: 2em;
            color: #333;
            margin-bottom: 15px;
        }
        
        .service-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .service-price {
            font-size: 2.5em;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .service-content {
            padding: 30px;
        }
        
        .section-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .service-description {
            color: #555;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .seller-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .seller-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .seller-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .seller-info h3 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .seller-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 5px;
        }
        
        .stars {
            color: #ffc107;
        }
        
        .seller-stats {
            font-size: 14px;
            color: #666;
        }
        
        .seller-bio {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .purchase-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
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
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        .quantity-btn:hover {
            background: #e9ecef;
        }
        
        .quantity-input {
            width: 80px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
        }
        
        .total-price {
            font-size: 1.5em;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-block;
            text-align: center;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: #005eff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0044cc;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-outline {
            background: white;
            color: #005eff;
            border: 2px solid #005eff;
        }
        
        .btn-outline:hover {
            background: #005eff;
            color: white;
        }
        
        .reviews-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .review-item {
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .reviewer-name {
            font-weight: bold;
            color: #333;
        }
        
        .review-date {
            font-size: 12px;
            color: #666;
        }
        
        .review-rating {
            color: #ffc107;
            margin-bottom: 8px;
        }
        
        .review-text {
            color: #555;
            line-height: 1.6;
        }
        
        .other-services {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .other-service-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background: #fafafa;
            transition: transform 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .other-service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .other-service-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .other-service-price {
            font-weight: bold;
            color: #28a745;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #005eff;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
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
            .service-layout {
                grid-template-columns: 1fr;
            }
            
            .purchase-card {
                position: static;
            }
            
            .other-services {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a> > 
            <a href="services.php">Services</a> > 
            <?php if ($service['category_name']): ?>
                <a href="services.php?category=<?= $service['talent_id'] ?>"><?= htmlspecialchars($service['category_name']) ?></a> > 
            <?php endif; ?>
            <?= htmlspecialchars($service['service_title']) ?>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="service-layout">
            <!-- Main Content -->
            <div class="service-main">
                <div class="service-header">
                    <h1 class="service-title"><?= htmlspecialchars($service['service_title']) ?></h1>
                    
                    <div class="service-meta">
                        <div class="meta-item">
                            <span>📋</span>
                            <span><?= ucfirst($service['service_type']) ?></span>
                        </div>
                        
                        <?php if ($service['category_name']): ?>
                            <div class="meta-item">
                                <span>🎨</span>
                                <span><?= htmlspecialchars($service['category_name']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($service['delivery_time']): ?>
                            <div class="meta-item">
                                <span>⏱️</span>
                                <span><?= htmlspecialchars($service['delivery_time']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <span>📦</span>
                            <span><?= $service['completed_orders'] ?> completed orders</span>
                        </div>
                    </div>
                    
                    <div class="service-price">RM <?= number_format($service['price'], 2) ?></div>
                </div>
                
                <div class="service-content">
                    <h3 class="section-title">Service Description</h3>
                    <div class="service-description">
                        <?= nl2br(htmlspecialchars($service['service_description'] ?: 'No description provided.')) ?>
                    </div>
                    
                    <?php if ($service['terms_conditions']): ?>
                        <h3 class="section-title">Terms & Conditions</h3>
                        <div class="service-description">
                            <?= nl2br(htmlspecialchars($service['terms_conditions'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="service-sidebar">
                <!-- Purchase Card -->
                <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $service['user_id']): ?>
                    <div class="purchase-card">
                        <h3>Order This Service</h3>
                        
                        <form method="POST" id="purchaseForm">
                            <input type="hidden" name="add_to_cart" value="1">
                            
                            <div class="form-group">
                                <label for="quantity">Quantity</label>
                                <div class="quantity-controls">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">−</button>
                                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="10" 
                                           class="quantity-input" onchange="updateTotal()">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="custom_requirements">Custom Requirements (Optional)</label>
                                <textarea id="custom_requirements" name="custom_requirements" rows="4"
                                          placeholder="Describe any specific requirements or preferences for your order"></textarea>
                            </div>
                            
                            <div class="total-price" id="totalPrice">
                                Total: RM <?= number_format($service['price'], 2) ?>
                            </div>
                            
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button type="submit" class="btn btn-primary">Add to Cart</button>
                                <a href="cart.php" class="btn btn-outline">View Cart</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Login to Purchase</a>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- Seller Card -->
                <div class="seller-card">
                    <div class="seller-header">
                        <img src="<?= htmlspecialchars($service['profile_picture_url'] ?: 'uploads/avatars/default-avatar.jpg') ?>" 
                             alt="<?= htmlspecialchars($service['full_name']) ?>" 
                             class="seller-avatar">
                        <div class="seller-info">
                            <h3><?= htmlspecialchars($service['full_name']) ?></h3>
                            <p style="margin: 0; color: #666;">@<?= htmlspecialchars($service['username']) ?></p>
                            
                            <?php if ($service['avg_rating']): ?>
                                <div class="seller-rating">
                                    <span class="stars">
                                        <?php 
                                        $rating = round($service['avg_rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '★' : '☆';
                                        }
                                        ?>
                                    </span>
                                    <span>(<?= number_format($service['avg_rating'], 1) ?>)</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="seller-stats">
                                <?= $service['review_count'] ?> reviews • <?= $service['completed_orders'] ?> orders completed
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($service['short_bio']): ?>
                        <div class="seller-bio">
                            <?= nl2br(htmlspecialchars($service['short_bio'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="view_profile.php?id=<?= $service['user_id'] ?>" class="btn btn-secondary">
                        View Full Profile
                    </a>
                    
                    <a href="mailto:<?= htmlspecialchars($service['contact_email']) ?>" class="btn btn-outline">
                        Contact Seller
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <?php if ($reviews->num_rows > 0): ?>
            <div class="reviews-section">
                <h3 class="section-title">Recent Reviews for <?= htmlspecialchars($service['full_name']) ?></h3>
                
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <span class="reviewer-name"><?= htmlspecialchars($review['reviewer_name']) ?></span>
                            <span class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                        </div>
                        
                        <div class="review-rating">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['rating'] ? '★' : '☆';
                            }
                            ?>
                        </div>
                        
                        <?php if ($review['review_text']): ?>
                            <div class="review-text">
                                <?= nl2br(htmlspecialchars($review['review_text'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        
        <!-- Other Services -->
        <?php if ($other_services->num_rows > 0): ?>
            <div class="reviews-section">
                <h3 class="section-title">More Services by <?= htmlspecialchars($service['full_name']) ?></h3>
                
                <div class="other-services">
                    <?php while ($other = $other_services->fetch_assoc()): ?>
                        <a href="service_details.php?id=<?= $other['id'] ?>" class="other-service-card">
                            <div class="other-service-title"><?= htmlspecialchars($other['service_title']) ?></div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 8px;">
                                <?= ucfirst($other['service_type']) ?>
                                <?php if ($other['talent_title']): ?>
                                    • <?= htmlspecialchars($other['talent_title']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="other-service-price">RM <?= number_format($other['price'], 2) ?></div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="services.php" class="btn btn-secondary">← Back to Marketplace</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        const basePrice = <?= $service['price'] ?>;
        
        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let newValue = parseInt(quantityInput.value) + change;
            
            if (newValue < 1) newValue = 1;
            if (newValue > 10) newValue = 10;
            
            quantityInput.value = newValue;
            updateTotal();
        }
        
        function updateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const total = basePrice * quantity;
            document.getElementById('totalPrice').textContent = 'Total: RM ' + total.toFixed(2);
        }
        
        // Initialize
        updateTotal();
    </script>
</body>
</html>