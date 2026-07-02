<?php
/**
 * M-Pesa Payment Initiation — routes to simulation or real Daraja based on environment
 */
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to make a payment']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['phone'], $data['amount'], $data['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: phone, amount, order_id']);
    exit;
}

// Route to sandbox simulation or real Daraja
if (defined('MPESA_SIMULATE') && MPESA_SIMULATE) {
    // Forward to simulation endpoint internally
    $_POST = $data;
    $input = file_get_contents('php://input');
    
    // Relay the request to simulate.php by including it
    ob_start();
    include __DIR__ . '/simulate.php';
    $response = ob_get_clean();
    echo $response;
    exit;
}

// ── Real Daraja API path ───────────────────────────────────
$orderId = (int) $data['order_id'];
$amount  = (float) $data['amount'];
$phone   = $data['phone'];
$userId  = (int) $_SESSION['user_id'];

try {
    $orderObj = new Order($conn);
    $existingOrder = $orderObj->get_order_by_id($orderId);
    if (!$existingOrder || (int)$existingOrder['customer_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID or unauthorized access.']);
        exit;
    }

    // Check for existing pending payment
    $stmt = $conn->prepare("SELECT id FROM payments WHERE order_id = ? AND is_escrow = 1 AND status = 'Pending'");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'A pending payment already exists for this order.']);
        exit;
    }

    $result = $darajaMpesa->stkPush(
        $phone,
        $amount,
        'ORDER_' . $orderId,
        'Payment for Order #' . $orderId
    );

    if ($result['success']) {
        $mpesa_transaction_id = $result['checkout_request_id'] ?? null;
        $stmt2 = $conn->prepare("INSERT INTO payments (order_id, amount, mpesa_transaction_id, status, method, is_escrow) VALUES (?, ?, ?, 'Pending', 'M-Pesa', 1)");
        $stmt2->bind_param("ids", $orderId, $amount, $mpesa_transaction_id);
        $stmt2->execute();

        $stmt3 = $conn->prepare("INSERT IGNORE INTO escrow_payments (order_id, escrow_status) VALUES (?, 'Held')");
        $stmt3->bind_param("i", $orderId);
        $stmt3->execute();

        echo json_encode([
            'success'           => true,
            'simulated'         => false,
            'CheckoutRequestID' => $mpesa_transaction_id,
            'order_id'          => $orderId,
            'customer_message'  => $result['customer_message'] ?? 'STK Push sent to your phone',
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to initiate M-Pesa payment']);
    }

} catch (Exception $e) {
    error_log("M-Pesa initiation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment service temporarily unavailable.']);
}