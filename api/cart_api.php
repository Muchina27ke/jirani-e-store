<?php
require_once '../config/config.php';

// Handle different actions
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'save_location':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lat = $_POST['lat'] ?? null;
            $lon = $_POST['lon'] ?? null;

            if ($lat && $lon) {
                $_SESSION['user_location'] = [
                    'lat' => floatval($lat),
                    'lon' => floatval($lon)
                ];
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
            }
        }
        break;

    case 'add':
        if (!$user->is_logged_in()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Please login first']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_id = $_POST['product_id'] ?? null;
            $quantity = $_POST['quantity'] ?? 1;
            $user_id = $_SESSION['user_id'];

            if ($product_id) {
                // Add to cart logic (simplified - would normally use a Cart class)
                $query = "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?) 
                         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", $user_id, $product_id, $quantity);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Product ID required']);
            }
        }
        break;

    case 'count':
        if (!$user->is_logged_in()) {
            echo json_encode(['count' => 0]);
            exit();
        }

        $user_id = $_SESSION['user_id'];
        $query = "SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        echo json_encode(['count' => $row['count'] ?? 0]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>