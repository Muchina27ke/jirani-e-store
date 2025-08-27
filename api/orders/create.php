<?php
// Set session path to match main site
ini_set('session.cookie_path', '/');
ini_set('session.save_path', session_save_path());

require_once '../../config/config.php';
require_once '../../includes/Auth.php';
require_once '../../includes/EmailTemplate.php';

// Debug: Check session status
error_log("API Session ID: " . session_id());
error_log("API Session Status: " . session_status());
error_log("API Session Data: " . print_r($_SESSION, true));

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $auth = new Auth($conn);

    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    $user = $auth->getUser();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    // Debug: Log user info
    error_log("Order creation - User ID: " . $user['id'] . ", Email: " . $user['email']);

    // Required fields
    $required = ['shipping_info', 'payment_method'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field {$field} is required");
        }
    }

    $conn->begin_transaction();
    try {
        // Get cart items with vendor information
        $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.vendor_id, v.business_name as vendor_name
        FROM cart c
        JOIN products p ON c.product_id = p.id
            JOIN vendors v ON p.vendor_id = v.user_id 
        WHERE c.user_id = ?
        ");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Debug: Log cart items count
    error_log("Cart items found: " . count($cartItems) . " for user ID: " . $user['id']);

    if (empty($cartItems)) {
        throw new Exception('Cart is empty for user ID: ' . $user['id']);
    }

    // Group cart items by vendor
    $vendorOrders = [];
    foreach ($cartItems as $item) {
        $vendorId = $item['vendor_id'];
        if (!isset($vendorOrders[$vendorId])) {
            $vendorOrders[$vendorId] = [
                'vendor_id' => $vendorId,
                'vendor_name' => $item['vendor_name'],
                'items' => [],
                'total' => 0
            ];
        }
        $vendorOrders[$vendorId]['items'][] = $item;
        $vendorOrders[$vendorId]['total'] += $item['price'] * $item['quantity'];
    }

    $createdOrders = [];

    // Create orders for each vendor
    foreach ($vendorOrders as $vendorOrder) {
        $vendorId = $vendorOrder['vendor_id'];
        $totalAmount = $vendorOrder['total'];

        // Create order (without total_amount column)
        $stmt = $conn->prepare("
            INSERT INTO orders (customer_id, vendor_id, delivery_address, status) 
            VALUES (?, ?, ?, 'pending')
    ");

        $stmt->bind_param(
            "iis",
            $user['id'],
            $vendorId,
            $input['shipping_info']['delivery_address']
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to create order for vendor ' . $vendorOrder['vendor_name']);
        }

        $orderId = $conn->insert_id;

        // Add order items
        foreach ($vendorOrder['items'] as $item) {
            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
        ");
            $stmt->bind_param(
                "iiid",
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            );
            $stmt->execute();

            // Update product stock
            $stmt = $conn->prepare("
            UPDATE products 
                SET stock = stock - ? 
            WHERE id = ?
        ");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
        }

        // Clear cart items for this vendor
        $stmt = $conn->prepare("
        DELETE c FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND p.vendor_id = ?
    ");
        $stmt->bind_param("ii", $user['id'], $vendorId);
        $stmt->execute();

        // Create payment record
        $stmt = $conn->prepare("
            INSERT INTO payments (order_id, amount, method, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->bind_param("ids", $orderId, $totalAmount, $input['payment_method']);
        $stmt->execute();

        $createdOrders[] = [
            'order_id' => $orderId,
            'vendor_id' => $vendorId,
            'vendor_name' => $vendorOrder['vendor_name'],
            'total_amount' => $totalAmount,
            'items' => $vendorOrder['items']
        ];
    }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

    // Send confirmation email if configs exist
    require_once '../../includes/EmailConfig.php';
    $emailConfig = new EmailConfig($conn);
    $mailerConfig = $emailConfig->getMailerConfig();
    if (!empty($mailerConfig['host']) && !empty($mailerConfig['username']) && !empty($mailerConfig['password']) && $mailerConfig['enabled']) {
        try {
            $emailNotifications = new EmailNotifications($conn);

            // Send confirmation email for each order
            foreach ($createdOrders as $order) {
                // Get vendor details
                $stmt = $conn->prepare("SELECT * FROM vendors WHERE user_id = ?");
                $stmt->bind_param("i", $order['vendor_id']);
                $stmt->execute();
                $vendor = $stmt->get_result()->fetch_assoc();

                // Get order details
                $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt->bind_param("i", $order['order_id']);
                $stmt->execute();
                $orderDetails = $stmt->get_result()->fetch_assoc();

                // Get order items
                $stmt = $conn->prepare("
                    SELECT oi.*, p.name as product_name 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?
                ");
                $stmt->bind_param("i", $order['order_id']);
                $stmt->execute();
                $orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Send confirmation email
                $emailNotifications->sendOrderConfirmation($orderDetails, $orderItems, $user, $vendor);
            }

        } catch (Exception $e) {
            // Log email error but don't fail the order
            error_log("Failed to send order confirmation email: " . $e->getMessage());
        }
    } else {
        error_log("Order confirmation email NOT sent: Email configuration missing or disabled.");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Orders created successfully',
        'orders' => $createdOrders,
        'total_amount' => array_sum(array_column($createdOrders, 'total_amount'))
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>