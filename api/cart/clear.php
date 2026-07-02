<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$cartObj = new Cart($conn, $_SESSION['user_id']);
$result  = $cartObj->clearCart();
echo json_encode($result);
