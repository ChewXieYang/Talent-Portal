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
    <link rel="stylesheet" href="css/faq.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <script src="js/main.js"></script>

    <style>
        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex-grow: 1;
            background-color: #f5f5f5;
        }
        
    </style>
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
                            <td class="faq-question">Who can view my submission?</td>
                        </tr>
                        <tr class="faq-answer-row">
                            <td class="faq-answer">All registered users and admins can view published submissions on the platform.</td>
                        </tr>
                        <tr class="faq-item">
                            <td class="faq-question">What file formats are supported?</td>
                        </tr>
                        <tr class="faq-answer-row">
                            <td class="faq-answer">We support common formats like JPG, PNG, MP4, PDF, and DOCX.</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="question-form-section">
                <h2>Have More Questions?</h2>
                <p>Send us your inquiry below and we'll get back to you as soon as possible.</p>
                <form action="submit_question.php" method="post">
                    <label for="email">Your Email:</label>
                    <input type="email" name="email" id="email" required>

                    <label for="subject">Your Question:</label>
                    <input type="text" name="subject" id="subject" required>

                    <label for="message">Additional Message:</label>
                    <textarea name="body" id="message" rows="5" required></textarea>

                    <button type="submit">Send Question</button>
                </form>
            </section>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script>
        const questions = document.querySelectorAll('.faq-question');
        questions.forEach((question) => {
            question.addEventListener('click', () => {
                const answerRow = question.parentElement.nextElementSibling;
                answerRow.classList.toggle('open');
            });
        });

        const form = document.querySelector("form");
        form.addEventListener("submit", function (e) {
            const email = document.getElementById("email").value.trim();
            const subject = document.getElementById("subject").value.trim();
            const message = document.getElementById("message").value.trim();

            let errors = [];

            if (!isValidEmail(email)) {
                errors.push("Please enter a valid email address.");
            }

            if (subject.length < 5) {
                errors.push("Your question must be at least 5 characters long.");
            }

            if (message.length < 10) {
                errors.push("Your additional message must be at least 10 characters long.");
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join("\\n"));
            }
        });

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

    </script>
</body>
</html>
