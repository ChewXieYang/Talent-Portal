<?php
include 'includes/db.php';

// Handle search input
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Basic SQL query
$sql = "SELECT * FROM users";
if (!empty($searchQuery)) {
    $searchQueryEscaped = $conn->real_escape_string($searchQuery);
    $sql .= " WHERE full_name LIKE '%$searchQueryEscaped%' OR short_bio LIKE '%$searchQueryEscaped%'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Talent Catalog</title>
    <style>
        .talent-card {
            border: 1px solid #ccc;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            max-width: 400px;
        }
        .talent-img {
            max-width: 100px;
            height: auto;
        }
    </style>
</head>
<body>
<h1>Talent Catalog</h1>

<form method="GET">
    Search: <input name="q" value="<?= htmlspecialchars($searchQuery) ?>">
    <button type="submit">Search</button>
</form>

<hr>

<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="talent-card">
            <img src="<?= htmlspecialchars($row['profile_picture_url']) ?>" class="talent-img" alt="Profile picture of <?= htmlspecialchars($row['full_name']) ?>">
            <h3><?= htmlspecialchars($row['full_name']) ?></h3>
            <p><strong>Bio:</strong> <?= htmlspecialchars($row['short_bio']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($row['contact_email']) ?> | <?= htmlspecialchars($row['phone_number']) ?></p>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No talents found.</p>
<?php endif; ?>

</body>
</html>
