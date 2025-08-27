<?php
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Geolocation.php';

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
    
    $geolocation = new Geolocation($conn);
    
    // Save location to session for non-authenticated users
    if (isset($input['lat']) && isset($input['lng'])) {
        session_start();
        $_SESSION['user_location'] = [
            'latitude' => $input['lat'],
            'longitude' => $input['lng'],
            'address' => $input['address'] ?? ''
        ];
        
        // If user is logged in, save to database
        $auth = new Auth($conn);
        if ($auth->isLoggedIn()) {
            $user = $auth->getUser();
            $geolocation->saveUserLocation(
                $user['id'], 
                $input['lat'], 
                $input['lng'], 
                $input['address'] ?? ''
            );
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Location saved successfully'
        ]);
    } else {
        throw new Exception('Latitude and longitude are required');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

