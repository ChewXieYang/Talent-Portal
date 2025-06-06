<?php
include 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$stmt = $conn->prepare('SELECT username, fullname, contact_email, bio FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($username, $fullname, $email, $bio);
$stmt->fetch();
?>
<h1>Your Profile</h1>
<p>Username: <?= htmlspecialchars($username) ?></p>
<p>Full Name: <?= htmlspecialchars($fullname) ?></p>
<p>Email: <?= htmlspecialchars($email) ?></p>
<p>Bio: <?= htmlspecialchars($bio) ?></p>
<a href="upload.php">Upload Talent</a>