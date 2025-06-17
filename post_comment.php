<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['content']) || !isset($_POST['image_id'])) {
    header("Location: catalog.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$image_id = intval($_POST['image_id']);
$content = trim($_POST['content']);

if ($content !== "") {
    $stmt = $conn->prepare("INSERT INTO comments (image_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $image_id, $user_id, $content);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header("Location: detailed_view.php?id=" . $image_id);
exit;
