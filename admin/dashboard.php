<?php
include 'includes/admin_auth.php';
requireAdmin();

$admin_id = $_SESSION['user_id'];
$admin_details = getAdminDetails($admin_id);
$stats = getDashboardStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MMU Talent Showcase</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="admin-layout">
        <div class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h2>Admin Panel</h2>
                <p>Welcome, <?= htmlspecialchars($admin_details['full_name']) ?></p>
            </div>
            
            <ul class="admin-sidebar-menu">
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
        
        <div class="admin-main-content">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-header-actions">
                    <a href="announcements.php?action=create" class="btn btn-primary">New Announcement</a>
                    <a href="../index.php" class="btn btn-primary">View Site</a>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <span class="admin-stat-number"><?= number_format($stats['total_users']) ?></span>
                    <div class="admin-stat-label">Total Users</div>
                </div>
                <div class="admin-stat-card">
                    <span class="admin-stat-number"><?= number_format($stats['active_users']) ?></span>
                    <div class="admin-stat-label">Active Users</div>
                </div>
                <div class="admin-stat-card">
                    <span class="admin-stat-number"><?= number_format($stats['total_talents']) ?></span>
                    <div class="admin-stat-label">Total Talents</div>
                </div>
                <div class="admin-stat-card">
                    <span class="admin-stat-number"><?= number_format($stats['total_portfolio']) ?></span>
                    <div class="admin-stat-label">Portfolio Items</div>
                </div>
            </div>
            
            <!-- Dashboard Content Grid -->
            <div class="admin-dashboard-grid">
                <!-- Recent Activity -->
                <div class="admin-dashboard-section">
                    <h3 class="admin-section-title">Recent Activity</h3>
                    <?php
                    $recent_activity = getRecentActivity(10);
                    if ($recent_activity->num_rows > 0):
                    ?>
                        <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                            <div class="admin-activity-item">
                                <strong><?= htmlspecialchars($activity['full_name'] ?: 'System') ?></strong>
                                <?= htmlspecialchars($activity['action_description']) ?>
                                <div class="admin-activity-time">
                                    <?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No recent activity.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Stats -->
                <div class="admin-dashboard-section">
                    <h3 class="admin-section-title">Quick Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="announcements.php?action=create" class="btn btn-primary">Create Announcement</a>
                        <a href="users.php" class="btn btn-secondary">Manage Users</a>
                        <a href="talents.php" class="btn btn-secondary">Review Talents</a>
                        <a href="reports.php" class="btn btn-secondary">View Reports</a>
                    </div>
                    
                    <h4 style="margin-top: 30px; margin-bottom: 15px;">System Status</h4>
                    <div>
                        <div class="admin-activity-item">
                            <strong>Announcements</strong>
                            <span class="admin-status-badge admin-status-published">
                                <?= $stats['published_announcements'] ?> Published
                            </span>
                        </div>
                        <div class="admin-activity-item">
                            <strong>User Status</strong>
                            <span class="admin-status-badge admin-status-active">
                                <?= $stats['active_users'] ?> Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
