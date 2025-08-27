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
    error_log("Payment API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetRequest($action, $orderObj)
{
    switch ($action) {
        case 'details':
            $paymentId = $_GET['id'] ?? null;
            if (!$paymentId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Payment ID required']);
                return;
            }

            $payment = $orderObj->get_payment_by_id($paymentId);
            if (!$payment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                return;
            }

            // Get additional payment data
            $payment['order'] = $orderObj->get_order_by_id($payment['order_id']);
            $payment['customer'] = $orderObj->get_order_customer($payment['order_id']);
            $payment['vendor'] = $orderObj->get_order_vendor($payment['order_id']);

            // Generate HTML for modal
            $html = generatePaymentDetailsHTML($payment);

            echo json_encode(['success' => true, 'data' => ['payment' => $payment, 'html' => $html]]);
            break;

        case 'statistics':
            $stats = $orderObj->get_payment_statistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'export':
            $filters = $_GET;
            unset($filters['action']); // Remove action from filters
            $payments = $orderObj->get_payments_with_filters($filters);
            echo json_encode(['success' => true, 'data' => $payments]);
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
        case 'release_escrow':
            $orderId = $input['order_id'] ?? null;

            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Order ID required']);
                return;
            }

            $result = $orderObj->release_escrow($orderId);
            if ($result) {
                // Log the action
                $orderObj->log_payment_action($orderId, 'escrow_release', [
                    'admin_id' => $_SESSION['user_id']
                ]);

                echo json_encode(['success' => true, 'message' => 'Escrow released successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to release escrow']);
            }
            break;

        case 'refund':
            $paymentId = $input['payment_id'] ?? null;
            $reason = $input['reason'] ?? 'Refunded by admin';

            if (!$paymentId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Payment ID required']);
                return;
            }

            $result = $orderObj->refund_payment($paymentId, $reason);
            if ($result) {
                // Log the action
                $payment = $orderObj->get_payment_by_id($paymentId);
                $orderObj->log_payment_action($payment['order_id'], 'refund', [
                    'admin_id' => $_SESSION['user_id'],
                    'reason' => $reason
                ]);

                echo json_encode(['success' => true, 'message' => 'Payment refunded successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to refund payment']);
            }
            break;

        case 'update_status':
            $paymentId = $input['payment_id'] ?? null;
            $newStatus = $input['status'] ?? null;

            if (!$paymentId || !$newStatus) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Payment ID and status required']);
                return;
            }

            // Validate status
            $validStatuses = ['pending', 'completed', 'failed', 'refunded'];
            if (!in_array($newStatus, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                return;
            }

            $result = $orderObj->update_payment_status($paymentId, $newStatus);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Payment status updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
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
            $paymentId = $input['id'] ?? null;
            if (!$paymentId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Payment ID required']);
                return;
            }

            // Validate and sanitize input
            $updateData = $security->sanitize_payment_data($input);
            $result = $orderObj->update_payment($paymentId, $updateData);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Payment updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update payment']);
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
            $paymentId = $_GET['id'] ?? null;
            if (!$paymentId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Payment ID required']);
                return;
            }

            // Check if payment can be deleted (not completed)
            $payment = $orderObj->get_payment_by_id($paymentId);
            if ($payment && $payment['status'] === 'completed') {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Cannot delete completed payments']);
                return;
            }

            $result = $orderObj->delete_payment($paymentId);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Payment deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete payment']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function generatePaymentDetailsHTML($payment)
{
    $html = '<div class="row">';

    // Payment Information
    $html .= '<div class="col-md-6">';
    $html .= '<h5><i class="fas fa-credit-card"></i> Payment Information</h5>';
    $html .= '<table class="table table-sm">';
    $html .= '<tr><td><strong>Payment ID:</strong></td><td>#' . $payment['id'] . '</td></tr>';
    $html .= '<tr><td><strong>Order ID:</strong></td><td>#' . $payment['order_id'] . '</td></tr>';
    $html .= '<tr><td><strong>Amount:</strong></td><td>KSh ' . number_format($payment['amount'], 2) . '</td></tr>';
    $html .= '<tr><td><strong>Method:</strong></td><td>' . htmlspecialchars($payment['method']) . '</td></tr>';
    $html .= '<tr><td><strong>Status:</strong></td><td><span class="badge badge-' .
        ($payment['status'] === 'completed' ? 'success' :
            ($payment['status'] === 'pending' ? 'warning' :
                ($payment['status'] === 'failed' ? 'danger' : 'secondary'))) .
        '">' . ucfirst($payment['status']) . '</span></td></tr>';
    $html .= '<tr><td><strong>Date:</strong></td><td>' . date('M j, Y H:i', strtotime($payment['created_at'])) . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';

    // Customer Information
    $html .= '<div class="col-md-6">';
    $html .= '<h5><i class="fas fa-user"></i> Customer Information</h5>';
    $html .= '<table class="table table-sm">';
    $html .= '<tr><td><strong>Name:</strong></td><td>' . htmlspecialchars($payment['customer']['name'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($payment['customer']['phone'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($payment['customer']['email'] ?? 'N/A') . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';

    $html .= '</div>';

    // Vendor Information
    if ($payment['vendor']) {
        $html .= '<hr><div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<h5><i class="fas fa-store"></i> Vendor Information</h5>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>Business:</strong></td><td>' . htmlspecialchars($payment['vendor']['business_name']) . '</td></tr>';
        $html .= '<tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($payment['vendor']['phone'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($payment['vendor']['email'] ?? 'N/A') . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';

        // Escrow Information
        $html .= '<div class="col-md-6">';
        $html .= '<h5><i class="fas fa-lock"></i> Escrow Information</h5>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>Escrow:</strong></td><td>' . ($payment['is_escrow'] ? 'Yes' : 'No') . '</td></tr>';
        $escrow_status = isset($payment['escrow_status']) ? $payment['escrow_status'] : ($payment['is_escrow'] ? 'Held' : 'N/A');
        $html .= '<tr><td><strong>Status:</strong></td><td><span class="badge badge-' .
            ($escrow_status === 'released' ? 'success' : 'warning') .
            '">' . ucfirst($escrow_status) . '</span></td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Order Information
    if ($payment['order']) {
        $html .= '<hr><h5><i class="fas fa-shopping-cart"></i> Order Information</h5>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><td><strong>Order Status:</strong></td><td><span class="badge badge-' .
            ($payment['order']['status'] === 'completed' ? 'success' :
                ($payment['order']['status'] === 'pending' ? 'warning' : 'info')) .
            '">' . ucfirst($payment['order']['status']) . '</span></td></tr>';
        $html .= '<tr><td><strong>Order Date:</strong></td><td>' . date('M j, Y H:i', strtotime($payment['order']['created_at'])) . '</td></tr>';
        $html .= '<tr><td><strong>Total Amount:</strong></td><td>KSh ' . number_format($payment['order']['total_amount'] ?? 0, 2) . '</td></tr>';
        $html .= '</table>';
    }

    return $html;
}
?>