<?php
include 'includes/db.php';

// Get search and filter parameters
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$service_type = isset($_GET['type']) ? $_GET['type'] : '';
$price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : 0;

// Build SQL query
$sql = "SELECT s.*, u.full_name, u.username, u.profile_picture_url, 
               ut.talent_title, tc.category_name,
               (SELECT AVG(rating) FROM reviews r JOIN orders o ON r.order_id = o.id WHERE o.seller_id = s.user_id) as avg_rating,
               (SELECT COUNT(*) FROM reviews r JOIN orders o ON r.order_id = o.id WHERE o.seller_id = s.user_id) as review_count
        FROM services s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN user_talents ut ON s.talent_id = ut.id
        LEFT JOIN talent_categories tc ON ut.category_id = tc.id
        WHERE s.is_available = 1 AND s.price IS NOT NULL";

$params = [];
$types = "";

// Add filters
if (!empty($search_query)) {
    $sql .= " AND (s.service_title LIKE ? OR s.service_description LIKE ? OR u.full_name LIKE ?)";
    $search_term = '%' . $search_query . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if ($category_filter > 0) {
    $sql .= " AND ut.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if (!empty($service_type)) {
    $sql .= " AND s.service_type = ?";
    $params[] = $service_type;
    $types .= "s";
}

if ($price_min > 0) {
    $sql .= " AND s.price >= ?";
    $params[] = $price_min;
    $types .= "d";
}

if ($price_max > 0) {
    $sql .= " AND s.price <= ?";
    $params[] = $price_max;
    $types .= "d";
}

$sql .= " ORDER BY s.created_at DESC";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$services = $stmt->get_result();

// Get categories for filter
$categories = $conn->query("SELECT * FROM talent_categories ORDER BY category_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Marketplace - MMU Talent Showcase</title>
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
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .filter-form input, .filter-form select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-form button {
            padding: 10px 20px;
            background: #005eff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .service-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .service-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .seller-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .seller-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .service-title {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .service-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        
        .meta-tag {
            padding: 4px 8px;
            background: #f0f0f0;
            border-radius: 12px;
            font-size: 12px;
            color: #666;
        }
        
        .service-price {
            font-size: 1.5em;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .service-content {
            padding: 20px;
        }
        
        .service-description {
            color: #555;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .service-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .stars {
            color: #ffc107;
        }
        
        .service-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #005eff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .cart-icon {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #005eff;
            color: white;
            padding: 15px;
            border-radius: 50%;
            text-decoration: none;
            font-size: 18px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Services Marketplace</h1>
            <p>Discover and hire talented MMU students for your projects</p>
        </div>
        
        <!-- Shopping Cart Icon -->
        <a href="cart.php" class="cart-icon">
            🛒
            <span class="cart-count" id="cartCount">0</span>
        </a>
        
        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" class="filter-form">
                <input type="text" name="q" placeholder="Search services..." 
                       value="<?= htmlspecialchars($search_query) ?>">
                
                <select name="category">
                    <option value="">All Categories</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <select name="type">
                    <option value="">All Types</option>
                    <option value="commission" <?= $service_type === 'commission' ? 'selected' : '' ?>>Commissions</option>
                    <option value="gig" <?= $service_type === 'gig' ? 'selected' : '' ?>>Gigs</option>
                    <option value="product" <?= $service_type === 'product' ? 'selected' : '' ?>>Products</option>
                </select>
                
                <input type="number" name="price_min" placeholder="Min Price" 
                       value="<?= $price_min > 0 ? $price_min : '' ?>" step="0.01">
                
                <input type="number" name="price_max" placeholder="Max Price" 
                       value="<?= $price_max > 0 ? $price_max : '' ?>" step="0.01">
                
                <button type="submit">Search</button>
            </form>
        </div>
        
        <!-- Services Grid -->
        <?php if ($services->num_rows > 0): ?>
            <div class="services-grid">
                <?php while ($service = $services->fetch_assoc()): ?>
                    <div class="service-card">
                        <div class="service-header">
                            <div class="seller-info">
                                <img src="<?= htmlspecialchars($service['profile_picture_url'] ?: 'uploads/avatars/default-avatar.jpg') ?>" 
                                     alt="<?= htmlspecialchars($service['full_name']) ?>" 
                                     class="seller-avatar">
                                <div>
                                    <strong><?= htmlspecialchars($service['full_name']) ?></strong>
                                    <div style="font-size: 12px; color: #666;">@<?= htmlspecialchars($service['username']) ?></div>
                                </div>
                            </div>
                            
                            <h3 class="service-title"><?= htmlspecialchars($service['service_title']) ?></h3>
                            
                            <div class="service-meta">
                                <span class="meta-tag"><?= ucfirst($service['service_type']) ?></span>
                                <?php if ($service['category_name']): ?>
                                    <span class="meta-tag"><?= htmlspecialchars($service['category_name']) ?></span>
                                <?php endif; ?>
                                <?php if ($service['delivery_time']): ?>
                                    <span class="meta-tag">⏱️ <?= htmlspecialchars($service['delivery_time']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="service-price">RM <?= number_format($service['price'], 2) ?></div>
                            
                            <?php if ($service['avg_rating']): ?>
                                <div class="service-rating">
                                    <span class="stars">
                                        <?php 
                                        $rating = round($service['avg_rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '★' : '☆';
                                        }
                                        ?>
                                    </span>
                                    <span>(<?= number_format($service['avg_rating'], 1) ?>) • <?= $service['review_count'] ?> reviews</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="service-content">
                            <p class="service-description">
                                <?= nl2br(htmlspecialchars(substr($service['service_description'], 0, 150))) ?>
                                <?= strlen($service['service_description']) > 150 ? '...' : '' ?>
                            </p>
                            
                            <div class="service-actions">
                                <a href="service_details.php?id=<?= $service['id'] ?>" class="btn btn-secondary">
                                    View Details
                                </a>
                                <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $service['user_id']): ?>
                                    <button onclick="addToCart(<?= $service['id'] ?>)" class="btn btn-primary">
                                        Add to Cart
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 60px; background: white; border-radius: 8px;">
                <h3>No services found</h3>
                <p>Try adjusting your search criteria or <a href="services.php">view all services</a></p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Load cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
        
        function addToCart(serviceId) {
            if (!<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
                alert('Please login to add items to cart');
                window.location.href = 'login.php';
                return;
            }
            
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add&service_id=' + serviceId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Service added to cart!');
                    updateCartCount();
                } else {
                    alert(data.message || 'Error adding to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding to cart');
            });
        }
        
        function updateCartCount() {
            if (!<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) return;
            
            fetch('cart_actions.php?action=count')
            .then(response => response.json())
            .then(data => {
                document.getElementById('cartCount').textContent = data.count || 0;
            })
            .catch(error => console.error('Error updating cart count:', error));
        }
    </script>
</body>
</html>