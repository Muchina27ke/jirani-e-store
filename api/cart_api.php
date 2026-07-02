<?php
/**
 * Unified Cart API — supports add, remove, update, count, clear via action param
 */
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$cartObj = new Cart($conn, $_SESSION['user_id']);
$action  = $_GET['action'] ?? (json_decode(file_get_contents('php://input'), true)['action'] ?? '');

if ($action === 'count') {
    echo json_encode(['success' => true, 'count' => $cartObj->getCartCount()]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {
    case 'add':
        $productId = (int) ($data['product_id'] ?? 0);
        $quantity  = (int) ($data['quantity'] ?? 1);
        $result    = $cartObj->addItem($productId, $quantity);
        $result['cart_count'] = $cartObj->getCartCount();
        echo json_encode($result);
        break;

    case 'remove':
        $productId = (int) ($data['product_id'] ?? 0);
        $result    = $cartObj->removeItem($productId);
        $result['cart_count'] = $cartObj->getCartCount();
        echo json_encode($result);
        break;

    case 'update':
        $productId = (int) ($data['product_id'] ?? 0);
        $quantity  = (int) ($data['quantity'] ?? 1);
        $result    = $cartObj->updateQuantity($productId, $quantity);
        $result['cart_count'] = $cartObj->getCartCount();
        echo json_encode($result);
        break;

    case 'clear':
        $result = $cartObj->clearCart();
        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}