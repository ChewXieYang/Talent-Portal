<?php
include 'includes/db.php';

// Handle post creation
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    if (!isset($_SESSION['user_id'])) {
        $message = 'Please login to post.';
        $messageType = 'error';
    } else {
        $user_id = $_SESSION['user_id'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $post_type = $_POST['post_type'];
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        
        if (empty($title) || empty($content)) {
            $message = 'Title and content are required.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO message_board_posts (user_id, title, content, post_type, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $title, $content, $post_type, $expires_at);
            
            if ($stmt->execute()) {
                $message = 'Post created successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error creating post.';
                $messageType = 'error';
            }
        }
    }
}

// Get filter parameters
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build query for posts
$sql = "SELECT mbp.*, u.full_name, u.username, u.profile_picture_url,
               (SELECT COUNT(*) FROM message_board_comments WHERE post_id = mbp.id) as comment_count
        FROM message_board_posts mbp
        JOIN users u ON mbp.user_id = u.id
        WHERE (mbp.expires_at IS NULL OR mbp.expires_at > NOW())";

$params = [];
$types = "";

if (!empty($filter_type)) {
    $sql .= " AND mbp.post_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (mbp.title LIKE ? OR mbp.content LIKE ?)";
    $search_term = '%' . $search_query . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$sql .= " ORDER BY mbp.is_featured DESC, mbp.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$posts = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Board - MMU Talent Showcase</title>
    <link rel="stylesheet" href="css/message_board.css">
    <link rel="stylesheet" href="css/sidebar.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
        <?php include 'includes/header.php'; ?>
            <div class="container">
                <div class="page-header">
                    <h1>Message Board</h1>
                    <p>Share opportunities, collaborate, and connect with the community</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <!-- Search and Filter Controls -->
                <div class="board-controls">
                    <div class="controls-row">
                        <form method="GET" class="search-form">
                            <input type="text" name="q" placeholder="Search posts..." 
                                value="<?= htmlspecialchars($search_query) ?>">
                            
                            <select name="type">
                                <option value="">All Types</option>
                                <option value="general" <?= $filter_type === 'general' ? 'selected' : '' ?>>General</option>
                                <option value="collaboration" <?= $filter_type === 'collaboration' ? 'selected' : '' ?>>Collaboration</option>
                                <option value="opportunity" <?= $filter_type === 'opportunity' ? 'selected' : '' ?>>Opportunity</option>
                                <option value="event" <?= $filter_type === 'event' ? 'selected' : '' ?>>Event</option>
                            </select>
                            
                            <button type="submit" class="btn">Search</button>
                        </form>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="btn" onclick="toggleQuickPost()">Quick Post</button>
                        <?php else: ?>
                            <a href="login.php" class="btn">Login to Post</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Post Form (Hidden by default) -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="quick-post" id="quickPostForm" style="display: none;">
                    <h3>Create a Post</h3>
                    <form method="POST">
                        <input type="hidden" name="create_post" value="1">
                        
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required 
                                placeholder="What's your post about?">
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea id="content" name="content" required 
                                    placeholder="Share your thoughts, opportunities, or ideas..."></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="post_type">Post Type</label>
                                <select id="post_type" name="post_type">
                                    <option value="general">General Discussion</option>
                                    <option value="collaboration">Looking for Collaboration</option>
                                    <option value="opportunity">Opportunity/Job</option>
                                    <option value="event">Event Announcement</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="expires_at">Expires (Optional)</label>
                                <input type="datetime-local" id="expires_at" name="expires_at">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn">Post</button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Posts Display -->
                <div class="posts-container">
                    <?php if ($posts->num_rows > 0): ?>
                        <?php while ($post = $posts->fetch_assoc()): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="post-meta">
                                    <img src="<?= htmlspecialchars($post['profile_picture_url'] ?: 'uploads/avatars/default-avatar.jpg') ?>" 
                                        alt="<?= htmlspecialchars($post['full_name']) ?>" 
                                        class="user-avatar">
                                    
                                    <div class="user-info">
                                        <div class="user-name"><?= htmlspecialchars($post['full_name']) ?></div>
                                        <div class="post-date"><?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></div>
                                    </div>
                                    
                                    <span class="post-type type-<?= $post['post_type'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $post['post_type'])) ?>
                                    </span>
                                    
                                    <?php if ($post['is_featured']): ?>
                                        <span class="post-type" style="background: #ff6b35; color: white;">Featured</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h2 class="post-title"><?= htmlspecialchars($post['title']) ?></h2>
                            </div>
                            
                            <div class="post-content">
                                <div class="post-text">
                                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                                </div>
                                
                                <div class="post-footer">
                                    <div class="post-stats">
                                        <span>💬 <?= $post['comment_count'] ?> comments</span>
                                        <?php if ($post['expires_at']): ?>
                                            <span>⏰ Expires <?= date('M j, Y', strtotime($post['expires_at'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="post-actions">
                                        <a href="view_post.php?id=<?= $post['id'] ?>">View Details</a>
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                                            <a href="edit_post.php?id=<?= $post['id'] ?>">Edit</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>No posts found</h3>
                            <?php if (!empty($search_query) || !empty($filter_type)): ?>
                                <p>No posts match your search criteria. <a href="message_board.php">View all posts</a></p>
                            <?php else: ?>
                                <p>Be the first to share something with the community!</p>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn" onclick="toggleQuickPost()">Create First Post</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function toggleQuickPost() {
            const form = document.getElementById('quickPostForm');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
                form.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('title').focus();
            } else {
                form.style.display = 'none';
            }
        }

        <?php if ($messageType === 'success'): ?>
            const form = document.getElementById('quickPostForm');
            if (form) {
                form.style.display = 'none';
            }
        <?php endif; ?>
    </script>
</body>
</html>