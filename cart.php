<?php
include 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $conn->prepare("
    SELECT sc.*, s.service_title, s.service_description, s.delivery_time, s.service_type,
           u.full_name as seller_name, u.username as seller_username, u.profile_picture_url
    FROM shopping_cart sc
    JOIN services s ON sc.service_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE sc.user_id = ?
    ORDER BY sc.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// Calculate totals
$total_amount = 0;
$total_items = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
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
        
        .cart-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .cart-item {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 20px;
            align-items: start;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .seller-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 0 0 200px;
        }
        
        .seller-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .item-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .item-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #888;
        }
        
        .item-controls {
            flex: 0 0 200px;
            text-align: right;
        }
        
        .price {
            font-size: 1.3em;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
            justify-content: flex-end;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .quantity-btn:hover {
            background: #e9ecef;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 5px;
            height: 30px;
        }
        
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .remove-btn:hover {
            background: #c82333;
        }
        
        .custom-requirements {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 14px;
            color: #555;
        }
        
        .cart-summary {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .summary-total {
            border-top: 2px solid #e0e0e0;
            padding-top: 15px;
            font-size: 1.3em;
            font-weight: bold;
        }
        
        .checkout-section {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-block;
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
            margin-right: 10px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            margin-left: 10px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                gap: 15px;
            }
            
            .seller-info, .item-controls {
                flex: none;
            }
            
            .item-controls {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Shopping Cart</h1>
            <p>Review your selected services before checkout</p>
        </div>
        
        <?php if ($cart_items->num_rows > 0): ?>
            <div class="cart-section">
                <?php while ($item = $cart_items->fetch_assoc()): 
                    $item_total = $item['price'] * $item['quantity'];
                    $total_amount += $item_total;
                    $total_items += $item['quantity'];
                ?>
                    <div class="cart-item" data-cart-id="<?= $item['id'] ?>">
                        <div class="seller-info">
                            <img src="<?= htmlspecialchars($item['profile_picture_url'] ?: 'uploads/avatars/default-avatar.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['seller_name']) ?>" 
                                 class="seller-avatar">
                            <div>
                                <div><strong><?= htmlspecialchars($item['seller_name']) ?></strong></div>
                                <div style="font-size: 12px; color: #666;">@<?= htmlspecialchars($item['seller_username']) ?></div>
                            </div>
                        </div>
                        
                        <div class="item-details">
                            <h3 class="item-title"><?= htmlspecialchars($item['service_title']) ?></h3>
                            <p class="item-description">
                                <?= nl2br(htmlspecialchars(substr($item['service_description'], 0, 100))) ?>
                                <?= strlen($item['service_description']) > 100 ? '...' : '' ?>
                            </p>
                            <div class="item-meta">
                                <span><?= ucfirst($item['service_type']) ?></span>
                                <?php if ($item['delivery_time']): ?>
                                    <span>⏱️ <?= htmlspecialchars($item['delivery_time']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['custom_requirements']): ?>
                                <div class="custom-requirements">
                                    <strong>Custom Requirements:</strong><br>
                                    <?= nl2br(htmlspecialchars($item['custom_requirements'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-controls">
                            <div class="price">RM <?= number_format($item['price'], 2) ?></div>
                            
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)">-</button>
                                <input type="number" class="quantity-input" 
                                       value="<?= $item['quantity'] ?>" 
                                       min="1" max="10"
                                       onchange="updateQuantity(<?= $item['id'] ?>, this.value)">
                                <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
                            </div>
                            
                            <div style="margin-bottom: 10px;">
                                <strong>Subtotal: RM <?= number_format($item_total, 2) ?></strong>
                            </div>
                            
                            <button class="remove-btn" onclick="removeItem(<?= $item['id'] ?>)">
                                Remove
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="cart-summary">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Items (<?= $total_items ?>):</span>
                    <span>RM <?= number_format($total_amount, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Service Fee (5%):</span>
                    <span>RM <?= number_format($total_amount * 0.05, 2) ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total:</span>
                    <span>RM <?= number_format($total_amount * 1.05, 2) ?></span>
                </div>
                
                <div class="checkout-section">
                    <a href="services.php" class="btn btn-secondary">Continue Shopping</a>
                    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                    <button onclick="clearCart()" class="btn btn-danger">Clear Cart</button>
                </div>
            </div>
            
        <?php else: ?>
            <div class="empty-cart">
                <h3>Your cart is empty</h3>
                <p>Browse our services marketplace to find talented students for your projects.</p>
                <a href="services.php" class="btn btn-primary">Browse Services</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function updateQuantity(cartId, newQuantity) {
            if (newQuantity < 1) {
                if (confirm('Remove this item from cart?')) {
                    removeItem(cartId);
                }
                return;
            }
            
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&cart_id=${cartId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating quantity');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating quantity');
            });
        }
        
        function removeItem(cartId) {
            if (!confirm('Remove this item from cart?')) return;
            
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&cart_id=${cartId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error removing item');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing item');
            });
        }
        
        function clearCart() {
            if (!confirm('Clear all items from cart?')) return;
            
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error clearing cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error clearing cart');
            });
        }
    </script>
</body>
</html>