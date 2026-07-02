<?php
/**
 * AfriGear.tech — M-Pesa STK Push initiator
 * POST: phone, amount, order_id  →  JSON { success, checkout_request_id, message }
 */
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/MpesaPayment.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$phone   = trim($_POST['phone']   ?? '');
$amount  = (float)($_POST['amount']   ?? 0);
$orderId = (int)($_POST['order_id']   ?? 0);

// Basic validation
if (!$phone || $amount < 1 || $orderId < 1) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate Kenyan phone
$cleanPhone = preg_replace('/\D/', '', $phone);
if (strlen($cleanPhone) < 9 || strlen($cleanPhone) > 13) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

try {
    $pdo = getPDO();

    // Verify order exists and belongs to this session
    $stmt = $pdo->prepare("SELECT id, total_amount, status FROM orders WHERE id = :id");
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    if ($order['status'] === 'paid') {
        echo json_encode(['success' => false, 'message' => 'Order already paid']);
        exit;
    }

    // Initiate STK Push
    $mpesa = new MpesaPayment([
        'consumer_key'    => MPESA_CONSUMER_KEY,
        'consumer_secret' => MPESA_CONSUMER_SECRET,
        'shortcode'       => MPESA_SHORTCODE,
        'passkey'         => MPESA_PASSKEY,
        'callback_url'    => MPESA_CALLBACK_URL,
        'env'             => MPESA_ENV,
    ]);

    $response = $mpesa->stkPush($phone, $amount, $orderId);

    if (!$response || ($response['ResponseCode'] ?? '1') !== '0') {
        $errMsg = $response['errorMessage'] ?? ($response['ResponseDescription'] ?? 'STK Push failed');
        echo json_encode(['success' => false, 'message' => $errMsg]);
        exit;
    }

    $checkoutRequestId  = $response['CheckoutRequestID'];
    $merchantRequestId  = $response['MerchantRequestID'];

    // Store pending transaction
    $ins = $pdo->prepare("
        INSERT INTO mpesa_transactions
            (order_id, checkout_request_id, merchant_request_id, phone, amount, status)
        VALUES (:oid, :crid, :mrid, :phone, :amount, 'pending')
        ON DUPLICATE KEY UPDATE
            order_id = VALUES(order_id),
            merchant_request_id = VALUES(merchant_request_id),
            status = 'pending',
            updated_at = CURRENT_TIMESTAMP
    ");
    $ins->execute([
        ':oid'   => $orderId,
        ':crid'  => $checkoutRequestId,
        ':mrid'  => $merchantRequestId,
        ':phone' => $phone,
        ':amount'=> $amount,
    ]);

    echo json_encode([
        'success'              => true,
        'checkout_request_id'  => $checkoutRequestId,
        'message'              => 'STK Push sent. Check your phone.',
    ]);

} catch (Throwable $e) {
    error_log('STK Push error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
