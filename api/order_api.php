<?php
require_once dirname(__DIR__) . "/config/config.php";

// Ensure user is logged in
if (!$user->is_logged_in()) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized."]);
    exit();
}

// Handle order actions
switch ($_GET["action"] ?? "") {
    case "create_order":
        if (!$user->is_customer()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied. Only customers can create orders."]);
            exit();
        }
        $data = json_decode(file_get_contents("php://input"), true);
        $vendor_id = $data["vendor_id"] ?? null;
        $delivery_address = $data["delivery_address"] ?? "";
        $location_lat = $data["location_lat"] ?? null;
        $location_lng = $data["location_lng"] ?? null;
        $items = $data["items"] ?? [];

        if (empty($vendor_id) || empty($delivery_address) || empty($items)) {
            http_response_code(400);
            echo json_encode(["message" => "Vendor ID, delivery address, and items are required."]);
            exit();
        }

        $order_id = $order->create_order($_SESSION["user_id"], $vendor_id, $delivery_address, $location_lat, $location_lng, $items);
        if ($order_id) {
            echo json_encode(["message" => "Order created successfully.", "order_id" => $order_id]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create order."]);
        }
        break;

    case "get_order_details":
        $order_id = $_GET["order_id"] ?? null;
        if (empty($order_id)) {
            http_response_code(400);
            echo json_encode(["message" => "Order ID is required."]);
            exit();
        }
        $order_details = $order->get_order_by_id($order_id);
        if ($order_details) {
            // Ensure user can only view their own orders unless admin/vendor
            if (!$user->is_admin() && !$user->is_vendor() && $order_details["customer_id"] != $_SESSION["user_id"]) {
                http_response_code(403);
                echo json_encode(["message" => "Access denied. You can only view your own orders."]);
                exit();
            }
            if ($user->is_vendor() && $order_details["vendor_id"] != $_SESSION["user_id"]) {
                http_response_code(403);
                echo json_encode(["message" => "Access denied. You can only view your own vendor orders."]);
                exit();
            }
            echo json_encode($order_details);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Order not found."]);
        }
        break;

    case "get_customer_orders":
        if (!$user->is_customer() && !$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }
        $customer_id = $_GET["customer_id"] ?? $_SESSION["user_id"];
        echo json_encode($order->get_orders_by_customer($customer_id));
        break;

    case "get_vendor_orders":
        if (!$user->is_vendor() && !$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }
        $vendor_id = $_GET["vendor_id"] ?? $_SESSION["user_id"];
        echo json_encode($order->get_orders_by_vendor($vendor_id));
        break;

    case "get_all_orders":
        if (!$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied. Admin privileges required."]);
            exit();
        }
        echo json_encode($order->get_all_orders());
        break;

    case "update_order_status":
        if (!$user->is_admin() && !$user->is_vendor()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }
        $data = json_decode(file_get_contents("php://input"), true);
        $order_id = $data["order_id"] ?? null;
        $status = $data["status"] ?? "";

        if (empty($order_id) || empty($status)) {
            http_response_code(400);
            echo json_encode(["message" => "Order ID and status are required."]);
            exit();
        }

        // Additional checks for vendor to only update their own orders
        $existing_order = $order->get_order_by_id($order_id);
        if ($user->is_vendor() && $existing_order["vendor_id"] != $_SESSION["user_id"]) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied. You can only update your own vendor orders."]);
            exit();
        }

        if ($order->update_order_status($order_id, $status)) {
            echo json_encode(["message" => "Order status updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update order status."]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(["message" => "Invalid action."]);
        break;
}

?>

