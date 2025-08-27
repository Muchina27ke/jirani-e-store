<?php
require_once '../../config/config.php';
require_once '../../includes/User.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? '';
    $verified = isset($data['verified']) ? (int)$data['verified'] : 0;

    if (!$name || !$email || !$phone || !$password || !$role) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email']);
        exit;
    }
    $allowed_roles = ['customer', 'vendor', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }
    // Check for duplicate email or phone
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR phone=? LIMIT 1");
    $stmt->bind_param('ss', $email, $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email or phone already exists']);
        exit;
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, verified, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('sssssi', $name, $email, $phone, $hashed_password, $role, $verified);
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        // Assign admin roles if provided
        if ($role === 'admin' && !empty($data['admin_roles']) && is_array($data['admin_roles'])) {
            require_once '../../includes/Admin.php';
            $admin = new Admin($conn);
            foreach ($data['admin_roles'] as $role_id) {
                $admin->assignRole($user_id, $role_id);
            }
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add user']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $role = $data['role'] ?? '';
    $verified = isset($data['verified']) ? (int) $data['verified'] : 0;

    if (!$id || !$name || !$email || !$phone || !$role) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email']);
        exit;
    }
    $allowed_roles = ['customer', 'vendor', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, role=?, verified=? WHERE id=?");
    $stmt->bind_param('ssssii', $name, $email, $phone, $role, $verified, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);