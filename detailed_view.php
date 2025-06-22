<?php
include 'includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid post ID.");
}

$post_id = intval($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && isset($_SESSION['user_id'])) {
    $comment = trim($_POST['comment']);
    if ($comment !== '') {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $_SESSION['user_id'], $comment);
        $stmt->execute();
        $stmt->close();

        // Redirect to prevent form resubmission
        header("Location: detailed_view.php?id=" . $post_id . "#comment-form");
        exit;
    }
}


$stmt = $conn->prepare("
    SELECT t.*, u.full_name, u.profile_picture_url 
    FROM talent_uploads t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ? AND t.file_type = 'image'
");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();
$comments = [];
$stmt = $conn->prepare("
    SELECT c.content, c.created_at, u.full_name, u.profile_picture_url
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
            <div style="margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
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
        <form method="post" action="#comment-form" id="comment-form">
            <textarea name="comment" rows="4" style="width:100%; padding:8px;" placeholder="Write your comment here..." required></textarea>
            <button type="submit" style="margin-top: 10px; padding: 8px 16px; background: #0079d3; color: white; border: none; border-radius: 4px;">Post Comment</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">Log in</a> to post a comment.</p>
    <?php endif; ?>


</div>

</body>
</html>
