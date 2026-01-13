<?php
// reset_password.php - One-time use password reset script
require_once 'app/config/database.php';

$new_password = 'Admin@123'; // Change this to your desired password

try {
    $db = Database::getInstance();
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update admin password
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$hashed_password]);
    
    echo "Password reset successful!<br>";
    echo "Username: admin<br>";
    echo "New Password: $new_password<br>";
    echo "<a href='login.php'>Go to Login</a>";
    
} catch (PDOException $e) {
    die("Password reset failed: " . $e->getMessage());
}
?>