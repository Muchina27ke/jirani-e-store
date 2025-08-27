<?php
require_once __DIR__ . '/../config/config.php';

echo "<h1>Database Connection Test</h1>";

// Test database connection
try {
    if ($conn->ping()) {
        echo "<p style='color: green;'>✅ Database connection successful</p>";
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection error: " . $e->getMessage() . "</p>";
    exit;
}

// Test required tables
$required_tables = [
    'users',
    'vendors',
    'products',
    'orders',
    'order_items',
    'payments',
    'escrow_payments',
    'verifications',
    'system_logs',
    'login_history'
];

echo "<h2>Table Availability Test</h2>";
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' missing</p>";
    }
}

// Test admin user
echo "<h2>Admin User Test</h2>";
$admin_query = "SELECT * FROM users WHERE role = 'admin' LIMIT 1";
$admin_result = $conn->query($admin_query);
if ($admin_result->num_rows > 0) {
    $admin = $admin_result->fetch_assoc();
    echo "<p style='color: green;'>✅ Admin user found: " . htmlspecialchars($admin['name']) . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ No admin user found. Creating one...</p>";

    // Create admin user
    $admin_name = "Admin User";
    $admin_email = "admin@jirani.com";
    $admin_phone = "254700000000";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);

    $create_admin = "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'admin')";
    $stmt = $conn->prepare($create_admin);
    $stmt->bind_param("ssss", $admin_name, $admin_email, $admin_phone, $admin_password);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Admin user created successfully</p>";
        echo "<p><strong>Login credentials:</strong></p>";
        echo "<p>Email: admin@jirani.com</p>";
        echo "<p>Password: admin123</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create admin user</p>";
    }
}

// Test vendor data
echo "<h2>Vendor Data Test</h2>";
$vendor_query = "SELECT COUNT(*) as count FROM vendors";
$vendor_result = $conn->query($vendor_query);
$vendor_count = $vendor_result->fetch_assoc()['count'];
echo "<p>Total vendors: $vendor_count</p>";

// Test product data
echo "<h2>Product Data Test</h2>";
$product_query = "SELECT COUNT(*) as count FROM products";
$product_result = $conn->query($product_query);
$product_count = $product_result->fetch_assoc()['count'];
echo "<p>Total products: $product_count</p>";

// Test order data
echo "<h2>Order Data Test</h2>";
$order_query = "SELECT COUNT(*) as count FROM orders";
$order_result = $conn->query($order_query);
$order_count = $order_result->fetch_assoc()['count'];
echo "<p>Total orders: $order_count</p>";

// Test system logs
echo "<h2>System Logs Test</h2>";
$log_query = "SELECT COUNT(*) as count FROM system_logs";
$log_result = $conn->query($log_query);
if ($log_result) {
    $log_count = $log_result->fetch_assoc()['count'];
    echo "<p>Total system logs: $log_count</p>";
} else {
    echo "<p style='color: red;'>❌ system_logs table not accessible</p>";
}

// Test login history
echo "<h2>Login History Test</h2>";
$login_query = "SELECT COUNT(*) as count FROM login_history";
$login_result = $conn->query($login_query);
if ($login_result) {
    $login_count = $login_result->fetch_assoc()['count'];
    echo "<p>Total login history records: $login_count</p>";
} else {
    echo "<p style='color: red;'>❌ login_history table not accessible</p>";
}

echo "<h2>Admin Page Test</h2>";
$admin_pages = [
    'vendors.php',
    'products.php',
    'orders.php',
    'payments.php',
    'verifications.php',
    'settings.php',
    'logs.php',
    'profile.php'
];

foreach ($admin_pages as $page) {
    if (file_exists($page)) {
        echo "<p style='color: green;'>✅ $page exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $page missing</p>";
    }
}

echo "<h2>Recommendations</h2>";
echo "<ul>";
echo "<li>If any tables are missing, run the SQL in database/missing_tables.sql</li>";
echo "<li>If no admin user exists, use the credentials above to login</li>";
echo "<li>Make sure all required includes are present in config/config.php</li>";
echo "</ul>";

echo "<p><a href='index.php'>Go to Admin Dashboard</a></p>";
?>