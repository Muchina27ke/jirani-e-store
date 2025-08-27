<?php
require_once '../../config/config.php';
require_once '../../includes/Cart.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to update cart']);
    exit;
}

// Get request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id']) || !isset($data['quantity'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Product ID and quantity are required']);
    exit;
}

$productId = (int) $data['product_id'];
$quantity = (int) $data['quantity'];

if ($quantity < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit;
}

try {
    $cart = new Cart($conn, $_SESSION['user_id']);
    $result = $cart->updateQuantity($productId, $quantity);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'cart_count' => $cart->getCartCount()
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
} catch (Exception $e) {
    error_log("Cart update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}