<?php
/**
 * AfriGear.tech — Search API
 * GET ?q=query&limit=10&format=json  → JSON (for live suggestions)
 * GET ?q=query                        → redirects to search results page
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/../config/config.php';

$q      = trim($_GET['q'] ?? '');
$limit  = min(10, max(1, (int)($_GET['limit'] ?? 8)));
$format = $_GET['format'] ?? 'json';

if (strlen($q) < 2) {
    echo json_encode(['results' => [], 'total' => 0, 'query' => $q]);
    exit;
}

try {
    $pdo = getPDO();

    // FULLTEXT search with relevance score
    // Falls back to LIKE for very short queries or when FULLTEXT minimum word length blocks it
    $sql = "
        SELECT p.id, p.name, p.slug, p.brand, p.price, p.stock_qty, p.images,
               c.name as category_name, c.slug as category_slug,
               MATCH(p.name, p.brand, p.description, p.sku)
                   AGAINST(:q IN NATURAL LANGUAGE MODE) AS relevance
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE MATCH(p.name, p.brand, p.description, p.sku)
                  AGAINST(:q2 IN NATURAL LANGUAGE MODE)
           OR p.name LIKE :like
           OR p.brand LIKE :like2
           OR p.sku LIKE :like3
        ORDER BY relevance DESC, p.featured DESC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':q',     $q,           PDO::PARAM_STR);
    $stmt->bindValue(':q2',    $q,           PDO::PARAM_STR);
    $stmt->bindValue(':like',  '%' . $q . '%', PDO::PARAM_STR);
    $stmt->bindValue(':like2', '%' . $q . '%', PDO::PARAM_STR);
    $stmt->bindValue(':like3', '%' . $q . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit,       PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll();

    // Format for response
    $formatted = array_map(function($p) {
        $images = json_decode($p['images'] ?? '[]', true);
        return [
            'id'            => (int)$p['id'],
            'name'          => $p['name'],
            'slug'          => $p['slug'],
            'brand'         => $p['brand'],
            'price'         => (float)$p['price'],
            'price_fmt'     => 'KES ' . number_format($p['price'], 0),
            'stock'         => (int)$p['stock_qty'],
            'image'         => $images[0] ?? null,
            'category'      => $p['category_name'],
            'category_slug' => $p['category_slug'],
            'url'           => 'pages/product.php?slug=' . urlencode($p['slug']),
        ];
    }, $results);

    echo json_encode([
        'results' => $formatted,
        'total'   => count($formatted),
        'query'   => $q,
    ]);

} catch (Throwable $e) {
    error_log('Search error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['results' => [], 'total' => 0, 'query' => $q, 'error' => 'Search unavailable']);
}
