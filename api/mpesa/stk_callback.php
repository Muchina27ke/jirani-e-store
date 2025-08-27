<?php
/**
 * Daraja API STK Push Callback Handler
 * Handles callbacks from Safaricom Daraja API for STK Push payments
 */

header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/config/config.php';

try {
    // Get the callback data from Safaricom
    $callbackData = json_decode(file_get_contents('php://input'), true);
    
    // Log the callback for debugging
    error_log("Daraja STK Callback: " . json_encode($callbackData));
    
    if (empty($callbackData)) {
        throw new Exception('No callback data received');
    }
    
    // Extract STK callback data
    $stkCallback = $callbackData['Body']['stkCallback'] ?? null;
    if (!$stkCallback) {
        throw new Exception('Invalid STK callback format');
    }
    
    $checkoutRequestID = $stkCallback['CheckoutRequestID'] ?? null;
    $resultCode = $stkCallback['ResultCode'] ?? null;
    $resultDesc = $stkCallback['ResultDesc'] ?? null;
    
    if (!$checkoutRequestID) {
        throw new Exception('Missing CheckoutRequestID in callback');
    }
    
    // Process the callback based on result code
    if ($resultCode === 0) {
        // Payment successful - extract transaction details
        $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
        $mpesaReceiptNumber = null;
        $transactionDate = null;
        $phoneNumber = null;
        $amount = null;
        
        foreach ($callbackMetadata as $item) {
            switch ($item['Name']) {
                case 'MpesaReceiptNumber':
                    $mpesaReceiptNumber = $item['Value'];
                    break;
                case 'TransactionDate':
                    $transactionDate = $item['Value'];
                    break;
                case 'PhoneNumber':
                    $phoneNumber = $item['Value'];
                    break;
                case 'Amount':
                    $amount = $item['Value'];
                    break;
            }
        }
        
        // Update payment status in database
        $stmt = $conn->prepare("UPDATE payments SET 
            status = 'Completed', 
            mpesa_receipt_number = ?, 
            transaction_date = ?, 
            updated_at = NOW() 
            WHERE mpesa_transaction_id = ?");
        $stmt->bind_param('sss', $mpesaReceiptNumber, $transactionDate, $checkoutRequestID);
        $stmt->execute();
        
        // Update order status to 'Paid'
        $stmt = $conn->prepare("UPDATE orders o 
            JOIN payments p ON o.id = p.order_id 
            SET o.status = 'Paid', o.updated_at = NOW() 
            WHERE p.mpesa_transaction_id = ?");
        $stmt->bind_param('s', $checkoutRequestID);
        $stmt->execute();
        
        // Log successful payment
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
        $action = 'mpesa_payment_success';
        $details = json_encode([
            'checkout_request_id' => $checkoutRequestID,
            'mpesa_receipt_number' => $mpesaReceiptNumber,
            'amount' => $amount,
            'phone_number' => $phoneNumber
        ]);
        $stmt->bind_param('ss', $action, $details);
        $stmt->execute();
        
        $response = ['success' => true, 'message' => 'Payment completed successfully'];
        
    } else {
        // Payment failed or cancelled
        $stmt = $conn->prepare("UPDATE payments SET 
            status = 'Failed', 
            failure_reason = ?, 
            updated_at = NOW() 
            WHERE mpesa_transaction_id = ?");
        $stmt->bind_param('ss', $resultDesc, $checkoutRequestID);
        $stmt->execute();
        
        // Update order status to 'Payment Failed'
        $stmt = $conn->prepare("UPDATE orders o 
            JOIN payments p ON o.id = p.order_id 
            SET o.status = 'Payment Failed', o.updated_at = NOW() 
            WHERE p.mpesa_transaction_id = ?");
        $stmt->bind_param('s', $checkoutRequestID);
        $stmt->execute();
        
        // Log failed payment
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
        $action = 'mpesa_payment_failed';
        $details = json_encode([
            'checkout_request_id' => $checkoutRequestID,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc
        ]);
        $stmt->bind_param('ss', $action, $details);
        $stmt->execute();
        
        $response = ['success' => false, 'message' => $resultDesc ?: 'Payment failed'];
    }
    
    // Send response to Safaricom
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("STK Callback Error: " . $e->getMessage());
    
    // Log error
    if (isset($conn)) {
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
        $action = 'mpesa_callback_error';
        $details = json_encode(['error' => $e->getMessage(), 'callback_data' => $callbackData ?? null]);
        $stmt->bind_param('ss', $action, $details);
        $stmt->execute();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
