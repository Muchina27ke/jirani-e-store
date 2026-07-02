<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';

if (empty($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$pdo    = getPDO();

// Image upload helper
function handleImageUpload(array $file): string|false {
    $uploadDir = __DIR__ . '/../../assets/images/products/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $allowed = ['image/jpeg','image/png','image/webp','image/gif','image/avif'];
    if (!in_array($file['type'], $allowed)) return false;
    if ($file['size'] > 5 * 1024 * 1024) return false; // 5MB max

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('prod_', true) . '.' . strtolower($ext);
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

    // Store as root-relative path so it works from any page depth
    // BASE_URL is e.g. http://localhost/ecommerce/jirani-e-store/ispman-shop/
    $basePath = rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/');
    return $basePath . '/assets/images/products/' . $filename;
}

try {
    switch ($action) {

        case 'add':
        case 'edit':
            $name       = trim($_POST['name']        ?? '');
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $brand      = trim($_POST['brand']        ?? '');
            $desc       = trim($_POST['description']  ?? '');
            $price      = (float)($_POST['price']     ?? 0);
            $stock      = (int)($_POST['stock_qty']   ?? 0);
            $sku        = trim($_POST['sku']           ?? '');
            $weight     = (float)($_POST['weight_kg'] ?? 0);
            $featured   = isset($_POST['featured']) ? 1 : 0;

            if (!$name || !$categoryId || $price <= 0) {
                echo json_encode(['success' => false, 'message' => 'Name, category and price are required']);
                exit;
            }

            // Generate slug
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $slug = trim($slug, '-');

            // Handle image upload
            $imagePath = null;
            if (!empty($_FILES['image']['name'])) {
                $imagePath = handleImageUpload($_FILES['image']);
            }

            if ($action === 'add') {
                // Ensure unique slug
                $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = :s");
                $check->execute([':s' => $slug]);
                if ($check->fetchColumn() > 0) $slug .= '-' . time();

                $images = $imagePath ? json_encode([$imagePath]) : '[]';
                $ins = $pdo->prepare("
                    INSERT INTO products
                        (category_id, name, slug, description, brand, price, stock_qty, images, sku, weight_kg, featured)
                    VALUES
                        (:cid, :name, :slug, :desc, :brand, :price, :stock, :images, :sku, :weight, :featured)
                ");
                $ins->execute([
                    ':cid'      => $categoryId,
                    ':name'     => $name,
                    ':slug'     => $slug,
                    ':desc'     => $desc,
                    ':brand'    => $brand,
                    ':price'    => $price,
                    ':stock'    => $stock,
                    ':images'   => $images,
                    ':sku'      => $sku ?: null,
                    ':weight'   => $weight ?: null,
                    ':featured' => $featured,
                ]);
                $newId = $pdo->lastInsertId();
                echo json_encode(['success' => true, 'product_id' => $newId, 'message' => 'Product added']);

            } else {
                $productId = (int)($_POST['product_id'] ?? 0);
                if (!$productId) { echo json_encode(['success' => false, 'message' => 'Missing product ID']); exit; }

                $setImage = $imagePath ? ", images = :images" : "";
                $params   = [
                    ':cid'      => $categoryId,
                    ':name'     => $name,
                    ':desc'     => $desc,
                    ':brand'    => $brand,
                    ':price'    => $price,
                    ':stock'    => $stock,
                    ':sku'      => $sku ?: null,
                    ':weight'   => $weight ?: null,
                    ':featured' => $featured,
                    ':id'       => $productId,
                ];
                if ($imagePath) $params[':images'] = json_encode([$imagePath]);

                $pdo->prepare("
                    UPDATE products SET
                        category_id = :cid, name = :name, description = :desc,
                        brand = :brand, price = :price, stock_qty = :stock,
                        sku = :sku, weight_kg = :weight, featured = :featured
                        $setImage
                    WHERE id = :id
                ")->execute($params);
                echo json_encode(['success' => true, 'message' => 'Product updated']);
            }
            break;

        case 'delete':
            $productId = (int)($_POST['product_id'] ?? 0);
            if (!$productId) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit; }
            $pdo->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $productId]);
            echo json_encode(['success' => true, 'message' => 'Product deleted']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
