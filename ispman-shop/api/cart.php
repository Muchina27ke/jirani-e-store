<?php
/**
 * AfriGear.tech — Cart API
 * Handles: add, remove, update, get
 * All responses: JSON
 */
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Cart.php';

// Session ID for guest carts
if (empty($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = bin2hex(random_bytes(16));
}
$sessionId = $_SESSION['cart_session_id'];
$userId    = $_SESSION['user_id'] ?? null;

$pdo  = getPDO();
$cart = new Cart($pdo);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        case 'add':
            $productId = (int)($_POST['product_id'] ?? 0);
            $qty       = max(1, (int)($_POST['qty'] ?? 1));
            if ($productId < 1) { echo json_encode(['success' => false, 'message' => 'Invalid product']); exit; }
            $ok = $cart->add($productId, $qty, $userId, $sessionId);
            echo json_encode([
                'success' => $ok,
                'message' => $ok ? 'Added to cart' : 'Could not add product (out of stock or invalid)',
                'count'   => $cart->getCount($userId, $sessionId),
            ]);
            break;

        case 'remove':
            $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
            if ($cartItemId < 1) { echo json_encode(['success' => false, 'message' => 'Invalid item']); exit; }
            $ok = $cart->remove($cartItemId, $userId, $sessionId);
            echo json_encode([
                'success'  => $ok,
                'count'    => $cart->getCount($userId, $sessionId),
                'subtotal' => $cart->getTotal($userId, $sessionId),
            ]);
            break;

        case 'update':
            $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
            $qty        = (int)($_POST['qty'] ?? 1);
            if ($cartItemId < 1) { echo json_encode(['success' => false, 'message' => 'Invalid item']); exit; }
            $ok = $cart->updateQty($cartItemId, $qty, $userId, $sessionId);
            // Return updated row subtotal
            $items = $cart->getItems($userId, $sessionId);
            $rowSubtotal = 0;
            foreach ($items as $item) {
                if ($item['cart_id'] == $cartItemId) {
                    $rowSubtotal = $item['price'] * $item['qty'];
                    break;
                }
            }
            echo json_encode([
                'success'      => $ok,
                'count'        => $cart->getCount($userId, $sessionId),
                'cart_total'   => $cart->getTotal($userId, $sessionId),
                'row_subtotal' => $rowSubtotal,
            ]);
            break;

        case 'get':
            $items = $cart->getItems($userId, $sessionId);
            echo json_encode([
                'success' => true,
                'count'   => $cart->getCount($userId, $sessionId),
                'total'   => $cart->getTotal($userId, $sessionId),
                'items'   => $items,
            ]);
            break;

        case 'clear':
            $ok = $cart->clear($userId, $sessionId);
            echo json_encode(['success' => $ok, 'count' => 0]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
