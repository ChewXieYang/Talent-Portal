<?php
include 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle actions (delete, toggle featured, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_item'])) {
        $item_id = intval($_POST['item_id']);
        
        // Get file paths before deletion
        $file_stmt = $conn->prepare("SELECT file_url, thumbnail_url FROM portfolio_items WHERE id = ? AND user_id = ?");
        $file_stmt->bind_param("ii", $item_id, $user_id);
        $file_stmt->execute();
        $file_result = $file_stmt->get_result();
        
        if ($file_result->num_rows > 0) {
            $file_data = $file_result->fetch_assoc();
            
            // Delete from database
            $delete_stmt = $conn->prepare("DELETE FROM portfolio_items WHERE id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $item_id, $user_id);
            
            if ($delete_stmt->execute()) {
                // Delete physical files
                if (file_exists($file_data['file_url'])) {
                    unlink($file_data['file_url']);
                }
                if ($file_data['thumbnail_url'] && file_exists($file_data['thumbnail_url'])) {
                    unlink($file_data['thumbnail_url']);
                }
                
                $message = 'Portfolio item deleted successfully!';
                $messageType = 'success';
                
                // Log activity
                $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description) VALUES (?, ?, ?)");
                $action_type = 'portfolio_delete';
                $action_desc = 'Deleted portfolio item';
                $log_stmt->bind_param("iss", $user_id, $action_type, $action_desc);
                $log_stmt->execute();
            } else {
                $message = 'Error deleting portfolio item.';
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['toggle_featured'])) {
        $item_id = intval($_POST['item_id']);
        $is_featured = intval($_POST['is_featured']);
        
        $update_stmt = $conn->prepare("UPDATE portfolio_items SET is_featured = ? WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("iii", $is_featured, $item_id, $user_id);
        
        if ($update_stmt->execute()) {
            $message = $is_featured ? 'Item featured successfully!' : 'Item unfeatured successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating item.';
            $messageType = 'error';
        }
    }
}

// Get filter parameters
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_talent = isset($_GET['talent']) ? intval($_GET['talent']) : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build WHERE clause for filtering
$where_conditions = ["user_id = ?"];
$params = [$user_id];
$param_types = "i";

if (!empty($filter_type)) {
    $where_conditions[] = "file_type = ?";
    $params[] = $filter_type;
    $param_types .= "s";
}

if ($filter_talent > 0) {
    $where_conditions[] = "talent_id = ?";
    $params[] = $filter_talent;
    $param_types .= "i";
}

// Build ORDER BY clause
$order_clause = match($sort_by) {
    'oldest' => 'upload_date ASC',
    'title' => 'title ASC',
    'views' => 'views DESC',
    'featured' => 'is_featured DESC, upload_date DESC',
    default => 'upload_date DESC'
};

// Get portfolio items with filters
$portfolio_sql = "
    SELECT pi.*, ut.talent_title 
    FROM portfolio_items pi 
    LEFT JOIN user_talents ut ON pi.talent_id = ut.id 
    WHERE " . implode(' AND ', $where_conditions) . "
    ORDER BY $order_clause
";

$portfolio_stmt = $conn->prepare($portfolio_sql);
$portfolio_stmt->bind_param($param_types, ...$params);
$portfolio_stmt->execute();
$portfolio_result = $portfolio_stmt->get_result();

// Get user's talents for filter dropdown
$talents_stmt = $conn->prepare("SELECT id, talent_title FROM user_talents WHERE user_id = ? ORDER BY talent_title");
$talents_stmt->bind_param("i", $user_id);
$talents_stmt->execute();
$talents_result = $talents_stmt->get_result();

// Get portfolio statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_items,
        COALESCE(SUM(views), 0) as total_views,
        COALESCE(SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END), 0) as featured_items,
        COALESCE(SUM(file_size), 0) as total_size
    FROM portfolio_items 
    WHERE user_id = ?
