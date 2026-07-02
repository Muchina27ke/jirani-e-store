<?php
/**
 * M-Pesa Sandbox Simulation — confirm payment
 * Called by frontend countdown timer to finalize the simulated payment.
 * Marks payment as Completed and order as confirmed.
 */
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$checkoutRequestId = $data['checkout_request_id'] ?? '';
$orderId           = (int) ($data['order_id'] ?? 0);

if (!$checkoutRequestId || !$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing checkout_request_id or order_id']);
    exit;
}

// Only allow simulation IDs (safety check)
if (strpos($checkoutRequestId, 'SIM_') !== 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Not a simulation request']);
    exit;
}

// Verify this payment belongs to the logged-in user's order
$stmt = $conn->prepare("
    SELECT p.id as payment_id, p.status, o.customer_id, o.total_amount
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.order_id = ? AND p.mpesa_transaction_id = ?
    LIMIT 1
");
$stmt->bind_param("is", $orderId, $checkoutRequestId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    echo json_encode(['success' => false, 'message' => 'Payment record not found']);
    exit;
}

if ((int)$payment['customer_id'] !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($payment['status'] === 'Completed') {
    // Already confirmed, just return success
    echo json_encode(['success' => true, 'message' => 'Payment already confirmed', 'order_id' => $orderId]);
    exit;
}

// Begin transaction
$conn->begin_transaction();
try {
    // 1. Update payment status → Completed
    $stmt2 = $conn->prepare("UPDATE payments SET status = 'Completed', updated_at = NOW() WHERE id = ?");
    $stmt2->bind_param("i", $payment['payment_id']);
    $stmt2->execute();

    // 2. Update order status → confirmed
    $stmt3 = $conn->prepare("UPDATE orders SET status = 'confirmed', updated_at = NOW() WHERE id = ?");
    $stmt3->bind_param("i", $orderId);
    $stmt3->execute();

    // 3. Update escrow → still Held (will be released after delivery)
    // No change needed — already Held

    // 4. Log the simulated payment
    $logDetails = json_encode([
        'simulated'           => true,
        'checkout_request_id' => $checkoutRequestId,
        'confirmed_at'        => date('Y-m-d H:i:s'),
        'user_id'             => $_SESSION['user_id'],
    ]);
    $stmt4 = $conn->prepare("INSERT INTO payment_logs (order_id, action, details, created_at) VALUES (?, 'simulation_confirmed', ?, NOW())");
    $stmt4->bind_param("is", $orderId, $logDetails);
    $stmt4->execute();

    $conn->commit();
    echo json_encode([
        'success'  => true,
        'message'  => 'Payment confirmed successfully (simulated)',
        'order_id' => $orderId,
        'status'   => 'Completed',
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Simulation confirm error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to confirm payment. Please try again.']);
}
