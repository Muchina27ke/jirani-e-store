<?php
require_once '../config/config.php';
require_once '../includes/Auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $auth = new Auth($conn);
    
    $identifier = $input['identifier'] ?? '';
    $password = $input['password'] ?? '';
    $rememberMe = $input['remember_me'] ?? false;
    
    if (empty($identifier) || empty($password)) {
        throw new Exception('Email/phone and password are required');
    }
    
    $result = $auth->login($identifier, $password, $rememberMe);
    
    if ($result['success']) {
        $user = $auth->getUser();
        
        // Remove sensitive data
        unset($user['password']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'redirect_url' => $user['role'] === 'admin' ? SITE_URL . 'admin/' : 
                            ($user['role'] === 'vendor' ? SITE_URL . 'seller/' : SITE_URL . 'dashboard.php')
        ]);
    } else {
        http_response_code(401);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

