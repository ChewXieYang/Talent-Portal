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

// Build the query
$sql = "SELECT pi.*, ut.talent_title, tc.category_name 
        FROM portfolio_items pi
        LEFT JOIN user_talents ut ON pi.talent_id = ut.id
        LEFT JOIN talent_categories tc ON ut.category_id = tc.id
        WHERE pi.user_id = ?";

$params = [$user_id];
$types = "i";

// Add filters
if (!empty($filter_type)) {
    $sql .= " AND pi.file_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if ($filter_talent > 0) {
    $sql .= " AND pi.talent_id = ?";
    $params[] = $filter_talent;
    $types .= "i";
}

// Add sorting
switch ($sort_by) {
    case 'oldest':
        $sql .= " ORDER BY pi.upload_date ASC";
        break;
    case 'title':
        $sql .= " ORDER BY pi.title ASC";
        break;
    case 'views':
        $sql .= " ORDER BY pi.views DESC";
        break;
    case 'featured':
        $sql .= " ORDER BY pi.is_featured DESC, pi.upload_date DESC";
        break;
    default: // newest
        $sql .= " ORDER BY pi.upload_date DESC";
        break;
}

// Execute query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$portfolio_result = $stmt->get_result();

// Get user's talents for filter dropdown
$talents_stmt = $conn->prepare("
    SELECT ut.id, ut.talent_title, tc.category_name 
    FROM user_talents ut
    JOIN talent_categories tc ON ut.category_id = tc.id
    WHERE ut.user_id = ?
    ORDER BY ut.talent_title
");
$talents_stmt->bind_param("i", $user_id);
$talents_stmt->execute();
$talents_result = $talents_stmt->get_result();

// Get statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_items,
        SUM(views) as total_views,
        SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_items,
        SUM(file_size) as total_size
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
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/my_portfolio.css">
    
    <title>My Portfolio - MMU Talent Showcase</title>
</head>
<body>
    <div class="wrapper">
        <link rel="stylesheet" href="css/sidebar.css">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="container">
                <div class="page-header">
                    <h1>My Portfolio</h1>
                    <p>Manage and organize your creative work</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <!-- Statistics Section -->
                <div class="stats-section">
                    <h3 style="margin-bottom: 20px; color: #333;">Portfolio Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['total_items'] ?: 0 ?></div>
                            <div class="stat-label">Total Items</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= number_format($stats['total_views'] ?: 0) ?></div>
                            <div class="stat-label">Total Views</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['featured_items'] ?: 0 ?></div>
                            <div class="stat-label">Featured Items</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= number_format(($stats['total_size'] ?: 0) / 1024 / 1024, 1) ?>MB</div>
                            <div class="stat-label">Total Size</div>
                        </div>
                    </div>
                </div>

                <!-- Controls Section -->
                <div class="controls-section">
                    <div class="controls-top">
                        <div class="filters">
                            <form method="GET" style="display: contents;">
                                <div class="filter-group">
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

                                <div class="filter-group">
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

                                <div class="filter-group">
                                    <label>Sort By</label>
                                    <select name="sort" onchange="this.form.submit()">
                                        <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                        <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                        <option value="title" <?= $sort_by === 'title' ? 'selected' : '' ?>>Title A-Z</option>
                                        <option value="views" <?= $sort_by === 'views' ? 'selected' : '' ?>>Most Views</option>
                                        <option value="featured" <?= $sort_by === 'featured' ? 'selected' : '' ?>>Featured First</option>
                                    </select>
                                </div>

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
                    <div class="portfolio-grid">
                        <?php while ($item = $portfolio_result->fetch_assoc()): ?>
                            <div class="portfolio-item">
                                <?php if ($item['is_featured']): ?>
                                    <div class="featured-badge">Featured</div>
                                <?php endif; ?>

                                <div class="file-type-badge"><?= ucfirst($item['file_type']) ?></div>

                                <div class="portfolio-thumbnail">
                                    <?php if ($item['file_type'] === 'image' && $item['thumbnail_url'] && file_exists($item['thumbnail_url'])): ?>
                                        <img src="<?= htmlspecialchars($item['thumbnail_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                    <?php else: ?>
                                        <div class="file-icon">
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

                                <div class="portfolio-info">
                                    <div class="portfolio-title"><?= htmlspecialchars($item['title']) ?></div>

                                    <div class="portfolio-meta">
                                        <span>
                                            <?= date('M j, Y', strtotime($item['upload_date'])) ?>
                                            <?php if ($item['talent_title']): ?>
                                                • <?= htmlspecialchars($item['talent_title']) ?>
                                            <?php endif; ?>
                                        </span>
                                        <span><?= $item['views'] ?> views</span>
                                    </div>

                                    <?php if ($item['description']): ?>
                                        <div class="portfolio-description">
                                            <?= nl2br(htmlspecialchars(substr($item['description'], 0, 100))) ?>
                                            <?= strlen($item['description']) > 100 ? '...' : '' ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="portfolio-actions">
                                        <a href="<?= htmlspecialchars($item['file_url']) ?>" 
                                           target="_blank" class="btn btn-primary" style="font-size: 12px; padding: 5px 10px;">
                                            <?= $item['file_type'] === 'document' ? 'Download' : 'View' ?>
                                        </a>

                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="toggle_featured" value="1">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="is_featured" value="<?= $item['is_featured'] ? 0 : 1 ?>">
                                            <button type="submit" class="btn <?= $item['is_featured'] ? 'btn-secondary' : 'btn-success' ?>">
                                                <?= $item['is_featured'] ? 'Unfeature' : 'Feature' ?>
                                            </button>
                                        </form>

                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this item? This action cannot be undone.')">
                                            <input type="hidden" name="delete_item" value="1">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No portfolio items found</h3>
                        <?php if (!empty($filter_type) || $filter_talent > 0): ?>
                            <p>No items match your current filters. <a href="my_portfolio.php">Clear filters</a> to see all items.</p>
                        <?php else: ?>
                            <p>You haven't uploaded any portfolio items yet.</p>
                            <a href="upload.php" class="btn btn-primary">Upload Your First Item</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="profile.php" class="btn btn-secondary">Back to Profile</a>
                </div>
            </div>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>