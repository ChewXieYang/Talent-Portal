<?php
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['to'])) {
    die("Access denied.");
}

$my_id = $_SESSION['user_id'];
$other_id = intval($_GET['to']);

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
    <link rel="stylesheet" href="css/chat.css">
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

setInterval(fetchMessages, 1000);
fetchMessages();
</script>
</body>
</html>
