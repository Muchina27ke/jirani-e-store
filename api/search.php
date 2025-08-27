<?php
require_once '../config/config.php';
$db = getDbConnection();
require_once '../includes/Product.php';
require_once '../includes/Geolocation.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Initialize services
$product = new Product($db);
$geolocation = new Geolocation($db);

// Get search parameters
$query = $_GET['q'] ?? '';
$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;
$radiusKm = $_GET['radius'] ?? 10;

// Validate parameters
if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Search query is required']);
    exit;
}

try {
    // Search products
    $products = $product->search_products($query);

    // Format response
    $response = array_map(function ($product) {
        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'seller' => $product['vendor_name'],
            'image' => $product['image_url'],
            'description' => $product['description'],
            'category' => $product['category_name'] ?? null,
            'stock' => $product['stock'],
            'location' => [
                'lat' => $product['location_latitude'] ?? null,
                'lon' => $product['location_longitude'] ?? null
            ]
        ];
    }, $products);

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while searching products']);
}
?>