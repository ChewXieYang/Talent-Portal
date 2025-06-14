<?php
include 'includes/db.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$message = '';
$messageType = '';
$valid_token = false;
$user_data = null;

// Validate token
if (!empty($token)) {
    $stmt = $conn->prepare('
        SELECT pr.*, u.id as user_id, u.username, u.full_name, u.email 
        FROM password_resets pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
    ');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        $user_data = $result->fetch_assoc();
    } else {
        $message = 'Invalid or expired reset token. Please request a new password reset.';
        $messageType = 'error';
    }
    $stmt->close();
} else {
    $message = 'No reset token provided.';
    $messageType = 'error';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($new_password) || empty($confirm_password)) {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update user password
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $hashed_password, $user_data['user_id']);
        
        if ($stmt->execute()) {
            // Mark token as used
            $stmt2 = $conn->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
            $stmt2->bind_param('s', $token);
            $stmt2->execute();
            
            // Log the password change
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $log_stmt = $conn->prepare('INSERT INTO activity_log (user_id, action_type, action_description, ip_address) VALUES (?, ?, ?, ?)');
            $action_type = 'password_reset_complete';
            $action_desc = 'Password successfully reset';
            $log_stmt->bind_param('isss', $user_data['user_id'], $action_type, $action_desc, $ip_address);
            $log_stmt->execute();
            
            $message = 'Your password has been successfully reset! You can now log in with your new password.';
            $messageType = 'success';
            $valid_token = false; // Hide the form
        } else {
            $message = 'An error occurred while updating your password. Please try again.';
            $messageType = 'error';
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
    <title>Reset Password - MMU Talent Showcase</title>
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
        
        .reset-password-container {
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
        
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .user-info h3 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .user-info p {
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
        
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="password"]:focus {
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
        
        .password-requirements {
            background: #e7f3ff;
            color: #0c5460;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        
        .password-requirements ul {
            margin-left: 20px;
            margin-top: 8px;
        }
        
        .back-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-links a {
            color: #005eff;
            text-decoration: none;
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
    <div class="reset-password-container">
        <div class="logo">
            <h1>MMU Talent Showcase</h1>
            <p>Create new password</p>
        </div>
        
        <?php if ($message): ?>
            <div class="<?= $messageType === 'success' ? 'success-message' : 'error-message' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($valid_token && $user_data): ?>
            <div class="user-info">
                <h3><?= htmlspecialchars($user_data['full_name']) ?></h3>
                <p><?= htmlspecialchars($user_data['email']) ?></p>
            </div>
            
            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li>At least 6 characters long</li>
                    <li>Should be different from your old password</li>
                    <li>Use a combination of letters, numbers, and symbols for better security</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required 
                           placeholder="Enter your new password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm your new password">
                </div>
                
                <button type="submit">Update Password</button>
            </form>
        <?php endif; ?>
        
        <div class="divider">OR</div>
        
        <div class="back-links">
            <?php if ($messageType === 'success'): ?>
                <a href="login.php">← Go to Login</a>
            <?php else: ?>
                <a href="forgot_password.php">← Request New Reset</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>