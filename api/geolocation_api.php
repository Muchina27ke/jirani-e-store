<?php
require_once dirname(__DIR__) . "/config/config.php";

// API endpoint for geolocation services
switch ($_GET["action"] ?? "") {
    case "get_products_near_me":
        $customer_lat = $_GET["lat"] ?? null;
        $customer_lng = $_GET["lng"] ?? null;
        $radius_km = $_GET["radius"] ?? MAX_DELIVERY_RADIUS;

        if (empty($customer_lat) || empty($customer_lng)) {
            http_response_code(400);
            echo json_encode(["message" => "Customer latitude and longitude are required."]);
            exit();
        }

        $products = $geolocation->get_products_near_location($customer_lat, $customer_lng, $radius_km);
        echo json_encode($products);
        break;

    case "save_vendor_geofence":
        if (!$user->is_admin() && !$user->is_vendor()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }
        $data = json_decode(file_get_contents("php://input"), true);
        $vendor_id = $data["vendor_id"] ?? $_SESSION["user_id"];
        $polygon_json = json_encode($data["polygon_json"] ?? null);
        $radius_km = $data["radius_km"] ?? null;

        if (empty($vendor_id)) {
            http_response_code(400);
            echo json_encode(["message" => "Vendor ID is required."]);
            exit();
        }

        if ($geolocation->save_vendor_geofence($vendor_id, $polygon_json, $radius_km)) {
            echo json_encode(["message" => "Geofence saved successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to save geofence."]);
        }
        break;

    case "get_vendor_geofence":
        if (!$user->is_admin() && !$user->is_vendor()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }
        $vendor_id = $_GET["vendor_id"] ?? $_SESSION["user_id"];
        if (empty($vendor_id)) {
            http_response_code(400);
            echo json_encode(["message" => "Vendor ID is required."]);
            exit();
        }
        echo json_encode($geolocation->get_vendor_geofence($vendor_id));
        break;

    case "check_point_in_geofence":
        $point_lat = $_GET["lat"] ?? null;
        $point_lng = $_GET["lng"] ?? null;
        $vendor_id = $_GET["vendor_id"] ?? null;

        if (empty($point_lat) || empty($point_lng) || empty($vendor_id)) {
            http_response_code(400);
            echo json_encode(["message" => "Latitude, longitude, and vendor ID are required."]);
            exit();
        }

        $geofence = $geolocation->get_vendor_geofence($vendor_id);
        $is_in_geofence = false;

        if ($geofence) {
            if ($geofence["polygon_json"]) {
                $is_in_geofence = $geolocation->is_point_in_polygon($point_lat, $point_lng, $geofence["polygon_json"]);
            } elseif ($geofence["radius_km"]) {
                // Assuming vendor's location is product's location for simplicity
                $vendor_products = $product->get_products_by_vendor($vendor_id);
                if (!empty($vendor_products)) {
                    $vendor_lat = $vendor_products[0]["location_lat"];
                    $vendor_lng = $vendor_products[0]["location_lng"];
                    $is_in_geofence = $geolocation->is_point_in_radius($point_lat, $point_lng, $vendor_lat, $vendor_lng, $geofence["radius_km"]);
                }
            }
        }
        echo json_encode(["is_in_geofence" => $is_in_geofence]);
        break;

    default:
        http_response_code(400);
        echo json_encode(["message" => "Invalid action."]);
        break;
}

?>

