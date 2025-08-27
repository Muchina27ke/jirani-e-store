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
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET read_at = NOW() 
        WHERE user_id = ? AND read_at IS NULL
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $success = $stmt->execute();

    echo json_encode(['success' => $success]);

} catch (Exception $e) {
    error_log('Notification Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to mark notifications as read']);
}