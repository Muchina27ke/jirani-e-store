<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';

// Test the cart and checkout process
$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    echo "Please log in first";
    exit;
}

$user = $auth->getUser();

echo "<h2>Testing Cart and Checkout for User: " . $user['name'] . " (ID: " . $user['id'] . ")</h2>";

// Get some products to add to cart
$stmt = $conn->prepare("
    SELECT p.*, v.business_name 
    FROM products p 
    JOIN vendors v ON p.vendor_id = v.user_id 
    WHERE p.status = 'active' AND v.status = 'approved' 
    LIMIT 3
");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($products)) {
    echo "<p>No products available</p>";
    exit;
}

echo "<h3>Available Products:</h3>";
echo "<table border='1'>";
echo "<tr><th>Product</th><th>Price</th><th>Stock</th><th>Vendor</th><th>Action</th></tr>";
foreach ($products as $product) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td>KSh " . number_format($product['price'], 2) . "</td>";
    echo "<td>" . $product['stock'] . "</td>";
    echo "<td>" . htmlspecialchars($product['business_name']) . "</td>";
    echo "<td><a href='?add_to_cart=" . $product['id'] . "'>Add to Cart</a></td>";
    echo "</tr>";
}
echo "</table>";

// Handle add to cart
if (isset($_GET['add_to_cart'])) {
    $productId = $_GET['add_to_cart'];

    // Add to cart
    $stmt = $conn->prepare("
        INSERT INTO cart (user_id, product_id, quantity) 
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE quantity = quantity + 1
    ");
    $stmt->bind_param("ii", $user['id'], $productId);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>Product added to cart successfully!</p>";
    } else {
        echo "<p style='color: red;'>Failed to add product to cart</p>";
    }
}

// Show current cart
echo "<h3>Current Cart:</h3>";
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

if (empty($cartItems)) {
    echo "<p>Cart is empty</p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Vendor</th></tr>";
    $total = 0;
    foreach ($cartItems as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $total += $subtotal;
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>KSh " . number_format($item['price'], 2) . "</td>";
        echo "<td>" . $item['quantity'] . "</td>";
        echo "<td>KSh " . number_format($subtotal, 2) . "</td>";
        echo "<td>" . htmlspecialchars($item['vendor_name']) . "</td>";
        echo "</tr>";
    }
    echo "<tr><td colspan='3'><strong>Total</strong></td><td><strong>KSh " . number_format($total, 2) . "</strong></td><td></td></tr>";
    echo "</table>";

    echo "<h3>Test Checkout API</h3>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='test_checkout' value='1'>";
    echo "<button type='submit'>Test Checkout API</button>";
    echo "</form>";
}

// Test checkout API
if (isset($_POST['test_checkout'])) {
    echo "<h3>Testing Checkout API...</h3>";

    // Prepare test data
    $testData = [
        'shipping_info' => [
            'full_name' => $user['name'],
            'phone' => $user['phone'],
            'email' => $user['email'] ?? 'test@example.com',
            'delivery_address' => 'Test Address, Kakamega',
            'delivery_instructions' => 'Test instructions'
        ],
        'payment_method' => 'cash'
    ];

    // Make API call using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/jirani/api/orders/create.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE'] ?? '');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";

    // Also test direct database call
    echo "<h3>Direct Database Test:</h3>";
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.vendor_id, v.business_name as vendor_name
        FROM cart c
        JOIN products p ON c.product_id = p.id
        JOIN vendors v ON p.vendor_id = v.user_id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $directCartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo "<p>Direct DB Cart Items: " . count($directCartItems) . "</p>";
    if (!empty($directCartItems)) {
        echo "<ul>";
        foreach ($directCartItems as $item) {
            echo "<li>" . htmlspecialchars($item['name']) . " - KSh " . number_format($item['price'], 2) . "</li>";
        }
        echo "</ul>";
    }
}
?>