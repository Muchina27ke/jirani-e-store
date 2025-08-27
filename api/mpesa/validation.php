<?php
/**
 * Daraja API C2B Validation Handler
 * Validates C2B (Customer to Business) payments before processing
 */

header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/config/config.php';

try {
    // Get the validation data from Safaricom
    $validationData = json_decode(file_get_contents('php://input'), true);
    
    // Log the validation request for debugging
    error_log("Daraja C2B Validation: " . json_encode($validationData));
    
    if (empty($validationData)) {
        throw new Exception('No validation data received');
    }
    
    // Extract validation parameters
    $transactionType = $validationData['TransactionType'] ?? null;
    $transID = $validationData['TransID'] ?? null;
    $transTime = $validationData['TransTime'] ?? null;
    $transAmount = $validationData['TransAmount'] ?? null;
    $businessShortCode = $validationData['BusinessShortCode'] ?? null;
    $billRefNumber = $validationData['BillRefNumber'] ?? null;
    $invoiceNumber = $validationData['InvoiceNumber'] ?? null;
    $orgAccountBalance = $validationData['OrgAccountBalance'] ?? null;
    $thirdPartyTransID = $validationData['ThirdPartyTransID'] ?? null;
    $msisdn = $validationData['MSISDN'] ?? null;
    $firstName = $validationData['FirstName'] ?? null;
    $middleName = $validationData['MiddleName'] ?? null;
    $lastName = $validationData['LastName'] ?? null;
    
    // Basic validation checks
    if (!$transID || !$transAmount || !$msisdn) {
        throw new Exception('Missing required validation parameters');
    }
    
    // Validate transaction amount (minimum amount check)
    if ($transAmount < 1) {
        $response = [
            'ResultCode' => 'C2B00011',
            'ResultDesc' => 'Invalid Amount'
        ];
    }
    // Validate business short code
    elseif ($businessShortCode !== DARAJA_SHORTCODE) {
        $response = [
            'ResultCode' => 'C2B00012',
            'ResultDesc' => 'Invalid Account'
        ];
    }
    // Additional validation logic can be added here
    else {
        // Validation passed
        $response = [
            'ResultCode' => '0',
            'ResultDesc' => 'Accepted'
        ];
        
        // Log successful validation
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
        $action = 'c2b_validation_success';
        $details = json_encode([
            'trans_id' => $transID,
            'amount' => $transAmount,
            'msisdn' => $msisdn,
            'bill_ref_number' => $billRefNumber
        ]);
        $stmt->bind_param('ss', $action, $details);
        $stmt->execute();
    }
    
    // Send response to Safaricom
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("C2B Validation Error: " . $e->getMessage());
    
    // Log error
    if (isset($conn)) {
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
        $action = 'c2b_validation_error';
        $details = json_encode(['error' => $e->getMessage(), 'validation_data' => $validationData ?? null]);
        $stmt->bind_param('ss', $action, $details);
        $stmt->execute();
    }
    
    // Reject the transaction
    echo json_encode([
        'ResultCode' => 'C2B00013',
        'ResultDesc' => 'System Error'
    ]);
}
?>
