<?php
/**
 * Basic Admin Functionality Test
 * Tests core admin features without requiring authentication
 */

echo "<h1>Admin System Basic Functionality Test</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    .warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>\n";

// Test 1: Database Connection
echo "<div class='test-section'>\n";
echo "<h3>1. Database Connection Test</h3>\n";
try {
    require_once '../config/config.php';
    $db = getDbConnection();
    if ($db && $db->ping()) {
        echo "<div class='success'>✓ Database connection successful</div>\n";
    } else {
        echo "<div class='error'>✗ Database connection failed</div>\n";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Database connection error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}
echo "</div>\n";

// Test 2: Required Files Exist
echo "<div class='test-section'>\n";
echo "<h3>2. Required Files Check</h3>\n";
$requiredFiles = [
    'index.php',
    'products.php',
    'vendors.php',
    'orders.php',
    'payments.php',
    'verifications.php',
    'settings.php',
    'profile.php',
    'logs.php',
    'layout.php',
    'api/product_actions.php',
    'api/order_actions.php',
    'api/payment_actions.php',
    'api/verification_actions.php'
];

$missingFiles = [];
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    echo "<div class='success'>✓ All required admin files exist</div>\n";
} else {
    echo "<div class='error'>✗ Missing files:</div>\n";
    echo "<ul>\n";
    foreach ($missingFiles as $file) {
        echo "<li>" . htmlspecialchars($file) . "</li>\n";
    }
    echo "</ul>\n";
}
echo "</div>\n";

// Test 3: Database Tables Check
echo "<div class='test-section'>\n";
echo "<h3>3. Database Tables Check</h3>\n";
try {
    $db = getDbConnection();
    $requiredTables = [
        'users',
        'products',
        'vendors',
        'orders',
        'payments',
        'escrow_payments',
        'vendor_verifications',
        'admin_logs'
    ];

    $missingTables = [];
    foreach ($requiredTables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            $missingTables[] = $table;
        }
    }

    if (empty($missingTables)) {
        echo "<div class='success'>✓ All required database tables exist</div>\n";
    } else {
        echo "<div class='warning'>⚠ Missing tables:</div>\n";
        echo "<ul>\n";
        foreach ($missingTables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>\n";
        }
        echo "</ul>\n";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Database table check error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}
echo "</div>\n";

// Test 4: Configuration Check
echo "<div class='test-section'>\n";
echo "<h3>4. Configuration Check</h3>\n";
$configVars = [
    'DB_HOST',
    'DB_USER',
    'DB_PASS',
    'DB_NAME',
    'SITE_URL',
    'ADMIN_EMAIL'
];

$missingConfig = [];
foreach ($configVars as $var) {
    if (!defined($var)) {
        $missingConfig[] = $var;
    }
}

if (empty($missingConfig)) {
    echo "<div class='success'>✓ All required configuration variables are defined</div>\n";
} else {
    echo "<div class='warning'>⚠ Missing configuration variables:</div>\n";
    echo "<ul>\n";
    foreach ($missingConfig as $var) {
        echo "<li>" . htmlspecialchars($var) . "</li>\n";
    }
    echo "</ul>\n";
}
echo "</div>\n";

// Test 5: API Endpoints Check
echo "<div class='test-section'>\n";
echo "<h3>5. API Endpoints Check</h3>\n";
$apiFiles = [
    'api/product_actions.php',
    'api/order_actions.php',
    'api/payment_actions.php',
    'api/verification_actions.php'
];

$apiErrors = [];
foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'session_start()') === false) {
            $apiErrors[] = "$file: Missing session_start()";
        }
        if (strpos($content, 'json_encode') === false) {
            $apiErrors[] = "$file: Missing JSON response";
        }
    }
}

if (empty($apiErrors)) {
    echo "<div class='success'>✓ All API endpoints have proper session handling and JSON responses</div>\n";
} else {
    echo "<div class='warning'>⚠ API endpoint issues:</div>\n";
    echo "<ul>\n";
    foreach ($apiErrors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>\n";
    }
    echo "</ul>\n";
}
echo "</div>\n";

// Test 6: Security Features Check
echo "<div class='test-section'>\n";
echo "<h3>6. Security Features Check</h3>\n";
$securityChecks = [];

// Check for CSRF protection
$layoutContent = file_get_contents('layout.php');
if (strpos($layoutContent, 'csrf') !== false) {
    $securityChecks[] = "✓ CSRF protection implemented";
} else {
    $securityChecks[] = "⚠ CSRF protection not found";
}

// Check for SQL injection protection
$apiContent = file_get_contents('api/product_actions.php');
if (strpos($apiContent, 'bind_param') !== false) {
    $securityChecks[] = "✓ Prepared statements used";
} else {
    $securityChecks[] = "⚠ Prepared statements not found";
}

// Check for XSS protection
if (strpos($layoutContent, 'htmlspecialchars') !== false) {
    $securityChecks[] = "✓ XSS protection implemented";
} else {
    $securityChecks[] = "⚠ XSS protection not found";
}

foreach ($securityChecks as $check) {
    if (strpos($check, '✓') !== false) {
        echo "<div class='success'>" . htmlspecialchars($check) . "</div>\n";
    } else {
        echo "<div class='warning'>" . htmlspecialchars($check) . "</div>\n";
    }
}
echo "</div>\n";

// Test 7: UI Assets Check
echo "<div class='test-section'>\n";
echo "<h3>7. UI Assets Check</h3>\n";
$assetFiles = [
    '../assets/css/admin.css',
    '../assets/js/admin.js',
    '../assets/css/adminlte.min.css',
    '../assets/js/adminlte.min.js'
];

$missingAssets = [];
foreach ($assetFiles as $file) {
    if (!file_exists($file)) {
        $missingAssets[] = $file;
    }
}

if (empty($missingAssets)) {
    echo "<div class='success'>✓ All required UI assets exist</div>\n";
} else {
    echo "<div class='warning'>⚠ Missing UI assets:</div>\n";
    echo "<ul>\n";
    foreach ($missingAssets as $file) {
        echo "<li>" . htmlspecialchars($file) . "</li>\n";
    }
    echo "</ul>\n";
}
echo "</div>\n";

// Summary
echo "<div class='test-section info'>\n";
echo "<h3>Test Summary</h3>\n";
echo "<p>This test verifies the basic functionality of the admin system without requiring authentication.</p>\n";
echo "<p><strong>Next Steps:</strong></p>\n";
echo "<ul>\n";
echo "<li>Access the admin panel at: <a href='index.php' target='_blank'>Admin Dashboard</a></li>\n";
echo "<li>Login with admin credentials to test full functionality</li>\n";
echo "<li>Run the comprehensive test script: <a href='test_admin_functionality.php' target='_blank'>Full Test</a></li>\n";
echo "<li>Check individual pages for functionality</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div class='test-section'>\n";
echo "<h3>Quick Access Links</h3>\n";
echo "<p><a href='index.php' target='_blank'>Admin Dashboard</a> | ";
echo "<a href='products.php' target='_blank'>Products</a> | ";
echo "<a href='vendors.php' target='_blank'>Vendors</a> | ";
echo "<a href='orders.php' target='_blank'>Orders</a> | ";
echo "<a href='payments.php' target='_blank'>Payments</a> | ";
echo "<a href='verifications.php' target='_blank'>Verifications</a> | ";
echo "<a href='settings.php' target='_blank'>Settings</a></p>\n";
echo "</div>\n";
?>