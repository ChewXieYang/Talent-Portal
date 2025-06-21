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
    <title>My Portfolio - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            text-align: center;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #005eff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .controls-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .controls-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 12px;
            color: #666;
            font-weight: bold;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
            font-size: 14px;
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
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            font-size: 12px;
            padding: 5px 10px;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            font-size: 12px;
            padding: 5px 10px;
        }
        
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .portfolio-item {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        
        .portfolio-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .portfolio-thumbnail {
            width: 100%;
            height: 200px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .portfolio-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .file-icon {
            font-size: 48px;
            color: #999;
        }
        
        .portfolio-info {
            padding: 20px;
        }
        
        .portfolio-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .portfolio-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .portfolio-description {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .portfolio-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff6b35;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .file-type-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state h3 {
            margin-bottom: 15px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .controls-top {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filters {
                justify-content: center;
            }
            
            .portfolio-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
            <div class="portfolio-grid">
                <?php while ($item = $portfolio_result->fetch_assoc()): ?>
                    <div class="portfolio-item">
                        <?php if ($item['is_featured']): ?>
                            <div class="featured-badge">Featured</div>
                        <?php endif; ?>
                        
                        <div class="file-type-badge"><?= ucfirst($item['file_type']) ?></div>
                        
                        <a href="view_portfolio_item.php?id=<?= $item['id'] ?>" style="text-decoration: none; color: inherit;">
                            <div class="portfolio-thumbnail">
                                <?php if ($item['file_type'] === 'image' && $item['thumbnail_url'] && file_exists($item['thumbnail_url'])): ?>
                                    <img src="<?= htmlspecialchars($item['thumbnail_url']) ?>" 
                                         alt="<?= htmlspecialchars($item['title']) ?>">
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
                        </a>
                        
                        <div class="portfolio-info">
                            <div class="portfolio-title">
                                <a href="view_portfolio_item.php?id=<?= $item['id'] ?>" style="text-decoration: none; color: inherit;">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </div>
                            
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
                                <!-- View Button -->
                                <a href="view_portfolio_item.php?id=<?= $item['id'] ?>" 
                                   class="btn btn-primary" style="font-size: 12px; padding: 5px 10px;">
                                    👁️ View
                                </a>
                                
                                <!-- Download Button (for documents) -->
                                <?php if ($item['file_type'] === 'document'): ?>
                                    <a href="<?= htmlspecialchars($item['file_url']) ?>" 
                                       target="_blank" class="btn btn-secondary" style="font-size: 12px; padding: 5px 10px;"
                                       download="<?= htmlspecialchars($item['title']) ?>">
                                        📥 Download
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Toggle Featured Button -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="toggle_featured" value="1">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="is_featured" value="<?= $item['is_featured'] ? 0 : 1 ?>">
                                    <button type="submit" class="btn <?= $item['is_featured'] ? 'btn-secondary' : 'btn-success' ?>">
                                        <?= $item['is_featured'] ? 'Unfeature' : 'Feature' ?>
                                    </button>
                                </form>
                                
                                <!-- Delete Button -->
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
</body>
</html>