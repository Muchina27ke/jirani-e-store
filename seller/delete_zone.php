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

if (!$id) {
    $_SESSION['error'] = 'Invalid zone ID';
    header('Location: /vendor/delivery_zones.php');
    exit;
}

try {
    // Initialize Geolocation service
    $geolocation = new Geolocation($db);

    // Delete zone
    $success = $geolocation->deleteDeliveryZone($id, $vendorId);

    if ($success) {
        $_SESSION['success'] = 'Delivery zone deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete delivery zone';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to delete delivery zone: ' . $e->getMessage();
}

header('Location: /vendor/delivery_zones.php');
exit;