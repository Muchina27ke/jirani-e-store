<?php
/**
 * M-Pesa Integration for Jirani Platform
 * Handles STK Push payments via IntaSend API
 */

class Mpesa
{
    private $conn;
    private $apiKey;
    private $publishableKey;
    private $environment;
    private $baseUrl;

    public function __construct($conn, $apiKey, $publishableKey, $environment = 'sandbox')
    {
        $this->conn = $conn;
        $this->apiKey = $apiKey;
        $this->publishableKey = $publishableKey;
        $this->environment = $environment;
        $this->baseUrl = $environment === 'live'
            ? 'https://payment.intasend.com/api/v1/'
            : 'https://sandbox.intasend.com/api/v1/';
    }

    /**
     * Initiate STK Push payment using M-Pesa Daraja API (sandbox)
     */
    public function initiatePayment($orderId, $amount, $phoneNumber, $description = '')
    {
        try {
            // Format phone number to 2547XXXXXXXX
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            if (!$phoneNumber) {
                return ['success' => false, 'message' => 'Invalid phone number format'];
            }

            // Daraja credentials from config
            $consumerKey = MPESA_CONSUMER_KEY;
            $consumerSecret = MPESA_CONSUMER_SECRET;
            $shortcode = MPESA_SHORTCODE;
            $passkey = MPESA_PASSKEY;
            $env = MPESA_ENV;

            $baseUrl = $env === 'production'
                ? 'https://api.safaricom.co.ke/'
                : 'https://sandbox.safaricom.co.ke/';

            // 1. Get access token
            $tokenUrl = $baseUrl . 'oauth/v1/generate?grant_type=client_credentials';
            $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
            $ch = curl_init($tokenUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $credentials
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $tokenResponse = curl_exec($ch);
            curl_close($ch);
            $tokenData = json_decode($tokenResponse, true);
            if (empty($tokenData['access_token'])) {
                return ['success' => false, 'message' => 'Failed to get M-Pesa access token'];
            }
            $accessToken = $tokenData['access_token'];

            // 2. Prepare STK Push request
            $timestamp = date('YmdHis');
            $password = base64_encode($shortcode . $passkey . $timestamp);
            $callbackUrl = SITE_URL . 'api/mpesa/callback.php';
            $requestData = [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int) $amount,
                'PartyA' => $phoneNumber,
                'PartyB' => $shortcode,
                'PhoneNumber' => $phoneNumber,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => 'ORDER_' . $orderId,
                'TransactionDesc' => $description ?: 'Payment for Order #' . $orderId
            ];

            // 3. Send STK Push request
            $stkUrl = $baseUrl . 'mpesa/stkpush/v1/processrequest';
            $ch = curl_init($stkUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            $stkResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $stkData = json_decode($stkResponse, true);

            if ($httpCode == 200 && isset($stkData['ResponseCode']) && $stkData['ResponseCode'] == '0') {
                // Success: store CheckoutRequestID for status tracking
                $checkoutRequestId = $stkData['CheckoutRequestID'] ?? null;
                // Optionally, store in DB for later status checks
                return [
                    'success' => true,
                    'CheckoutRequestID' => $checkoutRequestId,
                    'MerchantRequestID' => $stkData['MerchantRequestID'] ?? null,
                    'CustomerMessage' => $stkData['CustomerMessage'] ?? 'STK Push sent to your phone',
                    'message' => 'STK Push sent to your phone'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $stkData['errorMessage'] ?? 'Failed to initiate M-Pesa STK Push',
                    'raw' => $stkData
                ];
            }
        } catch (Exception $e) {
            error_log('M-Pesa Daraja error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Payment service temporarily unavailable'];
        }
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus($transactionId)
    {
        try {
            $response = $this->makeApiRequest("payment/status/", [
                'invoice_id' => $transactionId
            ], 'GET');

            if ($response && isset($response['invoice'])) {
                $status = strtolower($response['invoice']['state']);

                // Update payment record
                $stmt = $this->conn->prepare("
                    UPDATE payments 
                    SET status = ?, api_response = ? 
                    WHERE mpesa_transaction_id = ?
                ");
                $apiResponse = json_encode($response);
                $stmt->bind_param("sss", $status, $apiResponse, $transactionId);
                $stmt->execute();

                return [
                    'success' => true,
                    'status' => $status,
                    'amount' => $response['invoice']['amount'],
                    'phone' => $response['invoice']['phone_number'],
                    'data' => $response
                ];
            }

            return ['success' => false, 'message' => 'Transaction not found'];

        } catch (Exception $e) {
            error_log("M-Pesa status check error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to check payment status'];
        }
    }

    /**
     * Process payment callback
     */
    public function processCallback($callbackData)
    {
        try {
            $invoiceId = $callbackData['invoice_id'] ?? null;
            $state = strtolower($callbackData['state'] ?? '');

            if (!$invoiceId) {
                return ['success' => false, 'message' => 'Invalid callback data'];
            }

            // Find payment record
            $stmt = $this->conn->prepare("
                SELECT p.*, o.id as order_id, o.customer_id, o.vendor_id 
                FROM payments p 
                JOIN orders o ON p.order_id = o.id 
                WHERE p.mpesa_transaction_id = ?
            ");
            $stmt->bind_param("s", $invoiceId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Payment record not found'];
            }

            $payment = $result->fetch_assoc();

            // Update payment status
            $stmt = $this->conn->prepare("
                UPDATE payments 
                SET status = ?, api_response = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $apiResponse = json_encode($callbackData);
            $stmt->bind_param("ssi", $state, $apiResponse, $payment['id']);
            $stmt->execute();

            // Handle successful payment
            if ($state === 'complete' || $state === 'paid') {
                $this->handleSuccessfulPayment($payment, $callbackData);
            } elseif ($state === 'failed' || $state === 'cancelled') {
                $this->handleFailedPayment($payment, $callbackData);
            }

            $this->logTransaction($payment['order_id'], 'callback_processed', $callbackData);

            return ['success' => true, 'message' => 'Callback processed'];

        } catch (Exception $e) {
            error_log("M-Pesa callback error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process callback'];
        }
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment($payment, $callbackData)
    {
        // Update order status
        $stmt = $this->conn->prepare("
            UPDATE orders 
            SET status = 'confirmed', payment_status = 'paid', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $payment['order_id']);
        $stmt->execute();

        // Create escrow record if enabled
        if ($payment['is_escrow']) {
            $stmt = $this->conn->prepare("
                INSERT INTO escrow_payments (order_id, payment_id, escrow_status) 
                VALUES (?, ?, 'held')
                ON DUPLICATE KEY UPDATE escrow_status = 'held'
            ");
            $stmt->bind_param("ii", $payment['order_id'], $payment['id']);
            $stmt->execute();
        }

        // Send notifications
        $this->sendPaymentNotifications($payment, 'success');

        // Log successful payment
        $this->logTransaction($payment['order_id'], 'payment_successful', $callbackData);
    }

    /**
     * Handle failed payment
     */
    private function handleFailedPayment($payment, $callbackData)
    {
        // Update order status
        $stmt = $this->conn->prepare("
            UPDATE orders 
            SET status = 'payment_failed', payment_status = 'failed', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $payment['order_id']);
        $stmt->execute();

        // Send failure notifications
        $this->sendPaymentNotifications($payment, 'failed');

        // Log failed payment
        $this->logTransaction($payment['order_id'], 'payment_failed', $callbackData);
    }

    /**
     * Release escrow payment to vendor
     */
    public function releaseEscrowPayment($orderId, $releasedBy)
    {
        try {
            // Get escrow payment details
            $stmt = $this->conn->prepare("
                SELECT ep.*, p.amount, p.mpesa_transaction_id, o.vendor_id 
                FROM escrow_payments ep 
                JOIN payments p ON ep.payment_id = p.id 
                JOIN orders o ON ep.order_id = o.id 
                WHERE ep.order_id = ? AND ep.escrow_status = 'held'
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'No held escrow payment found'];
            }

            $escrow = $result->fetch_assoc();

            // Fetch vendor's M-Pesa number
            $vendorStmt = $this->conn->prepare("SELECT mpesa_number FROM vendors WHERE user_id = ?");
            $vendorStmt->bind_param("i", $escrow['vendor_id']);
            $vendorStmt->execute();
            $vendorResult = $vendorStmt->get_result();
            $vendorData = $vendorResult->fetch_assoc();

            if (empty($vendorData['mpesa_number'])) {
                throw new Exception("Vendor M-Pesa number not found for payout");
            }
            $vendorMpesaNumber = $vendorData['mpesa_number'];

            // Calculate commission and vendor payout
            require dirname(__DIR__) . '/config/config.php';
            $commissionRate = isset($globalSettings['commission_rate']) ? floatval($globalSettings['commission_rate']) : 5.0;
            $orderAmount = floatval($escrow['amount']);
            $commission = round($orderAmount * ($commissionRate / 100), 2);
            $vendorPayout = $orderAmount - $commission;

            // Prepare IntaSend payout request (deduct commission)
            $payoutData = [
                'api_key' => $this->apiKey, // Use API Key for payouts
                'amount' => $vendorPayout,
                'currency' => 'KES',
                'destination_type' => 'M-PESA',
                'destination_data' => [
                    'phone_number' => $vendorMpesaNumber
                ],
                'narration' => 'Jirani Platform Payout for Order #' . $orderId,
                'reference' => 'PAYOUT_' . $orderId . '_' . time()
            ];

            // Make API request for payout
            $payoutResponse = $this->makeApiRequest('payout/mpesa/', $payoutData);

            if ($payoutResponse && isset($payoutResponse['transaction_id'])) {
                // Update escrow status
                $stmt = $this->conn->prepare("
                    UPDATE escrow_payments 
                    SET escrow_status = 'released_to_vendor', released_by = ?, released_at = NOW(), payout_transaction_id = ? 
                    WHERE order_id = ?
                ");
                $stmt->bind_param("isi", $releasedBy, $payoutResponse['transaction_id'], $orderId);
                $stmt->execute();

                // Store commission record
                $stmt = $this->conn->prepare("INSERT INTO commissions (order_id, vendor_id, commission_amount, rate, payout_amount, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiddd", $orderId, $escrow['vendor_id'], $commission, $commissionRate, $vendorPayout);
                $stmt->execute();

                // Log the transaction
                $this->logTransaction($orderId, 'escrow_released_mpesa_payout', [
                    'amount' => $orderAmount,
                    'commission' => $commission,
                    'commission_rate' => $commissionRate,
                    'vendor_payout' => $vendorPayout,
                    'vendor_id' => $escrow['vendor_id'],
                    'released_by' => $releasedBy,
                    'payout_response' => $payoutResponse
                ]);

                $this->conn->commit();
                return ['success' => true, 'message' => 'Escrow payment released to vendor via M-Pesa', 'transaction_id' => $payoutResponse['transaction_id']];
            } else {
                $this->conn->rollback();
                $errorMessage = $payoutResponse['detail'] ?? 'Failed to initiate M-Pesa payout';
                throw new Exception($errorMessage);
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Escrow release error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to release escrow payment: ' . $e->getMessage()];
        }
    }

    /**
     * Refund payment to customer
     */
    public function refundPayment($orderId, $reason = '')
    {
        try {
            // Get payment details
            $stmt = $this->conn->prepare("
                SELECT p.*, o.customer_id 
                FROM payments p 
                JOIN orders o ON p.order_id = o.id 
                WHERE p.order_id = ? AND p.status = 'completed'
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'No completed payment found'];
            }

            $payment = $result->fetch_assoc();

            // Make refund API request
            $response = $this->makeApiRequest('payment/refund/', [
                'invoice_id' => $payment['mpesa_transaction_id'],
                'amount' => $payment['amount'],
                'reason' => $reason ?: 'Order refund'
            ]);

            if ($response && isset($response['refund_id'])) {
                // Update payment status
                $stmt = $this->conn->prepare("
                    UPDATE payments 
                    SET status = 'refunded', api_response = ? 
                    WHERE id = ?
                ");
                $apiResponse = json_encode($response);
                $stmt->bind_param("si", $apiResponse, $payment['id']);
                $stmt->execute();

                // Update escrow if exists
                $stmt = $this->conn->prepare("
                    UPDATE escrow_payments 
                    SET escrow_status = 'released_to_customer' 
                    WHERE order_id = ?
                ");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();

                $this->logTransaction($orderId, 'payment_refunded', $response);

                return ['success' => true, 'message' => 'Refund processed successfully'];
            }

            return ['success' => false, 'message' => 'Failed to process refund'];

        } catch (Exception $e) {
            error_log("Refund error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process refund'];
        }
    }

    /**
     * Create payment record in database
     */
    private function createPaymentRecord($orderId, $amount, $phoneNumber)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO payments (order_id, amount, method, is_escrow, status) 
            VALUES (?, ?, 'mpesa', 1, 'pending')
        ");
        $stmt->bind_param("id", $orderId, $amount);

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }

        return false;
    }

    /**
     * Update payment record
     */
    private function updatePaymentRecord($paymentId, $data)
    {
        $fields = [];
        $values = [];
        $types = '';

        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $values[] = $value;
            $types .= is_int($value) ? 'i' : 's';
        }

        $sql = "UPDATE payments SET " . implode(', ', $fields) . " WHERE id = ?";
        $values[] = $paymentId;
        $types .= 'i';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
    }

    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to international format
        if (preg_match('/^0([7-9]\d{8})$/', $phone, $matches)) {
            return '254' . $matches[1];
        } elseif (preg_match('/^254([7-9]\d{8})$/', $phone, $matches)) {
            return $phone;
        } elseif (preg_match('/^([7-9]\d{8})$/', $phone, $matches)) {
            return '254' . $matches[1];
        }

        return false;
    }

    /**
     * Make API request to IntaSend
     */
    private function makeApiRequest($endpoint, $data, $method = 'POST')
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'X-IntaSend-Public-Key-Test: ' . $this->publishableKey
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: " . $error);
        }

        if ($httpCode >= 400) {
            error_log("IntaSend API error: HTTP {$httpCode} - {$response}");
        }

        return json_decode($response, true);
    }

    /**
     * Send payment notifications
     */
    private function sendPaymentNotifications($payment, $status)
    {
        try {
            // Get order and user details
            $stmt = $this->conn->prepare("
                SELECT o.*, u.name as customer_name, u.email as customer_email, 
                       v.business_name as vendor_name 
                FROM orders o 
                JOIN users u ON o.customer_id = u.id 
                JOIN vendors v ON o.vendor_id = v.user_id 
                WHERE o.id = ?
            ");
            $stmt->bind_param("i", $payment['order_id']);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if ($order) {
                // Send email notifications based on status
                $emailNotifications = new EmailNotifications($this->conn);

                if ($status === 'success') {
                    // Send order confirmation to customer
                    $emailNotifications->sendOrderConfirmation($order, [], [
                        'name' => $order['customer_name'],
                        'email' => $order['customer_email']
                    ], [
                        'business_name' => $order['vendor_name']
                    ]);
                }

                // You can add SMS notifications here
            }

        } catch (Exception $e) {
            error_log("Failed to send payment notifications: " . $e->getMessage());
        }
    }

    /**
     * Log transaction activity
     */
    private function logTransaction($orderId, $action, $data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO payment_logs (order_id, action, data, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $jsonData = json_encode($data);
        $stmt->bind_param("iss", $orderId, $action, $jsonData);
        $stmt->execute();
    }

    public function b2cPaymentDaraja($phone, $amount, $remarks = 'Escrow Release', $occasion = 'Jirani Payout')
    {
        // 1. Get access token
        $consumerKey = MPESA_CONSUMER_KEY;
        $consumerSecret = MPESA_CONSUMER_SECRET;
        $shortcode = MPESA_SHORTCODE; // Paybill or BuyGoods
        $initiatorName = MPESA_INITIATOR_NAME;
        $initiatorPassword = MPESA_INITIATOR_PASSWORD;
        $env = MPESA_ENV;
        $baseUrl = $env === 'production'
            ? 'https://api.safaricom.co.ke/'
            : 'https://sandbox.safaricom.co.ke/';

        // Get OAuth token
        $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
        $ch = curl_init($baseUrl . 'oauth/v1/generate?grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tokenResponse = curl_exec($ch);
        curl_close($ch);
        $tokenData = json_decode($tokenResponse, true);
        if (empty($tokenData['access_token'])) {
            return ['success' => false, 'message' => 'Failed to get M-Pesa access token'];
        }
        $accessToken = $tokenData['access_token'];

        // 2. Encrypt initiator password
        $publicKey = file_get_contents(__DIR__ . '/certificates/mpesa_public_cert.cer'); // Path to Safaricom cert
        openssl_public_encrypt($initiatorPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        $securityCredential = base64_encode($encrypted);

        // 3. Prepare B2C request
        $payload = [
            'InitiatorName' => $initiatorName,
            'SecurityCredential' => $securityCredential,
            'CommandID' => 'BusinessPayment', // Or SalaryPayment, PromotionPayment
            'Amount' => (int) $amount,
            'PartyA' => $shortcode,
            'PartyB' => $phone,
            'Remarks' => $remarks,
            'QueueTimeOutURL' => SITE_URL . 'api/mpesa/b2c_timeout.php',
            'ResultURL' => SITE_URL . 'api/mpesa/b2c_result.php',
            'Occasion' => $occasion
        ];

        $ch = curl_init($baseUrl . 'mpesa/b2c/v1/paymentrequest');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode == 200 && isset($data['ResponseCode']) && $data['ResponseCode'] == '0') {
            return [
                'success' => true,
                'ConversationID' => $data['ConversationID'],
                'OriginatorConversationID' => $data['OriginatorConversationID'],
                'ResponseDescription' => $data['ResponseDescription'],
                'message' => 'B2C payout initiated'
            ];
        } else {
            return [
                'success' => false,
                'message' => $data['errorMessage'] ?? $error ?? 'Failed to initiate B2C payout',
                'raw' => $data
            ];
        }
    }
}
?>