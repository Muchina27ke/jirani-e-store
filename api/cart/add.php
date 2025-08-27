<?php
require_once '../../config/config.php';
require_once '../../includes/Auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $auth = new Auth($conn);
    
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $user = $auth->getUser();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add to cart
        $input = json_decode(file_get_contents('php://input'), true);
        
        $productId = $input['product_id'] ?? 0;
        $quantity = $input['quantity'] ?? 1;
        
        if (!$productId) {
            throw new Exception('Product ID is required');
        }
        
        // Check if product exists and is active
        $stmt = $conn->prepare("
            SELECT p.*, v.status as vendor_status 
            FROM products p 
            JOIN vendors v ON p.vendor_id = v.user_id 
            WHERE p.id = ? AND p.status = 'active' AND v.status = 'approved'
        ");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if (!$product) {
            throw new Exception('Product not found or unavailable');
        }
        
        // Check stock
        if ($product['stock'] < $quantity) {
            throw new Exception('Insufficient stock available');
        }
        
        // Add or update cart item
        $stmt = $conn->prepare("
            INSERT INTO cart (user_id, product_id, quantity) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt->bind_param("iii", $user['id'], $productId, $quantity);
        
        if ($stmt->execute()) {
            // Get updated cart count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $cartCount = $stmt->get_result()->fetch_assoc()['count'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart',
                'cart_count' => $cartCount
            ]);
        } else {
            throw new Exception('Failed to add product to cart');
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get cart items
        $stmt = $conn->prepare("
            SELECT c.*, p.name, p.price, p.image, p.stock,
                   v.business_name as vendor_name,
                   (c.quantity * p.price) as subtotal
            FROM cart c
            JOIN products p ON c.product_id = p.id
            JOIN vendors v ON p.vendor_id = v.user_id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $total = array_sum(array_column($cartItems, 'subtotal'));
        
        echo json_encode([
            'success' => true,
            'cart_items' => $cartItems,
            'total' => $total,
            'count' => count($cartItems)
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>