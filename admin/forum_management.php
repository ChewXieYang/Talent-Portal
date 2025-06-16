<?php
include 'includes/admin_auth.php';
requireAdmin();

$admin_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle forum category management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_category'])) {
        $category_name = trim($_POST['category_name']);
        $description = trim($_POST['description']);
        
        if (empty($category_name)) {
            $message = 'Category name is required.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO forum_categories (category_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $category_name, $description);
            
            if ($stmt->execute()) {
                $message = 'Category created successfully!';
                $messageType = 'success';
                logAdminActivity('forum_category_create', 'Created forum category: ' . $category_name);
            } else {
                $message = 'Error creating category.';
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['update_category'])) {
        $id = intval($_POST['category_id']);
        $category_name = trim($_POST['category_name']);
        $description = trim($_POST['description']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE forum_categories SET category_name = ?, description = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssii", $category_name, $description, $is_active, $id);
        
        if ($stmt->execute()) {
            $message = 'Category updated successfully!';
            $messageType = 'success';
            logAdminActivity('forum_category_update', 'Updated forum category: ' . $category_name);
        } else {
            $message = 'Error updating category.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = intval($_POST['category_id']);
        
        $stmt = $conn->prepare("DELETE FROM forum_categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = 'Category deleted successfully!';
            $messageType = 'success';
            logAdminActivity('forum_category_delete', 'Deleted forum category ID: ' . $id);
        } else {
            $message = 'Error deleting category.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['moderate_topic'])) {
        $topic_id = intval($_POST['topic_id']);
        $action = $_POST['moderate_action'];
        
        if ($action === 'pin') {
            $stmt = $conn->prepare("UPDATE forum_topics SET is_pinned = 1 WHERE id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            $message = 'Topic pinned successfully!';
            $messageType = 'success';
        } elseif ($action === 'unpin') {
            $stmt = $conn->prepare("UPDATE forum_topics SET is_pinned = 0 WHERE id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            $message = 'Topic unpinned successfully!';
            $messageType = 'success';
        } elseif ($action === 'lock') {
            $stmt = $conn->prepare("UPDATE forum_topics SET is_locked = 1 WHERE id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            $message = 'Topic locked successfully!';
            $messageType = 'success';
        } elseif ($action === 'unlock') {
            $stmt = $conn->prepare("UPDATE forum_topics SET is_locked = 0 WHERE id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            $message = 'Topic unlocked successfully!';
            $messageType = 'success';
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM forum_topics WHERE id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            $message = 'Topic deleted successfully!';
            $messageType = 'success';
        }
        
        if ($stmt) {
            logAdminActivity('forum_moderate', "Moderated topic ID: $topic_id - Action: $action");
        }
    }
}

// Get editing category
$editing_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM forum_categories WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editing_category = $result->fetch_assoc();
}

// Get all categories with stats
$categories_stmt = $conn->query("
    SELECT fc.*, 
           COUNT(ft.id) as topic_count,
           SUM(ft.reply_count) as total_replies
    FROM forum_categories fc
    LEFT JOIN forum_topics ft ON fc.id = ft.category_id
    GROUP BY fc.id
    ORDER BY fc.id
");

// Get recent topics that need moderation
$recent_topics_stmt = $conn->query("
    SELECT ft.*, fc.category_name, u.full_name, u.username
    FROM forum_topics ft
    JOIN forum_categories fc ON ft.category_id = fc.id
    JOIN users u ON ft.user_id = u.id
    ORDER BY ft.created_at DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Management - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 20px 20px 20px;
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .sidebar-menu a:hover {
            background: #34495e;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
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
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .table tr:hover {
            background: #f8f9fa;
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
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-pinned {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-locked {
            background: #f8d7da;
            color: #721c24;
        }
        
        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .moderate-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="users.php">👥 Manage Users</a></li>
            <li><a href="announcements.php">📢 Announcements</a></li>
            <li><a href="forum_management.php" style="background: #34495e;">💬 Forum Management</a></li>
            <li><a href="talents.php">🎨 Talents</a></li>
            <li><a href="portfolio.php">📁 Portfolio</a></li>
            <li><a href="reports.php">📈 Reports</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Forum Management</h1>
            <p>Manage forum categories, moderate topics, and oversee community discussions</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <!-- Forum Statistics -->
        <div class="stats-grid">
            <?php
            $forum_stats = $conn->query("
                SELECT 
                    (SELECT COUNT(*) FROM forum_categories WHERE is_active = 1) as active_categories,
                    (SELECT COUNT(*) FROM forum_topics) as total_topics,
                    (SELECT COUNT(*) FROM forum_replies) as total_replies,
                    (SELECT COUNT(DISTINCT user_id) FROM forum_topics) as active_posters
            ")->fetch_assoc();
            ?>
            <div class="stat-card">
                <div class="stat-number"><?= $forum_stats['active_categories'] ?></div>
                <div class="stat-label">Active Categories</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $forum_stats['total_topics'] ?></div>
                <div class="stat-label">Total Topics</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $forum_stats['total_replies'] ?></div>
                <div class="stat-label">Total Replies</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $forum_stats['active_posters'] ?></div>
                <div class="stat-label">Active Posters</div>
            </div>
        </div>
        
        <!-- Category Management -->
        <div class="section">
            <h3 class="section-title"><?= $editing_category ? 'Edit Category' : 'Create New Category' ?></h3>
            
            <form method="POST">
                <?php if ($editing_category): ?>
                    <input type="hidden" name="update_category" value="1">
                    <input type="hidden" name="category_id" value="<?= $editing_category['id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="create_category" value="1">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_name">Category Name *</label>
                        <input type="text" id="category_name" name="category_name" required 
                               value="<?= $editing_category ? htmlspecialchars($editing_category['category_name']) : '' ?>"
                               placeholder="Enter category name">
                    </div>
                    
                    <?php if ($editing_category): ?>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                   <?= $editing_category['is_active'] ? 'checked' : '' ?>>
                            <label for="is_active">Active</label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Describe what this category is for..."><?= $editing_category ? htmlspecialchars($editing_category['description']) : '' ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <?= $editing_category ? 'Update Category' : 'Create Category' ?>
                    </button>
                    <?php if ($editing_category): ?>
                        <a href="forum_management.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Categories List -->
        <div class="section">
            <h3 class="section-title">Forum Categories</h3>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Topics</th>
                        <th>Replies</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($category = $categories_stmt->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($category['category_name']) ?></strong></td>
                        <td><?= htmlspecialchars($category['description']) ?></td>
                        <td><?= number_format($category['topic_count']) ?></td>
                        <td><?= number_format($category['total_replies'] ?: 0) ?></td>
                        <td>
                            <span class="badge <?= $category['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="forum_management.php?edit=<?= $category['id'] ?>" class="btn btn-warning">Edit</a>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Are you sure you want to delete this category? This will also delete all topics and replies in it.')">
                                <input type="hidden" name="delete_category" value="1">
                                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Topics Moderation -->
        <div class="section">
            <h3 class="section-title">Recent Topics - Moderation</h3>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Topic</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($topic = $recent_topics_stmt->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($topic['title']) ?></strong>
                            <br>
                            <small><?= htmlspecialchars(substr($topic['content'], 0, 100)) ?>...</small>
                        </td>
                        <td><?= htmlspecialchars($topic['full_name']) ?></td>
                        <td><?= htmlspecialchars($topic['category_name']) ?></td>
                        <td><?= date('M j, Y g:i A', strtotime($topic['created_at'])) ?></td>
                        <td>
                            <?php if ($topic['is_pinned']): ?>
                                <span class="badge badge-pinned">Pinned</span>
                            <?php endif; ?>
                            <?php if ($topic['is_locked']): ?>
                                <span class="badge badge-locked">Locked</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="moderate-actions">
                                <input type="hidden" name="moderate_topic" value="1">
                                <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                                
                                <?php if (!$topic['is_pinned']): ?>
                                    <button type="submit" name="moderate_action" value="pin" class="btn btn-success">Pin</button>
                                <?php else: ?>
                                    <button type="submit" name="moderate_action" value="unpin" class="btn btn-secondary">Unpin</button>
                                <?php endif; ?>
                                
                                <?php if (!$topic['is_locked']): ?>
                                    <button type="submit" name="moderate_action" value="lock" class="btn btn-warning">Lock</button>
                                <?php else: ?>
                                    <button type="submit" name="moderate_action" value="unlock" class="btn btn-success">Unlock</button>
                                <?php endif; ?>
                                
                                <button type="submit" name="moderate_action" value="delete" class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this topic?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>