<?php
require_once '../../config/config.php';
require_once '../../includes/Mpesa.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to check payment status']);
    exit;
}

if (!isset($_GET['payment_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
    exit;
}

try {
    $mpesa = new Mpesa($conn);
    $result = $mpesa->verifyPayment($_GET['payment_id']);

    // Get order ID from payment record
    $stmt = $conn->prepare("
        SELECT order_id 
        FROM payments 
        WHERE mpesa_transaction_id = ?
    ");
    $stmt->bind_param("s", $_GET['payment_id']);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'status' => $result['status'],
        'order_id' => $payment['order_id'] ?? null
    ]);

} catch (Exception $e) {
    error_log("M-Pesa status check error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to check payment status']);
}