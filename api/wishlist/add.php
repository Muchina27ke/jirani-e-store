<?php
require_once '../../config/config.php';
require_once '../../includes/Wishlist.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to add items to wishlist']);
    exit;
}

// Get request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$productId = (int) $data['product_id'];

try {
    $wishlist = new Wishlist($conn, $_SESSION['user_id']);
    $result = $wishlist->addItem($productId);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'wishlist_count' => $wishlist->getWishlistCount()
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
} catch (Exception $e) {
    error_log("Wishlist add error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}