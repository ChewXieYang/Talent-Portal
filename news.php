<?php
include 'includes/db.php';

// Get filter parameters
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build SQL query
$sql = "SELECT a.*, u.full_name as admin_name
        FROM announcements a
        JOIN users u ON a.admin_id = u.id
        WHERE a.is_published = 1 
        AND (a.expiry_date IS NULL OR a.expiry_date > NOW())";

$params = [];
$types = "";

// Add type filter
if (!empty($filter_type)) {
    $sql .= " AND a.announcement_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

// Add search filter
if (!empty($search_query)) {
    $sql .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $search_term = '%' . $search_query . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$sql .= " ORDER BY a.publish_date DESC, a.created_at DESC";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$announcements = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News & Announcements - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .page-header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-form input[type="text"] {
            flex: 1;
            min-width: 250px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .filter-form select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .filter-form button {
            padding: 12px 20px;
            background: #005eff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .filter-form button:hover {
            background: #0044cc;
        }
        
        .announcements-grid {
            display: grid;
            gap: 25px;
        }
        
        .announcement-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .announcement-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .announcement-title {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .announcement-meta {
            display: flex;
            gap: 15px;
            align-items: center;
            color: #666;
            font-size: 14px;
            flex-wrap: wrap;
        }
        
        .announcement-type {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .type-general {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .type-event {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .type-workshop {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .type-competition {
            background: #ffebee;
            color: #c62828;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .announcement-content {
            padding: 20px;
        }
        
        .announcement-text {
            color: #555;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 15px;
        }
        
        .announcement-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            font-size: 14px;
            color: #666;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .date-info {
            font-style: italic;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .empty-state h3 {
            color: #999;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #666;
        }
        
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: #5a6268;
        }
        
        .results-info {
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-form input[type="text"] {
                min-width: auto;
            }
            
            .announcement-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .announcement-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <a href="index.php" class="back-button">← Back to Home</a>
        
        <div class="page-header">
            <h1>News & Announcements</h1>
            <p>Stay updated with the latest news, events, workshops, and competitions from MMU Talent Showcase</p>
        </div>
        
        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" class="filter-form">
                <input type="text" name="q" placeholder="Search announcements..." 
                       value="<?= htmlspecialchars($search_query) ?>">
                
                <select name="type">
                    <option value="">All Types</option>
                    <option value="general" <?= $filter_type === 'general' ? 'selected' : '' ?>>General</option>
                    <option value="event" <?= $filter_type === 'event' ? 'selected' : '' ?>>Events</option>
                    <option value="workshop" <?= $filter_type === 'workshop' ? 'selected' : '' ?>>Workshops</option>
                    <option value="competition" <?= $filter_type === 'competition' ? 'selected' : '' ?>>Competitions</option>
                </select>
                
                <button type="submit">Search</button>
                
                <?php if (!empty($search_query) || !empty($filter_type)): ?>
                    <a href="news.php" style="padding: 12px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Results Info -->
        <div class="results-info">
            <?php 
            $result_count = $announcements->num_rows;
            echo "Found $result_count announcement" . ($result_count !== 1 ? 's' : '');
            if (!empty($search_query)) {
                echo " matching '" . htmlspecialchars($search_query) . "'";
            }
            if (!empty($filter_type)) {
                echo " in " . ucfirst($filter_type) . " category";
            }
            ?>
        </div>
        
        <!-- Announcements -->
        <?php if ($announcements->num_rows > 0): ?>
            <div class="announcements-grid">
                <?php while ($announcement = $announcements->fetch_assoc()): ?>
                    <article class="announcement-card">
                        <div class="announcement-header">
                            <h2 class="announcement-title"><?= htmlspecialchars($announcement['title']) ?></h2>
                            <div class="announcement-meta">
                                <span class="announcement-type type-<?= $announcement['announcement_type'] ?>">
                                    <?= ucfirst($announcement['announcement_type']) ?>
                                </span>
                                <span>📅 <?= date('F j, Y', strtotime($announcement['publish_date'] ?: $announcement['created_at'])) ?></span>
                                <span>👤 By <?= htmlspecialchars($announcement['admin_name']) ?></span>
                                <?php if ($announcement['expiry_date']): ?>
                                    <span>⏰ Expires <?= date('M j, Y', strtotime($announcement['expiry_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="announcement-content">
                            <div class="announcement-text">
                                <?= nl2br(htmlspecialchars($announcement['content'])) ?>
                            </div>
                            
                            <div class="announcement-footer">
                                <div class="admin-info">
                                    <span>📢 Posted by <strong><?= htmlspecialchars($announcement['admin_name']) ?></strong></span>
                                </div>
                                <div class="date-info">
                                    <?= date('M j, Y \a\t g:i A', strtotime($announcement['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No announcements found</h3>
                <?php if (!empty($search_query) || !empty($filter_type)): ?>
                    <p>No announcements match your search criteria. <a href="news.php">View all announcements</a></p>
                <?php else: ?>
                    <p>There are currently no published announcements. Check back later for updates!</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>