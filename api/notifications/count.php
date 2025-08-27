<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $notification = new Notification();
    $count = $notification->getUnreadCount($_SESSION['user_id']);

    echo json_encode(['count' => $count]);

} catch (Exception $e) {
    error_log('Notification Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get notification count']);
}