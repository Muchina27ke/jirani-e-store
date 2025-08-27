<?php
require_once __DIR__ . '/../config/config.php';

// Fix database issues with correct table structure
echo "<h2>Corrected Database Fix Script</h2>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

try {
    // 1. Check current vendors table structure
    echo "<h3>1. Current vendors table structure:</h3>";
    $structure = $conn->query("DESCRIBE vendors");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 2. Update existing vendors to have proper business types and locations
    echo "<h3>2. Updating existing vendors with business types and locations...</h3>";
    $updateVendors = [
        [118, 'Fresh Farm Market', 'Agriculture', 'Nairobi, Kenya'],
        [119, 'Organic Valley', 'Agriculture', 'Nakuru, Kenya'],
        [120, 'Craft Corner', 'Handicrafts', 'Mombasa, Kenya'],
        [121, 'Dairy Delights', 'Dairy', 'Eldoret, Kenya'],
        [122, 'Grain Masters', 'Agriculture', 'Kisumu, Kenya']
    ];

    $updateStmt = $conn->prepare("
        UPDATE vendors 
        SET business_type = ?, location = ? 
        WHERE user_id = ?
    ");

    $updatedVendors = 0;
    foreach ($updateVendors as $vendor) {
        $updateStmt->bind_param("ssi", $vendor[2], $vendor[3], $vendor[0]);
        if ($updateStmt->execute()) {
            $updatedVendors++;
        }
    }
    echo "✓ Updated $updatedVendors vendors with business types and locations<br>";

    // 3. Add sample categories if they don't exist
    echo "<h3>3. Adding sample categories...</h3>";
    $categories = [
        ['Fruits', 'Fresh fruits and berries', 'active'],
        ['Vegetables', 'Fresh vegetables and greens', 'active'],
        ['Handicrafts', 'Handmade crafts and art', 'active'],
        ['Dairy', 'Milk and dairy products', 'active'],
        ['Grains', 'Rice, wheat, and other grains', 'active']
    ];

    $categoryStmt = $conn->prepare("
        INSERT INTO categories (name, description, status) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name),
            status = VALUES(status)
    ");

    $addedCategories = 0;
    foreach ($categories as $category) {
        $categoryStmt->bind_param("sss", $category[0], $category[1], $category[2]);
        if ($categoryStmt->execute()) {
            $addedCategories++;
        }
    }
    echo "✓ Added/updated $addedCategories categories<br>";

    // 4. Fix products table foreign key
    echo "<h3>4. Fixing products table foreign key...</h3>";
    
    // Drop existing foreign key if it exists
    $conn->query("ALTER TABLE products DROP FOREIGN KEY IF EXISTS products_ibfk_1");
    
    // Add the correct foreign key constraint
    if ($conn->query("ALTER TABLE products ADD CONSTRAINT products_ibfk_1 FOREIGN KEY (vendor_id) REFERENCES vendors(user_id) ON DELETE CASCADE")) {
        echo "✓ Foreign key constraint added successfully<br>";
    } else {
        echo "✗ Error adding foreign key: " . $conn->error . "<br>";
    }

    // 5. Add sample products using existing vendor IDs
    echo "<h3>5. Adding sample products...</h3>";
    $products = [
        [118, 1, 'Fresh Mangoes', 'Sweet and juicy mangoes from Kenya', 150.00, 50, 'active'],
        [118, 1, 'Organic Bananas', 'Fresh organic bananas', 80.00, 100, 'active'],
        [119, 2, 'Fresh Tomatoes', 'Red ripe tomatoes', 120.00, 75, 'active'],
        [119, 2, 'Green Spinach', 'Fresh green spinach leaves', 60.00, 40, 'active'],
        [120, 3, 'Sisal Basket', 'Handwoven sisal basket', 500.00, 10, 'active'],
        [120, 3, 'Wooden Carving', 'Traditional wooden carving', 800.00, 5, 'active'],
        [121, 4, 'Fresh Milk', 'Pure fresh milk', 80.00, 30, 'active'],
        [121, 4, 'Yogurt', 'Natural yogurt', 120.00, 25, 'active'],
        [122, 5, 'Rice', 'Quality rice', 200.00, 40, 'active'],
        [122, 5, 'Wheat Flour', 'Fine wheat flour', 150.00, 35, 'active')
    ];

    $productStmt = $conn->prepare("
        INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name),
            price = VALUES(price),
            stock = VALUES(stock),
            status = VALUES(status)
    ");

    $addedProducts = 0;
    foreach ($products as $product) {
        $productStmt->bind_param("iissdis", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6]);
        if ($productStmt->execute()) {
            $addedProducts++;
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
    $products = $conn->query("SELECT p.name, p.price, v.business_name FROM products p LEFT JOIN vendors v ON p.vendor_id = v.user_id LIMIT 5");
    while ($product = $products->fetch_assoc()) {
        echo "- " . $product['name'] . " (KSh " . $product['price'] . ") - " . $product['business_name'] . "<br>";
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