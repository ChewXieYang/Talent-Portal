<?php
include 'includes/db.php';

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

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
    <title>Student Dashboard - MMU Talent Showcase</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="css/dashboard.css">
    <link rel="stylesheet" type="text/css" href="css/sidebar.css">
    <link rel="stylesheet" type="text/css" href="css/student_dashboard.css">
    <meta charset="UTF-8">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <?php include 'includes/header.php'; ?>

    <form method="GET" class="dashboard-search-form">
        <div class="dashboard-search-input-group">
            <input name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search posts or users" class="dashboard-search-input">
        </div>
        <div class="dashboard-search-actions">
            <button type="submit" class="dashboard-search-btn">Search</button>
            <a href="upload_talent.php" class="dashboard-upload-btn">Upload Post</a>
        </div>
    </form>
    <hr>
    <div class="container">
        <h1>Latest Talent Uploads</h1>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <a href="detailed_view.php?id=<?= $row['id'] ?>" class="detailed-view-card-link">
                    <div class="card">
                        <h2><?= htmlspecialchars($row['title']) ?></h2>
                        <img src="<?= htmlspecialchars($row['file_url']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                        <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                        <div class="meta">Uploaded by <?= htmlspecialchars($row['full_name']) ?> | ID: <?= $row['id'] ?></div>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No image uploads found.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
