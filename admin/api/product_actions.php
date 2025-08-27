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

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $productObj);
            break;
        case 'POST':
            handlePostRequest($action, $productObj, $security);
            break;
        case 'PUT':
            handlePutRequest($action, $productObj, $security);
            break;
        case 'DELETE':
            handleDeleteRequest($action, $productObj, $security);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Product API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetRequest($action, $productObj)
{
    switch ($action) {
        case 'details':
            $productId = $_GET['id'] ?? null;
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product ID required']);
                return;
            }

            $product = $productObj->get_product_by_id($productId);
            if (!$product) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                return;
            }

            // Get additional product data
            $product['sales_history'] = $productObj->get_product_sales_history($productId);
            $product['reviews'] = $productObj->get_product_reviews($productId);
            $product['inventory_history'] = $productObj->get_inventory_history($productId);

            echo json_encode(['success' => true, 'data' => $product]);
            break;

        case 'statistics':
            $stats = $productObj->get_product_statistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'export':
            $filters = $_GET;
            unset($filters['action']); // Remove action from filters
            $products = $productObj->get_products_with_filters($filters);
            echo json_encode(['success' => true, 'data' => $products]);
            break;

        case 'preview':
            // Handle product preview
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST; // Fallback to POST data
            }

            $previewData = $productObj->generate_product_preview($input);
            echo json_encode(['success' => true, 'data' => $previewData]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePostRequest($action, $productObj, $security)
{
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'update_status':
            $productId = $input['product_id'] ?? null;
            $newStatus = $input['status'] ?? null;

            if (!$productId || !$newStatus) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product ID and status required']);
                return;
            }

            // Validate status
            $validStatuses = ['active', 'inactive', 'out_of_stock'];
            if (!in_array($newStatus, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                return;
            }

            $result = $productObj->update_product_status($productId, $newStatus);
            if ($result) {
                // Log the action
                $productObj->log_product_action($productId, 'status_update', [
                    'old_status' => $productObj->get_product_status($productId),
                    'new_status' => $newStatus,
                    'admin_id' => $_SESSION['user_id']
                ]);

                echo json_encode(['success' => true, 'message' => 'Product status updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update product status']);
            }
            break;

        case 'bulk_action':
            $productIds = $input['product_ids'] ?? [];
            $bulkAction = $input['action'] ?? '';

            if (empty($productIds) || !$bulkAction) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product IDs and action required']);
                return;
            }

            $result = $productObj->bulk_update_products($productIds, $bulkAction);
            echo json_encode(['success' => true, 'message' => 'Bulk action completed', 'affected' => $result]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePutRequest($action, $productObj, $security)
{
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'update':
            $productId = $input['id'] ?? null;
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product ID required']);
                return;
            }

            // Validate and sanitize input
            $updateData = $security->sanitize_product_data($input);
            $result = $productObj->update_product($productId, $updateData);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update product']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleDeleteRequest($action, $productObj, $security)
{
    switch ($action) {
        case 'delete':
            $productId = $_GET['id'] ?? null;
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product ID required']);
                return;
            }

            // Check if product has active orders
            $hasActiveOrders = $productObj->product_has_active_orders($productId);
            if ($hasActiveOrders) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Cannot delete product with active orders']);
                return;
            }

            $result = $productObj->delete_product($productId);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>