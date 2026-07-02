<?php
class Product {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(int $limit = 20, int $offset = 0): array {}

    public function getById(int $id): array|false {}

    public function getBySlug(string $slug): array|false {}

    public function getByCategory(int $categoryId, int $limit = 20, int $offset = 0): array {}

    public function getFeatured(int $limit = 8): array {}

    public function search(string $query, int $limit = 20): array {}

    public function create(array $data): int|false {}

    public function update(int $id, array $data): bool {}

    public function delete(int $id): bool {}

    public function decrementStock(int $id, int $qty): bool {}

    public function getAllCategories(): array {}
}
