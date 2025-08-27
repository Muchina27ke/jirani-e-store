<?php
// Suppress PHP warnings/notices from leaking into JSON output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Order.php';

header('Content-Type: application/json');
session_start();

function safe_json_response($data, $http_code = 200) {
    http_response_code($http_code);
    // Clear output buffer to avoid HTML/PHP errors leaking
    if (ob_get_length()) ob_end_clean();
    echo json_encode($data);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    safe_json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = isset($input['order_id']) ? (int)$input['order_id'] : 0;

if (!$orderId || !isset($_SESSION['user_id'])) {
    safe_json_response(['success' => false, 'message' => 'Invalid request'], 400);
}

$db = getDbConnection();
$auth = new Auth($db);
if (!$auth->isLoggedIn() || $auth->getUser()['role'] !== 'customer') {
    safe_json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = $_SESSION['user_id'];
$orderObj = new Order($db);

// Only allow cancellation if order belongs to user and is pending
$stmt = $db->prepare('SELECT status FROM orders WHERE id = ? AND customer_id = ?');
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
if (!$order) {
    safe_json_response(['success' => false, 'message' => 'Order not found'], 404);
}
if ($order['status'] !== 'pending') {
    safe_json_response(['success' => false, 'message' => 'Only pending orders can be cancelled'], 400);
}

$success = $orderObj->update_order_status($orderId, 'cancelled');
if ($success) {
    safe_json_response(['success' => true, 'message' => 'Order cancelled successfully']);
} else {
    safe_json_response(['success' => false, 'message' => 'Failed to cancel order'], 500);
}
