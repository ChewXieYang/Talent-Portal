<?php
include 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

$error = '';
$success = '';

// Check if coming from registration
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = 'Registration successful! Please login with your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check user credentials
        $stmt = $conn->prepare('SELECT id, password, user_type, status FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $username); // Allow login with username or email
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if account is active
            if ($user['status'] !== 'active') {
                $error = 'Your account is ' . $user['status'] . '. Please contact administrator.';
            } else if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Update last login
                $updateStmt = $conn->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
                $updateStmt->bind_param('i', $user['id']);
                $updateStmt->execute();
                
                // Log the login activity
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                $logStmt = $conn->prepare('INSERT INTO activity_log (user_id, action_type, action_description, ip_address) VALUES (?, ?, ?, ?)');
                $action_type = 'login';
                $action_desc = 'User logged in';
                $logStmt->bind_param('isss', $user['id'], $action_type, $action_desc, $ip_address);
                $logStmt->execute();
                
                // Redirect based on user type
                if ($user['user_type'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: profile.php');
                }
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
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
    <title>Login - MMU Talent Showcase</title>
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
        
        .login-container {
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
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
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
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .remember-forgot label {
            display: flex;
            align-items: center;
            font-weight: normal;
            margin-bottom: 0;
        }
        
        .remember-forgot input[type="checkbox"] {
            margin-right: 5px;
        }
        
        .remember-forgot a {
            color: #005eff;
            text-decoration: none;
        }
        
        .remember-forgot a:hover {
            text-decoration: underline;
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
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .register-link a {
            color: #005eff;
            text-decoration: none;
            font-weight: bold;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .back-home {
            text-align: center;
            margin-top: 15px;
        }
        
        .back-home a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-home a:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>MMU Talent Showcase</h1>
            <p>Login to your account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required 
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                       placeholder="Enter your username or email">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <div class="remember-forgot">
                <label>
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="forgot_password.php">Forgot password?</a>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="divider">OR</div>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        
        <div class="back-home">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>