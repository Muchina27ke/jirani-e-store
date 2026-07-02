<?php
/**
 * AfriGear.tech — Payment status poller
 * GET: checkout_request_id  →  JSON { status: pending|completed|failed, order_id }
 * Called every 3 s by the frontend until completed/failed or 2-min timeout
 */
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../config/config.php';

$checkoutRequestId = trim($_GET['checkout_request_id'] ?? '');

if (!$checkoutRequestId) {
    echo json_encode(['status' => 'error', 'message' => 'Missing checkout_request_id']);
    exit;
}

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("
        SELECT t.status, t.mpesa_receipt, t.result_desc, t.order_id
        FROM mpesa_transactions t
        WHERE t.checkout_request_id = :crid
        LIMIT 1
    ");
    $stmt->execute([':crid' => $checkoutRequestId]);
    $tx = $stmt->fetch();

    if (!$tx) {
        echo json_encode(['status' => 'pending']);
        exit;
    }

    echo json_encode([
        'status'       => $tx['status'],          // pending | completed | failed
        'order_id'     => $tx['order_id'],
        'receipt'      => $tx['mpesa_receipt'],
        'message'      => $tx['result_desc'],
    ]);

} catch (Throwable $e) {
    error_log('check_payment error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}
