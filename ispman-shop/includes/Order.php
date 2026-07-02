<?php
class Order {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(int $userId, array $items, float $total, string $phone, string $deliveryAddress): int|false {}

    public function getById(int $id): array|false {}

    public function getByUser(int $userId): array {}

    public function getAll(int $limit = 50, int $offset = 0): array {}

    public function updateStatus(int $id, string $status): bool {}

    public function attachMpesaRef(int $id, string $mpesaRef): bool {}

    public function getItems(int $orderId): array {}
}
