<?php
require_once '../config/config.php';
require_once '../includes/Mpesa.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get callback data
    $callbackData = json_decode(file_get_contents('php://input'), true);
    
    if (!$callbackData) {
        // Try to get from POST data
        $callbackData = $_POST;
    }
    
    // Log callback for debugging
    error_log("M-Pesa Callback: " . json_encode($callbackData));
    
    if (empty($callbackData)) {
        throw new Exception('No callback data received');
    }
    
    // Process Daraja M-Pesa callback
    require_once dirname(__DIR__, 2) . '/includes/DarajaMpesa.php';
    
    // Extract callback data from Daraja format
    $stkCallback = $callbackData['Body']['stkCallback'] ?? null;
    if (!$stkCallback) {
        throw new Exception('Invalid Daraja callback format');
    }
    
    $checkoutRequestID = $stkCallback['CheckoutRequestID'] ?? null;
    $resultCode = $stkCallback['ResultCode'] ?? null;
    $resultDesc = $stkCallback['ResultDesc'] ?? null;
    
    // Process the callback based on result code
    if ($resultCode === 0) {
        // Payment successful
        $result = ['success' => true, 'message' => 'Payment completed successfully'];
        
        // Update payment status in database
        $stmt = $conn->prepare("UPDATE payments SET status = 'Completed', updated_at = NOW() WHERE mpesa_transaction_id = ?");
        $stmt->bind_param('s', $checkoutRequestID);
        $stmt->execute();
        
        // Update order status
        $stmt = $conn->prepare("UPDATE orders o JOIN payments p ON o.id = p.order_id SET o.status = 'Paid' WHERE p.mpesa_transaction_id = ?");
        $stmt->bind_param('s', $checkoutRequestID);
        $stmt->execute();
    } else {
        // Payment failed or cancelled
        $result = ['success' => false, 'message' => $resultDesc ?: 'Payment failed'];
        
        // Update payment status
        $stmt = $conn->prepare("UPDATE payments SET status = 'Failed', updated_at = NOW() WHERE mpesa_transaction_id = ?");
        $stmt->bind_param('s', $checkoutRequestID);
        $stmt->execute();
    }
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'Callback processed successfully']);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("M-Pesa callback error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>

