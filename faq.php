<?php
include 'includes/db.php';

// Check user type (optional)
$user_type = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $resultUser = $stmt->get_result();
    if ($resultUser && $rowUser = $resultUser->fetch_assoc()) {
        $user_type = $rowUser['user_type'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ - MMU Talent Showcase</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/faq.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <script src="js/main.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">Your question has been submitted successfully!</div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="error-message">There was a problem with your submission. Please check your inputs.</div>
            <?php endif; ?>

            <header>
                <h1>Frequently Asked Questions (FAQ)</h1>
            </header>

            <section class="faq-section">
                <table class="faq-table">
                    <thead>
                        <tr>
                            <th>Question</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="faq-item">
                            <td class="faq-question">How do I submit my talent?</td>
                        </tr>
                        <tr class="faq-answer-row">
                            <td class="faq-answer">You can submit your talent through the 'Submit Talent' form after logging into your account.</td>
                        </tr>
                        <tr class="faq-item">
                            <td class="faq-question">Can I edit my portfolio after submission?</td>
                        </tr>
                        <tr class="faq-answer-row">
                            <td class="faq-answer">Yes, go to your dashboard and select 'Edit' next to your talent submission.</td>
                        </tr>
                        <tr class="faq-item">
                            <td class="faq-question">How long does it take for my submission to be reviewed?</td>
                        </tr>
                        <tr class="faq-answer-row">
                            <td class="faq-answer">Submissions are typically reviewed within 3-5 business days.</td>
                        </tr>
                        <tr class="faq-item">
                            <td class="faq-question">Can I submit multiple talents?</td>
                        </tr>
                        <tr class="faq-answer-row">
                            <td class="faq-answer">Yes, you can submit multiple talents by creating separate submissions for each.</td>
                        </tr>
                        <tr class="faq-item">
                            <td class="faq-question">What file formats are supported for uploads?</td>
                        </tr>
                        <tr class="faq-answer-row">
                            <td class="faq-answer">We support JPG, PNG, PDF, MP4, and MP3 files up to 50MB each.</td>
                        </tr>
                        <tr class="faq-item">
                            <td class="faq-question">How do I contact support?</td>
                        </tr>
                        <tr class="faq-answer-row">
                            <td class="faq-answer">You can contact our support team through the contact form below or email us at support@mmutalent.com</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Question submission form -->
            <section class="question-form-section">
                <h2>Have a question that's not answered above?</h2>
                <p>Submit your question and we'll get back to you as soon as possible.</p>
                
                <form method="POST" action="submit_question.php">
                    <label for="user_name">Your Name:</label>
                    <input type="text" id="user_name" name="user_name" required>
                    
                    <label for="user_email">Your Email:</label>
                    <input type="email" id="user_email" name="user_email" required>
                    
                    <label for="question_subject">Subject:</label>
                    <input type="text" id="question_subject" name="question_subject" required>
                    
                    <label for="question_text">Your Question:</label>
                    <textarea id="question_text" name="question_text" rows="5" required placeholder="Please describe your question in detail..."></textarea>
                    
                    <button type="submit">Submit Question</button>
                </form>
            </section>
        </div>
    </div>

    <script>
        // FAQ accordion functionality
        document.addEventListener('DOMContentLoaded', function() {
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    const answerRow = this.parentNode.nextElementSibling;
                    const isOpen = answerRow.classList.contains('open');
                    
                    // Close all other answers
                    document.querySelectorAll('.faq-answer-row').forEach(row => {
                        row.classList.remove('open');
                    });
                    
                    // Toggle current answer
                    if (!isOpen) {
                        answerRow.classList.add('open');
                    }
                });
            });
        });
    </script>
</body>
</html>
