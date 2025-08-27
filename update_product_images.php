<?php
require_once __DIR__ . '/config/config.php';

$db = getDbConnection();

// Product name keyword to image mapping
$productImages = [
    // Fruits
    'mango' => 'https://images.unsplash.com/photo-1550258987-190a2d41a8ba?w=400&h=300&fit=crop',
    'banana' => 'https://images.unsplash.com/photo-1502741338009-cac2772e18bc?w=400&h=300&fit=crop',
    'orange' => 'https://images.unsplash.com/photo-1502741338009-cac2772e18bc?w=400&h=300&fit=crop',
    'apple' => 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308?w=400&h=300&fit=crop',
    'avocado' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&h=300&fit=crop',
    // Vegetables
    'tomato' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&h=300&fit=crop',
    'potato' => 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=400&h=300&fit=crop',
    'onion' => 'https://images.unsplash.com/photo-1465101046530-73398c7f28ca?w=400&h=300&fit=crop',
    'carrot' => 'https://images.unsplash.com/photo-1502741338009-cac2772e18bc?w=400&h=300&fit=crop',
    'beans' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&h=300&fit=crop',
    'cabbage' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=300&fit=crop',
    // Dairy
    'milk' => 'https://images.unsplash.com/photo-1519864600265-abb23847ef2c?w=400&h=300&fit=crop',
    'yogurt' => 'https://images.unsplash.com/photo-1519864600265-abb23847ef2c?w=400&h=300&fit=crop',
    'cheese' => 'https://images.unsplash.com/photo-1519864600265-abb23847ef2c?w=400&h=300&fit=crop',
    // Handcrafts
    'basket' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
    'mat' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
    'necklace' => 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308?w=400&h=300&fit=crop',
    'pot' => 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308?w=400&h=300&fit=crop',
];

// Category fallback images
$categoryImages = [
    'Fruits' => 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=400&h=300&fit=crop',
    'Vegetables' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=300&fit=crop',
    'Handcrafts' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
    'Dairy' => 'https://images.unsplash.com/photo-1519864600265-abb23847ef2c?w=400&h=300&fit=crop',
];

// Get all products with their categories
$stmt = $db->prepare("
    SELECT p.id, p.name, p.image, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active'
");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$updated = 0;
$skipped = 0;

foreach ($products as $product) {
    $category = $product['category_name'];
    $name = strtolower($product['name']);
    // Try to match product name to a specific image
    $assignedImage = null;
    foreach ($productImages as $keyword => $imgUrl) {
        if (strpos($name, $keyword) !== false) {
            $assignedImage = $imgUrl;
            break;
        }
    }
    // If not matched, use category fallback
    if (!$assignedImage && isset($categoryImages[$category])) {
        $assignedImage = $categoryImages[$category];
    }
    // If still not matched, use a default
    if (!$assignedImage) {
        $assignedImage = 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=400&h=300&fit=crop';
    }
    // Always update product image
    $updateStmt = $db->prepare("UPDATE products SET image = ? WHERE id = ?");
    $updateStmt->bind_param("si", $assignedImage, $product['id']);
    if ($updateStmt->execute()) {
        $updated++;
        echo "Updated product '{$product['name']}' with image: $assignedImage\n";
    } else {
        echo "Failed to update product '{$product['name']}'\n";
    }
}

echo "\nUpdate complete!\n";
echo "Updated: $updated products\n";
echo "Skipped: $skipped products (already had images)\n";
?>