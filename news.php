<?php
include '../includes/db.php';
// Add admin auth checks later
?>
<h1>Admin News & Announcements</h1>
<form method="POST">
    Title: <input name="title" required><br>
    Content: <textarea name="content" required></textarea><br>
    <button type="submit">Post</button>
</form>
