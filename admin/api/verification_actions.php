<?php
require_once '../../config/config.php';
require_once '../../includes/VendorVerification.php';
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

// Initialize security and verification objects
$security = new Security($conn);
$verificationObj = new VendorVerification($conn);

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $verificationObj);
            break;
        case 'POST':
            handlePostRequest($action, $verificationObj, $security);
            break;
        case 'PUT':
            handlePutRequest($action, $verificationObj, $security);
            break;
        case 'DELETE':
            handleDeleteRequest($action, $verificationObj, $security);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Verification API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetRequest($action, $verificationObj)
{
    switch ($action) {
        case 'details':
            $verificationId = $_GET['id'] ?? null;
            if (!$verificationId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Verification ID required']);
                return;
            }

            $verification = $verificationObj->getVerificationStatus($verificationId);
            if (!$verification) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Verification not found']);
                return;
            }

            // Generate HTML for modal
            $html = generateVerificationDetailsHTML($verification);

            echo json_encode(['success' => true, 'data' => ['vendor' => $verification['vendor'], 'html' => $html]]);
            break;

        case 'statistics':
            $stats = $verificationObj->get_verification_statistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'export':
            $filters = $_GET;
            unset($filters['action']); // Remove action from filters
            $verifications = $verificationObj->get_verifications_with_filters($filters);
            echo json_encode(['success' => true, 'data' => $verifications]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePostRequest($action, $verificationObj, $security)
{
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'approve':
            $verificationId = $input['verification_id'] ?? null;
            $reason = $input['reason'] ?? 'Approved by admin';

            if (!$verificationId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Verification ID required']);
                return;
            }

            // Update both verifications and vendors tables
            try {
                $conn = getDbConnection();
                $conn->begin_transaction();
                
                // Update verifications table
                $stmt1 = $conn->prepare("UPDATE verifications SET status = 'approved', verified_by = ?, verified_at = NOW(), updated_at = NOW() WHERE vendor_id = ?");
                $stmt1->bind_param('ii', $_SESSION['user_id'], $verificationId);
                $result1 = $stmt1->execute();
                
                // Update vendors table
                $stmt2 = $conn->prepare("UPDATE vendors SET status = 'approved', updated_at = NOW() WHERE user_id = ?");
                $stmt2->bind_param('i', $verificationId);
                $result2 = $stmt2->execute();
                
                if ($result1 && $result2) {
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Vendor approved successfully - both verification and vendor status updated']);
                } else {
                    $conn->rollback();
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to approve vendor - database update failed']);
                }
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            break;

        case 'reject':
            $verificationId = $input['verification_id'] ?? null;
            $reason = $input['reason'] ?? '';

            if (!$verificationId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Verification ID required']);
                return;
            }

            if (empty($reason)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
                return;
            }

            // Update both verifications and vendors tables
            try {
                $conn = getDbConnection();
                $conn->begin_transaction();
                
                // Update verifications table
                $stmt1 = $conn->prepare("UPDATE verifications SET status = 'rejected', rejection_reason = ?, verified_by = ?, verified_at = NOW(), updated_at = NOW() WHERE vendor_id = ?");
                $stmt1->bind_param('sii', $reason, $_SESSION['user_id'], $verificationId);
                $result1 = $stmt1->execute();
                
                // Update vendors table
                $stmt2 = $conn->prepare("UPDATE vendors SET status = 'rejected', updated_at = NOW() WHERE user_id = ?");
                $stmt2->bind_param('i', $verificationId);
                $result2 = $stmt2->execute();
                
                if ($result1 && $result2) {
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Vendor rejected successfully - both verification and vendor status updated']);
                } else {
                    $conn->rollback();
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to reject vendor - database update failed']);
                }
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            break;

        case 'revoke':
            $verificationId = $input['verification_id'] ?? null;
            $adminId = $_SESSION['user_id'] ?? null;
            $reason = $input['reason'] ?? 'Verification revoked by admin';

            if (!$verificationId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Verification ID required']);
                return;
            }

            // Revoke verification - set both tables back to pending
            try {
                $conn = getDbConnection();
                $conn->begin_transaction();
                
                // Update verifications table back to pending
                $stmt1 = $conn->prepare("UPDATE verifications SET status = 'pending', verified_by = NULL, verified_at = NULL, updated_at = NOW() WHERE vendor_id = ?");
                $stmt1->bind_param('i', $verificationId);
                $result1 = $stmt1->execute();
                
                // Update vendors table back to pending
                $stmt2 = $conn->prepare("UPDATE vendors SET status = 'pending', updated_at = NOW() WHERE user_id = ?");
                $stmt2->bind_param('i', $verificationId);
                $result2 = $stmt2->execute();
                
                if ($result1 && $result2) {
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Verification revoked successfully - vendor status set back to pending']);
                } else {
                    $conn->rollback();
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to revoke verification - database update failed']);
                }
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            break;

        case 'update_status':
            $verificationId = $input['verification_id'] ?? null;
            $newStatus = $input['status'] ?? null;
            $adminId = $_SESSION['user_id'] ?? null;

            if (!$verificationId || !$newStatus) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Verification ID and status required']);
                return;
            }

            // Validate status
            $validStatuses = ['pending', 'approved', 'rejected'];
            if (!in_array($newStatus, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                return;
            }

            $result = $verificationObj->update_verification_status($verificationId, $newStatus, $adminId);
            echo json_encode($result);
            break;

        case 'add':
            // Validate required fields
            $required = ['business_name', 'name', 'email', 'phone', 'location'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
                    return;
                }
            }
            $businessName = trim($input['business_name']);
            $name = trim($input['name']);
            $email = trim($input['email']);
            $phone = trim($input['phone']);
            $location = trim($input['location']);
            $description = trim($input['description'] ?? '');

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                return;
            }
            // Validate phone (basic Kenyan format)
            if (!preg_match('/^(\\+254|0)[17]\\d{8}$/', $phone)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Use a valid Kenyan number.']);
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
                $tempPassword = bin2hex(random_bytes(4));
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
                // Send welcome email
                $to = $email;
                $subject = "Welcome to Jirani - Your Vendor Account";
                // Send vendor welcome email using PHPMailer and template
                require_once '../../includes/EmailTemplate.php';
                $emailNotifications = new EmailNotifications($conn);
                $variables = [
                    'vendor_name' => $name,
                    'business_name' => $businessName,
                    'vendor_email' => $email,
                    'temp_password' => $tempPassword,
                    'login_url' => SITE_URL . 'seller/login.php',
                ];
                $emailSent = $emailNotifications->emailTemplate->send(
                    $email,
                    'Welcome to Jirani! Your Vendor Account is Ready',
                    'vendor_welcome',
                    $variables
                );
                if (!$emailSent) {
                    error_log('Failed to send vendor welcome email to ' . $email);
                }
                echo json_encode(['success' => true, 'message' => 'Vendor added successfully. A welcome email has been sent.']);
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to add vendor: ' . $e->getMessage()]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePutRequest($action, $verificationObj, $security)
{
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'update':
            $verificationId = $input['id'] ?? null;
            if (!$verificationId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Verification ID required']);
                return;
            }

            // Validate and sanitize input
            $updateData = $security->sanitize_verification_data($input);
            $result = $verificationObj->update_verification($verificationId, $updateData);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Verification updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update verification']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleDeleteRequest($action, $verificationObj, $security)
{
    switch ($action) {
        case 'delete':
            $verificationId = $_GET['id'] ?? null;
            if (!$verificationId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Verification ID required']);
                return;
            }

            $result = $verificationObj->delete_verification($verificationId);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Verification deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete verification']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function generateVerificationDetailsHTML($verification)
{
    $html = '<div class="row">';

    // Verification Information
    $html .= '<div class="col-md-6">';
    $html .= '<h5><i class="fas fa-clipboard-check"></i> Verification Information</h5>';
    $html .= '<table class="table table-sm">';
    $html .= '<tr><td><strong>Verification ID:</strong></td><td>#' . $verification['vendor']['user_id'] . '</td></tr>';
    $html .= '<tr><td><strong>Status:</strong></td><td><span class="badge badge-' .
        ($verification['vendor']['status'] === 'approved' ? 'success' :
            ($verification['vendor']['status'] === 'pending' ? 'warning' : 'danger')) .
        '">' . ucfirst($verification['vendor']['status']) . '</span></td></tr>';
    $html .= '<tr><td><strong>Submitted:</strong></td><td>' . date('M j, Y H:i', strtotime($verification['vendor']['created_at'])) . '</td></tr>';
    if ($verification['vendor']['status'] !== 'pending') {
        $html .= '<tr><td><strong>Reviewed:</strong></td><td>' . date('M j, Y H:i', strtotime($verification['vendor']['updated_at'])) . '</td></tr>';
    }
    $html .= '</table>';
    $html .= '</div>';

    // Vendor Information
    $html .= '<div class="col-md-6">';
    $html .= '<h5><i class="fas fa-store"></i> Vendor Information</h5>';
    $html .= '<table class="table table-sm">';
    $html .= '<tr><td><strong>Business Name:</strong></td><td>' . htmlspecialchars($verification['vendor']['business_name']) . '</td></tr>';
    $html .= '<tr><td><strong>Contact Person:</strong></td><td>' . htmlspecialchars($verification['vendor']['name']) . '</td></tr>';
    $html .= '<tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($verification['vendor']['phone'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($verification['vendor']['email'] ?? 'N/A') . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';

    $html .= '</div>';

    // Documents Section
    if (!empty($verification['documents'])) {
        $html .= '<hr><h5><i class="fas fa-file"></i> Submitted Documents</h5>';
        $html .= '<div class="row">';

        foreach ($verification['documents'] as $document) {
            $html .= '<div class="col-md-4 mb-3">';
            $html .= '<div class="card">';
            $html .= '<div class="card-body text-center">';
            $html .= '<i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>';
            $html .= '<h6>' . htmlspecialchars($document['document_type']) . '</h6>';
            $html .= '<a href="../uploads/vendor_documents/' . htmlspecialchars($document['filename']) . '" target="_blank" class="btn btn-sm btn-primary">';
            $html .= '<i class="fas fa-eye"></i> View Document';
            $html .= '</a>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    // After the documents section, add action buttons
    $currentStatus = $verification['vendor']['status'];
    $vendorId = $verification['vendor']['user_id'];
    $html .= '<hr><div class="text-center">';
    if ($currentStatus === 'pending') {
        $html .= '<button class="btn btn-success mx-1" onclick="approveVerification(' . $vendorId . ')"><i class="fas fa-check"></i> Approve</button>';
        $html .= '<button class="btn btn-danger mx-1" onclick="rejectVerification(' . $vendorId . ')"><i class="fas fa-times"></i> Reject</button>';
    } else {
        $html .= '<button class="btn btn-warning mx-1" onclick="revokeVerification(' . $vendorId . ')"><i class="fas fa-undo"></i> Revoke Approval</button>';
    }
    $html .= '</div>';

    // Comments/Notes
    if (!empty($verification['notes'])) {
        $html .= '<hr><h5><i class="fas fa-comments"></i> Admin Notes</h5>';
        $html .= '<div class="alert alert-info">';
        $html .= htmlspecialchars($verification['notes']);
        $html .= '</div>';
    }

    return $html;
}
?>