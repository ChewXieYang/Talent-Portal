<?php
include 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'add':
        $service_id = intval($_POST['service_id']);
        $quantity = intval($_POST['quantity'] ?? 1);
        $custom_requirements = trim($_POST['custom_requirements'] ?? '');
        
        if ($service_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid service']);
            exit;
        }
        
        // Get service details
        $stmt = $conn->prepare("SELECT * FROM services WHERE id = ? AND is_available = 1");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $service = $stmt->get_result()->fetch_assoc();
        
        if (!$service) {
            echo json_encode(['success' => false, 'message' => 'Service not found']);
            exit;
        }
        
        // Check if user is trying to add their own service
        if ($service['user_id'] == $user_id) {
            echo json_encode(['success' => false, 'message' => 'Cannot add your own service to cart']);
            exit;
        }
        
        // Check if item already in cart
        $stmt = $conn->prepare("SELECT id FROM shopping_cart WHERE user_id = ? AND service_id = ?");
        $stmt->bind_param("ii", $user_id, $service_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing item
            $stmt = $conn->prepare("UPDATE shopping_cart SET quantity = quantity + ?, custom_requirements = ? WHERE id = ?");
            $stmt->bind_param("isi", $quantity, $custom_requirements, $existing['id']);
        } else {
            // Add new item
            $stmt = $conn->prepare("INSERT INTO shopping_cart (user_id, service_id, quantity, custom_requirements, price) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisd", $user_id, $service_id, $quantity, $custom_requirements, $service['price']);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Added to cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    case 'remove':
        $cart_id = intval($_POST['cart_id']);
        
        $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Removed from cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error removing item']);
        }
        break;
        
    case 'update':
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity <= 0) {
            // Remove item if quantity is 0 or negative
            $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cart_id, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE shopping_cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cart updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating cart']);
        }
        break;
        
    case 'count':
        $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM shopping_cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        echo json_encode(['count' => intval($result['count'] ?? 0)]);
        break;
        
    case 'clear':
        $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cart cleared']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error clearing cart']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>