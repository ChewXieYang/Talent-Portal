<?php
include 'includes/db.php';

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$selectedCategories = isset($_GET['categories']) ? $_GET['categories'] : [];

$categoryResult = $conn->query("SELECT * FROM talent_categories ORDER BY category_name");
if (!$categoryResult) {
    die("Error fetching categories: " . $conn->error);
}

$sql = "SELECT DISTINCT u.*, GROUP_CONCAT(tc.category_name SEPARATOR ', ') as user_categories
        FROM users u 
        LEFT JOIN user_talents ut ON u.id = ut.user_id
        LEFT JOIN talent_categories tc ON ut.category_id = tc.id";

$conditions = [];
$params = [];
$types = "";

if (!empty($selectedCategories)) {
    $placeholders = str_repeat('?,', count($selectedCategories) - 1) . '?';
    $conditions[] = "ut.category_id IN ($placeholders)";
    foreach ($selectedCategories as $catId) {
        $params[] = intval($catId);
        $types .= "i";
    }
}

if (!empty($searchQuery)) {
    $conditions[] = "(u.full_name LIKE ? OR u.short_bio LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY u.id ORDER BY u.full_name ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error . "<br>SQL: " . $sql);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/catalog.css">
    <title>Talent Catalog - MMU Talent Showcase</title>
</head>
<body>

<div class="wrapper">

    <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="catalog-header">
            <h1>Talent Catalog</h1>
            <p>Discover amazing talents from MMU students</p>
        </div>

        <div class="search-section">
            <form method="GET" class="search-form" id="mainForm">
                <input type="text" name="q" placeholder="Search talents by name or bio..." 
                       value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit">Search</button>
                <button type="button" class="filter-toggle" onclick="toggleFilters()">Filter by Category</button>
                <?php if (!empty($searchQuery) || !empty($selectedCategories)): ?>
                    <a href="catalog.php" class="search-form button clear-filters" 
                       style="display: inline-block; text-decoration: none; line-height: 1; padding: 12px 20px;">
                        Clear All
                    </a>
                <?php endif; ?>
            </form>

            <div class="filters" id="filterBox">
                <h4>Filter by Category:</h4>
                <form method="GET" id="filterForm">
                    <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
                    <div class="filter-items">
                        <?php 
                        $categoryResult->data_seek(0);
                        while ($cat = $categoryResult->fetch_assoc()): ?>
                            <label>
                                <input type="checkbox" name="categories[]" 
                                       value="<?= $cat['id'] ?>"
                                       <?= in_array($cat['id'], $selectedCategories) ? 'checked' : '' ?>
                                       onchange="document.getElementById('filterForm').submit()">
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </label>
                        <?php endwhile; ?>
                    </div>
                    <button type="submit">Apply Filters</button>
                </form>
            </div>
        </div>

        <div class="results-info">
            <?php 
            $resultCount = $result->num_rows;
            echo "Found $resultCount talent" . ($resultCount !== 1 ? 's' : '');
            if (!empty($searchQuery)) {
                echo " matching '" . htmlspecialchars($searchQuery) . "'";
            }
            if (!empty($selectedCategories)) {
                echo " in selected categories";
            }
            ?>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="talent-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <a href="view_profile.php?id=<?= $row['id'] ?>" style="text-decoration: none; color: inherit;">
                        <div class="talent-card">
                            <div class="talent-content">
                                <img src="<?= htmlspecialchars($row['profile_picture_url'] ?: 'https://via.placeholder.com/80x80?text=No+Image') ?>" 
                                     class="talent-img" 
                                     alt="Profile of <?= htmlspecialchars($row['full_name']) ?>">
                                <div class="talent-info">
                                    <h3><?= htmlspecialchars($row['full_name']) ?></h3>
                                    <?php if (!empty($row['user_categories'])): ?>
                                        <div class="talent-categories">
                                            <?php foreach (explode(', ', $row['user_categories']) as $category): ?>
                                                <?php if (trim($category)): ?>
                                                    <span class="category-tag"><?= htmlspecialchars(trim($category)) ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <p class="talent-bio">
                                        <?= nl2br(htmlspecialchars(substr($row['short_bio'] ?: 'No bio available.', 0, 120))) ?>
                                        <?= strlen($row['short_bio'] ?: '') > 120 ? '...' : '' ?>
                                    </p>
                                    <div class="talent-contact">
                                        <?php if ($row['contact_email']): ?>
                                            📧 <?= htmlspecialchars($row['contact_email']) ?>
                                        <?php endif; ?>
                                        <?php if ($row['phone_number']): ?>
                                            <?= $row['contact_email'] ? ' • ' : '' ?>
                                            📞 <?= htmlspecialchars($row['phone_number']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No talents found</h3>
                <p>Try adjusting your search criteria or browse all talents.</p>
                <?php if (!empty($searchQuery) || !empty($selectedCategories)): ?>
                    <p><a href="catalog.php">View all talents</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php include 'includes/footer.php'; ?>
    </div>

</div>

<script>
    function toggleFilters() {
        const filterBox = document.getElementById('filterBox');
        filterBox.style.display = (filterBox.style.display === 'block') ? 'none' : 'block';
    }
</script>

</body>
</html>
