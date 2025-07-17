<?php
require_once 'config/config.php';

echo "<h2>Creating Default Admin User</h2>";

// Default admin credentials
$username = 'admin';
$email = 'admin@airtelstore.co.ke';
$password = 'password';
$full_name = 'System Administrator';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin table exists
    $stmt = $db->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>❌ Admin table doesn't exist. Please run the database schema first.</p>";
        exit;
    }

    // Check if admin already exists
    $stmt = $db->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠️ Admin user already exists!</p>";
        
        // Update password just in case
        $stmt = $db->prepare("UPDATE admins SET password = ? WHERE username = ?");
        if ($stmt->execute([$hashed_password, $username])) {
            echo "<p style='color: green;'>✅ Admin password updated successfully!</p>";
        }
    } else {
        // Create admin user
        $stmt = $db->prepare("INSERT INTO admins (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
            echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create admin user!</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Admin Login Credentials:</h3>";
    echo "<p><strong>URL:</strong> <a href='admin/'>http://localhost/airtel-ecommerce/admin/</a></p>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>Email:</strong> $email</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>