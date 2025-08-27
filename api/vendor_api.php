<?php
require_once dirname(__DIR__) . "/config/config.php";

// Ensure user is logged in and is an admin for most actions
if (!$user->is_logged_in()) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized."]);
    exit();
}

// Handle vendor registration details submission (for vendors)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["action"]) && $_GET["action"] == "register_details") {
    if (!$user->is_vendor()) {
        http_response_code(403);
        echo json_encode(["message" => "Access denied. Only vendors can register details."]);
        exit();
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $business_name = $data["business_name"] ?? "";
    $business_cert = $data["business_cert"] ?? ""; // This would be a URL to the uploaded file

    if (empty($business_name) || empty($business_cert)) {
        http_response_code(400);
        echo json_encode(["message" => "Business name and certificate are required."]);
        exit();
    }

    if ($vendor->register_vendor_details($_SESSION["user_id"], $business_name, $business_cert)) {
        echo json_encode(["message" => "Vendor details submitted for verification."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to submit vendor details."]);
    }
    exit();
}

// Handle ID document upload (for vendors)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["action"]) && $_GET["action"] == "upload_id_doc") {
    if (!$user->is_vendor()) {
        http_response_code(403);
        echo json_encode(["message" => "Access denied. Only vendors can upload ID."]);
        exit();
    }

    // This is a simplified example. In a real application, handle file uploads securely.
    // For now, assume a URL is passed.
    $data = json_decode(file_get_contents("php://input"), true);
    $id_doc_url = $data["id_doc_url"] ?? "";

    if (empty($id_doc_url)) {
        http_response_code(400);
        echo json_encode(["message" => "ID document URL is required."]);
        exit();
    }

    if ($vendor->upload_id_document($_SESSION["user_id"], $id_doc_url)) {
        echo json_encode(["message" => "ID document uploaded for verification."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to upload ID document."]);
    }
    exit();
}

// Admin-specific actions
if (!$user->is_admin()) {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. Admin privileges required."]);
    exit();
}

switch ($_GET["action"] ?? "") {
    case "get_all_vendors":
        echo json_encode($vendor->get_all_vendors());
        break;
    case "approve_vendor":
        $data = json_decode(file_get_contents("php://input"), true);
        $vendor_user_id = $data["user_id"] ?? null;
        if ($vendor_user_id && $vendor->update_vendor_status($vendor_user_id, "approved")) {
            echo json_encode(["message" => "Vendor approved successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to approve vendor."]);
        }
        break;
    case "reject_vendor":
        $data = json_decode(file_get_contents("php://input"), true);
        $vendor_user_id = $data["user_id"] ?? null;
        $rejection_reason = $data["rejection_reason"] ?? null;
        if ($vendor_user_id && $vendor->update_vendor_status($vendor_user_id, "rejected", $rejection_reason)) {
            echo json_encode(["message" => "Vendor rejected successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to reject vendor."]);
        }
        break;
    case "get_vendor_verification_details":
        $vendor_user_id = $_GET["user_id"] ?? null;
        if ($vendor_user_id) {
            echo json_encode($vendor->get_verification_details($vendor_user_id));
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Vendor user ID is required."]);
        }
        break;
    default:
        http_response_code(400);
        echo json_encode(["message" => "Invalid action."]);
        break;
}

?>

