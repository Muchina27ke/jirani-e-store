<?php
/**
 * Daraja API C2B Confirmation Handler
 * Confirms and processes C2B (Customer to Business) payments
 */

header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/config/config.php';

try {
    // Get the confirmation data from Safaricom
    $confirmationData = json_decode(file_get_contents('php://input'), true);
    
    // Log the confirmation request for debugging
    error_log("Daraja C2B Confirmation: " . json_encode($confirmationData));
    
    if (empty($confirmationData)) {
        throw new Exception('No confirmation data received');
    }
    
    // Extract confirmation parameters
    $transactionType = $confirmationData['TransactionType'] ?? null;
    $transID = $confirmationData['TransID'] ?? null;
    $transTime = $confirmationData['TransTime'] ?? null;
    $transAmount = $confirmationData['TransAmount'] ?? null;
    $businessShortCode = $confirmationData['BusinessShortCode'] ?? null;
    $billRefNumber = $confirmationData['BillRefNumber'] ?? null;
    $invoiceNumber = $confirmationData['InvoiceNumber'] ?? null;
    $orgAccountBalance = $confirmationData['OrgAccountBalance'] ?? null;
    $thirdPartyTransID = $confirmationData['ThirdPartyTransID'] ?? null;
    $msisdn = $confirmationData['MSISDN'] ?? null;
    $firstName = $confirmationData['FirstName'] ?? null;
    $middleName = $confirmationData['MiddleName'] ?? null;
    $lastName = $confirmationData['LastName'] ?? null;
    
    // Basic validation
    if (!$transID || !$transAmount || !$msisdn) {
        throw new Exception('Missing required confirmation parameters');
    }
    
    // Process the confirmed payment
    // Check if this is for an existing order (using bill reference number)
    $orderId = null;
    if ($billRefNumber && preg_match('/ORDER_(\d+)/', $billRefNumber, $matches)) {
        $orderId = $matches[1];
    }
    
    if ($orderId) {
        // Update existing order payment
        $stmt = $conn->prepare("UPDATE payments SET 
            status = 'Completed',
            mpesa_receipt_number = ?,
            transaction_date = ?,
            updated_at = NOW()
            WHERE order_id = ? AND status = 'Pending'");
        $stmt->bind_param('ssi', $transID, $transTime, $orderId);
        $stmt->execute();
        
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET 
            status = 'Paid',
            updated_at = NOW()
            WHERE id = ?");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
    } else {
        // Create a new payment record for direct C2B payment
        $stmt = $conn->prepare("INSERT INTO c2b_payments (
            trans_id, trans_time, trans_amount, business_short_code,
            bill_ref_number, invoice_number, msisdn, first_name,
            middle_name, last_name, org_account_balance, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('ssdsssssssd', 
            $transID, $transTime, $transAmount, $businessShortCode,
            $billRefNumber, $invoiceNumber, $msisdn, $firstName,
            $middleName, $lastName, $orgAccountBalance
        );
        $stmt->execute();
    }
    
    // Log successful confirmation
    $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
    $action = 'c2b_confirmation_success';
    $details = json_encode([
        'trans_id' => $transID,
        'amount' => $transAmount,
        'msisdn' => $msisdn,
        'bill_ref_number' => $billRefNumber,
        'order_id' => $orderId
    ]);
    $stmt->bind_param('ss', $action, $details);
    $stmt->execute();
    
    // Send success response to Safaricom
    $response = [
        'ResultCode' => '0',
        'ResultDesc' => 'Accepted'
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("C2B Confirmation Error: " . $e->getMessage());
    
    // Log error
    if (isset($conn)) {
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
        $action = 'c2b_confirmation_error';
        $details = json_encode(['error' => $e->getMessage(), 'confirmation_data' => $confirmationData ?? null]);
        $stmt->bind_param('ss', $action, $details);
        $stmt->execute();
    }
    
    http_response_code(500);
    echo json_encode([
        'ResultCode' => '1',
        'ResultDesc' => 'System Error'
    ]);
}
?>
