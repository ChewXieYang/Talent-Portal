<?php
include 'includes/admin_auth.php';
requireAdmin();

$admin_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_announcement'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $type = $_POST['announcement_type'];
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        
        if (empty($title) || empty($content)) {
            $message = 'Title and content are required.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO announcements (admin_id, title, content, announcement_type, is_published, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssiss", $admin_id, $title, $content, $type, $is_published, $expiry_date);
            
            if ($stmt->execute()) {
                $message = 'Announcement created successfully!';
                $messageType = 'success';
                logAdminActivity('announcement_create', 'Created announcement: ' . $title);
            } else {
                $message = 'Error creating announcement.';
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['update_announcement'])) {
        $id = intval($_POST['announcement_id']);
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $type = $_POST['announcement_type'];
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, announcement_type = ?, is_published = ?, expiry_date = ? WHERE id = ?");
        $stmt->bind_param("sssisi", $title, $content, $type, $is_published, $expiry_date, $id);
        
        if ($stmt->execute()) {
            $message = 'Announcement updated successfully!';
            $messageType = 'success';
            logAdminActivity('announcement_update', 'Updated announcement: ' . $title);
        } else {
            $message = 'Error updating announcement.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['delete_announcement'])) {
        $id = intval($_POST['announcement_id']);
        
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = 'Announcement deleted successfully!';
            $messageType = 'success';
            logAdminActivity('announcement_delete', 'Deleted announcement ID: ' . $id);
        } else {
            $message = 'Error deleting announcement.';
            $messageType = 'error';
        }
    }
}

// Get announcement to edit
$editing_announcement = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editing_announcement = $result->fetch_assoc();
}

// Get all announcements
$announcements_stmt = $conn->prepare("
    SELECT a.*, u.full_name as admin_name
    FROM announcements a
    JOIN users u ON a.admin_id = u.id
    ORDER BY a.created_at DESC
");
$announcements_stmt->execute();
$announcements = $announcements_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Admin Panel</title>
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
        
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
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
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
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
        
        .btn-success {
            background: #27ae60;
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
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .announcements-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 15px;
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
        
        .badge-published {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-general {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .badge-event {
            background: #cce5ff;
            color: #004085;
        }
        
        .badge-workshop {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .badge-competition {
            background: #f8d7da;
            color: #721c24;
        }
        
        .announcement-content {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .actions {
            display: flex;
            gap: 5px;
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
            
            .table {
                font-size: 14px;
            }
            
            .table td {
                padding: 10px;
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
            <li><a href="announcements.php" style="background: #34495e;">📢 Announcements</a></li>
            <li><a href="talents.php">🎨 Talents</a></li>
            <li><a href="portfolio.php">📁 Portfolio</a></li>
            <li><a href="reports.php">📈 Reports</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Manage Announcements</h1>
            <p>Create and manage news, events, workshops, and competition announcements</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <!-- Create/Edit Form -->
        <div class="form-section">
            <h3><?= $editing_announcement ? 'Edit Announcement' : 'Create New Announcement' ?></h3>
            
            <form method="POST">
                <?php if ($editing_announcement): ?>
                    <input type="hidden" name="update_announcement" value="1">
                    <input type="hidden" name="announcement_id" value="<?= $editing_announcement['id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="create_announcement" value="1">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required 
                           value="<?= $editing_announcement ? htmlspecialchars($editing_announcement['title']) : '' ?>"
                           placeholder="Enter announcement title">
                </div>
                
                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea id="content" name="content" required 
                              placeholder="Enter announcement content..."><?= $editing_announcement ? htmlspecialchars($editing_announcement['content']) : '' ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="announcement_type">Type</label>
                        <select id="announcement_type" name="announcement_type">
                            <option value="general" <?= ($editing_announcement && $editing_announcement['announcement_type'] == 'general') ? 'selected' : '' ?>>General</option>
                            <option value="event" <?= ($editing_announcement && $editing_announcement['announcement_type'] == 'event') ? 'selected' : '' ?>>Event</option>
                            <option value="workshop" <?= ($editing_announcement && $editing_announcement['announcement_type'] == 'workshop') ? 'selected' : '' ?>>Workshop</option>
                            <option value="competition" <?= ($editing_announcement && $editing_announcement['announcement_type'] == 'competition') ? 'selected' : '' ?>>Competition</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date (Optional)</label>
                        <input type="datetime-local" id="expiry_date" name="expiry_date"
                               value="<?= $editing_announcement && $editing_announcement['expiry_date'] ? date('Y-m-d\TH:i', strtotime($editing_announcement['expiry_date'])) : '' ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_published" name="is_published" value="1"
                               <?= (!$editing_announcement || $editing_announcement['is_published']) ? 'checked' : '' ?>>
                        <label for="is_published">Publish immediately</label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <?= $editing_announcement ? 'Update Announcement' : 'Create Announcement' ?>
                    </button>
                    <?php if ($editing_announcement): ?>
                        <a href="announcements.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Announcements List -->
        <div class="announcements-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($announcements->num_rows > 0): ?>
                        <?php while ($announcement = $announcements->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                                <div class="announcement-content">
                                    <?= htmlspecialchars(substr($announcement['content'], 0, 100)) ?>...
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?= $announcement['announcement_type'] ?>">
                                    <?= ucfirst($announcement['announcement_type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $announcement['is_published'] ? 'badge-published' : 'badge-draft' ?>">
                                    <?= $announcement['is_published'] ? 'Published' : 'Draft' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($announcement['admin_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($announcement['created_at'])) ?></td>
                            <td>
                                <?= $announcement['expiry_date'] ? date('M j, Y', strtotime($announcement['expiry_date'])) : 'Never' ?>
                            </td>
                            <td class="actions">
                                <a href="announcements.php?edit=<?= $announcement['id'] ?>" class="btn btn-warning">Edit</a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                                    <input type="hidden" name="delete_announcement" value="1">
                                    <input type="hidden" name="announcement_id" value="<?= $announcement['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                No announcements found. Create your first announcement above.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>