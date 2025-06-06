<?php
include 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // handle upload logic here later
    echo 'File upload logic goes here';
}
?>
<form method="POST" enctype="multipart/form-data">
    Title: <input name="title" required><br>
    File: <input type="file" name="file" required><br>
    <button type="submit">Upload</button>
</form>
