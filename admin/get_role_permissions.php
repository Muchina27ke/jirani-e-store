<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Admin.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['role_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Role ID required']);
    exit;
}

$admin = new Admin($db);
$roleId = (int)$_GET['role_id'];

try {
    $permissions = $admin->getRolePermissions($roleId);
    header('Content-Type: application/json');
    echo json_encode($permissions);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get role permissions']);
}
?>
