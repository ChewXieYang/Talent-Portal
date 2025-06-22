<?php
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

$stmt = $conn->prepare("
    SELECT sc.*, s.service_title, s.service_description, s.delivery_time, s.service_type, s.user_id as seller_id,
           u.full_name as seller_name, u.username as seller_username, u.contact_email as seller_email
    FROM shopping_cart sc
    JOIN services s ON sc.service_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE sc.user_id = ?
    ORDER BY sc.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

if ($cart_items->num_rows === 0) {
    header('Location: cart.php');
    exit;
}

$total_amount = 0;
$service_fee = 0;
$cart_data = [];

while ($item = $cart_items->fetch_assoc()) {
    $item_total = $item['price'] * $item['quantity'];
    $total_amount += $item_total;
    $cart_data[] = $item;
}

$service_fee = $total_amount * 0.05;
$final_total = $total_amount + $service_fee;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $payment_method = $_POST['payment_method'];
    $billing_name = trim($_POST['billing_name']);
    $billing_email = trim($_POST['billing_email']);
    $billing_phone = trim($_POST['billing_phone']);
    $billing_address = trim($_POST['billing_address']);
    $special_instructions = trim($_POST['special_instructions']);
    
    if (empty($billing_name) || empty($billing_email) || empty($payment_method)) {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } else {
        $conn->begin_transaction();
        
        try {
            $orders_by_seller = [];
            foreach ($cart_data as $item) {
                $seller_id = $item['seller_id'];
                if (!isset($orders_by_seller[$seller_id])) {
                    $orders_by_seller[$seller_id] = [];
                }
                $orders_by_seller[$seller_id][] = $item;
            }
            
            $created_orders = [];
            
            foreach ($orders_by_seller as $seller_id => $items) {
                $order_total = 0;
                foreach ($items as $item) {
                    $order_total += $item['price'] * $item['quantity'];
                }
                
                $stmt = $conn->prepare("
                    INSERT INTO orders (buyer_id, seller_id, total_amount, status, payment_status) 
                    VALUES (?, ?, ?, 'pending', 'pending')
                ");
                $stmt->bind_param("iid", $user_id, $seller_id, $order_total);
                $stmt->execute();
                $order_id = $conn->insert_id;
                $created_orders[] = $order_id;

                foreach ($items as $item) {
                    $stmt = $conn->prepare("
                        INSERT INTO order_items (order_id, service_id, quantity, unit_price, custom_requirements) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iiidd", $order_id, $item['service_id'], $item['quantity'], $item['price'], $item['custom_requirements']);
                    $stmt->execute();
                }
            }
            
            $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description) VALUES (?, ?, ?)");
            $action_type = 'order_placed';
            $action_desc = 'Placed order with total: RM ' . number_format($final_total, 2);
            $stmt->bind_param("iss", $user_id, $action_type, $action_desc);
            $stmt->execute();
            
            $conn->commit();
            
            header('Location: order_success.php?orders=' . implode(',', $created_orders));
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error processing order. Please try again.';
            $messageType = 'error';
        }
    }
}

$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MMU Talent Showcase</title>
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
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .checkout-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
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
        
        .payment-options {
            display: grid;
            gap: 10px;
        }
        
        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option:hover {
            border-color: #005eff;
        }
        
        .payment-option.selected {
            border-color: #005eff;
            background-color: #f0f8ff;
        }
        
        .payment-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .item-meta {
            font-size: 12px;
            color: #666;
        }
        
        .item-price {
            font-weight: bold;
            color: #28a745;
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
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
            width: 100%;
            text-align: center;
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
            margin-bottom: 10px;
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="checkout-grid">
            <!-- Checkout Form -->
            <div class="checkout-section">
                <h2 class="section-title">Billing Information</h2>
                
                <form method="POST">
                    <input type="hidden" name="place_order" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="billing_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="billing_name" name="billing_name" required 
                                   value="<?= htmlspecialchars($user['full_name']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="billing_email">Email <span class="required">*</span></label>
                            <input type="email" id="billing_email" name="billing_email" required 
                                   value="<?= htmlspecialchars($user['contact_email'] ?: $user['email']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="billing_phone">Phone Number</label>
                        <input type="tel" id="billing_phone" name="billing_phone" 
                               value="<?= htmlspecialchars($user['phone_number']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="billing_address">Address</label>
                        <textarea id="billing_address" name="billing_address" rows="3"
                                  placeholder="Enter your address for delivery/communication"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="special_instructions">Special Instructions</label>
                        <textarea id="special_instructions" name="special_instructions" rows="3"
                                  placeholder="Any special requirements or notes for the sellers"></textarea>
                    </div>
                    
                    <h3 class="section-title">Payment Method</h3>
                    
                    <div class="payment-options">
                        <div class="payment-option" onclick="selectPayment('bank_transfer')">
                            <input type="radio" name="payment_method" value="bank_transfer" id="bank_transfer" required>
                            <label for="bank_transfer">
                                <strong>Bank Transfer</strong><br>
                                <small>Transfer payment directly to seller's account</small>
                            </label>
                        </div>
                        
                        <div class="payment-option" onclick="selectPayment('meet_pay')">
                            <input type="radio" name="payment_method" value="meet_pay" id="meet_pay" required>
                            <label for="meet_pay">
                                <strong>Meet & Pay</strong><br>
                                <small>Pay in person when meeting the seller</small>
                            </label>
                        </div>
                        
                        <div class="payment-option" onclick="selectPayment('escrow')">
                            <input type="radio" name="payment_method" value="escrow" id="escrow" required>
                            <label for="escrow">
                                <strong>Escrow Service</strong><br>
                                <small>Secure payment held until work completion</small>
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">Place Order</button>
                    </div>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="checkout-section">
                <h3 class="section-title">Order Summary</h3>
                
                <?php foreach ($cart_data as $item): 
                    $item_total = $item['price'] * $item['quantity'];
                ?>
                    <div class="order-item">
                        <div class="item-info">
                            <h4><?= htmlspecialchars($item['service_title']) ?></h4>
                            <div class="item-meta">
                                By <?= htmlspecialchars($item['seller_name']) ?> • 
                                Qty: <?= $item['quantity'] ?> • 
                                <?= ucfirst($item['service_type']) ?>
                            </div>
                            <?php if ($item['custom_requirements']): ?>
                                <div class="item-meta" style="margin-top: 5px;">
                                    <strong>Requirements:</strong> <?= htmlspecialchars(substr($item['custom_requirements'], 0, 50)) ?>...
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="item-price">
                            RM <?= number_format($item_total, 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 20px;">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>RM <?= number_format($total_amount, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Service Fee (5%):</span>
                        <span>RM <?= number_format($service_fee, 2) ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span>RM <?= number_format($final_total, 2) ?></span>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="cart.php" class="btn btn-secondary">← Back to Cart</a>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 12px; color: #666;">
                    <strong>Note:</strong> Your order will be split into separate orders for each seller. 
                    You'll receive confirmation emails with payment instructions for each seller.
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function selectPayment(method) {
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
            
            document.getElementById(method).checked = true;
        }
    </script>
</body>
</html>
