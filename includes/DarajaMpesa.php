<?php

class DarajaMpesa
{
    private $conn;
    private $settings;
    private $consumerKey;
    private $consumerSecret;
    private $environment;
    private $shortcode;
    private $passkey;
    private $initiatorName;
    private $securityCredential;
    private $baseUrl;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->loadSettings();
        $this->initializeConfig();
    }

    private function loadSettings()
    {
        $settingObj = new Setting($this->conn);
        $this->settings = $settingObj->get_all();
    }

    private function initializeConfig()
    {
        $this->consumerKey = $this->settings['daraja_consumer_key'] ?? '';
        $this->consumerSecret = base64_decode($this->settings['daraja_consumer_secret'] ?? '');
        $this->environment = $this->settings['daraja_environment'] ?? 'sandbox';
        $this->shortcode = $this->settings['daraja_shortcode'] ?? '174379';
        $this->passkey = $this->settings['daraja_passkey'] ?? '';
        $this->initiatorName = $this->settings['daraja_initiator_name'] ?? 'testapi';
        $this->securityCredential = base64_decode($this->settings['daraja_security_credential'] ?? '');
        
        // Set base URL based on environment
        $this->baseUrl = $this->environment === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Generate access token for Daraja API
     */
    private function generateAccessToken()
    {
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials',
            CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['access_token'] ?? null;
        }

        error_log("Failed to generate access token: " . $response);
        return null;
    }

    /**
     * Initiate STK Push for Lipa Na M-Pesa Online
     */
    public function stkPush($phoneNumber, $amount, $accountReference, $transactionDesc = 'Payment')
    {
        $accessToken = $this->generateAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to generate access token'];
        }

        // Format phone number (remove + and ensure it starts with 254)
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        // Generate timestamp and password
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $postData = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int)$amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $this->settings['daraja_stk_callback_url'] ?? '',
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . '/mpesa/stkpush/v1/processrequest',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
            return [
                'success' => true,
                'checkout_request_id' => $result['CheckoutRequestID'],
                'merchant_request_id' => $result['MerchantRequestID'],
                'message' => 'STK push initiated successfully'
            ];
        }

        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? 'STK push failed',
            'error_code' => $result['errorCode'] ?? null
        ];
    }

    /**
     * Query STK Push transaction status
     */
    public function stkQuery($checkoutRequestId)
    {
        $accessToken = $this->generateAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to generate access token'];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $postData = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . '/mpesa/stkpushquery/v1/query',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result = json_decode($response, true);

        if ($httpCode === 200) {
            return [
                'success' => true,
                'result_code' => $result['ResultCode'] ?? null,
                'result_desc' => $result['ResultDesc'] ?? null,
                'data' => $result
            ];
        }

        return ['success' => false, 'message' => 'Query failed', 'data' => $result];
    }

    /**
     * B2C Payment (Business to Customer)
     */
    public function b2cPayment($phoneNumber, $amount, $remarks = 'Payment', $occasion = '')
    {
        $accessToken = $this->generateAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to generate access token'];
        }

        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        $postData = [
            'InitiatorName' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => 'BusinessPayment',
            'Amount' => (int)$amount,
            'PartyA' => $this->shortcode,
            'PartyB' => $phoneNumber,
            'Remarks' => $remarks,
            'QueueTimeOutURL' => $this->settings['daraja_b2c_callback_url'] ?? '',
            'ResultURL' => $this->settings['daraja_b2c_callback_url'] ?? '',
            'Occasion' => $occasion
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . '/mpesa/b2c/v1/paymentrequest',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
            return [
                'success' => true,
                'originator_conversation_id' => $result['OriginatorConversationID'],
                'conversation_id' => $result['ConversationID'],
                'message' => 'B2C payment initiated successfully'
            ];
        }

        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? 'B2C payment failed',
            'error_code' => $result['errorCode'] ?? null
        ];
    }

    /**
     * Register C2B URLs
     */
    public function registerC2BUrls()
    {
        $accessToken = $this->generateAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to generate access token'];
        }

        $postData = [
            'ShortCode' => $this->shortcode,
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $this->settings['daraja_confirmation_url'] ?? '',
            'ValidationURL' => $this->settings['daraja_validation_url'] ?? ''
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . '/mpesa/c2b/v1/registerurl',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
            return ['success' => true, 'message' => 'C2B URLs registered successfully'];
        }

        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? 'C2B URL registration failed',
            'error_code' => $result['errorCode'] ?? null
        ];
    }

    /**
     * Format phone number to Kenyan format (254XXXXXXXXX)
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove any spaces, dashes, or plus signs
        $phoneNumber = preg_replace('/[\s\-\+]/', '', $phoneNumber);
        
        // If it starts with 0, replace with 254
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '254' . substr($phoneNumber, 1);
        }
        
        // If it doesn't start with 254, add it
        if (substr($phoneNumber, 0, 3) !== '254') {
            $phoneNumber = '254' . $phoneNumber;
        }
        
        return $phoneNumber;
    }

    /**
     * Test Daraja API connection
     */
    public function testConnection()
    {
        $accessToken = $this->generateAccessToken();
        
        if ($accessToken) {
            return ['success' => true, 'message' => 'Daraja API connection successful'];
        } else {
            return ['success' => false, 'message' => 'Failed to connect to Daraja API'];
        }
    }
}
