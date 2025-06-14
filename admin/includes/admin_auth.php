<?php
// admin/includes/admin_auth.php
include '../includes/db.php';

// Function to check if user is admin
function isAdmin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }
    return $_SESSION['user_type'] === 'admin';
}

// Function to require admin access
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../login.php?error=admin_required');
        exit;
    }
}

// Function to get admin details
function getAdminDetails($admin_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'admin'");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to log admin activity
function logAdminActivity($action_type, $description = '') {
    if (!isAdmin()) return;
    
    global $conn;
    $admin_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $action_type, $description, $ip_address);
    $stmt->execute();
}

// Function to get dashboard statistics
function getDashboardStats() {
    global $conn;
    $stats = array();
    
    // Total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    // Active users (logged in within last 30 days)
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student' AND last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['active_users'] = $result->fetch_assoc()['count'];
    
    // Total announcements
    $result = $conn->query("SELECT COUNT(*) as count FROM announcements");
    $stats['total_announcements'] = $result->fetch_assoc()['count'];
    
    // Published announcements
    $result = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE is_published = 1");
    $stats['published_announcements'] = $result->fetch_assoc()['count'];
    
    // Total talents
    $result = $conn->query("SELECT COUNT(*) as count FROM user_talents");
    $stats['total_talents'] = $result->fetch_assoc()['count'];
    
    // Total portfolio items
    $result = $conn->query("SELECT COUNT(*) as count FROM portfolio_items");
    $stats['total_portfolio'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

// Function to get recent activity
function getRecentActivity($limit = 10) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT al.*, u.full_name, u.username 
        FROM activity_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result();
}
?>
