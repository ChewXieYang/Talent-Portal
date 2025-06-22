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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <title><?= htmlspecialchars($user['full_name']) ?> - MMU Talent Showcase</title>
    <style>
        .message-button {
            display: inline-block;
            padding: 8px 16px;
            background: #0079d3;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 10px;
            font-size: 14px;
            transition: background 0.3s;
        }
        .message-button i {
            margin-right: 6px;
        }
        .message-button:hover {
            background: #005fa3;
        }

        .send-msg {
            display: inline-block;
            margin-top: 5px;
            font-size: 14px;
            color: #0079d3;
            text-decoration: none;
        }
        .send-msg i {
            margin-right: 5px;
        }
        .send-msg:hover {
            text-decoration: underline;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .profile-top {
            display: flex;
            gap: 30px;
            align-items: start;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e0e0e0;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-info h1 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 2.2em;
        }
        
        .profile-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .profile-meta span {
            margin-right: 20px;
            display: inline-block;
        }
        
        .profile-bio {
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .contact-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: #f8f9fa;
            border-radius: 20px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .contact-item:hover {
            background: #e9ecef;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .talents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .talent-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .talent-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .talent-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .talent-title {
            font-weight: bold;
            color: #333;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        
        .talent-category {
            display: inline-block;
            padding: 4px 12px;
            background: #007bff;
            color: white;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .talent-meta {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .talent-description {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .skill-level {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .skill-beginner { background: #ffc107; color: #856404; }
        .skill-intermediate { background: #28a745; color: white; }
        .skill-advanced { background: #17a2b8; color: white; }
        .skill-expert { background: #dc3545; color: white; }
        
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .portfolio-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .portfolio-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .portfolio-thumb {
            width: 100%;
            height: 180px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #999;
            position: relative;
            overflow: hidden;
        }
        
        .portfolio-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .portfolio-info {
            padding: 15px;
        }
        
        .portfolio-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .portfolio-meta {
            font-size: 12px;
            color: #666;
        }
        
        .services-list {
            display: grid;
            gap: 15px;
        }
        
        .service-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
        }
        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .service-title {
            font-weight: bold;
            color: #333;
            font-size: 1.1em;
        }
        
        .service-price {
            color: #28a745;
            font-weight: bold;
        }
        
        .service-description {
            color: #555;
            margin-bottom: 10px;
        }
        
        .service-meta {
            font-size: 12px;
            color: #666;
        }
        
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: #5a6268;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .view-all-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .view-all-btn:hover {
            background: #0056b3;
        }
        
        @media (max-width: 768px) {
            .profile-top {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-picture {
                width: 120px;
                height: 120px;
            }
            
            .talents-grid {
                grid-template-columns: 1fr;
            }
            
            .portfolio-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
