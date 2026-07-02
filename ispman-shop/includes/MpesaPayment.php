<?php
/**
 * AfriGear.tech — M-Pesa Daraja API wrapper
 * Supports: STK Push (Lipa Na M-Pesa Online), callback handling, status query
 */
class MpesaPayment {
    private string $consumerKey;
    private string $consumerSecret;
    private string $shortcode;
    private string $passkey;
    private string $callbackUrl;
    private bool   $sandbox;
    private string $baseUrl;

    public function __construct(array $config) {
        $this->consumerKey    = $config['consumer_key']    ?? '';
        $this->consumerSecret = $config['consumer_secret'] ?? '';
        $this->shortcode      = $config['shortcode']       ?? '';
        $this->passkey        = $config['passkey']         ?? '';
        $this->callbackUrl    = $config['callback_url']    ?? '';
        $this->sandbox        = ($config['env'] ?? 'sandbox') === 'sandbox';
        $this->baseUrl        = $this->sandbox
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';
    }

    /**
     * Get OAuth access token from Daraja
     */
    public function getAccessToken(): string|false {
        $url  = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
        $cred = base64_encode($this->consumerKey . ':' . $this->consumerSecret);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $cred],
            CURLOPT_SSL_VERIFYPEER => !$this->sandbox,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return false;
        $data = json_decode($response, true);
        return $data['access_token'] ?? false;
    }

    /**
     * Initiate STK Push (Lipa Na M-Pesa Online)
     * Returns Daraja response array or false on failure
     */
    public function stkPush(string $phone, float $amount, int $orderId): array|false {
        $token = $this->getAccessToken();
        if (!$token) return false;

        $timestamp = $this->getTimestamp();
        $password  = $this->generatePassword($timestamp);
        $phone     = $this->formatPhone($phone);
        $amount    = (int)ceil($amount); // M-Pesa requires whole KES

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $amount,
            'PartyA'            => $phone,
            'PartyB'            => $this->shortcode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $this->callbackUrl,
            'AccountReference'  => 'AfriGear-' . $orderId,
            'TransactionDesc'   => 'AfriGear.tech Order #' . $orderId,
        ];

        $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->sandbox,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response) return false;
        $data = json_decode($response, true);
        if (!is_array($data)) return false;
        // Attach HTTP code for caller inspection
        $data['_http_code'] = $httpCode;
        return $data;
    }

    /**
     * Query STK Push transaction status
     */
    public function queryStatus(string $checkoutRequestId): array|false {
        $token = $this->getAccessToken();
        if (!$token) return false;

        $timestamp = $this->getTimestamp();
        $password  = $this->generatePassword($timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->sandbox,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return false;
        return json_decode($response, true) ?: false;
    }

    /**
     * Parse Safaricom's STK callback payload
     * Returns normalised array with keys: success, receipt, amount, phone, result_code, result_desc
     */
    public function handleCallback(array $payload): array {
        $body     = $payload['Body']['stkCallback'] ?? [];
        $code     = (int)($body['ResultCode'] ?? -1);
        $desc     = $body['ResultDesc'] ?? 'Unknown';
        $checkId  = $body['CheckoutRequestID'] ?? '';
        $merchantId = $body['MerchantRequestID'] ?? '';

        $result = [
            'success'             => $code === 0,
            'result_code'         => $code,
            'result_desc'         => $desc,
            'checkout_request_id' => $checkId,
            'merchant_request_id' => $merchantId,
            'receipt'             => null,
            'amount'              => null,
            'phone'               => null,
        ];

        if ($code === 0) {
            $items = $body['CallbackMetadata']['Item'] ?? [];
            foreach ($items as $item) {
                switch ($item['Name']) {
                    case 'MpesaReceiptNumber': $result['receipt'] = $item['Value']; break;
                    case 'Amount':             $result['amount']  = $item['Value']; break;
                    case 'PhoneNumber':        $result['phone']   = $item['Value']; break;
                }
            }
        }
        return $result;
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /** Normalise phone to 254XXXXXXXXX format */
    private function formatPhone(string $phone): string {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0'))   $phone = '254' . substr($phone, 1);
        if (str_starts_with($phone, '+254')) $phone = substr($phone, 1);
        if (!str_starts_with($phone, '254')) $phone = '254' . $phone;
        return $phone;
    }

    /** Base64(shortcode + passkey + timestamp) */
    private function generatePassword(string $timestamp): string {
        return base64_encode($this->shortcode . $this->passkey . $timestamp);
    }

    /** YYYYMMDDHHmmss */
    private function getTimestamp(): string {
        return date('YmdHis');
    }
}
