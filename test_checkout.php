<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';

// Test the checkout process
$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    echo "Please log in first";
    exit;
}

$user = $auth->getUser();

// Get cart items
$stmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.vendor_id, v.business_name as vendor_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN vendors v ON p.vendor_id = v.user_id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h2>Cart Items for User ID: " . $user['id'] . "</h2>";
if (empty($cartItems)) {
    echo "<p>No items in cart</p>";
    echo "<p><a href='index.php'>Go shopping</a></p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Vendor</th></tr>";
    foreach ($cartItems as $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>KSh " . number_format($item['price'], 2) . "</td>";
        echo "<td>" . $item['quantity'] . "</td>";
        echo "<td>" . htmlspecialchars($item['vendor_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>Test Checkout API</h3>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='test_checkout' value='1'>";
    echo "<button type='submit'>Test Checkout</button>";
    echo "</form>";
}

if (isset($_POST['test_checkout'])) {
    echo "<h3>Testing Checkout API...</h3>";

    // Prepare test data
    $testData = [
        'shipping_info' => [
            'full_name' => 'Test User',
            'phone' => '0712345678',
            'email' => 'test@example.com',
            'delivery_address' => 'Test Address, Kakamega',
            'delivery_instructions' => 'Test instructions'
        ],
        'payment_method' => 'cash'
    ];

    // Make API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/jirani/api/orders/create.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Cookie: ' . $_SERVER['HTTP_COOKIE'] ?? ''
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
?>