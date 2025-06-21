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
    <meta charset="UTF-8">
    <title>Talent Uploads - Reddit Style</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #dae0e6;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 640px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            padding: 15px;
        }

        .card img {
            max-width: 100%;
            border-radius: 4px;
        }

        .card h2 {
            margin: 10px 0 5px;
            font-size: 20px;
            color: #333;
        }

        .card p {
            margin: 0;
            color: #666;
        }

        .card .meta {
            font-size: 12px;
            color: #999;
            margin-top: 8px;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .upload-btn {
            padding: 8px 12px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-left: 8px;
            font-size: 14px;
            transition: background 0.3s;
        }
        .upload-btn:hover {
            background: #218838;
        }
        .card {
            cursor: pointer;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="header-bar">
        <img src="bell-icon.png" alt="Notifications">
        <img src="user-icon.png" alt="Profile">
        <a href="logout.php" class="logout-link" style="margin-left: auto; font-weight: bold; text-decoration: none; color: #d00; padding: 8px;">Logout</a>
    </div>

    <form method="GET" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
        <div style="flex: 1;">
            <input name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search posts or users" style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-left: 10px;">
            <button type="submit" style="padding: 8px 12px;">Search</button>
            <a href="upload_talent.php" class="upload-btn">Upload Talent</a>
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