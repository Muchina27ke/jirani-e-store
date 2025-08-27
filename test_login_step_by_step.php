<?php
require_once 'config/config.php';

echo "<h2>Step-by-Step Login Debug</h2>";

// Test 1: Check if session is working
echo "<h3>Step 1: Session Check</h3>";
echo "Session status: " . session_status() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre><br>";

// Test 2: Check database connection
echo "<h3>Step 2: Database Connection</h3>";
if ($conn->ping()) {
    echo "Database connection: OK<br>";
} else {
    echo "Database connection: FAILED<br>";
}

// Test 3: Test user lookup
echo "<h3>Step 3: User Lookup Test</h3>";
$test_email = "admin@example.com";
$query = "SELECT id, name, phone, email, password, role, verified FROM users WHERE email = ? OR phone = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $test_email, $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user_data = $result->fetch_assoc();
    echo "User found: " . $user_data['name'] . "<br>";
    echo "Email: " . $user_data['email'] . "<br>";
    echo "Role: " . $user_data['role'] . "<br>";
    echo "Password hash: " . substr($user_data['password'], 0, 20) . "...<br>";
} else {
    echo "User not found<br>";
}

// Test 4: Test password verification
echo "<h3>Step 4: Password Verification Test</h3>";
$test_password = "password123";
if (password_verify($test_password, $user_data['password'])) {
    echo "Password verification: SUCCESS<br>";
} else {
    echo "Password verification: FAILED<br>";
}

// Test 5: Test User class login method
echo "<h3>Step 5: User Class Login Test</h3>";
$login_result = $user->login($test_email, $test_password);
echo "Login result: " . ($login_result ? "SUCCESS" : "FAILED") . "<br>";

// Test 6: Check session after login
echo "<h3>Step 6: Session After Login</h3>";
echo "Session data after login: <pre>" . print_r($_SESSION, true) . "</pre><br>";

// Test 7: Test role checks
echo "<h3>Step 7: Role Check Tests</h3>";
echo "Is admin: " . ($user->is_admin() ? "YES" : "NO") . "<br>";
echo "Is vendor: " . ($user->is_vendor() ? "YES" : "NO") . "<br>";
echo "Is customer: " . ($user->is_customer() ? "YES" : "NO") . "<br>";

// Test 8: Test logout
echo "<h3>Step 8: Logout Test</h3>";
$logout_result = $user->logout();
echo "Logout result: " . ($logout_result ? "SUCCESS" : "FAILED") . "<br>";
echo "Session data after logout: <pre>" . print_r($_SESSION, true) . "</pre><br>";

echo "<h3>Test Complete</h3>";
echo "<p>If all steps pass, the login system should work correctly.</p>";
?>