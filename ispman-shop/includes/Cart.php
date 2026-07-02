<?php
class Cart {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /** Add or increment a product in the session cart */
    public function add(int $productId, int $qty, ?int $userId, ?string $sessionId): bool {
        // Check product exists and has stock
        $stmt = $this->pdo->prepare("SELECT id, stock_qty FROM products WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();
        if (!$product || $product['stock_qty'] < 1) return false;

        // Check if already in cart
        $existing = $this->findItem($productId, $userId, $sessionId);
        if ($existing) {
            $newQty = $existing['qty'] + $qty;
            return $this->updateQty($existing['id'], $newQty, $userId, $sessionId);
        }

        $sql = "INSERT INTO cart (user_id, session_id, product_id, qty) VALUES (:uid, :sid, :pid, :qty)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':uid' => $userId,
            ':sid' => $sessionId,
            ':pid' => $productId,
            ':qty' => $qty,
        ]);
    }

    /** Remove a cart item by its cart row id */
    public function remove(int $cartItemId, ?int $userId, ?string $sessionId): bool {
        $sql = "DELETE FROM cart WHERE id = :id AND " . $this->ownerClause();
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(array_merge([':id' => $cartItemId], $this->ownerParams($userId, $sessionId)));
    }

    /** Update quantity of a cart item */
    public function updateQty(int $cartItemId, int $qty, ?int $userId, ?string $sessionId): bool {
        if ($qty < 1) return $this->remove($cartItemId, $userId, $sessionId);
        $sql = "UPDATE cart SET qty = :qty WHERE id = :id AND " . $this->ownerClause();
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(array_merge([':qty' => $qty, ':id' => $cartItemId], $this->ownerParams($userId, $sessionId)));
    }

    /** Get all cart items with product details */
    public function getItems(?int $userId, ?string $sessionId): array {
        $sql = "SELECT c.id as cart_id, c.qty, c.product_id,
                       p.name, p.price, p.brand, p.images, p.stock_qty, p.slug
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE " . $this->ownerClause()
               . " ORDER BY c.created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->ownerParams($userId, $sessionId));
        return $stmt->fetchAll();
    }

    /** Cart subtotal */
    public function getTotal(?int $userId, ?string $sessionId): float {
        $items = $this->getItems($userId, $sessionId);
        return array_reduce($items, fn($carry, $item) => $carry + ($item['price'] * $item['qty']), 0.0);
    }

    /** Total item count (sum of quantities) */
    public function getCount(?int $userId, ?string $sessionId): int {
        $sql = "SELECT COALESCE(SUM(qty), 0) FROM cart WHERE " . $this->ownerClause();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->ownerParams($userId, $sessionId));
        return (int)$stmt->fetchColumn();
    }

    /** Clear entire cart */
    public function clear(?int $userId, ?string $sessionId): bool {
        $sql = "DELETE FROM cart WHERE " . $this->ownerClause();
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->ownerParams($userId, $sessionId));
    }

    /** Merge guest session cart into user cart after login */
    public function mergeSessionCart(string $sessionId, int $userId): bool {
        $guestItems = $this->getItems(null, $sessionId);
        foreach ($guestItems as $item) {
            $this->add($item['product_id'], $item['qty'], $userId, null);
        }
        $this->clear(null, $sessionId);
        return true;
    }

    // ---- Private helpers ----

    private function findItem(int $productId, ?int $userId, ?string $sessionId): array|false {
        $sql = "SELECT id, qty FROM cart WHERE product_id = :pid AND " . $this->ownerClause();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([':pid' => $productId], $this->ownerParams($userId, $sessionId)));
        return $stmt->fetch();
    }

    private function ownerClause(): string {
        return "(user_id = :uid OR session_id = :sid)";
    }

    private function ownerParams(?int $userId, ?string $sessionId): array {
        return [':uid' => $userId, ':sid' => $sessionId];
    }
}
