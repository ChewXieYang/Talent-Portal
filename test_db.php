<?php
include 'includes/db.php';

// Test database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully!<br>";

// Test tables exist
$tables = ['users', 'talent_categories', 'user_talents', 'portfolio_items'];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Table '$table' has " . $row['count'] . " records<br>";
    } else {
        echo "Error accessing table '$table': " . $conn->error . "<br>";
    }
 }
 
 // Test user login (password is 'password123' for all test users)
 $test_username = 'john_doe';
 $test_password = 'password123';
 
 $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
 $stmt->bind_param("s", $test_username);
 $stmt->execute();
 $result = $stmt->get_result();
 
 if ($row = $result->fetch_assoc()) {
    if (password_verify($test_password, $row['password'])) {
        echo "<br>Login test successful! User ID: " . $row['id'];
    } else {
        echo "<br>Login test failed: Invalid password";
    }
 } else {
    echo "<br>Login test failed: User not found";
 }
 ?>