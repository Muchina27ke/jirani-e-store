<?php
require_once '../../config/config.php';
require_once '../../includes/Mpesa.php';
require_once '../../includes/Order.php'; // Include Order class

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to make a payment']);
    exit;
}

// Get request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['phone']) || !isset($data['amount']) || !isset($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$orderId = $data['order_id'];
$amount = $data['amount'];
$phone = $data['phone'];
$userId = $_SESSION['user_id'];

try {
    $db = getDbConnection(); // Get the mysqli connection
    $orderObj = new Order($db);

    // Verify the order exists and belongs to the customer
    $existingOrder = $orderObj->get_order_by_id($orderId);
    if (!$existingOrder || $existingOrder['customer_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID or unauthorized access.']);
        exit;
    }

    // Check if the order already has a pending escrow payment
    $paymentQuery = $db->prepare("SELECT id FROM payments WHERE order_id = ? AND is_escrow = 1 AND status = 'Pending'");
    $paymentQuery->bind_param("i", $orderId);
    $paymentQuery->execute();
    $paymentResult = $paymentQuery->get_result();
    if ($paymentResult->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'A pending payment already exists for this order.']);
        exit;
    }

    // Initiate M-Pesa payment using Daraja API
    require_once dirname(__DIR__, 2) . '/includes/DarajaMpesa.php';
    $darajaMpesa = new DarajaMpesa($db);
    
    $result = $darajaMpesa->stkPush(
        $phone,
        $amount,
        'ORDER_' . $orderId,
        'Payment for Order #' . $orderId
    );

    if ($result['success']) {
        // Create payment record and escrow record
        $mpesa_transaction_id = $result['checkout_request_id'] ?? null; // Use CheckoutRequestID from Daraja
        $payment_method = "M-Pesa";
        $payment_status = "Pending"; // Initial status for M-Pesa STK Push
        $is_escrow = 1;
        $escrow_status = "Held";

        $query_payment = "INSERT INTO payments (order_id, amount, mpesa_transaction_id, status, method, is_escrow) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_payment = $db->prepare($query_payment);
        $stmt_payment->bind_param("idsssi", $orderId, $amount, $mpesa_transaction_id, $payment_status, $payment_method, $is_escrow);
        $stmt_payment->execute();

        $query_escrow = "INSERT INTO escrow_payments (order_id, escrow_status) VALUES (?, ?)";
        $stmt_escrow = $db->prepare($query_escrow);
        $stmt_escrow->bind_param("is", $orderId, $escrow_status);
        $stmt_escrow->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Payment initiated successfully',
            'CheckoutRequestID' => $mpesa_transaction_id,
            'order_id' => $orderId,
            'customer_message' => $result['customer_message'] ?? ''
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to initiate M-Pesa payment']);
    }

} catch (Exception $e) {
    error_log("M-Pesa payment initiation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to initiate payment due to an internal error.']);
}