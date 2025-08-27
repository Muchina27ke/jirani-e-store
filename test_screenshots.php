<?php
// Test script to verify all pages are accessible for screenshot capture
echo "<h1>Jirani System - Screenshot Test</h1>";
echo "<p>This page helps verify all system pages are working for screenshot capture.</p>";

$pages = [
    'Registration Page' => 'Register/index.php',
    'Login Page' => 'Signin/index.php',
    'Homepage' => 'index.php',
    'Cart Page' => 'cart.php',
    'Checkout Page' => 'checkout.php',
    'Orders Page' => 'orders.php',
    'Wishlist Page' => 'wishlist.php',
    'Fruits Page' => 'fruits.php',
    'Vegetables Page' => 'vegetables.php',
    'Handcrafts Page' => 'handcrafts.php',
    'About Page' => 'about.php',
    'Contact Page' => 'contact_us.php',
    'Vendor Dashboard' => 'seller/index.php',
    'Vendor Add Product' => 'seller/add_product.php',
    'Vendor Products' => 'seller/products.php',
    'Vendor Orders' => 'seller/orders.php',
    'Vendor Payments' => 'seller/payments.php',
    'Admin Dashboard' => 'admin/index.php',
    'Admin Products' => 'admin/products.php',
    'Admin Vendors' => 'admin/vendors.php',
    'Admin Orders' => 'admin/orders.php',
    'Admin Users' => 'admin/manage_users.php',
    'Admin Payments' => 'admin/payments.php'
];

echo "<h2>Page Accessibility Test</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Page Name</th><th>File Path</th><th>Status</th></tr>";

foreach ($pages as $name => $path) {
    $fullPath = __DIR__ . '/' . $path;
    $exists = file_exists($fullPath);
    $status = $exists ? '✅ Exists' : '❌ Missing';
    $color = $exists ? 'green' : 'red';

    echo "<tr>";
    echo "<td>$name</td>";
    echo "<td>$path</td>";
    echo "<td style='color: $color;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Database Connection Test</h2>";
try {
    require_once 'config/config.php';
    $db = getDbConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";

    // Test basic queries
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $userCount = $result->fetch_assoc()['count'];
    echo "<p>Total users in database: $userCount</p>";

    $result = $db->query("SELECT COUNT(*) as count FROM products");
    $productCount = $result->fetch_assoc()['count'];
    echo "<p>Total products in database: $productCount</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Screenshot Checklist</h2>";
echo "<ul>";
echo "<li>✅ Registration Page (Register/index.php)</li>";
echo "<li>✅ Login Page (Signin/index.php)</li>";
echo "<li>✅ Forgot Password Modal (in Signin/index.php)</li>";
echo "<li>✅ Homepage with Products (index.php)</li>";
echo "<li>✅ Shopping Cart (cart.php)</li>";
echo "<li>✅ Checkout Process (checkout.php)</li>";
echo "<li>✅ Order Tracking (orders.php)</li>";
echo "<li>✅ Customer Dashboard (after login)</li>";
echo "<li>✅ Vendor Dashboard (seller/index.php)</li>";
echo "<li>✅ Vendor Add Product (seller/add_product.php)</li>";
echo "<li>✅ Admin Dashboard (admin/index.php)</li>";
echo "<li>✅ Admin User Management (admin/manage_users.php)</li>";
echo "<li>✅ Admin Reports (admin/payments.php)</li>";
echo "</ul>";

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Start your local server (XAMPP or php -S localhost:8000)</li>";
echo "<li>Open each page in your browser</li>";
echo "<li>Take screenshots using Windows Snipping Tool or browser dev tools</li>";
echo "<li>Save screenshots with descriptive names</li>";
echo "<li>Insert screenshots into your user manual document</li>";
echo "</ol>";
?>