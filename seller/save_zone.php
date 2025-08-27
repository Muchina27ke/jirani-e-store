<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

// Check if user is logged in and is a vendor
if (!Auth::isVendor()) {
    header('Location: /login.php');
    exit;
}

// Get vendor ID
$vendorId = $_SESSION['user']['id'];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: /vendor/delivery_zones.php');
    exit;
}

// Validate input
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$radiusKm = filter_input(INPUT_POST, 'radius_km', FILTER_VALIDATE_FLOAT);
$centerLat = filter_input(INPUT_POST, 'center_latitude', FILTER_VALIDATE_FLOAT);
$centerLon = filter_input(INPUT_POST, 'center_longitude', FILTER_VALIDATE_FLOAT);
$isActive = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_BOOLEAN);
$polygon = filter_input(INPUT_POST, 'polygon', FILTER_SANITIZE_STRING);

if (!$name || !$radiusKm || !$centerLat || !$centerLon) {
    $_SESSION['error'] = 'Invalid input parameters';
    header('Location: /vendor/delivery_zones.php');
    exit;
}

// Validate coordinates
if ($centerLat < -90 || $centerLat > 90 || $centerLon < -180 || $centerLon > 180) {
    $_SESSION['error'] = 'Invalid coordinates';
    header('Location: /vendor/delivery_zones.php');
    exit;
}

// Validate radius
if ($radiusKm < 0.1 || $radiusKm > 50) {
    $_SESSION['error'] = 'Invalid delivery radius';
    header('Location: /vendor/delivery_zones.php');
    exit;
}

try {
    // Initialize Geolocation service
    $geolocation = new Geolocation($db);

    // Prepare zone data
    $zoneData = [
        'name' => $name,
        'center_latitude' => $centerLat,
        'center_longitude' => $centerLon,
        'radius_km' => $radiusKm,
        'is_active' => $isActive,
        'vendor_id' => $vendorId
    ];

    // Add ID if editing existing zone
    if ($id) {
        $zoneData['id'] = $id;
    }

    // Add polygon if provided
    if ($polygon) {
        $zoneData['polygon'] = $polygon;
    }

    // Save zone
    $zoneId = $geolocation->saveDeliveryZone($zoneData);

    $_SESSION['success'] = 'Delivery zone saved successfully';
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to save delivery zone: ' . $e->getMessage();
}

header('Location: /vendor/delivery_zones.php');
exit;