<?php
include 'includes/db.php';

// Handle search input
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Fetch posts (replace with your actual posts table structure)
$sql = "SELECT posts.*, users.full_name FROM posts JOIN users ON posts.user_id = users.id";
if (!empty($searchQuery)) {
    $searchQueryEscaped = $conn->real_escape_string($searchQuery);
    $sql .= " WHERE users.full_name LIKE '%$searchQueryEscaped%' OR posts.caption LIKE '%$searchQueryEscaped%'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="admin-dashboard.css">
</head>
<body>

<div class="sidebar">
    <a href="#">🏠 Home</a>
    <a href="#">🔍 Search</a>
    <a href="#">➕ Create</a>
    <a href="#">📢 Make announcement</a>
</div>

<div class="main">
    <div class="header-bar">
        <img src="bell-icon.png" alt="Notifications">
        <img src="user-icon.png" alt="Profile">
    </div>

    <form method="GET">
        <input name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search posts or users">
        <button type="submit">Search</button>
    </form>

    <hr>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="post-card">
                <div><strong>@<?= htmlspecialchars($row['full_name']) ?></strong></div>
                <div>
                    <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="Post image">
                </div>
                <p><?= htmlspecialchars($row['caption']) ?></p>

                <div class="like-comment-bar">
                    <span>👍 <?= $row['likes'] ?? 0 ?></span>
                    <span>💬 <?= $row['comments_count'] ?? 0 ?> comments</span>
                </div>

                <div class="post-actions">
                    <button onclick="alert('Delete post ID: <?= $row['id'] ?>')">🗑️ Delete post</button>
                    <button onclick="alert('Warn user: <?= htmlspecialchars($row['full_name']) ?>')">⚠️ Warn user</button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No posts found.</p>
    <?php endif; ?>
</div>

</body>
</html>
