<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$lat = $data['lat'] ?? null;
$lon = $data['lon'] ?? null;

if (!$lat || !$lon) {
    http_response_code(400);
    echo json_encode(['error' => 'Latitude and longitude are required']);
    exit;
}

// Store location in session
$_SESSION['user_location'] = [
    'lat' => $lat,
    'lon' => $lon
];

echo json_encode(['success' => true]);