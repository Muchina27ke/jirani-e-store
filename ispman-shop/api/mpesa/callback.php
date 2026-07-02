<?php
/**
 * AfriGear.tech — Safaricom STK Push callback receiver
 * Safaricom POSTs JSON here after payment attempt.
 * Must be publicly accessible (not localhost) in production.
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/MpesaPayment.php';

// Log raw payload for debugging
$rawInput = file_get_contents('php://input');
error_log('M-Pesa Callback: ' . $rawInput);

$payload = json_decode($rawInput, true);

if (!$payload || !isset($payload['Body']['stkCallback'])) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid payload']);
    exit;
}

try {
    $pdo   = getPDO();
    $mpesa = new MpesaPayment([
        'consumer_key'    => MPESA_CONSUMER_KEY,
        'consumer_secret' => MPESA_CONSUMER_SECRET,
        'shortcode'       => MPESA_SHORTCODE,
        'passkey'         => MPESA_PASSKEY,
        'callback_url'    => MPESA_CALLBACK_URL,
        'env'             => MPESA_ENV,
    ]);

    $result = $mpesa->handleCallback($payload);
    $checkId = $result['checkout_request_id'];

    if ($result['success']) {
        // ── Payment successful ──────────────────────────────────────────
        $pdo->beginTransaction();

        // 1. Update mpesa_transactions
        $upd = $pdo->prepare("
            UPDATE mpesa_transactions
            SET status = 'completed',
                mpesa_receipt = :receipt,
                result_code   = :code,
                result_desc   = :desc
            WHERE checkout_request_id = :crid
        ");
        $upd->execute([
            ':receipt' => $result['receipt'],
            ':code'    => $result['result_code'],
            ':desc'    => $result['result_desc'],
            ':crid'    => $checkId,
        ]);

        // 2. Get order_id from transaction
        $txStmt = $pdo->prepare("SELECT order_id FROM mpesa_transactions WHERE checkout_request_id = :crid");
        $txStmt->execute([':crid' => $checkId]);
        $tx = $txStmt->fetch();

        if ($tx && $tx['order_id']) {
            $orderId = $tx['order_id'];

            // 3. Mark order as paid
            $pdo->prepare("UPDATE orders SET status = 'paid', mpesa_ref = :ref WHERE id = :id")
                ->execute([':ref' => $result['receipt'], ':id' => $orderId]);

            // 4. Reduce stock for each order item
            $itemsStmt = $pdo->prepare("SELECT product_id, qty FROM order_items WHERE order_id = :oid");
            $itemsStmt->execute([':oid' => $orderId]);
            $orderItems = $itemsStmt->fetchAll();

            $stockUpd = $pdo->prepare("
                UPDATE products SET stock_qty = GREATEST(0, stock_qty - :qty)
                WHERE id = :pid
            ");
            foreach ($orderItems as $item) {
                $stockUpd->execute([':qty' => $item['qty'], ':pid' => $item['product_id']]);
            }
        }

        $pdo->commit();

    } else {
        // ── Payment failed / cancelled ──────────────────────────────────
        $pdo->prepare("
            UPDATE mpesa_transactions
            SET status = 'failed',
                result_code = :code,
                result_desc = :desc
            WHERE checkout_request_id = :crid
        ")->execute([
            ':code' => $result['result_code'],
            ':desc' => $result['result_desc'],
            ':crid' => $checkId,
        ]);
    }

    // Safaricom expects this exact response
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

} catch (Throwable $e) {
    error_log('Callback error: ' . $e->getMessage());
    // Still return 200 so Safaricom doesn't retry indefinitely
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
}
