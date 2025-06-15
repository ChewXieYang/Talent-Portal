<?php
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['orders'])) {
    header('Location: services.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_ids = explode(',', $_GET['orders']);

// Get order details
$order_placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
$stmt = $conn->prepare("
    SELECT o.*, u.full_name as seller_name, u.contact_email as seller_email
    FROM orders o
    JOIN users u ON o.seller_id = u.id
    WHERE o.id IN ($order_placeholders) AND o.buyer_id = ?
");

$params = array_merge($order_ids, [$user_id]);
$types = str_repeat('i', count($order_ids)) . 'i';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();

$total_amount = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .success-header {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .success-icon {
            font-size: 4em;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .order-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .order-header {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .order-id {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .seller-info {
            color: #666;
            font-size: 14px;
        }
        
        .next-steps {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #005eff;
        }
        
        .next-steps h3 {
            color: #004085;
            margin-top: 0;
        }
        
        .next-steps ol {
            color: #004085;
            line-height: 1.6;
        }
        
        .contact-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
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
            margin: 5px;
        }
        
        .btn-primary {
            background: #005eff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="success-header">
            <div class="success-icon">✅</div>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your order. You'll receive confirmation emails shortly.</p>
        </div>
        
        <?php while ($order = $orders->fetch_assoc()): 
            $total_amount += $order['total_amount'];
        ?>
            <div class="order-section">
                <div class="order-header">
                    <div class="order-id">Order #<?= $order['id'] ?></div>
                    <div class="seller-info">
                        Seller: <?= htmlspecialchars($order['seller_name']) ?><br>
                        Amount: RM <?= number_format($order['total_amount'], 2) ?><br>
                        Date: <?= date('F j, Y \a\t g:i A', strtotime($order['order_date'])) ?>
                    </div>
                </div>
                
                <div class="next-steps">
                    <h3>What happens next?</h3>
                    <ol>
                        <li><strong>Seller Contact:</strong> <?= htmlspecialchars($order['seller_name']) ?> will contact you within 24 hours to discuss project details.</li>
                        <li><strong>Payment:</strong> You'll receive payment instructions based on your selected method.</li>
                        <li><strong>Project Start:</strong> Work will begin once payment terms are agreed upon.</li>
                        <li><strong>Completion:</strong> You'll be notified when your order is ready.</li>
                    </ol>
                    
                    <div class="contact-info">
                        <strong>Seller Contact:</strong> <?= htmlspecialchars($order['seller_email']) ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="my_orders.php" class="btn btn-primary">View My Orders</a>
            <a href="services.php" class="btn btn-secondary">Browse More Services</a>
            <a href="profile.php" class="btn btn-secondary">Back to Profile</a>
        </div>
        
        <div class="order-section">
            <h3>Important Notes</h3>
            <ul>
                <li>Keep your order numbers for reference: <?= implode(', #', $order_ids) ?></li>
                <li>Total amount: RM <?= number_format($total_amount * 1.05, 2) ?> (including 5% service fee)</li>
                <li>You can track your orders in the "My Orders" section</li>
                <li>For any issues, contact our support team</li>
                <li>Please leave a review after order completion to help other students</li>
            </ul>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>