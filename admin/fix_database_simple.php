<?php
require_once __DIR__ . '/../config/config.php';

// Simple database fix script
echo "<h2>Simple Database Fix Script</h2>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

try {
    // 1. Update existing vendors with business types
    echo "<h3>1. Updating existing vendors...</h3>";

    $updateQueries = [
        "UPDATE vendors SET business_type = 'Agriculture', location = 'Nairobi, Kenya' WHERE user_id = 118",
        "UPDATE vendors SET business_type = 'Agriculture', location = 'Nakuru, Kenya' WHERE user_id = 119",
        "UPDATE vendors SET business_type = 'Handicrafts', location = 'Mombasa, Kenya' WHERE user_id = 120",
        "UPDATE vendors SET business_type = 'Dairy', location = 'Eldoret, Kenya' WHERE user_id = 121",
        "UPDATE vendors SET business_type = 'Agriculture', location = 'Kisumu, Kenya' WHERE user_id = 122"
    ];

    $updatedVendors = 0;
    foreach ($updateQueries as $query) {
        if ($conn->query($query)) {
            $updatedVendors++;
        }
    }
    echo "✓ Updated $updatedVendors vendors with business types and locations<br>";

    // 2. Add sample categories and get their IDs
    echo "<h3>2. Adding sample categories...</h3>";

    $categoryQueries = [
        "INSERT INTO categories (name, description, status) VALUES ('Fruits', 'Fresh fruits and berries', 'active') ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO categories (name, description, status) VALUES ('Vegetables', 'Fresh vegetables and greens', 'active') ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO categories (name, description, status) VALUES ('Handicrafts', 'Handmade crafts and art', 'active') ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO categories (name, description, status) VALUES ('Dairy', 'Milk and dairy products', 'active') ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO categories (name, description, status) VALUES ('Grains', 'Rice, wheat, and other grains', 'active') ON DUPLICATE KEY UPDATE name = VALUES(name)"
    ];

    $addedCategories = 0;
    foreach ($categoryQueries as $query) {
        if ($conn->query($query)) {
            $addedCategories++;
        }
    }
    echo "✓ Added/updated $addedCategories categories<br>";

    // 3. Get actual category IDs
    echo "<h3>3. Getting category IDs...</h3>";
    $categoryMap = [];
    $categories = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
    while ($category = $categories->fetch_assoc()) {
        $categoryMap[$category['name']] = $category['id'];
        echo "- " . $category['name'] . " (ID: " . $category['id'] . ")<br>";
    }

    // 4. Fix products table foreign key
    echo "<h3>4. Fixing products table foreign key...</h3>";

    // Drop existing foreign key if it exists
    $conn->query("ALTER TABLE products DROP FOREIGN KEY IF EXISTS products_ibfk_1");
    $conn->query("ALTER TABLE products DROP FOREIGN KEY IF EXISTS products_ibfk_2");

    // Add the correct foreign key constraints
    if ($conn->query("ALTER TABLE products ADD CONSTRAINT products_ibfk_1 FOREIGN KEY (vendor_id) REFERENCES vendors(user_id) ON DELETE CASCADE")) {
        echo "✓ Vendor foreign key constraint added successfully<br>";
    } else {
        echo "✗ Error adding vendor foreign key: " . $conn->error . "<br>";
    }

    if ($conn->query("ALTER TABLE products ADD CONSTRAINT products_ibfk_2 FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL")) {
        echo "✓ Category foreign key constraint added successfully<br>";
    } else {
        echo "✗ Error adding category foreign key: " . $conn->error . "<br>";
    }

    // 5. Add sample products using actual category IDs
    echo "<h3>5. Adding sample products...</h3>";

    // Use actual category IDs from the map
    $fruitsId = $categoryMap['Fruits'] ?? 1;
    $vegetablesId = $categoryMap['Vegetables'] ?? 2;
    $handicraftsId = $categoryMap['Handicrafts'] ?? 3;
    $dairyId = $categoryMap['Dairy'] ?? 4;
    $grainsId = $categoryMap['Grains'] ?? 5;

    $productQueries = [
        "INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) VALUES (118, $fruitsId, 'Fresh Mangoes', 'Sweet and juicy mangoes from Kenya', 150.00, 50, 'active', NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) VALUES (118, $fruitsId, 'Organic Bananas', 'Fresh organic bananas', 80.00, 100, 'active', NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) VALUES (119, $vegetablesId, 'Fresh Tomatoes', 'Red ripe tomatoes', 120.00, 75, 'active', NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) VALUES (119, $vegetablesId, 'Green Spinach', 'Fresh green spinach leaves', 60.00, 40, 'active', NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) VALUES (120, $handicraftsId, 'Sisal Basket', 'Handwoven sisal basket', 500.00, 10, 'active', NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) VALUES (120, $handicraftsId, 'Wooden Carving', 'Traditional wooden carving', 800.00, 5, 'active', NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) VALUES (121, $dairyId, 'Fresh Milk', 'Pure fresh milk', 80.00, 30, 'active', NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) VALUES (121, $dairyId, 'Yogurt', 'Natural yogurt', 120.00, 25, 'active', NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) VALUES (122, $grainsId, 'Rice', 'Quality rice', 200.00, 40, 'active', NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)",
        "INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) VALUES (122, $grainsId, 'Wheat Flour', 'Fine wheat flour', 150.00, 35, 'active', NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name)"
    ];

    $addedProducts = 0;
    foreach ($productQueries as $query) {
        if ($conn->query($query)) {
            $addedProducts++;
        } else {
            echo "✗ Error adding product: " . $conn->error . "<br>";
        }
    }
    echo "✓ Added/updated $addedProducts products<br>";

    // 6. Verify the fix
    echo "<h3>6. Verification...</h3>";

    // Check vendors
    $vendorCount = $conn->query("SELECT COUNT(*) as count FROM vendors WHERE status = 'approved'")->fetch_assoc()['count'];
    echo "✓ Approved vendors: $vendorCount<br>";

    // Check products
    $productCount = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    echo "✓ Total products: $productCount<br>";

    // Check categories
    $categoryCount = $conn->query("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")->fetch_assoc()['count'];
    echo "✓ Active categories: $categoryCount<br>";

    // Show sample data
    echo "<h3>7. Sample Data Preview:</h3>";

    // Show vendors
    echo "<strong>Vendors:</strong><br>";
    $vendors = $conn->query("SELECT user_id, business_name, business_type, status FROM vendors LIMIT 5");
    while ($vendor = $vendors->fetch_assoc()) {
        echo "- " . $vendor['business_name'] . " (" . $vendor['business_type'] . ") - " . $vendor['status'] . "<br>";
    }

    // Show products
    echo "<br><strong>Products:</strong><br>";
    $products = $conn->query("SELECT p.name, p.price, v.business_name, c.name as category_name FROM products p LEFT JOIN vendors v ON p.vendor_id = v.user_id LEFT JOIN categories c ON p.category_id = c.id LIMIT 5");
    while ($product = $products->fetch_assoc()) {
        echo "- " . $product['name'] . " (KSh " . $product['price'] . ") - " . $product['business_name'] . " (" . $product['category_name'] . ")<br>";
    }

    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>✓ Database fix completed successfully!</strong><br>";
    echo "The add_product.php page should now work correctly with the existing vendors.";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>✗ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</div>";

// Navigation
echo "<div style='margin: 20px 0;'>";
echo "<a href='test_add_product.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Add Product</a>";
echo "<a href='add_product.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Add Product</a>";
echo "<a href='products.php' style='padding: 10px 20px; background: #ffc107; color: white; text-decoration: none; border-radius: 5px;'>View Products</a>";
echo "</div>";
?>