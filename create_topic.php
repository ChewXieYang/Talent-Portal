<?php
include 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=login_required');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Get all active forum categories
$categories_stmt = $conn->prepare("SELECT id, category_name FROM forum_categories WHERE is_active = 1 ORDER BY category_name");
$categories_stmt->execute();
$categories = $categories_stmt->get_result();

// Handle topic creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id']);
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    // Validate inputs
    if (empty($category_id) || empty($title) || empty($content)) {
        $message = 'All fields are required.';
        $messageType = 'error';
    } elseif (strlen($title) < 5) {
        $message = 'Topic title must be at least 5 characters long.';
        $messageType = 'error';
    } elseif (strlen($content) < 10) {
        $message = 'Topic content must be at least 10 characters long.';
        $messageType = 'error';
    } else {
        // Check if category exists and is active
        $check_category = $conn->prepare("SELECT id FROM forum_categories WHERE id = ? AND is_active = 1");
        $check_category->bind_param("i", $category_id);
        $check_category->execute();
        $result = $check_category->get_result();
        
        if ($result->num_rows === 0) {
            $message = 'Invalid category selected.';
            $messageType = 'error';
        } else {
            // Insert new topic
            $stmt = $conn->prepare("INSERT INTO forum_topics (user_id, category_id, title, content, created_at, last_reply_at, last_reply_user_id) VALUES (?, ?, ?, ?, NOW(), NOW(), ?)");
            $stmt->bind_param("iissi", $user_id, $category_id, $title, $content, $user_id);
            
            if ($stmt->execute()) {
                $topic_id = $conn->insert_id;
                
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description, ip_address) VALUES (?, ?, ?, ?)");
                $action_type = 'forum_topic_create';
                $action_desc = 'Created new topic: ' . $title;
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                $activity_stmt->bind_param("isss", $user_id, $action_type, $action_desc, $ip_address);
                $activity_stmt->execute();
                
                // Redirect to the new topic
                header('Location: forum_topic.php?id=' . $topic_id);
                exit;
            } else {
                $message = 'Error creating topic. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// Get user details for display
$user_stmt = $conn->prepare("SELECT full_name, username FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Topic - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .header p {
            margin: 0;
            color: #666;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #005eff;
            box-shadow: 0 0 5px rgba(0, 94, 255, 0.3);
        }
        
        .btn {
            background: #005eff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #0051cc;
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #005eff;
        }
        
        .char-count {
            font-size: 12px;
            color: #666;
            text-align: right;
            margin-top: 5px;
        }
        
        .guidelines {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .guidelines h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .guidelines ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .guidelines li {
            margin-bottom: 5px;
            color: #856404;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .btn-secondary {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1>Create New Topic</h1>
            <p>Start a new discussion in the community forum</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="user-info">
                <strong>Posting as:</strong> <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['username']) ?>)
            </div>
            
            <div class="guidelines">
                <h4>Community Guidelines</h4>
                <ul>
                    <li>Be respectful and constructive in your discussions</li>
                    <li>Keep topics relevant to the selected category</li>
                    <li>Use clear, descriptive titles for better visibility</li>
                    <li>Provide detailed content to encourage meaningful responses</li>
                    <li>Search existing topics before creating new ones</li>
                </ul>
            </div>
            
            <form method="POST" action="create_topic.php">
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select a category...</option>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['category_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="title">Topic Title *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           required 
                           maxlength="200"
                           placeholder="Enter a descriptive title for your topic..."
                           value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
                           oninput="updateCharCount('title', 'title-count', 200)">
                    <div id="title-count" class="char-count">0/200 characters</div>
                </div>
                
                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea id="content" 
                              name="content" 
                              required 
                              placeholder="Write your topic content here. Be detailed and specific to encourage good discussions..."
                              oninput="updateCharCount('content', 'content-count', 5000)"><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>
                    <div id="content-count" class="char-count">0/5000 characters</div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn">Create Topic</button>
                    <a href="forum.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function updateCharCount(inputId, countId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(countId);
            const currentLength = input.value.length;
            
            counter.textContent = currentLength + '/' + maxLength + ' characters';
            
            if (currentLength > maxLength * 0.9) {
                counter.style.color = '#dc3545';
            } else if (currentLength > maxLength * 0.8) {
                counter.style.color = '#ffc107';
            } else {
                counter.style.color = '#666';
            }
        }
        
        // Initialize character counts on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCharCount('title', 'title-count', 200);
            updateCharCount('content', 'content-count', 5000);
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();
            const category = document.getElementById('category_id').value;
            
            if (!category) {
                alert('Please select a category.');
                e.preventDefault();
                return;
            }
            
            if (title.length < 5) {
                alert('Topic title must be at least 5 characters long.');
                e.preventDefault();
                return;
            }
            
            if (content.length < 10) {
                alert('Topic content must be at least 10 characters long.');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>