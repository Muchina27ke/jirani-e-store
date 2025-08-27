<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get notification ID from request
$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['id'] ?? null;

if (!$notificationId) {
    http_response_code(400);
    echo json_encode(['error' => 'Notification ID is required']);
    exit;
}

try {
    $notification = new Notification();
    $success = $notification->markAsRead($notificationId, $_SESSION['user_id']);

    echo json_encode(['success' => $success]);

} catch (Exception $e) {
    error_log('Notification Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to mark notification as read']);
}