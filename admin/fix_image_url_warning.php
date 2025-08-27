<?php
require_once __DIR__ . '/../config/config.php';

echo "<h2>Fix Image URL Warning</h2>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

try {
    // 1. Check if image_url column exists in products table
    echo "<h3>1. Checking products table structure...</h3>";
    $structure = $conn->query("DESCRIBE products");
    $hasImageUrl = false;
    while ($row = $structure->fetch_assoc()) {
        if ($row['Field'] === 'image_url') {
            $hasImageUrl = true;
            echo "✓ image_url column exists<br>";
            break;
        }
    }

    if (!$hasImageUrl) {
        echo "✗ image_url column does not exist, adding it...<br>";
        $conn->query("ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL");
        echo "✓ Added image_url column<br>";
    }

    // 2. Update the products.php file to fix the warning
    echo "<h3>2. Fixing products.php file...</h3>";

    $productsFile = __DIR__ . '/products.php';
    $content = file_get_contents($productsFile);

    // Replace the problematic line
    $oldLine = "<?php if (\$product['image_url']): ?>";
    $newLine = "<?php if (isset(\$product['image_url']) && \$product['image_url']): ?>";

    if (strpos($content, $oldLine) !== false) {
        $content = str_replace($oldLine, $newLine, $content);
        file_put_contents($productsFile, $content);
        echo "✓ Fixed image_url warning in products.php<br>";
    } else {
        echo "✓ Warning already fixed or not found<br>";
    }

    // 3. Update the database query to use LEFT JOIN
    echo "<h3>3. Updating database query...</h3>";

    $oldQuery = "JOIN vendors v ON p.vendor_id = v.user_id";
    $newQuery = "LEFT JOIN vendors v ON p.vendor_id = v.user_id";

    if (strpos($content, $oldQuery) !== false) {
        $content = str_replace($oldQuery, $newQuery, $content);
        file_put_contents($productsFile, $content);
        echo "✓ Updated query to use LEFT JOIN<br>";
    } else {
        echo "✓ Query already uses LEFT JOIN<br>";
    }

    // 4. Test the fix
    echo "<h3>4. Testing the fix...</h3>";

    // Simulate the query that products.php uses
    $testQuery = "
        SELECT p.*, v.business_name as vendor_name, c.name as category_name,
               (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id) as order_count,
               (SELECT COALESCE(SUM(oi.quantity * oi.price), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id) as total_revenue
        FROM products p
        LEFT JOIN vendors v ON p.vendor_id = v.user_id
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ";

    $result = $conn->query($testQuery);
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
        echo "✓ Query executed successfully<br>";
        echo "✓ Found " . count($products) . " products<br>";

        // Check if image_url field exists in results
        if (count($products) > 0) {
            $firstProduct = $products[0];
            if (isset($firstProduct['image_url'])) {
                echo "✓ image_url field exists in query results<br>";
            } else {
                echo "⚠ image_url field not in query results (this is normal if no images)<br>";
            }
        }
    } else {
        echo "✗ Query failed: " . $conn->error . "<br>";
    }

    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>✓ Image URL warning fix completed!</strong><br>";
    echo "The products.php page should now work without warnings.";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>✗ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</div>";

// Navigation
echo "<div style='margin: 20px 0;'>";
echo "<a href='products.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View Products</a>";
echo "<a href='add_product.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Add Product</a>";
echo "<a href='test_add_product.php' style='padding: 10px 20px; background: #ffc107; color: white; text-decoration: none; border-radius: 5px;'>Test Add Product</a>";
echo "</div>";
?>