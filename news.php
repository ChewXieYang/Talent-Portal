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
    <link rel="stylesheet" href="css/news.css">
    <title>News & Announcements - MMU Talent Showcase</title>
</head>
<body>
    <div class="wrapper">
        <link rel="stylesheet" href="css/sidebar.css">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
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
        </div>
    </div>
</body>

</html>