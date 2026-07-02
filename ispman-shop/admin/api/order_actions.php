<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';

// Auth check
if (empty($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$pdo    = getPDO();

try {
    switch ($action) {
        case 'update_status':
            $orderId = (int)($_POST['order_id'] ?? 0);
            $status  = $_POST['status'] ?? '';
            $allowed = ['pending','paid','processing','shipped','delivered','cancelled'];
            if (!$orderId || !in_array($status, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                exit;
            }
            $pdo->prepare("UPDATE orders SET status = :s WHERE id = :id")
                ->execute([':s' => $status, ':id' => $orderId]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
