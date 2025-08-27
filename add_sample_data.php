<?php
require_once 'config/config.php';
$db = getDbConnection();

echo "<h1>Adding Sample Data for Screenshots</h1>";

// Add categories if they don't exist
$categories = [
    ['name' => 'Fruits', 'description' => 'Fresh fruits and berries'],
    ['name' => 'Vegetables', 'description' => 'Fresh vegetables and greens'],
    ['name' => 'Handcrafts', 'description' => 'Handmade crafts and artisanal products'],
    ['name' => 'Dairy', 'description' => 'Milk, cheese, and dairy products'],
    ['name' => 'Grains', 'description' => 'Rice, maize, and other grains']
];

echo "<h2>Adding Categories...</h2>";
foreach ($categories as $category) {
    $stmt = $db->prepare("INSERT IGNORE INTO categories (name, description, status) VALUES (?, ?, 'active')");
    $stmt->bind_param("ss", $category['name'], $category['description']);
    if ($stmt->execute()) {
        echo "✅ Added category: {$category['name']}<br>";
    } else {
        echo "⚠️ Category {$category['name']} already exists<br>";
    }
}

// Get category IDs
$categoryIds = [];
$result = $db->query("SELECT id, name FROM categories WHERE status = 'active'");
while ($row = $result->fetch_assoc()) {
    $categoryIds[$row['name']] = $row['id'];
}

// Get a vendor user for products
$stmt = $db->prepare("SELECT v.user_id FROM vendors v JOIN users u ON v.user_id = u.id WHERE u.role = 'vendor' LIMIT 1");
$stmt->execute();
$vendorResult = $stmt->get_result();
$vendorId = null;

if ($vendorResult->num_rows > 0) {
    $vendorId = $vendorResult->fetch_assoc()['user_id'];
} else {
    // Create a sample vendor if none exists
    $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role, verified) VALUES (?, ?, ?, ?, 'vendor', 1)");
    $vendorName = "Sample Vendor";
    $vendorEmail = "vendor@jirani.com";
    $vendorPhone = "254700000002";
    $vendorPassword = password_hash("password123", PASSWORD_DEFAULT);
    $stmt->bind_param("ssss", $vendorName, $vendorEmail, $vendorPhone, $vendorPassword);
    $stmt->execute();
    $userId = $db->insert_id;

    // Add to vendors table
    $stmt = $db->prepare("INSERT INTO vendors (user_id, business_name, status) VALUES (?, ?, 'approved')");
    $businessName = "Sample Vendor Business";
    $stmt->bind_param("is", $userId, $businessName);
    $stmt->execute();

    $vendorId = $userId;
    echo "✅ Created sample vendor<br>";
}

// Sample products data
$products = [
    // Fruits
    [
        'name' => 'Fresh Mangoes',
        'description' => 'Sweet and juicy mangoes from local farms',
        'price' => 150.00,
        'quantity' => 50,
        'category_id' => $categoryIds['Fruits']
    ],
    [
        'name' => 'Organic Bananas',
        'description' => 'Fresh organic bananas, perfect for smoothies',
        'price' => 80.00,
        'quantity' => 100,
        'category_id' => $categoryIds['Fruits']
    ],
    [
        'name' => 'Sweet Oranges',
        'description' => 'Juicy oranges rich in vitamin C',
        'price' => 120.00,
        'quantity' => 75,
        'category_id' => $categoryIds['Fruits']
    ],

    // Vegetables
    [
        'name' => 'Fresh Tomatoes',
        'description' => 'Ripe red tomatoes for cooking and salads',
        'price' => 100.00,
        'quantity' => 60,
        'category_id' => $categoryIds['Vegetables']
    ],
    [
        'name' => 'Green Capsicum',
        'description' => 'Fresh green bell peppers',
        'price' => 90.00,
        'quantity' => 40,
        'category_id' => $categoryIds['Vegetables']
    ],
    [
        'name' => 'Fresh Carrots',
        'description' => 'Sweet and crunchy carrots',
        'price' => 70.00,
        'quantity' => 80,
        'category_id' => $categoryIds['Vegetables']
    ],

    // Handcrafts
    [
        'name' => 'Sisal Basket',
        'description' => 'Handwoven sisal basket, perfect for storage',
        'price' => 2500.00,
        'quantity' => 15,
        'category_id' => $categoryIds['Handcrafts']
    ],
    [
        'name' => 'Wooden Carving',
        'description' => 'Beautiful hand-carved wooden decoration',
        'price' => 3500.00,
        'quantity' => 8,
        'category_id' => $categoryIds['Handcrafts']
    ],
    [
        'name' => 'Beaded Necklace',
        'description' => 'Traditional beaded necklace, handmade',
        'price' => 800.00,
        'quantity' => 25,
        'category_id' => $categoryIds['Handcrafts']
    ],

    // Dairy
    [
        'name' => 'Fresh Milk',
        'description' => 'Pure fresh milk from local dairy farms',
        'price' => 120.00,
        'quantity' => 30,
        'category_id' => $categoryIds['Dairy']
    ],

    // Grains
    [
        'name' => 'Organic Rice',
        'description' => 'Premium quality organic rice',
        'price' => 180.00,
        'quantity' => 45,
        'category_id' => $categoryIds['Grains']
    ]
];

