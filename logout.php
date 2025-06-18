<?php
session_start();
include 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    // Log the logout action
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description, ip_address) VALUES (?, ?, ?, ?)");
    $action_type = 'logout';
    $action_desc = 'User logged out';
    $stmt->bind_param("isss", $user_id, $action_type, $action_desc, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// Clear session
session_unset();
session_destroy();

// Redirect to login
header('Location: student_dashboard.php?message=logged_out');
exit;
