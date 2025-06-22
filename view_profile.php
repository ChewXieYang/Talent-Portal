<?php
include 'includes/db.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    header('Location: catalog.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    // User not found or inactive
    header('Location: catalog.php');
    exit;
}

$user = $user_result->fetch_assoc();

$talents_stmt = $conn->prepare("
    SELECT ut.*, tc.category_name 
    FROM user_talents ut
    JOIN talent_categories tc ON ut.category_id = tc.id
    WHERE ut.user_id = ?
    ORDER BY ut.is_featured DESC, ut.created_at DESC
");
$talents_stmt->bind_param("i", $user_id);
$talents_stmt->execute();
$talents_result = $talents_stmt->get_result();

$portfolio_stmt = $conn->prepare("
    SELECT pi.*, ut.talent_title 
    FROM portfolio_items pi
    LEFT JOIN user_talents ut ON pi.talent_id = ut.id
    WHERE pi.user_id = ?
    ORDER BY pi.is_featured DESC, pi.upload_date DESC
    LIMIT 10
");
$portfolio_stmt->bind_param("i", $user_id);
$portfolio_stmt->execute();
$portfolio_result = $portfolio_stmt->get_result();

$services_stmt = $conn->prepare("
    SELECT s.*, ut.talent_title 
    FROM services s
    LEFT JOIN user_talents ut ON s.talent_id = ut.id
    WHERE s.user_id = ? AND s.is_available = 1
    ORDER BY s.created_at DESC
");
$services_stmt->bind_param("i", $user_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - MMU Talent Showcase</title>
    <link rel="stylesheet" href="css/view-profile.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="profile-container">
        <a href="catalog.php" class="back-button">← Back to Catalog</a>
        
        <div class="profile-header">
            <div class="profile-top">
                <img src="<?= htmlspecialchars($user['profile_picture_url'] ?: 'https://via.placeholder.com/150x150?text=No+Image') ?>" 
                     alt="<?= htmlspecialchars($user['full_name']) ?>" 
                     class="profile-picture">
                     <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user_id): ?>
                        <a href="chat.php?to=<?= $user_id ?>" class="message-button">
                            <i class="fas fa-envelope"></i> Message
                        </a>
                    <?php endif; ?>


</a>
                
                <div class="profile-info">
                    <h1><?= htmlspecialchars($user['full_name']) ?></h1>
                    
                    <div class="profile-meta">
                        <span><strong>Username:</strong> @<?= htmlspecialchars($user['username']) ?></span>
                        <span><strong>Student ID:</strong> <?= htmlspecialchars($user['student_id']) ?></span>
                        <span><strong>Section:</strong> <?= htmlspecialchars($user['section']) ?></span>
                        <span><strong>Joined:</strong> <?= date('F Y', strtotime($user['created_at'])) ?></span>
                    </div>
                    
                    <?php if ($user['short_bio']): ?>
                        <div class="profile-bio">
                            <?= nl2br(htmlspecialchars($user['short_bio'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="contact-info">
                        <?php if ($user['contact_email']): ?>
                            <a href="mailto:<?= htmlspecialchars($user['contact_email']) ?>" class="contact-item">
                                📧 <?= htmlspecialchars($user['contact_email']) ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($user['phone_number']): ?>
                            <a href="tel:<?= htmlspecialchars($user['phone_number']) ?>" class="contact-item">
                                📞 <?= htmlspecialchars($user['phone_number']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Talents Section -->
        <?php if ($talents_result->num_rows > 0): ?>
            <div class="section">
                <h2 class="section-title">Talents & Skills</h2>
                <div class="talents-grid">
                    <?php while ($talent = $talents_result->fetch_assoc()): ?>
                        <div class="talent-card">
                            <div class="talent-header">
                                <div>
                                    <div class="talent-title"><?= htmlspecialchars($talent['talent_title']) ?></div>
                                    <span class="talent-category"><?= htmlspecialchars($talent['category_name']) ?></span>
                                </div>
                                <div>
                                    <span class="skill-level skill-<?= $talent['skill_level'] ?>">
                                        <?= ucfirst($talent['skill_level']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="talent-meta">
                                <strong>Experience:</strong> <?= $talent['years_experience'] ?> year<?= $talent['years_experience'] != 1 ? 's' : '' ?>
                                <?php if ($talent['is_featured']): ?>
                                    <span style="color: #007bff; font-weight: bold;">• Featured</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($talent['talent_description']): ?>
                                <div class="talent-description">
                                    <?= nl2br(htmlspecialchars($talent['talent_description'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Portfolio Section -->
        <?php if ($portfolio_result->num_rows > 0): ?>
            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="section-title" style="margin-bottom: 0;">Portfolio</h2>
                    <?php
                    // Get total portfolio count for this user
                    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM portfolio_items WHERE user_id = ?");
                    $count_stmt->bind_param("i", $user_id);
                    $count_stmt->execute();
                    $total_count = $count_stmt->get_result()->fetch_assoc()['total'];
                    if ($total_count > 10):
                    ?>
                        <a href="browse_portfolio.php?user_id=<?= $user_id ?>" class="view-all-btn">
                            View All (<?= $total_count ?> items)
                        </a>
                    <?php endif; ?>
                </div>
                <div class="portfolio-grid">
                    <?php while ($item = $portfolio_result->fetch_assoc()): ?>
                        <a href="view_portfolio_item.php?id=<?= $item['id'] ?>" class="portfolio-item">
                            <div class="portfolio-thumb">
                                <?php if ($item['file_type'] == 'image' && $item['thumbnail_url']): ?>
                                    <img src="<?= htmlspecialchars($item['thumbnail_url']) ?>" 
                                         alt="<?= htmlspecialchars($item['title']) ?>">
                                <?php else: ?>
                                    <?php
                                    $icons = [
                                        'video' => '🎥',
                                        'audio' => '🎵',
                                        'document' => '📄',
                                        'code' => '💻',
                                        'other' => '📎'
                                    ];
                                    echo $icons[$item['file_type']] ?? '📎';
                                    ?>
                                <?php endif; ?>
                            </div>
                            <div class="portfolio-info">
                                <div class="portfolio-title"><?= htmlspecialchars($item['title']) ?></div>
                                <div class="portfolio-meta">
                                    <?php if ($item['talent_title']): ?>
                                        <strong><?= htmlspecialchars($item['talent_title']) ?></strong> • 
                                    <?php endif; ?>
                                    <?= date('M Y', strtotime($item['upload_date'])) ?>
                                    <?php if ($item['views'] > 0): ?>
                                        • <?= $item['views'] ?> views
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Services Section -->
        <?php if ($services_result->num_rows > 0): ?>
            <div class="section">
                <h2 class="section-title">Available Services</h2>
                <div class="services-list">
                    <?php while ($service = $services_result->fetch_assoc()): ?>
                        <div class="service-item">
                            <div class="service-header">
                                <div class="service-title"><?= htmlspecialchars($service['service_title']) ?></div>
                                <?php if ($service['price_range']): ?>
                                    <div class="service-price"><?= htmlspecialchars($service['price_range']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($service['service_description']): ?>
                                <div class="service-description">
                                    <?= nl2br(htmlspecialchars($service['service_description'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="service-meta">
                                <?php if ($service['talent_title']): ?>
                                    <strong>Category:</strong> <?= htmlspecialchars($service['talent_title']) ?>
                                <?php endif; ?>
                                <?php if ($service['delivery_time']): ?>
                                    <?= $service['talent_title'] ? ' • ' : '' ?>
                                    <strong>Delivery:</strong> <?= htmlspecialchars($service['delivery_time']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- If user has no talents, show a message -->
        <?php if ($talents_result->num_rows === 0): ?>
            <div class="section">
                <div class="empty-state">
                    <h3>No talents added yet</h3>
                    <p>This user hasn't added any talents to their profile.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
