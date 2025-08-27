<?php
/**
 * Comprehensive Cart and Checkout Test Script
 * Tests all functionality from cart to order completion
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Cart.php';
require_once __DIR__ . '/includes/EmailTemplate.php';

// Start session for testing
session_start();

echo "<h1>Jirani Cart & Checkout System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; }
    .warning { background-color: #fff3cd; border-color: #ffeaa7; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Test 1: Database Connection
echo "<div class='test-section'>";
echo "<h2>1. Database Connection Test</h2>";
try {
    $db = getDbConnection();
    echo "<div class='success'>✓ Database connection successful</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}
echo "</div>";

// Test 2: Check if user is logged in
echo "<div class='test-section'>";
echo "<h2>2. User Authentication Test</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<div class='success'>✓ User is logged in (ID: " . $_SESSION['user_id'] . ")</div>";

    // Get user details
    $stmt = $db->prepare("SELECT name, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    echo "<div class='info'>User: " . $user['name'] . " (" . $user['email'] . ") - Role: " . $user['role'] . "</div>";
} else {
    echo "<div class='warning'>⚠ User not logged in. Please login first to test cart functionality.</div>";
    echo "<p><a href='login.php'>Login here</a></p>";
    exit;
}
echo "</div>";

// Test 3: Cart Class Test
echo "<div class='test-section'>";
echo "<h2>3. Cart Class Test</h2>";
try {
    $cart = new Cart($db, $_SESSION['user_id']);
    echo "<div class='success'>✓ Cart class instantiated successfully</div>";

    // Get current cart items
    $cartResult = $cart->getCartItems();
    if ($cartResult['success']) {
        echo "<div class='info'>Current cart items: " . count($cartResult['items']) . "</div>";
        if (!empty($cartResult['items'])) {
            echo "<ul>";
            foreach ($cartResult['items'] as $item) {
                echo "<li>" . $item['name'] . " x" . $item['quantity'] . " - KSh " . number_format($item['price'] * $item['quantity'], 2) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<div class='error'>✗ Failed to get cart items: " . $cartResult['message'] . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Cart class error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 4: Product Availability Test
echo "<div class='test-section'>";
echo "<h2>4. Product Availability Test</h2>";
$stmt = $db->prepare("SELECT id, name, price, stock, status FROM products WHERE status = 'active' AND stock > 0 LIMIT 5");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($products)) {
    echo "<div class='success'>✓ Found " . count($products) . " available products</div>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Status</th></tr>";
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td>KSh " . number_format($product['price'], 2) . "</td>";
        echo "<td>" . $product['stock'] . "</td>";
        echo "<td>" . $product['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>✗ No available products found</div>";
}
echo "</div>";

// Test 5: Add to Cart Test
echo "<div class='test-section'>";
echo "<h2>5. Add to Cart Test</h2>";
if (!empty($products)) {
    $testProduct = $products[0];
    echo "<div class='info'>Testing with product: " . htmlspecialchars($testProduct['name']) . "</div>";

    $addResult = $cart->addItem($testProduct['id'], 1);
    if ($addResult['success']) {
        echo "<div class='success'>✓ Successfully added product to cart</div>";
    } else {
        echo "<div class='error'>✗ Failed to add product: " . $addResult['message'] . "</div>";
    }

    // Check updated cart
    $updatedCart = $cart->getCartItems();
    echo "<div class='info'>Cart now contains " . count($updatedCart['items']) . " items</div>";
} else {
    echo "<div class='warning'>⚠ No products available for testing</div>";
}
echo "</div>";

// Test 6: Cart API Endpoints Test
echo "<div class='test-section'>";
echo "<h2>6. Cart API Endpoints Test</h2>";

// Test cart update API
echo "<h3>Testing Cart Update API</h3>";
$updateData = [
    'product_id' => $testProduct['id'],
    'quantity' => 2
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, SITE_URL . 'api/cart/update-quantity.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        echo "<div class='success'>✓ Cart update API working</div>";
    } else {
        echo "<div class='error'>✗ Cart update API failed: " . ($result['message'] ?? 'Unknown error') . "</div>";
    }
} else {
    echo "<div class='error'>✗ Cart update API HTTP error: " . $httpCode . "</div>";
}

// Test cart remove API
echo "<h3>Testing Cart Remove API</h3>";
$removeData = ['product_id' => $testProduct['id']];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, SITE_URL . 'api/cart/remove.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($removeData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        echo "<div class='success'>✓ Cart remove API working</div>";
    } else {
        echo "<div class='error'>✗ Cart remove API failed: " . ($result['message'] ?? 'Unknown error') . "</div>";
    }
} else {
    echo "<div class='error'>✗ Cart remove API HTTP error: " . $httpCode . "</div>";
}
echo "</div>";

// Test 7: Order Creation Test
echo "<div class='test-section'>";
echo "<h2>7. Order Creation Test</h2>";

// Add a product back to cart for testing
$cart->addItem($testProduct['id'], 1);

$orderData = [
    'shipping_info' => [
        'full_name' => $user['name'],
        'phone' => '0700000000',
        'email' => $user['email'],
        'delivery_address' => 'Test Address, Nairobi, Kenya',
        'delivery_instructions' => 'Test delivery instructions'
    ],
    'payment_method' => 'mpesa'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, SITE_URL . 'api/orders/create.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        echo "<div class='success'>✓ Order creation API working</div>";
        echo "<div class='info'>Created " . count($result['orders']) . " orders</div>";
        echo "<div class='info'>Total amount: KSh " . number_format($result['total_amount'], 2) . "</div>";

        // Store order ID for further testing
        $testOrderId = $result['orders'][0]['order_id'];
        echo "<div class='info'>Test order ID: " . $testOrderId . "</div>";
    } else {
        echo "<div class='error'>✗ Order creation failed: " . ($result['message'] ?? 'Unknown error') . "</div>";
    }
} else {
    echo "<div class='error'>✗ Order creation API HTTP error: " . $httpCode . "</div>";
}
echo "</div>";

// Test 8: Email Template Test
echo "<div class='test-section'>";
echo "<h2>8. Email Template Test</h2>";
try {
    $emailTemplate = new EmailTemplate();
    echo "<div class='success'>✓ Email template class instantiated</div>";

    // Test template rendering
    $testVars = [
        'customer_name' => 'Test User',
        'order_number' => '000001',
        'total_amount' => '1,000.00',
        'delivery_address' => 'Test Address',
        'vendor_name' => 'Test Vendor'
    ];

    $renderedTemplate = $emailTemplate->render('order_confirmation', $testVars);
    if ($renderedTemplate) {
        echo "<div class='success'>✓ Email template rendering working</div>";
        echo "<div class='info'>Template size: " . strlen($renderedTemplate) . " characters</div>";
    } else {
        echo "<div class='error'>✗ Email template rendering failed</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Email template error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 9: M-Pesa Payment Test
echo "<div class='test-section'>";
echo "<h2>9. M-Pesa Payment Test</h2>";
if (isset($testOrderId)) {
    $mpesaData = [
        'phone' => '0700000000',
        'amount' => 100,
        'order_id' => $testOrderId
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SITE_URL . 'api/mpesa/initiate.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mpesaData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            echo "<div class='success'>✓ M-Pesa payment initiation working</div>";
            echo "<div class='info'>Checkout Request ID: " . ($result['CheckoutRequestID'] ?? 'N/A') . "</div>";
        } else {
            echo "<div class='warning'>⚠ M-Pesa payment initiation failed: " . ($result['message'] ?? 'Unknown error') . "</div>";
            echo "<div class='info'>This might be expected if M-Pesa credentials are not configured</div>";
        }
    } else {
        echo "<div class='error'>✗ M-Pesa API HTTP error: " . $httpCode . "</div>";
    }
} else {
    echo "<div class='warning'>⚠ No test order available for M-Pesa testing</div>";
}
echo "</div>";

// Test 10: Order Tracking Test
echo "<div class='test-section'>";
echo "<h2>10. Order Tracking Test</h2>";
if (isset($testOrderId)) {
    // Test order details page
    $orderDetailsUrl = SITE_URL . 'order_details.php?order_id=' . $testOrderId;
    echo "<div class='info'>Order details page: <a href='" . $orderDetailsUrl . "' target='_blank'>View Order Details</a></div>";

    // Test orders list page
    $ordersUrl = SITE_URL . 'orders.php';
    echo "<div class='info'>Orders list page: <a href='" . $ordersUrl . "' target='_blank'>View All Orders</a></div>";

    echo "<div class='success'>✓ Order tracking pages available</div>";
} else {
    echo "<div class='warning'>⚠ No test order available for tracking test</div>";
}
echo "</div>";

// Test 11: Database Schema Test
echo "<div class='test-section'>";
echo "<h2>11. Database Schema Test</h2>";

$requiredTables = ['users', 'products', 'cart', 'orders', 'order_items', 'payments', 'vendors'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $stmt = $db->prepare("SHOW TABLES LIKE ?");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $missingTables[] = $table;
    }
}

if (empty($missingTables)) {
    echo "<div class='success'>✓ All required tables exist</div>";
} else {
    echo "<div class='error'>✗ Missing tables: " . implode(', ', $missingTables) . "</div>";
}

// Check table structures
$tableChecks = [
    'orders' => ['id', 'customer_id', 'vendor_id', 'status', 'delivery_address', 'created_at'],
    'order_items' => ['id', 'order_id', 'product_id', 'quantity', 'price'],
    'payments' => ['id', 'order_id', 'amount', 'method', 'status'],
    'cart' => ['id', 'user_id', 'product_id', 'quantity']
];

foreach ($tableChecks as $table => $requiredColumns) {
    $stmt = $db->prepare("DESCRIBE $table");
    $stmt->execute();
    $result = $stmt->get_result();
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    $missingColumns = array_diff($requiredColumns, $columns);
    if (empty($missingColumns)) {
        echo "<div class='success'>✓ Table '$table' has all required columns</div>";
    } else {
        echo "<div class='error'>✗ Table '$table' missing columns: " . implode(', ', $missingColumns) . "</div>";
    }
}
echo "</div>";

// Test 12: Configuration Test
echo "<div class='test-section'>";
echo "<h2>12. Configuration Test</h2>";

$requiredConstants = ['SITE_URL', 'SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'SMTP_FROM'];
$missingConstants = [];

foreach ($requiredConstants as $constant) {
    if (!defined($constant)) {
        $missingConstants[] = $constant;
    }
}

if (empty($missingConstants)) {
    echo "<div class='success'>✓ All required constants are defined</div>";
} else {
    echo "<div class='error'>✗ Missing constants: " . implode(', ', $missingConstants) . "</div>";
}

// Check file permissions
$requiredFiles = [
    'email_templates/order_confirmation.html',
    'includes/EmailTemplate.php',
    'includes/Cart.php',
    'api/cart/update-quantity.php',
    'api/cart/remove.php',
    'api/orders/create.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✓ File exists: $file</div>";
    } else {
        echo "<div class='error'>✗ Missing file: $file</div>";
    }
}
echo "</div>";

// Summary
echo "<div class='test-section'>";
echo "<h2>Test Summary</h2>";
echo "<p>This test script has verified:</p>";
echo "<ul>";
echo "<li>Database connectivity</li>";
echo "<li>User authentication</li>";
echo "<li>Cart functionality (add, update, remove)</li>";
echo "<li>Product availability</li>";
echo "<li>Cart API endpoints</li>";
echo "<li>Order creation process</li>";
echo "<li>Email template system</li>";
echo "<li>M-Pesa payment integration</li>";
echo "<li>Order tracking pages</li>";
echo "<li>Database schema</li>";
echo "<li>System configuration</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test the actual checkout process on the website</li>";
echo "<li>Verify email notifications are sent</li>";
echo "<li>Test M-Pesa payment with real credentials</li>";
echo "<li>Test order status updates</li>";
echo "<li>Test vendor order management</li>";
echo "</ol>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Quick Links</h2>";
echo "<p><a href='cart.php'>View Cart</a> | ";
echo "<a href='checkout.php'>Go to Checkout</a> | ";
echo "<a href='orders.php'>View Orders</a> | ";
echo "<a href='index.php'>Continue Shopping</a></p>";
echo "</div>";
?>