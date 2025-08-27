<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

// Check if user is logged in and is a vendor
if (!Auth::isVendor()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get vendor ID
$vendorId = $_SESSION['user']['id'];

// Validate input
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid zone ID']);
    exit;
}

try {
    // Get polygon data
    $stmt = $db->prepare("
        SELECT ST_AsText(polygon) as polygon_wkt
        FROM delivery_zone_polygons dzp
        JOIN delivery_zones dz ON dzp.zone_id = dz.id
        WHERE dz.id = ? AND dz.vendor_id = ?
    ");
    $stmt->execute([$id, $vendorId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        http_response_code(404);
        echo json_encode(['error' => 'Zone not found']);
        exit;
    }

    // Convert WKT to array of coordinates
    $wkt = $result['polygon_wkt'];
    preg_match('/POLYGON\(\((.*)\)\)/', $wkt, $matches);
    $coordinates = array_map(function ($pair) {
        list($lon, $lat) = explode(' ', $pair);
        return [(float) $lat, (float) $lon];
    }, explode(',', $matches[1]));

    echo json_encode(['polygon' => $coordinates]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve polygon data']);
}