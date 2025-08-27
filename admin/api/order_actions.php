<?php
require_once '../../config/config.php';
require_once '../../includes/Order.php';
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

// Initialize security and order objects
$security = new Security($conn);
$orderObj = new Order($conn);

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $orderObj);
            break;
        case 'POST':
            handlePostRequest($action, $orderObj, $security);
            break;
        case 'PUT':
            handlePutRequest($action, $orderObj, $security);
            break;
        case 'DELETE':
            handleDeleteRequest($action, $orderObj, $security);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Order API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetRequest($action, $orderObj)
{
    switch ($action) {
        case 'details':
            $orderId = $_GET['id'] ?? null;
            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Order ID required']);
                return;
            }

            $order = $orderObj->get_order_by_id($orderId);
            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                return;
            }

            // Get additional order data
            $order['items'] = $orderObj->get_order_items($orderId);
            $order['payment'] = $orderObj->get_order_payment($orderId);
            $order['customer'] = $orderObj->get_order_customer($orderId);
            $order['vendor'] = $orderObj->get_order_vendor($orderId);

            // Generate HTML for modal
            $html = generateOrderDetailsHTML($order);

            echo json_encode(['success' => true, 'data' => ['order' => $order, 'html' => $html]]);
            break;

        case 'statistics':
            $stats = $orderObj->get_order_statistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'export':
            $filters = $_GET;
            unset($filters['action']); // Remove action from filters
            $orders = $orderObj->get_orders_with_filters($filters);
            echo json_encode(['success' => true, 'data' => $orders]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePostRequest($action, $orderObj, $security)
{
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'update_status':
            $orderId = $input['order_id'] ?? null;
            $newStatus = $input['status'] ?? null;

            if (!$orderId || !$newStatus) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Order ID and status required']);
                return;
            }

            // Validate status
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                return;
            }

            $result = $orderObj->update_order_status($orderId, $newStatus);
            if ($result) {
                // Log the action
                $orderObj->log_order_action($orderId, 'status_update', [
                    'old_status' => $orderObj->get_order_status($orderId),
                    'new_status' => $newStatus,
                    'admin_id' => $_SESSION['user_id']
                ]);

                echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
            }
            break;

        case 'release_escrow':
            $orderId = $input['order_id'] ?? null;

            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Order ID required']);
                return;
            }

            $result = $orderObj->release_escrow($orderId, $_SESSION['user_id']);
            if ($result) {
                // Log the action
                $orderObj->log_order_action($orderId, 'escrow_release', [
                    'admin_id' => $_SESSION['user_id']
                ]);

                echo json_encode(['success' => true, 'message' => 'Escrow released successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to release escrow']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePutRequest($action, $orderObj, $security)
{
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'update':
            $orderId = $input['id'] ?? null;
            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Order ID required']);
                return;
            }

            // Validate and sanitize input
            $updateData = $security->sanitize_order_data($input);
            $result = $orderObj->update_order($orderId, $updateData);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update order']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleDeleteRequest($action, $orderObj, $security)
{
    switch ($action) {
        case 'delete':
            $orderId = $_GET['id'] ?? null;
            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Order ID required']);
                return;
            }

            // Check if order can be deleted (not completed/delivered)
            $order = $orderObj->get_order_by_id($orderId);
            if ($order && in_array($order['status'], ['delivered', 'completed'])) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Cannot delete completed orders']);
                return;
            }

            $result = $orderObj->delete_order($orderId);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete order']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function generateOrderDetailsHTML($order)
{
    $html = '<div class="row">';

    // Order Information
    $html .= '<div class="col-md-6">';
    $html .= '<h5><i class="fas fa-shopping-cart"></i> Order Information</h5>';
    $html .= '<table class="table table-sm">';
    $html .= '<tr><td><strong>Order ID:</strong></td><td>#' . $order['id'] . '</td></tr>';
    $html .= '<tr><td><strong>Status:</strong></td><td><span class="badge badge-' .
        ($order['status'] === 'completed' ? 'success' :
            ($order['status'] === 'pending' ? 'warning' :
                ($order['status'] === 'processing' ? 'info' :
                    ($order['status'] === 'shipped' ? 'primary' : 'danger')))) .
        '">' . ucfirst($order['status']) . '</span></td></tr>';
    $html .= '<tr><td><strong>Date:</strong></td><td>' . date('M j, Y H:i', strtotime($order['created_at'])) . '</td></tr>';
    $html .= '<tr><td><strong>Total Amount:</strong></td><td>KSh ' . number_format($order['total_amount'] ?? 0, 2) . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';

    // Customer Information
    $html .= '<div class="col-md-6">';
    $html .= '<h5><i class="fas fa-user"></i> Customer Information</h5>';
    $html .= '<table class="table table-sm">';
    $html .= '<tr><td><strong>Name:</strong></td><td>' . htmlspecialchars($order['customer']['name'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($order['customer']['phone'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($order['customer']['email'] ?? 'N/A') . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';

    $html .= '</div>';

    // Vendor Information
    if ($order['vendor']) {
        $html .= '<hr><div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<h5><i class="fas fa-store"></i> Vendor Information</h5>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>Business:</strong></td><td>' . htmlspecialchars($order['vendor']['business_name']) . '</td></tr>';
        $html .= '<tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($order['vendor']['phone'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($order['vendor']['email'] ?? 'N/A') . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';

        // Payment Information
        $html .= '<div class="col-md-6">';
        $html .= '<h5><i class="fas fa-credit-card"></i> Payment Information</h5>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>Status:</strong></td><td><span class="badge badge-' .
            ($order['payment']['status'] === 'completed' ? 'success' :
                ($order['payment']['status'] === 'pending' ? 'warning' : 'secondary')) .
            '">' . ucfirst($order['payment']['status'] ?? 'N/A') . '</span></td></tr>';
        $html .= '<tr><td><strong>Method:</strong></td><td>' . htmlspecialchars($order['payment']['method'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td><strong>Amount:</strong></td><td>KSh ' . number_format($order['payment']['amount'] ?? 0, 2) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Order Items
    if (!empty($order['items'])) {
        $html .= '<hr><h5><i class="fas fa-box"></i> Order Items (' . count($order['items']) . ')</h5>';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-sm table-striped">';
        $html .= '<thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th></tr></thead><tbody>';

        foreach ($order['items'] as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['product_name']) . '</td>';
            $html .= '<td>KSh ' . number_format($item['price'], 2) . '</td>';
            $html .= '<td>' . $item['quantity'] . '</td>';
            $html .= '<td>KSh ' . number_format($item['price'] * $item['quantity'], 2) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';
    }

    return $html;
}
?>