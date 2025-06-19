<?php
include 'includes/db.php';

// Get topic ID from URL
$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($topic_id === 0) {
    header('Location: forum.php');
    exit;
}

// Get topic information with category and user details
$topic_stmt = $conn->prepare("
    SELECT 
        ft.*,
        fc.category_name,
        fc.id as category_id,
        u.full_name,
        u.username,
        u.profile_picture_url
    FROM forum_topics ft
    JOIN forum_categories fc ON ft.category_id = fc.id
    JOIN users u ON ft.user_id = u.id
    WHERE ft.id = ? AND fc.is_active = 1
");
$topic_stmt->bind_param("i", $topic_id);
$topic_stmt->execute();
$topic_result = $topic_stmt->get_result();

if ($topic_result->num_rows === 0) {
    header('Location: forum.php');
    exit;
}

$topic = $topic_result->fetch_assoc();

// Update view count
$conn->query("UPDATE forum_topics SET view_count = view_count + 1 WHERE id = $topic_id");

// Handle reply submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content'])) {
    if (!isset($_SESSION['user_id'])) {
        $message = 'You must be logged in to reply.';
        $messageType = 'error';
    } elseif ($topic['is_locked']) {
        $message = 'This topic is locked and cannot be replied to.';
        $messageType = 'error';
    } else {
        $user_id = $_SESSION['user_id'];
        $reply_content = trim($_POST['reply_content']);
        
        if (empty($reply_content)) {
            $message = 'Reply content is required.';
            $messageType = 'error';
        } elseif (strlen($reply_content) < 5) {
            $message = 'Reply must be at least 5 characters long.';
            $messageType = 'error';
        } else {
            // Insert reply
            $reply_stmt = $conn->prepare("INSERT INTO forum_replies (topic_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $reply_stmt->bind_param("iis", $topic_id, $user_id, $reply_content);
            
            if ($reply_stmt->execute()) {
                // Update topic reply count and last reply info
                $update_topic = $conn->prepare("
                    UPDATE forum_topics 
                    SET reply_count = reply_count + 1, 
                        last_reply_at = NOW(), 
                        last_reply_user_id = ? 
                    WHERE id = ?
                ");
                $update_topic->bind_param("ii", $user_id, $topic_id);
                $update_topic->execute();
                
                $message = 'Your reply has been posted successfully!';
                $messageType = 'success';
                
                // Redirect to prevent resubmission
                header("Location: forum_topic.php?id=$topic_id#latest");
                exit;
            } else {
                $message = 'Error posting reply. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// Pagination for replies
$replies_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $replies_per_page;

// Get total replies count
$replies_count = $topic['reply_count'];
$total_pages = ceil($replies_count / $replies_per_page);

// Get replies with user information
$replies_stmt = $conn->prepare("
    SELECT 
        fr.*,
        u.full_name,
        u.username,
        u.profile_picture_url,
        u.created_at as user_joined
    FROM forum_replies fr
    JOIN users u ON fr.user_id = u.id
    WHERE fr.topic_id = ?
    ORDER BY fr.created_at ASC
    LIMIT ? OFFSET ?
");
$replies_stmt->bind_param("iii", $topic_id, $replies_per_page, $offset);
$replies_stmt->execute();
$replies = $replies_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/forum.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <title><?= htmlspecialchars($topic['title']) ?> - MMU Talent Showcase Forum</title>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="container">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="index.php">Home</a> › 
                    <a href="forum.php">Forum</a> › 
                    <a href="forum_category.php?id=<?= $topic['category_id'] ?>"><?= htmlspecialchars($topic['category_name']) ?></a> › 
                    <?= htmlspecialchars($topic['title']) ?>
                </div>

                <!-- Topic Header -->
                <div class="topic-header">
                    <div class="topic-title">
                        <?= htmlspecialchars($topic['title']) ?>
                        <div class="topic-badges">
                            <?php if ($topic['is_pinned']): ?>
                                <span class="badge pinned">📌 Pinned</span>
                            <?php endif; ?>
                            <?php if ($topic['is_locked']): ?>
                                <span class="badge locked">🔒 Locked</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="topic-meta">
                        Started by <strong><?= htmlspecialchars($topic['full_name']) ?></strong> in 
                        <strong><a href="forum_category.php?id=<?= $topic['category_id'] ?>"><?= htmlspecialchars($topic['category_name']) ?></a></strong>
                        • <?= date('M j, Y g:i A', strtotime($topic['created_at'])) ?>
                    </div>

                    <div class="topic-stats">
                        <span><strong><?= number_format($topic['reply_count']) ?></strong> replies</span>
                        <span><strong><?= number_format($topic['view_count']) ?></strong> views</span>
                        <span>Last reply: <?= date('M j, Y g:i A', strtotime($topic['last_reply_at'])) ?></span>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <!-- Original Post -->
                <div class="post">
                    <div class="post-header">
                        <div class="post-author">
                            <img src="<?= !empty($topic['profile_picture_url']) ? htmlspecialchars($topic['profile_picture_url']) : 'assets/images/default-avatar.png' ?>" 
                                 alt="<?= htmlspecialchars($topic['full_name']) ?>">
                            <div class="post-author-info">
                                <h4><?= htmlspecialchars($topic['full_name']) ?></h4>
                                <small>@<?= htmlspecialchars($topic['username']) ?></small>
                            </div>
                        </div>
                        <div class="post-date">
                            <?= date('M j, Y g:i A', strtotime($topic['created_at'])) ?>
                        </div>
                    </div>
                    <div class="post-content">
                        <?= nl2br(htmlspecialchars($topic['content'])) ?>
                    </div>
                </div>

                <!-- Replies -->
                <?php if ($replies->num_rows > 0): ?>
                    <?php while ($reply = $replies->fetch_assoc()): ?>
                    <div class="post" id="reply-<?= $reply['id'] ?>">
                        <div class="post-header">
                            <div class="post-author">
                                <img src="<?= !empty($reply['profile_picture_url']) ? htmlspecialchars($reply['profile_picture_url']) : 'assets/images/default-avatar.png' ?>" 
                                     alt="<?= htmlspecialchars($reply['full_name']) ?>">
                                <div class="post-author-info">
                                    <h4><?= htmlspecialchars($reply['full_name']) ?></h4>
                                    <small>@<?= htmlspecialchars($reply['username']) ?></small>
                                </div>
                            </div>
                            <div class="post-date">
                                <?= date('M j, Y g:i A', strtotime($reply['created_at'])) ?>
                            </div>
                        </div>
                        <div class="post-content">
                            <?= nl2br(htmlspecialchars($reply['content'])) ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>

                <!-- Pagination for replies -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?= $topic_id ?>&page=<?= $page - 1 ?>">← Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?id=<?= $topic_id ?>&page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?id=<?= $topic_id ?>&page=<?= $page + 1 ?>">Next →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Reply Form -->
                <?php if ($topic['is_locked']): ?>
                    <div class="locked-notice">
                        🔒 This topic has been locked and is no longer accepting new replies.
                    </div>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <div class="reply-form" id="latest">
                        <h3>Post a Reply</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label for="reply_content">Your Reply *</label>
                                <textarea id="reply_content" 
                                          name="reply_content" 
                                          required 
                                          placeholder="Write your reply here..."></textarea>
                            </div>

                            <button type="submit" class="btn">Post Reply</button>
                            <a href="forum_category.php?id=<?= $topic['category_id'] ?>" class="btn btn-secondary">Back to Category</a>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="reply-form">
                        <h3>Join the Discussion</h3>
                        <p>You must be logged in to reply to this topic.</p>
                        <a href="login.php" class="btn">Login</a>
                        <a href="register.php" class="btn btn-secondary">Register</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>