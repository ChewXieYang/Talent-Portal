<?php
include 'includes/db.php';

// Log the logout activity if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['full_name'] = $user['full_name'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description, ip_address) VALUES (?, ?, ?, ?)");
    $action_type = 'logout';
    $action_desc = 'User logged out';
    $stmt->bind_param("isss", $user_id, $action_type, $action_desc, $ip_address);
    $stmt->execute();
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php?message=logged_out');
exit;
?>