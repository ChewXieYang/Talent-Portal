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
    <link rel="stylesheet" type="text/css" href="css/sidebar.css">
    <link rel="stylesheet" type="text/css" href="css/student_dashboard.css">
    <meta charset="UTF-8">
    <title>Upload Post</title>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <?php include 'includes/header.php'; ?>

    <form method="GET" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
        <div style="flex: 1; margin-top: 10px;">
            <input name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search posts or users" style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-left: 10px; margin-top: 10px;">
            <button type="submit" style="padding: 8px 12px;">Search</button>
            <a href="upload_talent.php" class="upload-btn">Upload Post</a>
        </div>
    </form>


    <hr>

    <div class="container">
    <h1>Latest Talent Uploads</h1>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <a href="detailed_view.php?id=<?= $row['id'] ?>" style="text-decoration: none; color: inherit;">
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

</body>
</html>