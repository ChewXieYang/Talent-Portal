<?php
include 'includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $stmt = $conn->prepare('INSERT INTO users (username, password, fullname, contact_email) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $username, $password, $fullname, $email);
    if ($stmt->execute()) {
        header('Location: login.php');
        exit;
    } else {
        echo 'Registration failed';
    }
}
?>
<form method="POST">
    Username: <input name="username" required><br>
    Password: <input type="password" name="password" required><br>
    Full Name: <input name="fullname" required><br>
    Email: <input type="email" name="email" required><br>
    <button type="submit">Register</button>
</form>