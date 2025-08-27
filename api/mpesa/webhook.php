<?php
require_once '../../config/config.php';
require_once '../../includes/Mpesa.php';

// Get raw POST data
$payload = file_get_contents('php://input');

try {
    $mpesa = new Mpesa($conn);
    $result = $mpesa->handleWebhook($payload);

    if ($result) {
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid webhook data']);
    }
} catch (Exception $e) {
    error_log("M-Pesa webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Webhook processing failed']);
}