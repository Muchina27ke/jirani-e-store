<?php
require_once 'config/config.php';

echo "<h1>Login Debug Information</h1>";

// Check all users in the database
echo "<h2>All Users in Database:</h2>";
$stmt = $conn->prepare("SELECT id, name, email, phone, role, verified, password FROM users");
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Verified</th><th>Password Hash</th></tr>";

while ($user = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['name']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['phone']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "<td>{$user['verified']}</td>";
    echo "<td>" . substr($user['password'], 0, 20) . "...</td>";
    echo "</tr>";
}
echo "</table>";

// Test password verification for known users
echo "<h2>Password Verification Tests:</h2>";

$testPasswords = [
    'password123',
    'test123',
    'admin123',
    '123456'
];

foreach ($testPasswords as $testPassword) {
    echo "<h3>Testing password: '$testPassword'</h3>";

    $stmt = $conn->prepare("SELECT id, name, email, phone, password FROM users");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($user = $result->fetch_assoc()) {
        if (password_verify($testPassword, $user['password'])) {
            echo "✅ <strong>MATCH FOUND!</strong> User: {$user['name']} ({$user['email']}) - Password: '$testPassword'<br>";
        }
    }
}

// Test specific login scenarios
echo "<h2>Specific Login Tests:</h2>";

// Test 1: Admin user
echo "<h3>Test 1: Admin Login</h3>";
$adminEmail = "admin@example.com";
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
$stmt->bind_param("ss", $adminEmail, $adminEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "Admin user found: {$admin['name']}<br>";
    echo "Password hash: " . substr($admin['password'], 0, 20) . "...<br>";

    // Test with common passwords
    $testPasswords = ['password123', 'admin123', 'test123'];
    foreach ($testPasswords as $pwd) {
        if (password_verify($pwd, $admin['password'])) {
            echo "✅ Admin password is: '$pwd'<br>";
            break;
        }
    }
} else {
    echo "❌ Admin user not found<br>";
}

// Test 2: Vendor user
echo "<h3>Test 2: Vendor Login</h3>";
$vendorEmail = "vendor@example.com";
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
$stmt->bind_param("ss", $vendorEmail, $vendorEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $vendor = $result->fetch_assoc();
    echo "Vendor user found: {$vendor['name']}<br>";
    echo "Password hash: " . substr($vendor['password'], 0, 20) . "...<br>";

    // Test with common passwords
    $testPasswords = ['password123', 'vendor123', 'test123'];
    foreach ($testPasswords as $pwd) {
        if (password_verify($pwd, $vendor['password'])) {
            echo "✅ Vendor password is: '$pwd'<br>";
            break;
        }
    }
} else {
    echo "❌ Vendor user not found<br>";
}

// Test 3: Customer user
echo "<h3>Test 3: Customer Login</h3>";
$customerEmail = "testuser@example.com";
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
$stmt->bind_param("ss", $customerEmail, $customerEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
    echo "Customer user found: {$customer['name']}<br>";
    echo "Password hash: " . substr($customer['password'], 0, 20) . "...<br>";

    // Test with common passwords
    $testPasswords = ['password123', 'test123', 'user123'];
    foreach ($testPasswords as $pwd) {
        if (password_verify($pwd, $customer['password'])) {
            echo "✅ Customer password is: '$pwd'<br>";
            break;
        }
    }
} else {
    echo "❌ Customer user not found<br>";
}

echo "<h2>Working Login Credentials:</h2>";
echo "<p><strong>Try these credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@example.com / password123</li>";
echo "<li><strong>Vendor:</strong> vendor@example.com / password123</li>";
echo "<li><strong>Customer:</strong> testuser@example.com / password123</li>";
echo "</ul>";

echo "<h2>If Still Having Issues:</h2>";
echo "<p>The problem might be:</p>";
echo "<ol>";
echo "<li>Password hashes in database are corrupted</li>";
echo "<li>Session issues</li>";
echo "<li>Database connection problems</li>";
echo "</ol>";

echo "<p><a href='Signin/index.php'>Try logging in here</a></p>";
?>