<?php
require_once dirname(__DIR__) . "/config/config.php";

// Ensure user is logged in
if (!$user->is_logged_in()) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized."]);
    exit();
}

// Handle product actions
switch ($_GET["action"] ?? "") {
    case "add_product":
        if (!$user->is_vendor()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied. Only vendors can add products."]);
            exit();
        }
        $data = json_decode(file_get_contents("php://input"), true);
        $name = $data["name"] ?? "";
        $description = $data["description"] ?? "";
        $price = $data["price"] ?? 0;
        $stock = $data["stock"] ?? 0;
        $location_lat = $data["location_lat"] ?? null;
        $location_lng = $data["location_lng"] ?? null;

        if (empty($name) || empty($price) || empty($stock)) {
            http_response_code(400);
            echo json_encode(["message" => "Product name, price, and stock are required."]);
            exit();
        }

        if ($product->add_product($_SESSION["user_id"], $name, $description, $price, $stock, $location_lat, $location_lng)) {
            echo json_encode(["message" => "Product added successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to add product."]);
        }
        break;

    case "get_products_by_vendor":
        if (!$user->is_vendor() && !$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }
        $vendor_id = $_GET["vendor_id"] ?? $_SESSION["user_id"];
        echo json_encode($product->get_products_by_vendor($vendor_id));
        break;

    case "get_all_products":
        if (!$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied. Admin privileges required."]);
            exit();
        }
        echo json_encode($product->get_all_products());
        break;

    case "update_product":
        if (!$user->is_vendor() && !$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }
        $data = json_decode(file_get_contents("php://input"), true);
        $product_id = $data["product_id"] ?? null;
        $name = $data["name"] ?? "";
        $description = $data["description"] ?? "";
        $price = $data["price"] ?? 0;
        $stock = $data["stock"] ?? 0;
        $location_lat = $data["location_lat"] ?? null;
        $location_lng = $data["location_lng"] ?? null;
        $status = $data["status"] ?? null;

        if (empty($product_id) || empty($name) || empty($price) || empty($stock)) {
            http_response_code(400);
            echo json_encode(["message" => "Product ID, name, price, and stock are required."]);
            exit();
        }

        // Ensure vendor can only update their own products unless admin
        $existing_product = $product->get_product_by_id($product_id);
        if (!$user->is_admin() && $existing_product["vendor_id"] != $_SESSION["user_id"]) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied. You can only update your own products."]);
            exit();
        }

        if ($product->update_product($product_id, $name, $description, $price, $stock, $location_lat, $location_lng, $status)) {
            echo json_encode(["message" => "Product updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update product."]);
        }
        break;

    case "delete_product":
        if (!$user->is_vendor() && !$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }
        $data = json_decode(file_get_contents("php://input"), true);
        $product_id = $data["product_id"] ?? null;

        if (empty($product_id)) {
            http_response_code(400);
            echo json_encode(["message" => "Product ID is required."]);
            exit();
        }

        // Ensure vendor can only delete their own products unless admin
        $existing_product = $product->get_product_by_id($product_id);
        if (!$user->is_admin() && $existing_product["vendor_id"] != $_SESSION["user_id"]) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied. You can only delete your own products."]);
            exit();
        }

        if ($product->delete_product($product_id)) {
            echo json_encode(["message" => "Product deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete product."]);
        }
        break;

    case "search_products":
        $keyword = $_GET["keyword"] ?? "";
        if (empty($keyword)) {
            http_response_code(400);
            echo json_encode(["message" => "Search keyword is required."]);
            exit();
        }
        echo json_encode($product->search_products($keyword));
        break;

    default:
        http_response_code(400);
        echo json_encode(["message" => "Invalid action."]);
        break;
}

?>

