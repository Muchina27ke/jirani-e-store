<?php
class Cart
{
    private $conn;
    private $userId;

    public function __construct($conn, $userId)
    {
        $this->conn = $conn;
        $this->userId = $userId;
    }

    public function addItem($productId, $quantity = 1)
    {
        try {
            // Check if product exists and has stock
            $stmt = $this->conn->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Product not found'];
            }

            $product = $result->fetch_assoc();
            // Check current cart quantity for this product
            $stmt = $this->conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $this->userId, $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentCartQty = 0;
            if ($result->num_rows > 0) {
                $cartItem = $result->fetch_assoc();
                $currentCartQty = $cartItem['quantity'];
            }
            $newQuantity = $currentCartQty + $quantity;
            if ($newQuantity > $product['stock']) {
                return ['success' => false, 'message' => 'Not enough stock available. Only ' . $product['stock'] . ' left.'];
            }

            if ($currentCartQty > 0) {
                // Update quantity
                $stmt = $this->conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("iii", $newQuantity, $this->userId, $productId);
            } else {
                // Add new item
                $stmt = $this->conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $this->userId, $productId, $quantity);
            }

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Item added to cart'];
            } else {
                return ['success' => false, 'message' => 'Failed to add item to cart'];
            }
        } catch (Exception $e) {
            error_log("Cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }

    public function removeItem($productId)
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE product_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $productId, $this->userId);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Item removed from cart'];
            } else {
                return ['success' => false, 'message' => 'Failed to remove item'];
            }
        } catch (Exception $e) {
            error_log("Cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }

    public function updateQuantity($productId, $quantity)
    {
        try {
            // Check stock availability
            $stmt = $this->conn->prepare("
                SELECT p.stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.product_id = ? AND c.user_id = ?
            ");
            $stmt->bind_param("ii", $productId, $this->userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Cart item not found'];
            }

            $product = $result->fetch_assoc();
            if ($product['stock'] < $quantity) {
                return ['success' => false, 'message' => 'Not enough stock available. Only ' . $product['stock'] . ' left.'];
            }

            $stmt = $this->conn->prepare("UPDATE cart SET quantity = ? WHERE product_id = ? AND user_id = ?");
            $stmt->bind_param("iii", $quantity, $productId, $this->userId);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Quantity updated'];
            } else {
                return ['success' => false, 'message' => 'Failed to update quantity'];
            }
        } catch (Exception $e) {
            error_log("Cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }

    public function getCartItems()
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.*, p.name, p.price, p.image, p.stock, v.business_name as vendor_name
                FROM cart c
                JOIN products p ON c.product_id = p.id
                JOIN vendors v ON p.vendor_id = v.user_id
                WHERE c.user_id = ?
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $result = $stmt->get_result();

            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }

            return ['success' => true, 'items' => $items];
        } catch (Exception $e) {
            error_log("Cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }

    public function getCartCount()
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            return $row['count'];
        } catch (Exception $e) {
            error_log("Cart error: " . $e->getMessage());
            return 0;
        }
    }

    public function clearCart()
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $this->userId);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Cart cleared'];
            } else {
                return ['success' => false, 'message' => 'Failed to clear cart'];
            }
        } catch (Exception $e) {
            error_log("Cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }
}