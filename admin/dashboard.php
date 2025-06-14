<?php
include 'includes/admin_auth.php';
requireAdmin();

$admin_id = $_SESSION['user_id'];
$admin_details = getAdminDetails($admin_id);
$stats = getDashboardStats();
$recent_activity = getRecentActivity(10);

// Get recent users
$recent_users_stmt = $conn->prepare("
    SELECT id, full_name, username, email, created_at, last_login 
    FROM users 
    WHERE user_type = 'student' 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_users_stmt->execute();
$recent_users = $recent_users_stmt->get_result();

// Get recent announcements
$recent_announcements_stmt = $conn->prepare("
    SELECT a.*, u.full_name as admin_name
    FROM announcements a
    JOIN users u ON a.admin_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 5
");
$recent_announcements_stmt->execute();
$recent_announcements = $recent_announcements_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MMU Talent Showcase</title>
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
        
        .sidebar-header h2 {
            color: #ecf0f1;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            color: #bdc3c7;
            font-size: 14px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #34495e;
            border-left: 3px solid #3498db;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2c3e50;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            font-size: 2.5em;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .dashboard-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .announcement-item {
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .announcement-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .announcement-meta {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-published {
            background: #cce5ff;
            color: #004085;
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
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
            <p>Welcome, <?= htmlspecialchars($admin_details['full_name']) ?></p>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="users.php">👥 Manage Users</a></li>
            <li><a href="announcements.php">📢 Announcements</a></li>
            <li><a href="talents.php">🎨 Talents</a></li>
            <li><a href="portfolio.php">📁 Portfolio</a></li>
            <li><a href="reports.php">📈 Reports</a></li>
            <li><a href="settings.php">⚙️ Settings</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="header-actions">
                <a href="announcements.php?action=create" class="btn btn-primary">New Announcement</a>
                <a href="../index.php" class="btn btn-primary">View Site</a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['active_users'] ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_talents'] ?></div>
                <div class="stat-label">Total Talents</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['published_announcements'] ?></div>
                <div class="stat-label">Published Announcements</div>
            </div>
        </div>
        
        <!-- Dashboard Content -->
        <div class="dashboard-grid">
            <div class="dashboard-section">
                <h3 class="section-title">Recent Users</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td>@<?= htmlspecialchars($user['username']) ?></td>
                            <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                            <td><?= $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never' ?></td>
                            <td><span class="status-badge status-active">Active</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div>
                <div class="dashboard-section" style="margin-bottom: 20px;">
                    <h3 class="section-title">Recent Announcements</h3>
                    <?php while ($announcement = $recent_announcements->fetch_assoc()): ?>
                    <div class="announcement-item">
                        <div class="announcement-title"><?= htmlspecialchars($announcement['title']) ?></div>
                        <div class="announcement-meta">
                            By <?= htmlspecialchars($announcement['admin_name']) ?> • 
                            <?= date('M j, Y', strtotime($announcement['created_at'])) ?>
                            <span class="status-badge status-published">Published</span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="dashboard-section">
                    <h3 class="section-title">Recent Activity</h3>
                    <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                    <div class="activity-item">
                        <div><strong><?= htmlspecialchars($activity['full_name'] ?: 'System') ?></strong></div>
                        <div><?= htmlspecialchars($activity['action_description'] ?: $activity['action_type']) ?></div>
                        <div class="activity-time"><?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>