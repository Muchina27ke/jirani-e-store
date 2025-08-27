<?php
require_once '../../config/config.php';
require_once '../../includes/Product.php';
require_once '../../includes/Security.php';

// Set JSON response header
header('Content-Type: application/json');

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access - Admin role required']);
    exit;
}

// Initialize security and product objects
$security = new Security($conn);
$productObj = new Product($conn);

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $vendorId = (int) ($_POST['vendor_id'] ?? 0);
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $location_lat = !empty($_POST['location_lat']) ? (float) $_POST['location_lat'] : null;
    $location_lng = !empty($_POST['location_lng']) ? (float) $_POST['location_lng'] : null;

    // Validate required fields
    if (empty($name)) {
        throw new Exception('Product name is required');
    }
    if ($vendorId <= 0) {
        throw new Exception('Please select a valid vendor');
    }
    if ($categoryId <= 0) {
        throw new Exception('Please select a valid category');
    }
    if ($price <= 0) {
        throw new Exception('Price must be greater than zero');
    }
    if ($stock < 0) {
        throw new Exception('Stock cannot be negative');
    }

    // Get vendor and category information
    $vendor_stmt = $conn->prepare("SELECT business_name FROM vendors WHERE user_id = ?");
    $vendor_stmt->bind_param("i", $vendorId);
    $vendor_stmt->execute();
    $vendor_result = $vendor_stmt->get_result()->fetch_assoc();

    $category_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $category_stmt->bind_param("i", $categoryId);
    $category_stmt->execute();
    $category_result = $category_stmt->get_result()->fetch_assoc();

    if (!$vendor_result) {
        throw new Exception('Invalid vendor selected');
    }
    if (!$category_result) {
        throw new Exception('Invalid category selected');
    }

    // Handle image upload for preview
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            throw new Exception('Invalid image format. Only JPG, PNG, GIF, and WebP are allowed.');
        }

        if ($_FILES['image']['size'] > $maxSize) {
            throw new Exception('Image size exceeds 5MB limit.');
        }

        // Create temporary preview image
        $uploadDir = '../../uploads/temp/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'preview_' . uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imageUrl = 'uploads/temp/' . $filename;
        }
    }

    // Create preview data
    $previewData = [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'stock' => $stock,
        'status' => $status,
        'vendor_name' => $vendor_result['business_name'],
        'category_name' => $category_result['name'],
        'image_url' => $imageUrl,
        'location_lat' => $location_lat,
        'location_lng' => $location_lng,
        'created_at' => date('Y-m-d H:i:s'),
        'preview' => true
    ];

    // Calculate estimated metrics
    $previewData['estimated_metrics'] = [
        'revenue_potential' => $price * $stock,
        'stock_value' => $price * $stock,
        'status_class' => $status === 'active' ? 'success' : ($status === 'inactive' ? 'secondary' : 'danger'),
        'stock_class' => $stock > 0 ? 'success' : 'danger'
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Product preview generated successfully',
        'data' => $previewData
    ]);

} catch (Exception $e) {
    error_log("Product preview error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>