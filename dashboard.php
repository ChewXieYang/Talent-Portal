<?php
include 'includes/db.php';

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Check user type
$user_type = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $resultUser = $stmt->get_result();
    if ($resultUser && $rowUser = $resultUser->fetch_assoc()) {
        $user_type = $rowUser['user_type'];
    }
    $stmt->close();
}

// SQL query to fetch recent image posts (with optional search by title or description)
$sql = "SELECT p.*, u.full_name FROM talent_uploads p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.file_type = 'image'";

if (!empty($searchQuery)) {
    $searchQueryEscaped = $conn->real_escape_string($searchQuery);
    $sql .= " AND (p.title LIKE '%$searchQueryEscaped%' OR p.description LIKE '%$searchQueryEscaped%' OR u.full_name LIKE '%$searchQueryEscaped%')";
}

$sql .= " ORDER BY p.upload_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="css/dashboard.css">
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
        <a href="logout.php" class="logout-link" style="margin-left: auto; font-weight: bold; text-decoration: none; color: #d00; padding: 8px;">Logout</a>
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
                    <img src="<?= htmlspecialchars($row['file_url']) ?>" alt="Post image" style="max-width: 100%; border-radius: 4px;">
                </div>
                <p><?= htmlspecialchars($row['description']) ?></p>

                <div class="like-comment-bar">
                    <span>👁️ <?= number_format($row['views']) ?> views</span>
                    <span>📅 <?= date('F j, Y', strtotime($row['upload_date'])) ?></span>
                </div>

                <?php if ($user_type === 'admin'): ?>
                <div class="post-actions">
                    <button onclick="alert('Delete post ID: <?= $row['id'] ?>')">🗑️ Delete post</button>
                    <button onclick="alert('Warn user: <?= htmlspecialchars($row['full_name']) ?>')">⚠️ Warn user</button>
                </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No posts found.</p>
    <?php endif; ?>

</div>

</body>
</html>