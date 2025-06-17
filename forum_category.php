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
    <title><?= htmlspecialchars($topic['title']) ?> - MMU Talent Showcase Forum</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .breadcrumb {
            background: white;
            padding: 15px 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .breadcrumb a {
            color: #005eff;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .topic-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .topic-title {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .topic-badges {
            display: flex;
            gap: 10px;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .badge.pinned {
            background: #28a745;
            color: white;
        }
        
        .badge.locked {
            background: #dc3545;
            color: white;
        }
        
        .topic-meta {
            color: #666;
            margin-bottom: 20px;
        }
        
        .topic-stats {
            display: flex;
            gap: 30px;
            color: #666;
            font-size: 0.9em;
        }
        
        .post {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .post-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .post-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .post-author img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .post-author-info h4 {
            margin: 0;
            color: #333;
        }
        
        .post-author-info small {
            color: #666;
        }
        
        .post-date {
            color: #666;
            font-size: 0.9em;
        }
        
        .post-content {
            padding: 20px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .reply-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .reply-form h3 {
            margin-top: 0;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            font-family: inherit;
            min-height: 150px;
            resize: vertical;
            box-sizing: border-box;
        }
        
        .btn {
            background: #005eff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #0046cc;
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin: 30px 0;
            gap: 10px;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 15px;
            background: white;
            border: 1px solid #dee2e6;
            text-decoration: none;
            color: #333;
            border-radius: 5px;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
        }
        
        .pagination .current {
            background: #005eff;
            color: white;
            border-color: #005eff;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .locked-notice {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #ffeaa7;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .topic-header,
            .post,
            .reply-form {
                padding: 20px;
            }
            
            .topic-stats {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
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
</body>
</html>