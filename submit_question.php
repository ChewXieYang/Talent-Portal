<?php
include 'includes/db.php'; // make sure this file connects to your DB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['body']);

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($subject) < 5 || strlen($message) < 10) {
        header("Location: faq.php?error=invalid");
        exit;
    }

    // Prepare and insert into database
    $stmt = $conn->prepare("INSERT INTO question_submissions (email, question_title, question_message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $subject, $message);

    if ($stmt->execute()) {
        header("Location: faq.php?success=1");
    } else {
        header("Location: faq.php?error=db");
    }

    $stmt->close();
    $conn->close();
}
?>
