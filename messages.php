<?php
include 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = intval($_POST['recipient_id']);
    $subject = trim($_POST['subject']);
    $content = trim($_POST['content']);
    
    if (empty($subject) || empty($content) || $recipient_id <= 0) {
        $message = 'All fields are required.';
        $messageType = 'error';
    } else if ($recipient_id == $user_id) {
        $message = 'You cannot send a message to yourself.';
        $messageType = 'error';
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
        $check_stmt->bind_param("i", $recipient_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, recipient_id, subject, content) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $user_id, $recipient_id, $subject, $content);
            
            if ($stmt->execute()) {
                $message = 'Message sent successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error sending message.';
                $messageType = 'error';
            }
        } else {
            $message = 'Recipient not found or inactive.';
            $messageType = 'error';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $msg_id = intval($_POST['message_id']);
    $action = $_POST['action'];
    
    if ($action === 'mark_read') {
        $stmt = $conn->prepare("UPDATE private_messages SET is_read = 1, read_at = NOW() WHERE id = ? AND recipient_id = ?");
        $stmt->bind_param("ii", $msg_id, $user_id);
        $stmt->execute();
    } elseif ($action === 'delete_received') {
        $stmt = $conn->prepare("UPDATE private_messages SET is_deleted_by_recipient = 1 WHERE id = ? AND recipient_id = ?");
        $stmt->bind_param("ii", $msg_id, $user_id);
        $stmt->execute();
    } elseif ($action === 'delete_sent') {
        $stmt = $conn->prepare("UPDATE private_messages SET is_deleted_by_sender = 1 WHERE id = ? AND sender_id = ?");
        $stmt->bind_param("ii", $msg_id, $user_id);
        $stmt->execute();
    }
}

$view = isset($_GET['view']) ? $_GET['view'] : 'inbox';
$compose = isset($_GET['compose']) ? true : false;
$reply_to = isset($_GET['reply']) ? intval($_GET['reply']) : 0;

