<?php
include 'includes/db.php';

// Handle search input (optional to leave this for now)
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="dashboard.css">
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

    <!-- 🔹 MOCK POST CARDS: Replace these later with actual DB results -->
    <?php for ($i = 1; $i <= 2; $i++): ?>
        <div class="post-card">
            <div><strong>@Student<?= $i ?></strong></div>
            <div>
                <img src="https://via.placeholder.com/600x300.png?text=Post+Image+<?= $i ?>" alt="Post image">
            </div>
            <p>This is a sample caption for post <?= $i ?>. This is just for layout testing.</p>

            <div class="like-comment-bar">
                <span>👍 <?= rand(50, 200) ?></span>
                <span>💬 <?= rand(1, 20) ?> comments</span>
            </div>

            <div class="post-actions">
                <button onclick="alert('Delete post ID: <?= $i ?>')">🗑️ Delete post</button>
                <button onclick="alert('Warn user: Student<?= $i ?>')">⚠️ Warn user</button>
            </div>
        </div>
    <?php endfor; ?>

</div>

</body>
</html>
