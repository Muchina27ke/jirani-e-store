<?php
require_once dirname(__DIR__) . "/config/config.php";

// Handle location saving
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lat = $_POST['lat'] ?? null;
    $lon = $_POST['lon'] ?? null;
    
    if ($lat && $lon) {
        $_SESSION['user_location'] = [
            'lat' => floatval($lat),
            'lon' => floatval($lon)
        ];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