");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portfolio - MMU Talent Showcase</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="my-portfolio-container">
                <!-- Portfolio Header -->
                <div class="my-portfolio-header">
                    <h1>My Portfolio</h1>
                    <p>Manage and showcase your creative work</p>
                </div>
                
                <!-- Statistics -->
                <div class="my-portfolio-stats">
                    <div class="my-portfolio-stat-card">
                        <div class="my-portfolio-stat-number"><?= $stats['total_items'] ?: 0 ?></div>
                        <div class="my-portfolio-stat-label">Total Items</div>
                    </div>
                    <div class="my-portfolio-stat-card">
                        <div class="my-portfolio-stat-number"><?= number_format($stats['total_views'] ?: 0) ?></div>
                        <div class="my-portfolio-stat-label">Total Views</div>
                    </div>
                    <div class="my-portfolio-stat-card">
                        <div class="my-portfolio-stat-number"><?= $stats['featured_items'] ?: 0 ?></div>
                        <div class="my-portfolio-stat-label">Featured Items</div>
                    </div>
                    <div class="my-portfolio-stat-card">
                        <div class="my-portfolio-stat-number"><?= number_format(($stats['total_size'] ?: 0) / 1024 / 1024, 1) ?>MB</div>
                        <div class="my-portfolio-stat-label">Total Size</div>
                    </div>
                </div>
                
                <!-- Controls Section -->
                <div class="my-portfolio-controls">
                    <div class="my-portfolio-controls-top">
                        <div class="my-portfolio-filters">
                            <form method="GET" class="d-flex">
                                <div class="my-portfolio-filter-group">
                                    <label>File Type</label>
                                    <select name="type" onchange="this.form.submit()">
                                        <option value="">All Types</option>
                                        <option value="image" <?= $filter_type === 'image' ? 'selected' : '' ?>>Images</option>
                                        <option value="video" <?= $filter_type === 'video' ? 'selected' : '' ?>>Videos</option>
                                        <option value="audio" <?= $filter_type === 'audio' ? 'selected' : '' ?>>Audio</option>
                                        <option value="document" <?= $filter_type === 'document' ? 'selected' : '' ?>>Documents</option>
                                        <option value="other" <?= $filter_type === 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="my-portfolio-filter-group">
                                    <label>Talent</label>
                                    <select name="talent" onchange="this.form.submit()">
                                        <option value="">All Talents</option>
                                        <?php 
                                        $talents_result->data_seek(0);
                                        while ($talent = $talents_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?= $talent['id'] ?>" <?= $filter_talent == $talent['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($talent['talent_title']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="my-portfolio-filter-group">
                                    <label>Sort By</label>
                                    <select name="sort" onchange="this.form.submit()">
                                        <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                        <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                        <option value="title" <?= $sort_by === 'title' ? 'selected' : '' ?>>Title A-Z</option>
                                        <option value="views" <?= $sort_by === 'views' ? 'selected' : '' ?>>Most Views</option>
                                        <option value="featured" <?= $sort_by === 'featured' ? 'selected' : '' ?>>Featured First</option>
                                    </select>
                                </div>
                                
                                <!-- Hidden inputs to preserve other filters -->
                                <input type="hidden" name="type" value="<?= htmlspecialchars($filter_type) ?>">
                                <input type="hidden" name="talent" value="<?= $filter_talent ?>">
                                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
                            </form>
                        </div>
                        
                        <div>
                            <a href="upload.php" class="btn btn-primary">Upload New Item</a>
                            <?php if (!empty($filter_type) || $filter_talent > 0): ?>
                                <a href="my_portfolio.php" class="btn btn-secondary">Clear Filters</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Portfolio Grid -->
                <?php if ($portfolio_result->num_rows > 0): ?>
                    <div class="my-portfolio-grid">
                        <?php while ($item = $portfolio_result->fetch_assoc()): ?>
                            <div class="my-portfolio-item">
                                <?php if ($item['is_featured']): ?>
                                    <div class="my-portfolio-featured-badge">Featured</div>
                                <?php endif; ?>
                                
                                <div class="my-portfolio-file-type-badge"><?= ucfirst($item['file_type']) ?></div>
                                
                                <a href="view_portfolio_item.php?id=<?= $item['id'] ?>" class="detailed-view-card-link">
                                    <div class="my-portfolio-thumbnail">
                                        <?php if ($item['file_type'] === 'image' && $item['thumbnail_url'] && file_exists($item['thumbnail_url'])): ?>
                                            <img src="<?= htmlspecialchars($item['thumbnail_url']) ?>" 
                                                 alt="<?= htmlspecialchars($item['title']) ?>">
                                        <?php else: ?>
                                            <div class="my-portfolio-file-icon">
                                                <?php
                                                $icons = [
                                                    'video' => '🎥',
                                                    'audio' => '🎵',
                                                    'document' => '📄',
                                                    'code' => '💻',
                                                    'other' => '📎'
                                                ];
                                                echo $icons[$item['file_type']] ?? '📎';
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                
                                <div class="my-portfolio-info">
                                    <div class="my-portfolio-title">
                                        <a href="view_portfolio_item.php?id=<?= $item['id'] ?>" class="detailed-view-card-link">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </a>
                                    </div>
                                    
                                    <div class="my-portfolio-meta">
                                        <span>
                                            <?= date('M j, Y', strtotime($item['upload_date'])) ?>
                                            <?php if ($item['talent_title']): ?>
                                                • <?= htmlspecialchars($item['talent_title']) ?>
                                            <?php endif; ?>
                                        </span>
                                        <span><?= $item['views'] ?> views</span>
                                    </div>
                                    
                                    <?php if ($item['description']): ?>
                                        <div class="my-portfolio-description">
                                            <?= nl2br(htmlspecialchars(substr($item['description'], 0, 100))) ?>
                                            <?= strlen($item['description']) > 100 ? '...' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="my-portfolio-actions">
                                        <a href="edit_portfolio_item.php?id=<?= $item['id'] ?>" class="btn btn-small btn-secondary">Edit</a>
                                        
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="is_featured" value="<?= $item['is_featured'] ? 0 : 1 ?>">
                                            <button type="submit" name="toggle_featured" class="btn btn-small <?= $item['is_featured'] ? 'btn-warning' : 'btn-success' ?>">
                                                <?= $item['is_featured'] ? 'Unfeature' : 'Feature' ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <button type="submit" name="delete_item" class="btn btn-small btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="my-portfolio-empty-state">
                        <div class="empty-state-icon">📁</div>
                        <h3>No Portfolio Items Found</h3>
                        <p>
                            <?php if (!empty($filter_type) || $filter_talent > 0): ?>
                                No items match your current filters. <a href="my_portfolio.php">Clear filters</a> to see all items.
                            <?php else: ?>
                                Start building your portfolio by uploading your first item.
                            <?php endif; ?>
                        </p>
                        <a href="upload.php" class="btn btn-primary">Upload Your First Item</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
