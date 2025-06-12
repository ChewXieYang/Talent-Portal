<?php
// includes/functions.php

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    if (!isLoggedIn()) return false;
    
    global $conn;
    $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    return $user && $user['user_type'] === 'admin';
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Function to redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

// Function to get user details
function getUserDetails($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get user talents
function getUserTalents($user_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT ut.*, tc.category_name 
        FROM user_talents ut
        JOIN talent_categories tc ON ut.category_id = tc.id
        WHERE ut.user_id = ?
        ORDER BY ut.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get portfolio items
function getPortfolioItems($user_id = null, $talent_id = null, $limit = null) {
    global $conn;
    $sql = "SELECT pi.*, u.full_name, u.username, ut.talent_title 
            FROM portfolio_items pi
            JOIN users u ON pi.user_id = u.id
            JOIN user_talents ut ON pi.talent_id = ut.id
            WHERE 1=1";
    
    $types = "";
    $params = array();
    
    if ($user_id) {
        $sql .= " AND pi.user_id = ?";
        $types .= "i";
        $params[] = $user_id;
    }
    
    if ($talent_id) {
        $sql .= " AND pi.talent_id = ?";
        $types .= "i";
        $params[] = $talent_id;
    }
    
    $sql .= " ORDER BY pi.upload_date DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $types .= "i";
        $params[] = $limit;
    }
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get talent categories
function getTalentCategories() {
    global $conn;
    $result = $conn->query("SELECT * FROM talent_categories ORDER BY category_name");
    return $result;
}

// Function to search talents
function searchTalents($search_term = '', $category_id = null) {
    global $conn;
    $stmt = $conn->prepare("CALL SearchTalents(?, ?)");
    $stmt->bind_param("si", $search_term, $category_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to log user activity
function logActivity($action_type, $action_description = '') {
    if (!isLoggedIn()) return;
    
    global $conn;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $_SESSION['user_id'], $action_type, $action_description, $ip_address);
    $stmt->execute();
}

// Function to handle file uploads
function uploadFile($file, $upload_dir = 'uploads/', $allowed_types = ['image/jpeg', 'image/png', 'image/gif']) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    // Check file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large (5MB max)'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file'];
    }
}

// Function to get recent announcements
function getRecentAnnouncements($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as admin_name 
        FROM announcements a
        JOIN users u ON a.admin_id = u.id
        WHERE a.is_published = 1 
        AND (a.expiry_date IS NULL OR a.expiry_date > NOW())
        ORDER BY a.publish_date DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get FAQ categories
function getFAQCategories() {
    global $conn;
    $result = $conn->query("SELECT DISTINCT category FROM faqs WHERE is_published = 1 ORDER BY category");
    return $result;
}

// Function to sanitize output
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Function to create thumbnail
function createThumbnail($source_path, $thumb_path, $max_width = 300, $max_height = 300) {
    $image_info = getimagesize($source_path);
    if (!$image_info) return false;
    
    $source_type = $image_info['mime'];
    $source_width = $image_info[0];
    $source_height = $image_info[1];
    
    // Calculate new dimensions
    $ratio = min($max_width / $source_width, $max_height / $source_height);
    $new_width = round($source_width * $ratio);
    $new_height = round($source_height * $ratio);
    
    // Create source image resource
    switch ($source_type) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }
    
    // Create new image
    $thumb_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($source_type == 'image/png' || $source_type == 'image/gif') {
        imagecolortransparent($thumb_image, imagecolorallocatealpha($thumb_image, 0, 0, 0, 127));
        imagealphablending($thumb_image, false);
        imagesavealpha($thumb_image, true);
    }
    
    // Copy and resize
    imagecopyresampled($thumb_image, $source_image, 0, 0, 0, 0, 
                      $new_width, $new_height, $source_width, $source_height);
    
    // Save thumbnail
    switch ($source_type) {
        case 'image/jpeg':
            imagejpeg($thumb_image, $thumb_path, 85);
            break;
        case 'image/png':
            imagepng($thumb_image, $thumb_path);
            break;
        case 'image/gif':
            imagegif($thumb_image, $thumb_path);
            break;
    }
    
    // Clean up
    imagedestroy($source_image);
    imagedestroy($thumb_image);
    
    return true;
}
?>