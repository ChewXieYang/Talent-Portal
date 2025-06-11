<?php
include 'includes/db.php';

// Handle search and category filter input
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$selectedCategories = isset($_GET['categories']) ? $_GET['categories'] : [];

// Fetch categories for filter list
$categoryResult = $conn->query("SELECT * FROM categories");

// Build SQL query with filters
$sql = "SELECT DISTINCT u.*, GROUP_CONCAT(c.category_name SEPARATOR ', ') as categories
        FROM users u 
        LEFT JOIN user_categories uc ON u.user_id = uc.user_id
        LEFT JOIN categories c ON uc.category_id = c.category_id";

$conditions = [];
$params = [];
$types = "";

// Add category filter if selected
if (!empty($selectedCategories)) {
    $placeholders = str_repeat('?,', count($selectedCategories) - 1) . '?';
    $conditions[] = "uc.category_id IN ($placeholders)";
    foreach ($selectedCategories as $catId) {
        $params[] = intval($catId);
        $types .= "i";
    }
}

// Add search filter if provided
if (!empty($searchQuery)) {
    $conditions[] = "(u.full_name LIKE ? OR u.short_bio LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Add WHERE clause if we have conditions
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY u.user_id ORDER BY u.full_name ASC";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talent Catalog - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .catalog-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .catalog-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .search-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .search-form input[type="text"] {
            flex: 1;
            min-width: 250px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .search-form button {
            padding: 12px 20px;
            background: #005eff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .search-form button:hover {
            background: #0044cc;
        }
        
        .filter-toggle {
            background: #6c757d;
            margin-left: 5px;
        }
        
        .filter-toggle:hover {
            background: #5a6268;
        }
        
        .filters {
            display: none;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            background-color: #f8f9fa;
            margin-top: 15px;
        }
        
        .filters.show {
            display: block;
        }
        
        .filter-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .filter-items label {
            display: flex;
            align-items: center;
            background: white;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .filter-items label:hover {
            background: #e9ecef;
        }
        
        .filter-items input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .results-info {
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .talent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .talent-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        
        .talent-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .talent-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .talent-content {
            padding: 20px;
            display: flex;
        }
        
        .talent-info {
            flex: 1;
        }
        
        .talent-info h3 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 18px;
        }
        
        .talent-categories {
            margin-bottom: 10px;
        }
        
        .category-tag {
            display: inline-block;
            padding: 3px 8px;
            background: #007bff;
            color: white;
            border-radius: 12px;
            font-size: 11px;
            margin-right: 5px;
            margin-bottom: 3px;
        }
        
        .talent-bio {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 10px;
        }
        
        .talent-contact {
            font-size: 12px;
            color: #888;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #999;
        }
        
        .clear-filters {
            background: #dc3545;
            margin-left: 10px;
        }
        
        .clear-filters:hover {
            background: #c82333;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .search-form input[type="text"] {
                min-width: 100%;
            }
            
            .talent-grid {
                grid-template-columns: 1fr;
            }
            
            .talent-content {
                flex-direction: column;
                text-align: center;
            }
            
            .talent-img {
                align-self: center;
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="catalog-header">
        <h1>Talent Catalog</h1>
        <p>Discover amazing talents from MMU students</p>
    </div>

    <div class="search-section">
        <form method="GET" class="search-form">
            <input type="text" name="q" placeholder="Search talents by name or bio..." 
                   value="<?= htmlspecialchars($searchQuery) ?>">
            <button type="submit">Search</button>
            <button type="button" class="filter-toggle" onclick="toggleFilters()">
                Filter by Category
            </button>
            <?php if (!empty($searchQuery) || !empty($selectedCategories)): ?>
                <a href="catalog.php" class="search-form button clear-filters" 
                   style="display: inline-block; text-decoration: none; line-height: 1;">
                    Clear All
                </a>
            <?php endif; ?>
        </form>
        
        <div class="filters" id="filterBox">
            <h4>Filter by Category:</h4>
            <div class="filter-items">
                <?php 
                // Reset the result pointer for categories
                $categoryResult->data_seek(0);
                while ($cat = $categoryResult->fetch_assoc()): 
                ?>
                    <label>
                        <input type="checkbox" name="categories[]" 
                               value="<?= $cat['category_id'] ?>"
                               <?= in_array($cat['category_id'], $selectedCategories) ? 'checked' : '' ?>
                               onchange="this.form.submit()">
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </label>
                <?php endwhile; ?>
            </div>
            <button type="submit" form="searchForm">Apply Filters</button>
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
                <a href="view_profile.php?id=<?= $row['user_id'] ?>" style="text-decoration: none; color: inherit;">
                    <div class="talent-card">
                        <div class="talent-content">
                            <img src="<?= htmlspecialchars($row['profile_picture_url'] ?: 'uploads/avatars/default-avatar.jpg') ?>" 
                                 class="talent-img" 
                                 alt="Profile of <?= htmlspecialchars($row['full_name']) ?>"
                                 onerror="this.src='uploads/avatars/default-avatar.jpg'">
                            
                            <div class="talent-info">
                                <h3><?= htmlspecialchars($row['full_name']) ?></h3>
                                
                                <?php if (!empty($row['categories'])): ?>
                                    <div class="talent-categories">
                                        <?php 
                                        $categories = explode(', ', $row['categories']);
                                        foreach ($categories as $category): 
                                        ?>
                                            <span class="category-tag"><?= htmlspecialchars($category) ?></span>
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

    <script>
        function toggleFilters() {
            const filterBox = document.getElementById('filterBox');
            filterBox.classList.toggle('show');
        }

        // Auto-submit form when category checkboxes change
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="categories[]"]');
            const form = document.querySelector('form');
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    form.submit();
                });
            });
        });
    </script>
</body>
</html>
