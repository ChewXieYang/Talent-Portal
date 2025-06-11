<?php
include 'includes/db.php';

// Handle search and category filter input
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$selectedCategories = isset($_GET['categories']) ? $_GET['categories'] : [];

// Fetch categories for filter list
$categoryResult = $conn->query("SELECT * FROM categories");

// Build SQL query with filters
$sql = "SELECT DISTINCT users.* FROM users 
        LEFT JOIN user_categories ON users.user_id = user_categories.user_id";

$conditions = [];

if (!empty($selectedCategories)) {
    $catIds = implode(',', array_map('intval', $selectedCategories));
    $conditions[] = "user_categories.category_id IN ($catIds)";
}

if (!empty($searchQuery)) {
    $searchQueryEscaped = $conn->real_escape_string($searchQuery);
    $conditions[] = "(users.full_name LIKE '%$searchQueryEscaped%' OR users.short_bio LIKE '%$searchQueryEscaped%')";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY users.full_name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Talent Catalog</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: auto;
            padding: 20px;
            text-align: center;
        }

        .talent-card {
            border: 1px solid #ccc;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            text-align: left;
            display: block;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .talent-card:hover {
            background-color: #f0f0f0;
        }

        .talent-img {
            max-width: 100px;
            height: auto;
            float: left;
            margin-right: 12px;
        }

        form {
            margin-bottom: 20px;
        }

        .filters {
            display: none;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 6px;
            background-color: #f8f8f8;
            text-align: left;
        }

        .filter-items {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-items label {
            white-space: nowrap;
            background: #eee;
            padding: 6px 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>

<h1>Talent Catalog</h1>

<form method="GET">
    <input name="q" placeholder="Search talents..." value="<?= htmlspecialchars($searchQuery) ?>">
    <button type="submit">Search</button>
    <button type="button" onclick="toggleFilters()">Filter by Category</button>

    <div class="filters" id="filterBox">
        <h4>Filter by Category:</h4>
        <div class="filter-items">
            <?php while ($cat = $categoryResult->fetch_assoc()): ?>
                <label>
                    <input type="checkbox" name="categories[]" value="<?= $cat['category_id'] ?>"
                        <?= in_array($cat['category_id'], $selectedCategories) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($cat['category_name']) ?>
                </label>
            <?php endwhile; ?>
        </div>
        <br>
        <button type="submit">Apply Filters</button>
    </div>
</form>

<hr>

<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <a href="profile.php?id=<?= $row['user_id'] ?>" style="text-decoration: none; color: inherit;">
            <div class="talent-card clearfix">
                <img src="<?= htmlspecialchars($row['profile_picture_url']) ?>" class="talent-img" alt="Profile of <?= htmlspecialchars($row['full_name']) ?>">
                <h3><?= htmlspecialchars($row['full_name']) ?></h3>
                <p><strong>Bio:</strong> <?= htmlspecialchars($row['short_bio']) ?></p>
                <p><strong>Contact:</strong> <?= htmlspecialchars($row['contact_email']) ?> | <?= htmlspecialchars($row['phone_number']) ?></p>
            </div>
        </a>
    <?php endwhile; ?>
<?php else: ?>
    <p>No talents found.</p>
<?php endif; ?>

<script>
    function toggleFilters() {
        const filterBox = document.getElementById('filterBox');
        filterBox.style.display = filterBox.style.display === 'block' ? 'none' : 'block';
    }
</script>

</body>
</html>
