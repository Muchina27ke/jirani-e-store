<?php
require_once '../../config/config.php';
require_once '../../includes/Vendor.php';
require_once '../../includes/Security.php';
require_once __DIR__ . '/../../includes/Product.php';
require_once __DIR__ . '/../../includes/Order.php';

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

// Initialize security and vendor objects
$security = new Security($conn);
$vendorObj = new Vendor($conn);
$productObj = new Product($conn);
$orderObj = new Order($conn);

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $vendorObj, $productObj, $orderObj);
            break;
        case 'POST':
            handlePostRequest($action, $vendorObj, $security);
            break;
        case 'PUT':
            handlePutRequest($action, $vendorObj, $security);
            break;
        case 'DELETE':
            handleDeleteRequest($action, $vendorObj, $security);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Vendor API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetRequest($action, $vendorObj, $productObj, $orderObj)
{
    switch ($action) {
        case 'details':
            $vendorId = $_GET['id'] ?? null;
            if (!$vendorId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Vendor ID required']);
                return;
            }

            $vendor = $vendorObj->get_vendor_by_user_id($vendorId);
            if (!$vendor) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Vendor not found']);
                return;
            }

            // Get additional vendor data
            $vendor['products'] = $productObj->get_products_by_vendor($vendorId);
            $vendor['orders'] = $orderObj->get_orders_by_vendor($vendorId);
            $vendor['statistics'] = $vendorObj->get_vendor_statistics($vendorId);

            // Generate HTML for modal
            $html = generateVendorDetailsHTML($vendor);

            echo json_encode(['success' => true, 'data' => ['vendor' => $vendor, 'html' => $html]]);
            break;

        case 'statistics':
            $stats = $vendorObj->get_vendor_statistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'export':
            $filters = $_GET;
            unset($filters['action']); // Remove action from filters
            $vendors = $vendorObj->get_vendors_with_filters($filters);
            echo json_encode(['success' => true, 'data' => $vendors]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePostRequest($action, $vendorObj, $security)
{
    global $conn;
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'approve':
            $vendorId = $input['vendor_id'] ?? null;

            if (!$vendorId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Vendor ID required']);
                return;
            }

            $result = $vendorObj->approve_vendor($vendorId);
            if ($result) {
                // Log the action
                $vendorObj->log_vendor_action($vendorId, 'approval', [
                    'admin_id' => $_SESSION['user_id'],
                    'reason' => $input['reason'] ?? 'Approved by admin'
                ]);

                echo json_encode(['success' => true, 'message' => 'Vendor approved successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to approve vendor']);
            }
            break;

        case 'reject':
            $vendorId = $input['vendor_id'] ?? null;
            $reason = $input['reason'] ?? '';

            if (!$vendorId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Vendor ID required']);
                return;
            }

            $result = $vendorObj->reject_vendor($vendorId, $reason);
            if ($result) {
                // Log the action
                $vendorObj->log_vendor_action($vendorId, 'rejection', [
                    'admin_id' => $_SESSION['user_id'],
                    'reason' => $reason
                ]);

                echo json_encode(['success' => true, 'message' => 'Vendor rejected successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to reject vendor']);
            }
            break;

        case 'update_status':
            $vendorId = $input['vendor_id'] ?? null;
            $newStatus = $input['status'] ?? null;

            if (!$vendorId || !$newStatus) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Vendor ID and status required']);
                return;
            }

            // Validate status
            $validStatuses = ['approved', 'pending', 'rejected'];
            if (!in_array($newStatus, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                return;
            }

            $result = $vendorObj->update_vendor_status($vendorId, $newStatus);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Vendor status updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update vendor status']);
            }
            break;

        case 'add':
            // Sanitize input
            $businessName = $security->sanitizeInput($input['business_name'] ?? '');
            $name = $security->sanitizeInput($input['name'] ?? '');
            $email = $security->sanitizeInput($input['email'] ?? '');
            $phone = $security->sanitizeInput($input['phone'] ?? '');
            $location = $security->sanitizeInput($input['location'] ?? '');
            $description = $security->sanitizeInput($input['description'] ?? '');

            // Validate required fields
            if (empty($businessName) || empty($name) || empty($email) || empty($phone) || empty($location)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled out.']);
                return;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
                return;
            }
            if (!preg_match('/^(\+254|0)[17]\d{8}$/', $phone)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Please use a valid Kenyan number.']);
                return;
            }
            try {
                $conn->begin_transaction();
                // Check for duplicate name/email
                $stmt = $conn->prepare("SELECT id FROM users WHERE name = ? OR email = ?");
                $stmt->bind_param("ss", $name, $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Name or email already exists.");
                }
                // Generate a temporary password
                $tempPassword = bin2hex(random_bytes(8));
                $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
                // Create user
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role, created_at) VALUES (?, ?, ?, ?, 'vendor', NOW())");
                $stmt->bind_param("ssss", $name, $email, $hashedPassword, $phone);
                $stmt->execute();
                $userId = $conn->insert_id;
                // Create vendor profile
                $stmt = $conn->prepare("INSERT INTO vendors (user_id, business_name, description, location, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
                $stmt->bind_param("isss", $userId, $businessName, $description, $location);
                $stmt->execute();
                $vendorId = $conn->insert_id;
                // Create verification record
                $stmt = $conn->prepare("INSERT INTO verifications (vendor_id, status, created_at) VALUES (?, 'pending', NOW())");
                $stmt->bind_param("i", $vendorId);
                $stmt->execute();
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Vendor added successfully.']);
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to add vendor: ' . $e->getMessage()]);
            }
            return;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePutRequest($action, $vendorObj, $security)
{
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'update':
            $vendorId = $input['id'] ?? null;
            if (!$vendorId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Vendor ID required']);
                return;
            }

            // Sanitize input fields (fallback if sanitize_vendor_data does not exist)
            $fields = ['business_name', 'business_cert', 'description', 'location', 'status', 'name', 'email', 'phone'];
            $updateData = [];
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $security->sanitizeInput($input[$field]);
                }
            }

            $result = $vendorObj->update_vendor($vendorId, $updateData);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Vendor updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update vendor']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleDeleteRequest($action, $vendorObj, $security)
{
    switch ($action) {
        case 'delete':
            $vendorId = $_GET['id'] ?? null;
            if (!$vendorId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Vendor ID required']);
                return;
            }

            // Check if vendor has active products or orders
            $hasActiveProducts = $vendorObj->vendor_has_active_products($vendorId);
            $hasActiveOrders = $vendorObj->vendor_has_active_orders($vendorId);

            if ($hasActiveProducts || $hasActiveOrders) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Cannot delete vendor with active products or orders']);
                return;
            }

            $result = $vendorObj->delete_vendor($vendorId);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Vendor deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete vendor']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function generateVendorDetailsHTML($vendor)
{
    $html = '<div class="row">';

    // Vendor Information
    $html .= '<div class="col-md-6">';
    $html .= '<h5><i class="fas fa-store"></i> Business Information</h5>';
    $html .= '<table class="table table-sm">';
    $html .= '<tr><td><strong>Business Name:</strong></td><td>' . htmlspecialchars($vendor['business_name']) . '</td></tr>';
    $html .= '<tr><td><strong>Contact Person:</strong></td><td>' . htmlspecialchars(isset($vendor['name']) ? $vendor['name'] : '') . '</td></tr>';
    $html .= '<tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($vendor['phone'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($vendor['email'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td><strong>Status:</strong></td><td><span class="badge badge-' .
        ($vendor['status'] === 'approved' ? 'success' : ($vendor['status'] === 'pending' ? 'warning' : 'danger')) .
        '">' . ucfirst($vendor['status']) . '</span></td></tr>';
    $html .= '<tr><td><strong>Joined:</strong></td><td>' . date('M j, Y', strtotime(isset($vendor['created_at']) ? $vendor['created_at'] : '')) . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';

    // Statistics
    $html .= '<div class="col-md-6">';
    $html .= '<h5><i class="fas fa-chart-bar"></i> Statistics</h5>';
    $html .= '<table class="table table-sm">';
    $html .= '<tr><td><strong>Total Products:</strong></td><td>' . count($vendor['products']) . '</td></tr>';
    $html .= '<tr><td><strong>Total Orders:</strong></td><td>' . count($vendor['orders']) . '</td></tr>';
    $html .= '<tr><td><strong>Total Revenue:</strong></td><td>KSh ' . number_format(isset($vendor['statistics']['total_revenue']) ? $vendor['statistics']['total_revenue'] : 0, 2) . '</td></tr>';
    $html .= '<tr><td><strong>Active Products:</strong></td><td>' . (isset($vendor['statistics']['active_products']) ? $vendor['statistics']['active_products'] : 0) . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';

    $html .= '</div>';

    // Products Section
    if (!empty($vendor['products'])) {
        $html .= '<hr><h5><i class="fas fa-box"></i> Products (' . count($vendor['products']) . ')</h5>';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-sm table-striped">';
        $html .= '<thead><tr><th>Name</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead><tbody>';

        foreach (array_slice($vendor['products'], 0, 5) as $product) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($product['name']) . '</td>';
            $html .= '<td>KSh ' . number_format($product['price'], 2) . '</td>';
            $html .= '<td>' . $product['stock'] . '</td>';
            $html .= '<td><span class="badge badge-' .
                ($product['status'] === 'active' ? 'success' : 'secondary') .
                '">' . ucfirst($product['status']) . '</span></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        if (count($vendor['products']) > 5) {
            $html .= '<p class="text-muted">Showing first 5 products. <a href="products.php?vendor_id=' . (isset($vendor['id']) ? $vendor['id'] : '') . '">View all products</a></p>';
        }
    }

    // Recent Orders Section
    if (!empty($vendor['orders'])) {
        $html .= '<hr><h5><i class="fas fa-shopping-cart"></i> Recent Orders (' . count($vendor['orders']) . ')</h5>';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-sm table-striped">';
        $html .= '<thead><tr><th>Order ID</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>';

        foreach (array_slice($vendor['orders'], 0, 5) as $order) {
            $html .= '<tr>';
            $html .= '<td>#' . $order['id'] . '</td>';
            $html .= '<td>' . htmlspecialchars($order['customer_name']) . '</td>';
            $html .= '<td>KSh ' . number_format(isset($order['total_amount']) ? $order['total_amount'] : '0.00', 2) . '</td>';
            $html .= '<td><span class="badge badge-' .
                ($order['status'] === 'completed' ? 'success' :
                    ($order['status'] === 'pending' ? 'warning' : 'info')) .
                '">' . ucfirst($order['status']) . '</span></td>';
            $html .= '<td>' . date('M j, Y', strtotime($order['created_at'])) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        if (count($vendor['orders']) > 5) {
            $html .= '<p class="text-muted">Showing first 5 orders. <a href="orders.php?vendor_id=' . (isset($vendor['id']) ? $vendor['id'] : '') . '">View all orders</a></p>';
        }
    }

    return $html;
}
?>