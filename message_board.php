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
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .board-controls {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .controls-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            flex: 1;
            min-width: 300px;
        }
        
        .search-form input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-form select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #005eff;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #0044cc;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .posts-container {
            display: grid;
            gap: 20px;
        }
        
        .post-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .post-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .post-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
        }
        
        .post-date {
            color: #666;
            font-size: 12px;
        }
        
        .post-type {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }
        
        .type-general {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .type-collaboration {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .type-opportunity {
            background: #fff3e0;
            color: #ef6c00;
        }
        
        .type-event {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .post-title {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .post-content {
            padding: 0 20px 20px 20px;
        }
        
        .post-text {
            color: #555;
            line-height: 1.7;
            margin-bottom: 15px;
        }
        
        .post-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            color: #666;
            font-size: 14px;
        }
        
        .post-stats {
            display: flex;
            gap: 15px;
        }
        
        .post-actions {
            display: flex;
            gap: 10px;
        }
        
        .post-actions a {
            color: #005eff;
            text-decoration: none;
            font-size: 12px;
        }
        
        .quick-post {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 15px;
            align-items: end;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            color: #666;
        }
        
        @media (max-width: 768px) {
            .controls-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-form {
                min-width: auto;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .post-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .post-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
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
        
        // Auto-hide quick post form after successful submission
        <?php if ($messageType === 'success'): ?>
            const form = document.getElementById('quickPostForm');
            if (form) {
                form.style.display = 'none';
            }
        <?php endif; ?>
    </script>
</body>
</html>