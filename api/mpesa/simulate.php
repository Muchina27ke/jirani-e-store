<?php
/**
 * M-Pesa Sandbox Simulation — initiate fake STK push
 * Returns a simulated CheckoutRequestID without calling Safaricom.
 * Only active when MPESA_SIMULATE = true (DARAJA_ENVIRONMENT != 'production')
 */
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to make a payment']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['order_id'], $data['amount'], $data['phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: order_id, amount, phone']);
    exit;
}

$orderId = (int) $data['order_id'];
$amount  = (float) $data['amount'];
$phone   = preg_replace('/[^0-9]/', '', $data['phone']);
$userId  = (int) $_SESSION['user_id'];

// Verify order belongs to user
$stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND customer_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$existingOrder = $stmt->get_result()->fetch_assoc();
if (!$existingOrder) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid order or access denied']);
    exit;
}

// Generate a simulated CheckoutRequestID
$checkoutRequestId = 'SIM_' . strtoupper(bin2hex(random_bytes(8))) . '_' . time();

// Check if a pending payment already exists
$stmt = $conn->prepare("SELECT id FROM payments WHERE order_id = ? AND status = 'Pending'");
$stmt->bind_param("i", $orderId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    // Update existing pending payment with new simulation ID
    $stmt2 = $conn->prepare("UPDATE payments SET mpesa_transaction_id = ? WHERE order_id = ? AND status = 'Pending'");
    $stmt2->bind_param("si", $checkoutRequestId, $orderId);
    $stmt2->execute();
} else {
    // Insert new payment record
    $stmt2 = $conn->prepare("INSERT INTO payments (order_id, amount, mpesa_transaction_id, status, method, is_escrow) VALUES (?, ?, ?, 'Pending', 'M-Pesa', 1)");
    $stmt2->bind_param("ids", $orderId, $amount, $checkoutRequestId);
    $stmt2->execute();
}

// Ensure escrow record exists
$stmt3 = $conn->prepare("INSERT IGNORE INTO escrow_payments (order_id, escrow_status) VALUES (?, 'Held')");
$stmt3->bind_param("i", $orderId);
$stmt3->execute();

echo json_encode([
    'success'            => true,
    'simulated'          => true,
    'CheckoutRequestID'  => $checkoutRequestId,
    'order_id'           => $orderId,
    'customer_message'   => 'STK Push simulated. Confirm in the dialog.',
    'amount'             => $amount,
    'phone'              => $phone,
    'auto_confirm_delay' => 8, // seconds until auto-confirm
]);
