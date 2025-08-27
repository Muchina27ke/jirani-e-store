<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';

echo "<h2>Session Debug Information</h2>";

// Check session status
echo "<h3>Session Information:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session Name: " . session_name() . "</p>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";

// Check session data
echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check Auth class
echo "<h3>Auth Class Test:</h3>";
$auth = new Auth($conn);

echo "<p>Is Logged In: " . ($auth->isLoggedIn() ? 'Yes' : 'No') . "</p>";

if ($auth->isLoggedIn()) {
    $user = $auth->getUser();
    echo "<p>User ID: " . $user['id'] . "</p>";
    echo "<p>User Email: " . $user['email'] . "</p>";
    echo "<p>User Role: " . $user['role'] . "</p>";

    // Check cart items
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

    echo "<p>Cart Items Count: " . count($cartItems) . "</p>";

    if (!empty($cartItems)) {
        echo "<h4>Cart Items:</h4>";
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
    }
} else {
    echo "<p>Not logged in</p>";
}

// Check cookies
echo "<h3>Cookies:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Check if there are any cart items in the database at all
echo "<h3>All Cart Items in Database:</h3>";
$stmt = $conn->prepare("
    SELECT c.*, p.name, u.name as user_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 10
");
$stmt->execute();
$allCartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($allCartItems)) {
    echo "<table border='1'>";
    echo "<tr><th>User</th><th>Product</th><th>Quantity</th><th>Created</th></tr>";
    foreach ($allCartItems as $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['user_name']) . " (ID: " . $item['user_id'] . ")</td>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>" . $item['quantity'] . "</td>";
        echo "<td>" . $item['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No cart items found in database</p>";
}
?>