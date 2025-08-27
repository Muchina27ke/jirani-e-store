<?php
require_once 'config/config.php';

echo "<h1>Reset User Passwords</h1>";

// Reset passwords for existing users
$users = [
    [
        'email' => 'admin@example.com',
        'password' => 'password123',
        'name' => 'Admin User'
    ],
    [
        'email' => 'vendor@example.com',
        'password' => 'password123',
        'name' => 'Vendor User'
    ],
    [
        'email' => 'testuser@example.com',
        'password' => 'password123',
        'name' => 'Test User'
    ]
];

echo "<h2>Resetting Passwords...</h2>";

foreach ($users as $userData) {
    $email = $userData['email'];
    $password = $userData['password'];
    $name = $userData['name'];

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

    // Update the user's password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "✅ Reset password for {$name} ({$email}) to: {$password}<br>";
        } else {
            echo "⚠️ User {$name} ({$email}) not found<br>";
        }
    } else {
        echo "❌ Failed to reset password for {$name} ({$email})<br>";
    }
}

// Also reset passwords for any other users in the database
echo "<h2>Resetting Passwords for Other Users...</h2>";

$admin_email = 'admin@example.com';
$vendor_email = 'vendor@example.com';
$testuser_email = 'testuser@example.com';

$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email NOT IN (?, ?, ?)");
$stmt->bind_param("sss", $admin_email, $vendor_email, $testuser_email);
$stmt->execute();
$result = $stmt->get_result();

while ($user = $result->fetch_assoc()) {
    $defaultPassword = 'password123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 10]);

    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $user['id']);

    if ($updateStmt->execute()) {
        echo "✅ Reset password for {$user['name']} ({$user['email']}) to: {$defaultPassword}<br>";
    } else {
        echo "❌ Failed to reset password for {$user['name']} ({$user['email']})<br>";
    }
}

echo "<h2>✅ Password Reset Complete!</h2>";
echo "<p><strong>All users now have the password: <code>password123</code></strong></p>";

echo "<h3>Working Login Credentials:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@example.com / password123</li>";
echo "<li><strong>Vendor:</strong> vendor@example.com / password123</li>";
echo "<li><strong>Customer:</strong> testuser@example.com / password123</li>";
echo "</ul>";

echo "<p><strong>For any other users in your database, use:</strong></p>";
echo "<ul>";
echo "<li><strong>Email/Phone:</strong> (their email or phone)</li>";
echo "<li><strong>Password:</strong> password123</li>";
echo "</ul>";

echo "<p><a href='Signin/index.php'>Try logging in now</a></p>";
echo "<p><a href='debug_login.php'>Check debug information</a></p>";
?>