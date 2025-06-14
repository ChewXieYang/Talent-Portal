<?php
include 'includes/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        // Check if user exists
        $stmt = $conn->prepare('SELECT id, username, full_name FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
            
            // Store reset token
            $stmt = $conn->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $user['id'], $token, $expires_at);
            
            if ($stmt->execute()) {
                // Create reset link
                $reset_link = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/reset_password.php?token=' . $token;
                
                // In a real application, you would send this via email
                // For now, we'll just display it (for development/testing)
                $message = 'Password reset instructions have been sent to your email. 
                           <br><br><strong>For testing purposes, here is your reset link:</strong>
                           <br><a href="' . $reset_link . '" style="color: #005eff; word-break: break-all;">' . $reset_link . '</a>
                           <br><br><small>In a live system, this would be sent to your email instead.</small>';
                $messageType = 'success';
                
                // Log the password reset request
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                $log_stmt = $conn->prepare('INSERT INTO activity_log (user_id, action_type, action_description, ip_address) VALUES (?, ?, ?, ?)');
                $action_type = 'password_reset_request';
                $action_desc = 'Password reset requested for: ' . $email;
                $log_stmt->bind_param('isss', $user['id'], $action_type, $action_desc, $ip_address);
                $log_stmt->execute();
                
                /* 
                // EMAIL SENDING CODE (uncomment and configure for production)
                // You would need to install PHPMailer or use mail() function
                
                $to = $email;
                $subject = 'Password Reset - MMU Talent Showcase';
                $email_message = "
                    <html>
                    <body>
                        <h2>Password Reset Request</h2>
                        <p>Hello " . htmlspecialchars($user['full_name']) . ",</p>
                        <p>You have requested to reset your password for MMU Talent Showcase.</p>
                        <p>Click the link below to reset your password:</p>
                        <p><a href='" . $reset_link . "'>Reset My Password</a></p>
                        <p>This link will expire in 1 hour.</p>
                        <p>If you did not request this reset, please ignore this email.</p>
                        <br>
                        <p>Best regards,<br>MMU Talent Showcase Team</p>
                    </body>
                    </html>
                ";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: noreply@mmu.edu.my' . "\r\n";
                
                mail($to, $subject, $email_message, $headers);
                */
                
            } else {
                $message = 'An error occurred. Please try again.';
                $messageType = 'error';
            }
        } else {
            // Don't reveal if email exists or not (security best practice)
            $message = 'If an account with this email exists, password reset instructions have been sent.';
            $messageType = 'success';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MMU Talent Showcase</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .forgot-password-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #005eff;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            text-align: center;
            line-height: 1.5;
        }
        
        .success-message a {
            color: #005eff;
            word-break: break-all;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: #005eff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #0044cc;
        }
        
        .info-text {
            background: #e7f3ff;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .back-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-links a {
            color: #005eff;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        
        .back-links a:hover {
            text-decoration: underline;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
            position: relative;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #ddd;
        }
        
        .divider::before {
            left: 0;
        }
        
        .divider::after {
            right: 0;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="logo">
            <h1>MMU Talent Showcase</h1>
            <p>Reset your password</p>
        </div>
        
        <?php if ($message): ?>
            <div class="<?= $messageType === 'success' ? 'success-message' : 'error-message' ?>">
                <?= $message ?>
            </div>
        <?php else: ?>
            <div class="info-text">
                Enter your email address and we'll send you instructions to reset your password.
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                       placeholder="Enter your MMU email address">
            </div>
            
            <button type="submit">Send Reset Instructions</button>
        </form>
        
        <div class="divider">OR</div>
        
        <div class="back-links">
            <a href="login.php">← Back to Login</a>
            <a href="register.php">Create Account</a>
        </div>
    </div>
</body>
</html>