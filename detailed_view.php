<?php
include 'includes/db.php';

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($post_id <= 0) {
    die("Invalid post ID.");
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && isset($_SESSION['user_id'])) {
    $comment_content = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($comment_content)) {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $user_id, $comment_content);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to avoid resubmission
        header("Location: detailed_view.php?id=$post_id#comment-form");
        exit;
    }
}

// Get post details
$stmt = $conn->prepare("
    SELECT p.*, u.full_name, u.profile_picture_url 
    FROM talent_uploads p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ? LIMIT 1
");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

// Get comments
$comments = [];
$stmt = $conn->prepare("
    SELECT c.*, u.full_name, u.profile_picture_url 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.post_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}
$stmt->close();

if (!$post) {
    die("Post not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?> - Detailed View</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/detailed_view.css">
</head>
<body>

<div class="container">
    <a href="student_dashboard.php" class="back-button">← Back to Dashboard</a>

    <div class="user-info">
        <img src="<?= htmlspecialchars($post['profile_picture_url']) ?>" alt="Profile">
        <strong><?= htmlspecialchars($post['full_name']) ?></strong>
    </div>

    <h2><?= htmlspecialchars($post['title']) ?></h2>
    <img src="<?= htmlspecialchars($post['file_url']) ?>" alt="Post Image" class="post-image">
    <div class="description"><?= nl2br(htmlspecialchars($post['description'])) ?></div>
    <div class="meta">Uploaded on <?= date("F j, Y", strtotime($post['upload_date'])) ?> | ID: <?= $post['id'] ?></div>
    <hr>
    <h3 id="comment-form">Comments (<?= count($comments) ?>)</h3>
    <?php if (count($comments) > 0): ?>
        <?php foreach ($comments as $comment): ?>
            <div class="detailed-view-comment">
                <div class="user-info">
                    <img src="<?= htmlspecialchars($comment['profile_picture_url']) ?>" alt="User">
                    <strong><?= htmlspecialchars($comment['full_name']) ?></strong>
                </div>
                <div><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
                <div class="meta"><?= date("F j, Y H:i", strtotime($comment['created_at'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No comments yet.</p>
    <?php endif; ?>
    <hr>
    <?php if (isset($_SESSION['user_id'])): ?>
        <h3>Leave a Comment</h3>
        <form method="post" action="#comment-form" id="comment-form" class="detailed-view-comment-form">
            <textarea name="comment" rows="4" class="detailed-view-comment-textarea" placeholder="Write your comment here..." required></textarea>
            <button type="submit" class="detailed-view-comment-btn">Post Comment</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">Log in</a> to post a comment.</p>
    <?php endif; ?>
</div>

</body>
</html>
