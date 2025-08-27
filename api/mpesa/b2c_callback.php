<?php
/**
 * Daraja API B2C (Business to Customer) Callback Handler
 * Handles callbacks from Safaricom Daraja API for B2C payments (payouts to vendors)
 */

header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/config/config.php';

try {
    // Get the callback data from Safaricom
    $callbackData = json_decode(file_get_contents('php://input'), true);
    
    // Log the callback for debugging
    error_log("Daraja B2C Callback: " . json_encode($callbackData));
    
    if (empty($callbackData)) {
        throw new Exception('No callback data received');
    }
    
    // Extract B2C callback data
    $result = $callbackData['Result'] ?? null;
    if (!$result) {
        throw new Exception('Invalid B2C callback format');
    }
    
    $conversationID = $result['ConversationID'] ?? null;
    $originatorConversationID = $result['OriginatorConversationID'] ?? null;
    $resultCode = $result['ResultCode'] ?? null;
    $resultDesc = $result['ResultDesc'] ?? null;
    
    if (!$conversationID) {
        throw new Exception('Missing ConversationID in callback');
    }
    
    // Process the callback based on result code
    if ($resultCode === 0) {
        // B2C payment successful
        $resultParameters = $result['ResultParameters']['ResultParameter'] ?? [];
        $transactionID = null;
        $transactionAmount = null;
        $transactionReceipt = null;
        $recipientNumber = null;
        $b2cCharges = null;
        $b2cUtilityBalance = null;
        $b2cWorkingBalance = null;
        
        foreach ($resultParameters as $param) {
            switch ($param['Key']) {
                case 'TransactionID':
                    $transactionID = $param['Value'];
                    break;
                case 'TransactionAmount':
                    $transactionAmount = $param['Value'];
                    break;
                case 'TransactionReceipt':
                    $transactionReceipt = $param['Value'];
                    break;
                case 'ReceiverPartyPublicName':
                    $recipientNumber = $param['Value'];
                    break;
                case 'B2CChargesPaidAccountAvailableFunds':
                    $b2cCharges = $param['Value'];
                    break;
                case 'B2CUtilityAccountAvailableFunds':
                    $b2cUtilityBalance = $param['Value'];
                    break;
                case 'B2CWorkingAccountAvailableFunds':
                    $b2cWorkingBalance = $param['Value'];
                    break;
            }
        }
        
        // Update payout record if exists
        $stmt = $conn->prepare("UPDATE vendor_payouts SET 
            status = 'Completed',
            transaction_id = ?,
            transaction_receipt = ?,
            completed_at = NOW(),
            updated_at = NOW()
            WHERE conversation_id = ?");
        $stmt->bind_param('sss', $transactionID, $transactionReceipt, $conversationID);
        $stmt->execute();
        
        // Log successful B2C payment
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
        $action = 'b2c_payment_success';
        $details = json_encode([
            'conversation_id' => $conversationID,
            'transaction_id' => $transactionID,
            'transaction_receipt' => $transactionReceipt,
            'amount' => $transactionAmount,
            'recipient' => $recipientNumber
        ]);
        $stmt->bind_param('ss', $action, $details);
        $stmt->execute();
        
        $response = ['success' => true, 'message' => 'B2C payment completed successfully'];
        
    } else {
        // B2C payment failed
        $stmt = $conn->prepare("UPDATE vendor_payouts SET 
            status = 'Failed',
            failure_reason = ?,
            updated_at = NOW()
            WHERE conversation_id = ?");
        $stmt->bind_param('ss', $resultDesc, $conversationID);
        $stmt->execute();
        
        // Log failed B2C payment
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
        $action = 'b2c_payment_failed';
        $details = json_encode([
            'conversation_id' => $conversationID,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc
        ]);
        $stmt->bind_param('ss', $action, $details);
        $stmt->execute();
        
        $response = ['success' => false, 'message' => $resultDesc ?: 'B2C payment failed'];
    }
    
    // Send response to Safaricom
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("B2C Callback Error: " . $e->getMessage());
    
    // Log error
    if (isset($conn)) {
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
        $action = 'b2c_callback_error';
        $details = json_encode(['error' => $e->getMessage(), 'callback_data' => $callbackData ?? null]);
        $stmt->bind_param('ss', $action, $details);
        $stmt->execute();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
