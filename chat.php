<?php
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['to'])) {
    die("Access denied.");
}

$my_id = $_SESSION['user_id'];
$other_id = intval($_GET['to']);

// Send message via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $my_id, $other_id, $message);
        $stmt->execute();
        $stmt->close();
    }
    exit;
}

// Fetch messages (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    $stmt = $conn->prepare("
        SELECT sender_id, message, sent_at 
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY sent_at ASC
    ");
    $stmt->bind_param("iiii", $my_id, $other_id, $other_id, $my_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($messages);
    exit;
}

// Get receiver's name
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $other_id);
$stmt->execute();
$stmt->bind_result($receiver_name);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with <?= htmlspecialchars($receiver_name) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .chat-container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .chat-header {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .chat-box {
            height: 350px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #ccc;
            background: #f9f9f9;
            margin-bottom: 15px;
        }
        .bubble {
            display: inline-block;
            padding: 10px;
            border-radius: 10px;
            margin: 5px 0;
            max-width: 75%;
        }
        .me {
            background: #dcf8c6;
            float: right;
            clear: both;
        }
        .other {
            background: #e6e6e6;
            float: left;
            clear: both;
        }
        form {
            display: flex;
        }
        input[type="text"] {
            flex: 1;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        button {
            padding: 10px 15px;
            background: #0079d3;
            color: white;
            border: none;
            border-radius: 4px;
            margin-left: 10px;
        }
        .back-button {
            display: inline-block;
            margin-bottom: 15px;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #5a6268;
        }

    </style>
</head>
<body>
<div class="chat-container">
    <a href="view_profile.php?id=<?= $other_id ?>" class="back-button">← Back to Profile</a>
    <div class="chat-header">Chat with <?= htmlspecialchars($receiver_name) ?></div>
    <div id="chat-box" class="chat-box"></div>

    <form id="chat-form">
        <input type="text" name="message" id="message" placeholder="Type your message..." required>
        <button type="submit">Send</button>
    </form>
</div>

<script>
const chatBox = document.getElementById('chat-box');
const form = document.getElementById('chat-form');
const input = document.getElementById('message');

// Fetch and display messages
function fetchMessages() {
    fetch('chat.php?action=fetch&to=<?= $other_id ?>')
        .then(response => response.json())
        .then(data => {
            chatBox.innerHTML = '';
            data.forEach(msg => {
                const div = document.createElement('div');
                div.classList.add('bubble');
                div.classList.add(msg.sender_id == <?= $my_id ?> ? 'me' : 'other');
                div.textContent = msg.message;
                chatBox.appendChild(div);
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        });
}

// Submit message
form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(form);
    fetch('chat.php?to=<?= $other_id ?>', {
        method: 'POST',
        body: formData
    }).then(() => {
        input.value = '';
        fetchMessages();
    });
});

// Real-time polling
setInterval(fetchMessages, 1000);
fetchMessages();
</script>
</body>
</html>