echo "<h2>Adding Sample Products...</h2>";
$addedProducts = 0;

foreach ($products as $product) {
    $stmt = $db->prepare("INSERT INTO products (name, description, price, stock, category_id, vendor_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param(
        "ssdiis",
        $product['name'],
        $product['description'],
        $product['price'],
        $product['quantity'],
        $product['category_id'],
        $vendorId
    );

    if ($stmt->execute()) {
        echo "✅ Added product: {$product['name']} - KSh {$product['price']}<br>";
        $addedProducts++;
    } else {
        echo "❌ Failed to add product: {$product['name']}<br>";
    }
}

// Add some sample orders for order tracking screenshots
echo "<h2>Adding Sample Orders...</h2>";

// Get a customer user
$stmt = $db->prepare("SELECT id FROM users WHERE role = 'customer' LIMIT 1");
$stmt->execute();
$customerResult = $stmt->get_result();

if ($customerResult->num_rows > 0) {
    $customerId = $customerResult->fetch_assoc()['id'];

    // Get some product IDs
    $productIds = [];
    $result = $db->query("SELECT id FROM products LIMIT 3");
    while ($row = $result->fetch_assoc()) {
        $productIds[] = $row['id'];
    }

    if (!empty($productIds)) {
        // Create sample orders
        $orders = [
            [
                'customer_id' => $customerId,
                'vendor_id' => $vendorId,
                'status' => 'pending',
                'delivery_address' => '123 Main Street, Kakamega',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'customer_id' => $customerId,
                'vendor_id' => $vendorId,
                'status' => 'accepted',
                'delivery_address' => '456 Market Road, Kakamega',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'customer_id' => $customerId,
                'vendor_id' => $vendorId,
                'status' => 'delivered',
                'delivery_address' => '789 School Lane, Kakamega',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ]
        ];

        foreach ($orders as $order) {
            $stmt = $db->prepare("INSERT INTO orders (customer_id, vendor_id, status, delivery_address, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "iisss",
                $order['customer_id'],
                $order['vendor_id'],
                $order['status'],
                $order['delivery_address'],
                $order['created_at']
            );

            if ($stmt->execute()) {
                $orderId = $db->insert_id;
                echo "✅ Added order #$orderId - {$order['status']}<br>";

                // Add order items
                $productId = $productIds[array_rand($productIds)];
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $quantity = rand(1, 3);
                $price = rand(50, 200);
                $stmt->bind_param("iiid", $orderId, $productId, $quantity, $price);
                $stmt->execute();
            }
        }
    }
}

echo "<h2>Summary</h2>";
echo "✅ Added " . count($categories) . " categories<br>";
echo "✅ Added $addedProducts products<br>";
echo "✅ Added sample orders for tracking screenshots<br>";

echo "<h2>Next Steps for Screenshots</h2>";
echo "<ol>";
echo "<li>Refresh your browser and visit the homepage</li>";
echo "<li>You should now see products displayed</li>";
echo "<li>Login as different users to test all features</li>";
echo "<li>Take screenshots of all pages with the new sample data</li>";
echo "</ol>";

echo "<p><strong>Your system is now ready for professional screenshot capture!</strong></p>";
?>