if ($view === 'sent') {
    $messages_stmt = $conn->prepare("
        SELECT pm.*, u.full_name, u.username, u.profile_picture_url
        FROM private_messages pm
        JOIN users u ON pm.recipient_id = u.id
        WHERE pm.sender_id = ? AND pm.is_deleted_by_sender = 0
        ORDER BY pm.created_at DESC
    ");
    $messages_stmt->bind_param("i", $user_id);
} else { // inbox
    $messages_stmt = $conn->prepare("
        SELECT pm.*, u.full_name, u.username, u.profile_picture_url
        FROM private_messages pm
        JOIN users u ON pm.sender_id = u.id
        WHERE pm.recipient_id = ? AND pm.is_deleted_by_recipient = 0
        ORDER BY pm.is_read ASC, pm.created_at DESC
    ");
    $messages_stmt->bind_param("i", $user_id);
}

$messages_stmt->execute();
$messages = $messages_stmt->get_result();

$reply_message = null;
if ($reply_to > 0) {
    $reply_stmt = $conn->prepare("
        SELECT pm.*, u.full_name, u.username
        FROM private_messages pm
        JOIN users u ON pm.sender_id = u.id
        WHERE pm.id = ? AND pm.recipient_id = ?
    ");
    $reply_stmt->bind_param("ii", $reply_to, $user_id);
    $reply_stmt->execute();
    $reply_message = $reply_stmt->get_result()->fetch_assoc();
}

$unread_stmt = $conn->prepare("SELECT COUNT(*) as count FROM private_messages WHERE recipient_id = ? AND is_read = 0 AND is_deleted_by_recipient = 0");
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['count'];

$users_stmt = $conn->prepare("SELECT id, full_name, username FROM users WHERE id != ? AND status = 'active' ORDER BY full_name");
$users_stmt->bind_param("i", $user_id);
$users_stmt->execute();
$users = $users_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages-MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .messages-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            min-height: 600px;
        }
        
        .sidebar {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 0;
            height: fit-content;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            border-bottom: 1px solid #f0f0f0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #f8f9fa;
            color: #005eff;
        }
        
        .sidebar-menu .unread-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            float: right;
        }
        
        .main-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .content-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .message {
            padding: 15px 20px;
            margin: 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .messages-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .message-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .message-item:hover {
            background: #f8f9fa;
        }
        
        .message-item.unread {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .message-content {
            flex: 1;
        }
        
        .message-sender {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .message-subject {
            color: #555;
            margin-bottom: 5px;
        }
        
        .message-preview {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .message-date {
            color: #999;
            font-size: 12px;
        }
        
        .message-actions {
            display: flex;
            gap: 5px;
        }
        
        .message-actions button {
            background: transparent;
            border: 1px solid #ddd;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .compose-form {
            padding: 20px;
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
            min-height: 150px;
            resize: vertical;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .message-detail {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .detail-subject {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .detail-meta {
            color: #666;
            font-size: 14px;
        }
        
        .detail-content {
            color: #555;
            line-height: 1.7;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .messages-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: 2;
            }
            
            .main-content {
                order: 1;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="messages-layout">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <h3>Messages</h3>
                </div>
                
                <ul class="sidebar-menu">
                    <li>
                        <a href="messages.php?view=inbox" class="<?= $view === 'inbox' ? 'active' : '' ?>">
                            Inbox
                            <?php if ($unread_count > 0): ?>
                                <span class="unread-badge"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="messages.php?view=sent" class="<?= $view === 'sent' ? 'active' : '' ?>">
                            Sent
                        </a>
                    </li>
                    <li>
                        <a href="messages.php?compose=1">
                            Compose
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="main-content">
                <div class="content-header">
                    <h2>
                        <?php if ($compose || $reply_to): ?>
                            Compose Message
                        <?php else: ?>
                            <?= ucfirst($view) ?>
                        <?php endif; ?>
                    </h2>
                    
                    <?php if (!$compose && !$reply_to): ?>
                        <a href="messages.php?compose=1" class="btn">New Message</a>
                    <?php endif; ?>
                </div>
                
                <?php if ($message): ?>
                    <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($compose || $reply_to): ?>
                    <!-- Compose Form -->
                    <div class="compose-form">
                        <form method="POST">
                            <input type="hidden" name="send_message" value="1">
                            
                            <div class="form-group">
                                <label for="recipient_id">To:</label>
                                <select id="recipient_id" name="recipient_id" required>
                                    <option value="">Select recipient...</option>
                                    <?php 
                                    $users->data_seek(0);
                                    while ($user = $users->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $user['id'] ?>" 
                                                <?= ($reply_message && $reply_message['sender_id'] == $user['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['full_name']) ?> (@<?= htmlspecialchars($user['username']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject:</label>
                                <input type="text" id="subject" name="subject" required
                                       value="<?= $reply_message ? 'Re: ' . htmlspecialchars($reply_message['subject']) : '' ?>"
                                       placeholder="Message subject">
                            </div>
                            
                            <div class="form-group">
                                <label for="content">Message:</label>
                                <textarea id="content" name="content" required 
                                          placeholder="Type your message here..."><?php if ($reply_message): ?>


--- Original Message ---
From: <?= htmlspecialchars($reply_message['full_name']) ?>
Date: <?= date('M j, Y g:i A', strtotime($reply_message['created_at'])) ?>
Subject: <?= htmlspecialchars($reply_message['subject']) ?>

<?= htmlspecialchars($reply_message['content']) ?><?php endif; ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn">Send Message</button>
                                <a href="messages.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Messages List -->
                    <div class="messages-list">
                        <?php if ($messages->num_rows > 0): ?>
                            <?php while ($msg = $messages->fetch_assoc()): ?>
                            <div class="message-item <?= !$msg['is_read'] && $view === 'inbox' ? 'unread' : '' ?>"
                                 onclick="toggleMessageDetail(<?= $msg['id'] ?>)">
                                <img src="<?= htmlspecialchars($msg['profile_picture_url'] ?: 'uploads/avatars/default-avatar.jpg') ?>" 
                                     alt="<?= htmlspecialchars($msg['full_name']) ?>" 
                                     class="message-avatar">
                                
                                <div class="message-content">
                                    <div class="message-sender">
                                        <?= htmlspecialchars($msg['full_name']) ?>
                                        <?php if (!$msg['is_read'] && $view === 'inbox'): ?>
                                            <span style="color: #dc3545; font-weight: normal;">(Unread)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-subject"><?= htmlspecialchars($msg['subject']) ?></div>
                                    <div class="message-preview">
                                        <?= htmlspecialchars(substr($msg['content'], 0, 100)) ?>...
                                    </div>
                                    <div class="message-date">
                                        <?= date('M j, Y g:i A', strtotime($msg['created_at'])) ?>
                                    </div>
                                </div>
                                
                                <div class="message-actions" onclick="event.stopPropagation()">
                                    <?php if ($view === 'inbox'): ?>
                                        <?php if (!$msg['is_read']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                <button type="submit">Mark Read</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="messages.php?reply=<?= $msg['id'] ?>" style="padding: 5px 10px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; font-size: 12px;">Reply</a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_received">
                                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                            <button type="submit" onclick="return confirm('Delete this message?')">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_sent">
                                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                            <button type="submit" onclick="return confirm('Delete this message?')">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Message Detail (Hidden by default) -->
                            <div id="detail-<?= $msg['id'] ?>" class="message-detail" style="display: none;">
                                <div class="detail-header">
                                    <img src="<?= htmlspecialchars($msg['profile_picture_url'] ?: 'uploads/avatars/default-avatar.jpg') ?>" 
                                         alt="<?= htmlspecialchars($msg['full_name']) ?>" 
                                         class="message-avatar">
                                    <div>
                                        <div class="detail-subject"><?= htmlspecialchars($msg['subject']) ?></div>
                                        <div class="detail-meta">
                                            From: <strong><?= htmlspecialchars($msg['full_name']) ?></strong> 
                                            • <?= date('M j, Y g:i A', strtotime($msg['created_at'])) ?>
                                            <?php if ($msg['read_at']): ?>
                                                • Read: <?= date('M j, Y g:i A', strtotime($msg['read_at'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="detail-content">
                                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <h3>No messages</h3>
                                <p>
                                    <?php if ($view === 'sent'): ?>
                                        You haven't sent any messages yet.
                                    <?php else: ?>
                                        Your inbox is empty.
                                    <?php endif; ?>
                                </p>
                                <a href="messages.php?compose=1" class="btn">Send Your First Message</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function toggleMessageDetail(messageId) {
            const detail = document.getElementById('detail-' + messageId);
            const isVisible = detail.style.display !== 'none';
            
            document.querySelectorAll('[id^="detail-"]').forEach(el => {
                el.style.display = 'none';
            });
            
            detail.style.display = isVisible ? 'none' : 'block';
            
            const messageItem = detail.previousElementSibling;
            if (messageItem.classList.contains('unread') && !isVisible) {
                // Auto-mark as read when opened
                const markReadForm = messageItem.querySelector('form input[value="mark_read"]');
                if (markReadForm) {
                    markReadForm.closest('form').submit();
                }
            }
        }
    </script>
</body>
</html>
