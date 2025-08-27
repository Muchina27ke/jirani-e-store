<?php
require_once '../../config/config.php';
require_once '../../includes/Cart.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}
$cart = new Cart($conn, $_SESSION['user_id']);
echo json_encode(['count' => $cart->getCartCount()]);