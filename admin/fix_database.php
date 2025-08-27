<?php
require_once __DIR__ . '/../config/config.php';

// Fix database issues
echo "<h2>Database Fix Script</h2>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

try {
    // 1. Create vendors table if it doesn't exist
    echo "<h3>1. Creating vendors table...</h3>";
    $createVendorsTable = "
    CREATE TABLE IF NOT EXISTS vendors (
        user_id INT PRIMARY KEY,
        business_name VARCHAR(255) NOT NULL,
        business_type VARCHAR(100),
        email VARCHAR(255),
        address TEXT,
        status ENUM('pending', 'active', 'inactive', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($createVendorsTable)) {
        echo "✓ Vendors table created/verified successfully<br>";
    } else {
        echo "✗ Error creating vendors table: " . $conn->error . "<br>";
    }

    // 2. Add sample vendors (without phone column)
    echo "<h3>2. Adding sample vendors...</h3>";
    $vendors = [
        [1, 'Fresh Fruits Kenya', 'Agriculture', 'fresh@example.com', 'active'],
        [2, 'Organic Vegetables Ltd', 'Agriculture', 'organic@example.com', 'active'),
        [3, 'Handcrafted Goods', 'Handicrafts', 'handcraft@example.com', 'active'),
        [4, 'Fashion Forward', 'Fashion', 'fashion@example.com', 'active'),
        [5, 'Tech Solutions', 'Technology', 'tech@example.com', 'active']
    ];

    $vendorStmt = $conn->prepare("
        INSERT INTO vendors (user_id, business_name, business_type, email, status) 
        VALUES (?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            business_name = VALUES(business_name),
            status = VALUES(status)
    ");

    $addedVendors = 0;
    foreach ($vendors as $vendor) {
        $vendorStmt->bind_param("issss", $vendor[0], $vendor[1], $vendor[2], $vendor[3], $vendor[4]);
        if ($vendorStmt->execute()) {
            $addedVendors++;
        }
    }
    echo "✓ Added/updated $addedVendors vendors<br>";

    // 3. Add sample categories
    echo "<h3>3. Adding sample categories...</h3>";
    $categories = [
        ['Fruits', 'Fresh fruits and berries', 'active'],
        ['Vegetables', 'Fresh vegetables and greens', 'active'],
        ['Handicrafts', 'Handmade crafts and art', 'active'],
        ['Fashion', 'Clothing and accessories', 'active'],
        ['Technology', 'Electronics and gadgets', 'active']
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

    // 5. Add sample products
    echo "<h3>5. Adding sample products...</h3>";
    $products = [
        [1, 1, 'Fresh Mangoes', 'Sweet and juicy mangoes from Kenya', 150.00, 50, 'active'],
        [1, 1, 'Organic Bananas', 'Fresh organic bananas', 80.00, 100, 'active'],
        [2, 2, 'Fresh Tomatoes', 'Red ripe tomatoes', 120.00, 75, 'active'],
        [2, 2, 'Green Spinach', 'Fresh green spinach leaves', 60.00, 40, 'active'],
        [3, 3, 'Sisal Basket', 'Handwoven sisal basket', 500.00, 10, 'active'],
        [3, 3, 'Wooden Carving', 'Traditional wooden carving', 800.00, 5, 'active'],
        [4, 4, 'Cotton T-Shirt', 'Comfortable cotton t-shirt', 300.00, 25, 'active'),
        [4, 4, 'Leather Sandals', 'Handmade leather sandals', 450.00, 15, 'active'),
        [5, 5, 'Smartphone Case', 'Durable phone case', 200.00, 30, 'active'),
        [5, 5, 'USB Cable', 'High-quality USB cable', 150.00, 50, 'active']
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
    $vendorCount = $conn->query("SELECT COUNT(*) as count FROM vendors WHERE status = 'active'")->fetch_assoc()['count'];
    echo "✓ Active vendors: $vendorCount<br>";
    
    // Check products
    $productCount = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    echo "✓ Total products: $productCount<br>";
    
    // Check categories
    $categoryCount = $conn->query("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")->fetch_assoc()['count'];
    echo "✓ Active categories: $categoryCount<br>";

    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>✓ Database fix completed successfully!</strong><br>";
    echo "The add_product.php page should now work correctly.";
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
echo "<a href='add_product.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Add Product</a>";
echo "</div>";
?>