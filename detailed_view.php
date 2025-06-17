<?php
include 'includes/db.php';

$imageData = null;
$userData = null;
$comments = [];

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Fetch image + uploader info
    $stmt = $conn->prepare("
        SELECT t.*, u.full_name, u.profile_picture_url 
        FROM talent_uploads t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ? AND t.file_type = 'image'
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $imageData = $result->fetch_assoc();
    }
    $stmt->close();

    // Fetch comments
    $cstmt = $conn->prepare("
        SELECT c.content, c.created_at, u.full_name, u.profile_picture_url 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.image_id = ? 
        ORDER BY c.created_at DESC
    ");
    $cstmt->bind_param("i", $id);
    $cstmt->execute();
    $cResult = $cstmt->get_result();
    while ($row = $cResult->fetch_assoc()) {
        $comments[] = $row;
    }
    $cstmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $imageData ? htmlspecialchars($imageData['title']) : "Image Not Found" ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #dae0e6; margin: 0; padding: 0; }
        .container { width: 640px; margin: 40px auto; padding: 20px; }
        .card { background: white; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 15px; margin-bottom: 20px; }
        img.post-image { max-width: 100%; border-radius: 4px; }
        h2 { margin: 10px 0; font-size: 22px; }
        .meta { font-size: 12px; color: #888; margin-top: 10px; }
        .user-info { display: flex; align-items: center; margin-bottom: 10px; }
        .user-info img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; }
        .comment { border-top: 1px solid #ccc; padding: 10px 0; }
        .comment:first-child { border-top: none; }
        .comment-user { font-weight: bold; margin-bottom: 5px; }
        .comment-meta { font-size: 11px; color: #777; margin-top: 4px; }
        .not-found { text-align: center; color: #999; font-size: 18px; margin-top: 50px; }
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
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
</head>

<body>
<?php include 'includes/header.php'; ?>
<div class="profile-container">
    <div class="container">
        <a href="catalog.php" class="back-button">← Back to Catalog</a>

        <?php if ($imageData): ?>
            <div class="card">
                <div class="user-info">
                    <img src="<?= htmlspecialchars($imageData['profile_picture_url']) ?>" alt="User">
                    <strong><?= htmlspecialchars($imageData['full_name']) ?></strong>
                </div>
                <h2><?= htmlspecialchars($imageData['title']) ?></h2>
                <img src="<?= htmlspecialchars($imageData['file_url']) ?>" alt="Image" class="post-image">
                <p><?= nl2br(htmlspecialchars($imageData['description'])) ?></p>
                <div class="meta">Uploaded on <?= date("F j, Y", strtotime($imageData['upload_date'])) ?></div>
            </div>

            <!-- Comment Section -->
            <div class="card">
                <h3>Comments (<?= count($comments) ?>)</h3>
                <?php if (count($comments) > 0): ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="user-info">
                                <img src="<?= htmlspecialchars($comment['profile_picture_url']) ?>" alt="User">
                                <div>
                                    <div class="comment-user"><?= htmlspecialchars($comment['full_name']) ?></div>
                                    <div><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
                                    <div class="comment-meta"><?= date("F j, Y H:i", strtotime($comment['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No comments yet.</p>
                <?php endif; ?>

                <!-- Comment Form (if logged in) -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="post_comment.php" method="post" style="margin-top:20px;">
                        <textarea name="content" rows="3" style="width:100%; padding:10px;" placeholder="Write a comment..." required></textarea>
                        <input type="hidden" name="image_id" value="<?= $id ?>">
                        <button type="submit" style="margin-top:10px; padding:10px 20px; background:#0079d3; color:white; border:none; border-radius:4px;">Post Comment</button>
                    </form>
                <?php else: ?>
                    <p><a href="login.php">Log in</a> to post a comment.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="not-found">Image not found or invalid ID.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

