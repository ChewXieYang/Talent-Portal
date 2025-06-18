<?php
include 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $student_id = trim($_POST['student_id']);
    $section = $_POST['section'];
    $phone_number = trim($_POST['phone_number']);
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } else if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    } else if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } else if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    } else if (!str_ends_with($email, '@student.mmu.edu.my')) {
        $errors[] = 'Please use your MMU student email (@student.mmu.edu.my).';
    }
    
    if (empty($student_id)) {
        $errors[] = 'Student ID is required.';
    } else if (!preg_match('/^\d{10}$/', $student_id)) {
        $errors[] = 'Student ID must be 10 digits.';
    }
    
    if (empty($section) || !in_array($section, ['TC1L', 'TC2L'])) {
        $errors[] = 'Please select a valid section.';
    }
    
    if (!empty($phone_number) && !preg_match('/^[0-9\-\+\s]+$/', $phone_number)) {
        $errors[] = 'Invalid phone number format.';
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Username already taken.';
        }
        $stmt->close();
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Email already registered.';
        }
        $stmt->close();
    }
    
    // Check if student ID already exists
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE student_id = ?');
        $stmt->bind_param('s', $student_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Student ID already registered.';
        }
        $stmt->close();
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare('
            INSERT INTO users (username, password, email, full_name, student_id, section, phone_number, contact_email) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->bind_param('ssssssss', 
            $username, $hashed_password, $email, $full_name, 
            $student_id, $section, $phone_number, $email
        );
        
        if ($stmt->execute()) {
            $success = true;
            // Redirect to login page after 2 seconds
            header('refresh:2;url=login.php?registered=1');
        } else {
            $errors[] = 'Registration failed. Please try again.';
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
    <title>Register - MMU Talent Showcase</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        
        .required {
            color: #dc3545;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: #005eff;
        }
        
        .error-list {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .error-list ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .error-list li {
            margin-bottom: 5px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: #005eff;
            text-decoration: none;
            font-weight: bold;
        }
        
        .login-link a:hover {
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
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1>MMU Talent Showcase</h1>
            <p>Create your account</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-list">
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <strong>Registration successful!</strong><br>
                Redirecting to login page...
            </div>
        <?php endif; ?>
        
        <form id="registerForm" method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input type="text" id="username" name="username" required 
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                       placeholder="Choose a username">
                <div class="help-text">Letters, numbers, and underscores only (min 3 characters)</div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Min 6 characters">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Re-enter password">
                </div>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" required 
                       value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>"
                       placeholder="Enter your full name">
            </div>
            
            <div class="form-group">
                <label for="email">MMU Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" required 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                       placeholder="yourname@student.mmu.edu.my">
                <div class="help-text">Must be your official MMU student email</div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="student_id">Student ID <span class="required">*</span></label>
                    <input type="text" id="student_id" name="student_id" required 
                           pattern="\d{10}" maxlength="10"
                           value="<?= isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : '' ?>"
                           placeholder="1191234567">
                </div>
                
                <div class="form-group">
                    <label for="section">Section <span class="required">*</span></label>
                    <select id="section" name="section" required>
                        <option value="">Select section</option>
                        <option value="TC1L" <?= (isset($_POST['section']) && $_POST['section'] == 'TC1L') ? 'selected' : '' ?>>TC1L</option>
                        <option value="TC2L" <?= (isset($_POST['section']) && $_POST['section'] == 'TC2L') ? 'selected' : '' ?>>TC2L</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" 
                       value="<?= isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : '' ?>"
                       placeholder="012-3456789 (optional)">
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        
        <div class="back-home">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>