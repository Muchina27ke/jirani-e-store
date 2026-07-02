<?php
/**
 * AfriGear.tech — Create pending order from cart
 * POST: full_name, email, phone, delivery_address, town, county
 * Returns: JSON { success, order_id, total }
 */
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Cart.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Sanitise inputs
$fullName  = trim($_POST['full_name']        ?? '');
$email     = trim($_POST['email']            ?? '');
$phone     = trim($_POST['phone']            ?? '');
$address   = trim($_POST['delivery_address'] ?? '');
$town      = trim($_POST['town']             ?? '');
$county    = trim($_POST['county']           ?? '');

// Validate required fields
$errors = [];
if (!$fullName)  $errors[] = 'Full name is required';
if (!$phone)     $errors[] = 'Phone number is required';
if (!$address)   $errors[] = 'Delivery address is required';
if (!$town)      $errors[] = 'Town is required';
if (!$county)    $errors[] = 'County is required';

// Validate phone format
$cleanPhone = preg_replace('/\D/', '', $phone);
if ($cleanPhone && (strlen($cleanPhone) < 9 || strlen($cleanPhone) > 13)) {
    $errors[] = 'Invalid phone number format';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
    exit;
}

try {
    $pdo = getPDO();

    if (empty($_SESSION['cart_session_id'])) {
        echo json_encode(['success' => false, 'message' => 'Cart session not found']);
        exit;
    }
    $sessionId = $_SESSION['cart_session_id'];
    $userId    = $_SESSION['user_id'] ?? null;

    $cart  = new Cart($pdo);
    $items = $cart->getItems($userId, $sessionId);

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
        exit;
    }

    $subtotal    = $cart->getTotal($userId, $sessionId);
    $deliveryFee = DELIVERY_FEE;
    $total       = $subtotal + $deliveryFee;

    $pdo->beginTransaction();

    // Insert order
    $ins = $pdo->prepare("
        INSERT INTO orders
            (user_id, full_name, email, phone, delivery_address, town, county,
             subtotal, delivery_fee, total_amount, status)
        VALUES
            (:uid, :name, :email, :phone, :addr, :town, :county,
             :sub, :fee, :total, 'pending')
    ");
    $ins->execute([
        ':uid'    => $userId,
        ':name'   => $fullName,
        ':email'  => $email,
        ':phone'  => $phone,
        ':addr'   => $address,
        ':town'   => $town,
        ':county' => $county,
        ':sub'    => $subtotal,
        ':fee'    => $deliveryFee,
        ':total'  => $total,
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // Insert order items (snapshot prices at time of order)
    $insItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, brand, qty, unit_price)
        VALUES (:oid, :pid, :name, :brand, :qty, :price)
    ");
    foreach ($items as $item) {
        $insItem->execute([
            ':oid'   => $orderId,
            ':pid'   => $item['product_id'],
            ':name'  => $item['name'],
            ':brand' => $item['brand'] ?? '',
            ':qty'   => $item['qty'],
            ':price' => $item['price'],
        ]);
    }

    $pdo->commit();

    // Store order_id in session for post-payment use
    $_SESSION['pending_order_id'] = $orderId;

    echo json_encode([
        'success'  => true,
        'order_id' => $orderId,
        'total'    => $total,
        'subtotal' => $subtotal,
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Order create error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create order. Please try again.']);
}
