<?php
include 'includes/db.php';

// Get portfolio item ID from URL
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id <= 0) {
    header('Location: catalog.php');
    exit;
}

// Get portfolio item details with user and talent information
$stmt = $conn->prepare("
    SELECT pi.*, u.full_name, u.username, u.profile_picture_url, ut.talent_title, tc.category_name 
    FROM portfolio_items pi
    JOIN users u ON pi.user_id = u.id
    LEFT JOIN user_talents ut ON pi.talent_id = ut.id
    LEFT JOIN talent_categories tc ON ut.category_id = tc.id
    WHERE pi.id = ?
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: catalog.php');
    exit;
}

$item = $result->fetch_assoc();

// Update view count
$update_stmt = $conn->prepare("UPDATE portfolio_items SET views = views + 1 WHERE id = ?");
$update_stmt->bind_param("i", $item_id);
$update_stmt->execute();

// Get other portfolio items from the same user (excluding current item)
$other_items_stmt = $conn->prepare("
    SELECT id, title, file_type, thumbnail_url, file_url 
    FROM portfolio_items 
    WHERE user_id = ? AND id != ? 
    ORDER BY is_featured DESC, upload_date DESC 
    LIMIT 6
");
$other_items_stmt->bind_param("ii", $item['user_id'], $item_id);
$other_items_stmt->execute();
$other_items = $other_items_stmt->get_result();

// Function to get file size in human readable format
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['title']) ?> - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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
        
        .portfolio-detail {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .portfolio-header {
            padding: 25px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .portfolio-title {
            font-size: 2em;
            color: #333;
            margin-bottom: 15px;
        }
        
        .portfolio-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            color: #666;
        }
        
        .author-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .talent-tag {
            background: #007bff;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .portfolio-content {
            padding: 25px;
        }
        
        .media-container {
            margin-bottom: 25px;
            text-align: center;
        }
        
        .media-container img {
            max-width: 100%;
            max-height: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .media-container video {
            max-width: 100%;
            max-height: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .media-container audio {
            width: 100%;
            max-width: 500px;
        }
        
        .file-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .file-icon {
            font-size: 64px;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .download-btn {
            display: inline-block;
            padding: 12px 25px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
            margin-top: 10px;
        }
        
        .download-btn:hover {
            background: #218838;
        }
        
        .description-section {
            margin-bottom: 25px;
        }
        
        .description-section h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .description-text {
            color: #555;
            line-height: 1.8;
        }
        
        .stats-section {
            display: flex;
            gap: 30px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .other-works {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .other-works h3 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .other-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .other-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .other-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .other-item-thumb {
            width: 100%;
            height: 120px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #999;
        }
        
        .other-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .other-item-info {
            padding: 10px;
        }
        
        .other-item-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .other-item-type {
            font-size: 11px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .portfolio-meta {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .stats-section {
                justify-content: space-around;
            }
            
            .other-items-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <a href="view_profile.php?id=<?= $item['user_id'] ?>" class="back-button">← Back to <?= htmlspecialchars($item['full_name']) ?>'s Profile</a>
        
        <div class="portfolio-detail">
            <div class="portfolio-header">
                <h1 class="portfolio-title"><?= htmlspecialchars($item['title']) ?></h1>
                
                <div class="portfolio-meta">
                    <div class="author-info">
                        <img src="<?= htmlspecialchars($item['profile_picture_url'] ?: 'uploads/avatars/default-avatar.jpg') ?>" 
                             alt="<?= htmlspecialchars($item['full_name']) ?>" 
                             class="author-avatar">
                        <div>
                            <strong><?= htmlspecialchars($item['full_name']) ?></strong>
                            <div style="font-size: 12px;">@<?= htmlspecialchars($item['username']) ?></div>
                        </div>
                    </div>
                    
                    <?php if ($item['talent_title']): ?>
                        <span class="talent-tag"><?= htmlspecialchars($item['talent_title']) ?></span>
                    <?php endif; ?>
                    
                    <span>📅 <?= date('F j, Y', strtotime($item['upload_date'])) ?></span>
                    <span>👁️ <?= number_format($item['views']) ?> views</span>
                    <span>📁 <?= formatFileSize($item['file_size']) ?></span>
                </div>
            </div>
            
            <div class="portfolio-content">
                <!-- Media Display -->
                <div class="media-container">
                    <?php if ($item['file_type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($item['file_url']) ?>" 
                             alt="<?= htmlspecialchars($item['title']) ?>">
                    <?php elseif ($item['file_type'] === 'video'): ?>
                        <video controls>
                            <source src="<?= htmlspecialchars($item['file_url']) ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php elseif ($item['file_type'] === 'audio'): ?>
                        <audio controls>
                            <source src="<?= htmlspecialchars($item['file_url']) ?>" type="audio/mpeg">
                            Your browser does not support the audio tag.
                        </audio>
                    <?php else: ?>
                        <div class="file-info">
                            <div class="file-icon">
                                <?php
                                $icons = [
                                    'document' => '📄',
                                    'code' => '💻',
                                    'other' => '📎'
                                ];
                                echo $icons[$item['file_type']] ?? '📎';
                                ?>
                            </div>
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p>File Type: <?= ucfirst($item['file_type']) ?></p>
                            <p>Size: <?= formatFileSize($item['file_size']) ?></p>
                            <a href="<?= htmlspecialchars($item['file_url']) ?>" 
                               class="download-btn" 
                               download="<?= htmlspecialchars($item['title']) ?>">
                                📥 Download File
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Description -->
                <?php if ($item['description']): ?>
                    <div class="description-section">
                        <h3>Description</h3>
                        <div class="description-text">
                            <?= nl2br(htmlspecialchars($item['description'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="stats-section">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($item['views']) ?></div>
                        <div class="stat-label">Views</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= date('M j', strtotime($item['upload_date'])) ?></div>
                        <div class="stat-label">Uploaded</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= ucfirst($item['file_type']) ?></div>
                        <div class="stat-label">Type</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Other Works by Same User -->
        <?php if ($other_items->num_rows > 0): ?>
            <div class="other-works">
                <h3>More from <?= htmlspecialchars($item['full_name']) ?></h3>
                <div class="other-items-grid">
                    <?php while ($other_item = $other_items->fetch_assoc()): ?>
                        <a href="view_portfolio_item.php?id=<?= $other_item['id'] ?>" class="other-item">
                            <div class="other-item-thumb">
                                <?php if ($other_item['file_type'] === 'image' && $other_item['thumbnail_url']): ?>
                                    <img src="<?= htmlspecialchars($other_item['thumbnail_url']) ?>" 
                                         alt="<?= htmlspecialchars($other_item['title']) ?>">
                                <?php else: ?>
                                    <?php
                                    $icons = [
                                        'video' => '🎥',
                                        'audio' => '🎵',
                                        'document' => '📄',
                                        'code' => '💻',
                                        'other' => '📎'
                                    ];
                                    echo $icons[$other_item['file_type']] ?? '📎';
                                    ?>
                                <?php endif; ?>
                            </div>
                            <div class="other-item-info">
                                <div class="other-item-title"><?= htmlspecialchars($other_item['title']) ?></div>
                                <div class="other-item-type"><?= ucfirst($other_item['file_type']) ?></div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>