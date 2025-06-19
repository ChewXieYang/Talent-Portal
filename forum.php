<?php
include 'includes/db.php';

// Get all forum categories with latest topic info
$categories_stmt = $conn->prepare("
    SELECT 
        fc.*,
        COUNT(ft.id) as topic_count,
        SUM(ft.reply_count) as total_replies,
        MAX(ft.last_reply_at) as last_activity,
        (SELECT CONCAT(u.full_name, ' - ', ft2.title) 
         FROM forum_topics ft2 
         JOIN users u ON ft2.last_reply_user_id = u.id 
         WHERE ft2.category_id = fc.id 
         ORDER BY ft2.last_reply_at DESC LIMIT 1) as last_post_info
    FROM forum_categories fc
    LEFT JOIN forum_topics ft ON fc.id = ft.category_id
    WHERE fc.is_active = 1
    GROUP BY fc.id
    ORDER BY fc.id
");
$categories_stmt->execute();
$categories = $categories_stmt->get_result();

// Get recent topics across all categories
$recent_topics_stmt = $conn->prepare("
    SELECT ft.*, fc.category_name, u.full_name, u.username
    FROM forum_topics ft
    JOIN forum_categories fc ON ft.category_id = fc.id
    JOIN users u ON ft.user_id = u.id
    WHERE fc.is_active = 1
    ORDER BY ft.created_at DESC
    LIMIT 10
");
$recent_topics_stmt->execute();
$recent_topics = $recent_topics_stmt->get_result();

// Get forum statistics
$stats_stmt = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM forum_topics) as total_topics,
        (SELECT COUNT(*) FROM forum_replies) as total_replies,
        (SELECT COUNT(DISTINCT user_id) FROM (
            SELECT user_id FROM forum_topics 
            UNION 
            SELECT user_id FROM forum_replies
        ) AS combined_users) as active_users
");
$stats = $stats_stmt->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/forum.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <title>Community Forum - MMU Talent Showcase</title>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="container">
                <div class="breadcrumb">
                    <a href="index.php">Home</a> > Forum
                </div>

                <div class="forum-header">
                    <h1>Community Forum</h1>
                    <p>Connect, collaborate, and share with the MMU talent community</p>

                    <div class="forum-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?= number_format($stats['total_topics']) ?></span>
                            <span class="stat-label">Topics</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= number_format($stats['total_replies']) ?></span>
                            <span class="stat-label">Replies</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= number_format($stats['active_users']) ?></span>
                            <span class="stat-label">Active Users</span>
                        </div>
                    </div>
                </div>

                <div class="forum-actions">
                    <h2 style="margin: 0;">Forum Categories</h2>
                    <div>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="create_topic.php" class="btn">Start New Topic</a>
                            <a href="messages.php" class="btn btn-secondary">My Messages</a>
                        <?php else: ?>
                            <a href="login.php" class="btn">Login to Post</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="categories-section">
                    <ul class="category-list">
                        <?php while ($category = $categories->fetch_assoc()): ?>
                        <li class="category-item">
                            <div class="category-info">
                                <div class="category-name">
                                    <a href="forum_category.php?id=<?= $category['id'] ?>">
                                        <?= htmlspecialchars($category['category_name']) ?>
                                    </a>
                                </div>
                                <div class="category-description">
                                    <?= htmlspecialchars($category['description']) ?>
                                </div>
                            </div>

                            <div class="category-stats">
                                <strong><?= number_format($category['topic_count']) ?></strong>
                                Topics
                                <br>
                                <strong><?= number_format($category['total_replies'] ?: 0) ?></strong>
                                Replies
                            </div>

                            <div class="category-last-post">
                                <?php if ($category['last_activity']): ?>
                                    <?= htmlspecialchars($category['last_post_info']) ?><br>
                                    <?= date('M j, Y g:i A', strtotime($category['last_activity'])) ?>
                                <?php else: ?>
                                    No posts yet
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="recent-topics">
                    <div class="section-header">
                        <h3 class="section-title">Recent Topics</h3>
                    </div>

                    <ul class="topic-list">
                        <?php if ($recent_topics->num_rows > 0): ?>
                            <?php while ($topic = $recent_topics->fetch_assoc()): ?>
                            <li class="topic-item">
                                <div class="topic-info">
                                    <div class="topic-title">
                                        <a href="forum_topic.php?id=<?= $topic['id'] ?>">
                                            <?= htmlspecialchars($topic['title']) ?>
                                        </a>
                                    </div>
                                    <div class="topic-meta">
                                        in <strong><?= htmlspecialchars($topic['category_name']) ?></strong> 
                                        by <strong><?= htmlspecialchars($topic['full_name']) ?></strong>
                                        • <?= date('M j, Y g:i A', strtotime($topic['created_at'])) ?>
                                    </div>
                                </div>

                                <div class="topic-replies">
                                    <strong><?= $topic['reply_count'] ?></strong><br>replies
                                </div>
                            </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="topic-item">
                                <div class="topic-info">
                                    <p style="text-align: center; color: #666; margin: 20px 0;">
                                        No topics yet. Be the first to start a discussion!
                                    </p>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>