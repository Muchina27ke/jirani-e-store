<?php
class Wishlist
{
    private $conn;
    private $userId;

    public function __construct($conn, $userId)
    {
        $this->conn = $conn;
        $this->userId = $userId;
    }

    public function addItem($productId)
    {
        try {
            // Check if product exists
            $stmt = $this->conn->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Product not found'];
            }

            // Check if already in wishlist
            $stmt = $this->conn->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $this->userId, $productId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                return ['success' => false, 'message' => 'Item already in wishlist'];
            }

            // Add to wishlist
            $stmt = $this->conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $this->userId, $productId);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Item added to wishlist'];
            } else {
                return ['success' => false, 'message' => 'Failed to add item to wishlist'];
            }
        } catch (Exception $e) {
            error_log("Wishlist error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }

    public function removeItem($productId)
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $this->userId, $productId);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Item removed from wishlist'];
            } else {
                return ['success' => false, 'message' => 'Failed to remove item'];
            }
        } catch (Exception $e) {
            error_log("Wishlist error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }

    public function getWishlistItems()
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT w.product_id, w.created_at, p.name, p.price, p.image_url, p.stock, p.description, v.business_name as vendor_name
                FROM wishlist w
                JOIN products p ON w.product_id = p.id
                JOIN vendors v ON p.vendor_id = v.user_id
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC
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
            error_log("Wishlist error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }

    public function getWishlistCount()
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM wishlist_items WHERE user_id = ?");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            return $row['count'];
        } catch (Exception $e) {
            error_log("Wishlist error: " . $e->getMessage());
            return 0;
        }
    }

    public function clearWishlist()
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM wishlist_items WHERE user_id = ?");
            $stmt->bind_param("i", $this->userId);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Wishlist cleared'];
            } else {
                return ['success' => false, 'message' => 'Failed to clear wishlist'];
            }
        } catch (Exception $e) {
            error_log("Wishlist error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }
}