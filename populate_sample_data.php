<?php
require_once 'config/config.php';

echo "<h1>Populating Sample Data for Jirani E-Commerce</h1>";

// Clear existing data (except admin users)
$conn->query("DELETE FROM order_items");
$conn->query("DELETE FROM orders");
$conn->query("DELETE FROM payments");
$conn->query("DELETE FROM escrow_payments");
$conn->query("DELETE FROM cart");
$conn->query("DELETE FROM wishlist");
$conn->query("DELETE FROM products");
$conn->query("DELETE FROM verifications");
$conn->query("DELETE FROM vendors WHERE user_id NOT IN (SELECT id FROM users WHERE role = 'admin')");
$conn->query("DELETE FROM users WHERE role != 'admin'");
$conn->query("DELETE FROM categories");

echo "<h2>✅ Cleared existing data</h2>";

// Create Categories
$categories = [
    ['name' => 'Fresh Fruits', 'description' => 'Fresh and organic fruits from local farms'],
    ['name' => 'Fresh Vegetables', 'description' => 'Fresh vegetables from local farmers'],
    ['name' => 'Handcrafts', 'description' => 'Beautiful handmade crafts and artifacts'],
    ['name' => 'Dairy Products', 'description' => 'Fresh dairy products from local farms'],
    ['name' => 'Grains & Cereals', 'description' => 'Quality grains and cereals']
];
$category_ids = [];
foreach ($categories as $category) {
    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $category['name'], $category['description']);
    $stmt->execute();
}
// Fetch category IDs by name
$result = $conn->query("SELECT id, name FROM categories");
while ($row = $result->fetch_assoc()) {
    $category_ids[$row['name']] = $row['id'];
}
echo "<h2>✅ Created categories</h2>";

// Create Sample Users (Customers and Vendors)
$users = [
    // Customers
    ['name' => 'John Doe', 'phone' => '+254700000101', 'email' => 'john@example.com', 'role' => 'customer'],
    ['name' => 'Jane Smith', 'phone' => '+254700000102', 'email' => 'jane@example.com', 'role' => 'customer'],
    ['name' => 'Mike Johnson', 'phone' => '+254700000103', 'email' => 'mike@example.com', 'role' => 'customer'],
    ['name' => 'Sarah Wilson', 'phone' => '+254700000104', 'email' => 'sarah@example.com', 'role' => 'customer'],
    ['name' => 'David Brown', 'phone' => '+254700000105', 'email' => 'david@example.com', 'role' => 'customer'],

    // Vendors
    ['name' => 'Fresh Farm Market', 'phone' => '+254700000201', 'email' => 'freshfarm@example.com', 'role' => 'vendor'],
    ['name' => 'Organic Valley', 'phone' => '+254700000202', 'email' => 'organicvalley@example.com', 'role' => 'vendor'],
    ['name' => 'Craft Corner', 'phone' => '+254700000203', 'email' => 'craftcorner@example.com', 'role' => 'vendor'],
    ['name' => 'Dairy Delights', 'phone' => '+254700000204', 'email' => 'dairydelights@example.com', 'role' => 'vendor'],
    ['name' => 'Grain Masters', 'phone' => '+254700000205', 'email' => 'grainmasters@example.com', 'role' => 'vendor']
];

$vendor_ids = [];
$customer_ids = [];
foreach ($users as $user) {
    $hashed_password = password_hash('password123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (name, phone, email, password, role, verified) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssss", $user['name'], $user['phone'], $user['email'], $hashed_password, $user['role']);
    $stmt->execute();
    if ($user['role'] === 'vendor') {
        $vendor_ids[$user['email']] = $conn->insert_id;
    }
    if ($user['role'] === 'customer') {
        $customer_ids[$user['email']] = $conn->insert_id;
    }
}

echo "<h2>✅ Created users</h2>";

// Create Vendors
$vendor_businesses = [
    ['email' => 'freshfarm@example.com', 'business_name' => 'Fresh Farm Market', 'mpesa_number' => '+254700000201', 'status' => 'approved'],
    ['email' => 'organicvalley@example.com', 'business_name' => 'Organic Valley', 'mpesa_number' => '+254700000202', 'status' => 'approved'],
    ['email' => 'craftcorner@example.com', 'business_name' => 'Craft Corner', 'mpesa_number' => '+254700000203', 'status' => 'approved'],
    ['email' => 'dairydelights@example.com', 'business_name' => 'Dairy Delights', 'mpesa_number' => '+254700000204', 'status' => 'approved'],
    ['email' => 'grainmasters@example.com', 'business_name' => 'Grain Masters', 'mpesa_number' => '+254700000205', 'status' => 'approved']
];

foreach ($vendor_businesses as $vendor) {
    $user_id = $vendor_ids[$vendor['email']];
    $stmt = $conn->prepare("INSERT INTO vendors (user_id, business_name, mpesa_number, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $vendor['business_name'], $vendor['mpesa_number'], $vendor['status']);
    $stmt->execute();
}

echo "<h2>✅ Created vendors</h2>";

// Create Products with Images
$products = [
    // Fresh Fruits
    ['name' => 'Fresh Mangoes', 'description' => 'Sweet and juicy mangoes from local farms', 'price' => 150.00, 'stock' => 50, 'vendor_email' => 'freshfarm@example.com', 'category' => 'Fresh Fruits', 'image' => 'Images/fruits/mangoes.jpg'],
    ['name' => 'Organic Bananas', 'description' => 'Organic bananas rich in potassium', 'price' => 80.00, 'stock' => 100, 'vendor_email' => 'freshfarm@example.com', 'category' => 'Fresh Fruits', 'image' => 'Images/fruits/banana.jpg'],
    ['name' => 'Fresh Oranges', 'description' => 'Vitamin C rich oranges', 'price' => 120.00, 'stock' => 75, 'vendor_email' => 'organicvalley@example.com', 'category' => 'Fresh Fruits', 'image' => 'Images/fruits/oranges.jpg'],
    ['name' => 'Pineapples', 'description' => 'Sweet and tangy pineapples', 'price' => 200.00, 'stock' => 30, 'vendor_email' => 'organicvalley@example.com', 'category' => 'Fresh Fruits', 'image' => 'Images/fruits/1.avif'],
    ['name' => 'Avocados', 'description' => 'Creamy and nutritious avocados', 'price' => 100.00, 'stock' => 60, 'vendor_email' => 'freshfarm@example.com', 'category' => 'Fresh Fruits', 'image' => 'Images/fruits/2.avif'],

    // Fresh Vegetables
    ['name' => 'Fresh Tomatoes', 'description' => 'Ripe and juicy tomatoes', 'price' => 80.00, 'stock' => 80, 'vendor_email' => 'freshfarm@example.com', 'category' => 'Fresh Vegetables', 'image' => 'Images/vegetables/tomatoes.jpg'],
    ['name' => 'Green Bell Peppers', 'description' => 'Fresh green bell peppers', 'price' => 120.00, 'stock' => 45, 'vendor_email' => 'organicvalley@example.com', 'category' => 'Fresh Vegetables', 'image' => 'Images/vegetables/Capsicum.jpg'],
    ['name' => 'Fresh Onions', 'description' => 'Quality red onions', 'price' => 60.00, 'stock' => 100, 'vendor_email' => 'freshfarm@example.com', 'category' => 'Fresh Vegetables', 'image' => 'Images/vegetables/1.jpg'],
    ['name' => 'Carrots', 'description' => 'Sweet and crunchy carrots', 'price' => 90.00, 'stock' => 70, 'vendor_email' => 'organicvalley@example.com', 'category' => 'Fresh Vegetables', 'image' => 'Images/vegetables/2.jpg'],
    ['name' => 'Fresh Spinach', 'description' => 'Nutritious green spinach', 'price' => 50.00, 'stock' => 40, 'vendor_email' => 'freshfarm@example.com', 'category' => 'Fresh Vegetables', 'image' => 'Images/vegetables/3.jpg'],

    // Handcrafts
    ['name' => 'Sisal Basket', 'description' => 'Beautiful handwoven sisal basket', 'price' => 800.00, 'stock' => 15, 'vendor_email' => 'craftcorner@example.com', 'category' => 'Handcrafts', 'image' => 'Images/handicrafts/1.jpg'],
    ['name' => 'Wooden Carving', 'description' => 'Traditional wooden carving', 'price' => 1200.00, 'stock' => 10, 'vendor_email' => 'craftcorner@example.com', 'category' => 'Handcrafts', 'image' => 'Images/handicrafts/2.jpg'],
    ['name' => 'Beaded Necklace', 'description' => 'Handmade beaded jewelry', 'price' => 300.00, 'stock' => 25, 'vendor_email' => 'craftcorner@example.com', 'category' => 'Handcrafts', 'image' => 'Images/handicrafts/3.jpg'],
    ['name' => 'Clay Pot', 'description' => 'Traditional clay cooking pot', 'price' => 500.00, 'stock' => 20, 'vendor_email' => 'craftcorner@example.com', 'category' => 'Handcrafts', 'image' => 'Images/handicrafts/4.jpg'],
    ['name' => 'Woven Mat', 'description' => 'Colorful woven floor mat', 'price' => 400.00, 'stock' => 12, 'vendor_email' => 'craftcorner@example.com', 'category' => 'Handcrafts', 'image' => 'Images/handicrafts/5.jpg'],

    // Dairy Products
    ['name' => 'Fresh Milk', 'description' => 'Pure fresh milk from local farms', 'price' => 120.00, 'stock' => 50, 'vendor_email' => 'dairydelights@example.com', 'category' => 'Dairy Products', 'image' => 'Images/milk.jpg'],
    ['name' => 'Yogurt', 'description' => 'Natural yogurt', 'price' => 150.00, 'stock' => 30, 'vendor_email' => 'dairydelights@example.com', 'category' => 'Dairy Products', 'image' => 'Images/handicrafts/6.jpg'],
    ['name' => 'Cheese', 'description' => 'Local farm cheese', 'price' => 300.00, 'stock' => 20, 'vendor_email' => 'dairydelights@example.com', 'category' => 'Dairy Products', 'image' => 'Images/handicrafts/7.jpg'],

    // Grains & Cereals
    ['name' => 'Maize Flour', 'description' => 'Quality maize flour', 'price' => 200.00, 'stock' => 40, 'vendor_email' => 'grainmasters@example.com', 'category' => 'Grains & Cereals', 'image' => 'Images/handicrafts/8.jpg'],
    ['name' => 'Rice', 'description' => 'Premium quality rice', 'price' => 250.00, 'stock' => 35, 'vendor_email' => 'grainmasters@example.com', 'category' => 'Grains & Cereals', 'image' => 'Images/handicrafts/9.jpg'],
    ['name' => 'Beans', 'description' => 'Fresh beans from local farms', 'price' => 180.00, 'stock' => 45, 'vendor_email' => 'grainmasters@example.com', 'category' => 'Grains & Cereals', 'image' => 'Images/vegetables/Bush beans.jpg']
];

$product_ids = [];
foreach ($products as $product) {
    $vendor_id = $vendor_ids[$product['vendor_email']];
    $category_id = $category_ids[$product['category']];
    $stmt = $conn->prepare("INSERT INTO products (vendor_id, category_id, name, description, price, stock, status, image) VALUES (?, ?, ?, ?, ?, ?, 'active', ?)");
    $stmt->bind_param("iissdis", $vendor_id, $category_id, $product['name'], $product['description'], $product['price'], $product['stock'], $product['image']);
    $stmt->execute();
    $product_ids[$product['name']] = $conn->insert_id;
}

echo "<h2>✅ Created products</h2>";

// Create Orders with realistic dates
$orders = [
    ['customer_email' => 'john@example.com', 'vendor_email' => 'freshfarm@example.com', 'status' => 'delivered', 'delivery_address' => '123 Main St, Kakamega', 'created_at' => '2025-01-15 10:30:00'],
    ['customer_email' => 'jane@example.com', 'vendor_email' => 'organicvalley@example.com', 'status' => 'delivered', 'delivery_address' => '456 Oak Ave, Kakamega', 'created_at' => '2025-01-16 14:20:00'],
    ['customer_email' => 'mike@example.com', 'vendor_email' => 'craftcorner@example.com', 'status' => 'shipped', 'delivery_address' => '789 Pine Rd, Kakamega', 'created_at' => '2025-01-17 09:15:00'],
    ['customer_email' => 'sarah@example.com', 'vendor_email' => 'dairydelights@example.com', 'status' => 'accepted', 'delivery_address' => '321 Elm St, Kakamega', 'created_at' => '2025-01-18 16:45:00'],
    ['customer_email' => 'david@example.com', 'vendor_email' => 'grainmasters@example.com', 'status' => 'pending', 'delivery_address' => '654 Maple Dr, Kakamega', 'created_at' => '2025-01-19 11:30:00'],
    ['customer_email' => 'john@example.com', 'vendor_email' => 'organicvalley@example.com', 'status' => 'delivered', 'delivery_address' => '123 Main St, Kakamega', 'created_at' => '2025-01-20 13:20:00'],
    ['customer_email' => 'jane@example.com', 'vendor_email' => 'craftcorner@example.com', 'status' => 'delivered', 'delivery_address' => '456 Oak Ave, Kakamega', 'created_at' => '2025-01-21 15:10:00'],
    ['customer_email' => 'mike@example.com', 'vendor_email' => 'dairydelights@example.com', 'status' => 'shipped', 'delivery_address' => '789 Pine Rd, Kakamega', 'created_at' => '2025-01-22 08:45:00'],
    ['customer_email' => 'sarah@example.com', 'vendor_email' => 'freshfarm@example.com', 'status' => 'accepted', 'delivery_address' => '321 Elm St, Kakamega', 'created_at' => '2025-01-23 12:30:00'],
    ['customer_email' => 'david@example.com', 'vendor_email' => 'organicvalley@example.com', 'status' => 'pending', 'delivery_address' => '654 Maple Dr, Kakamega', 'created_at' => '2025-01-24 10:15:00']
];

$order_ids = [];
foreach ($orders as $order) {
    $customer_id = $customer_ids[$order['customer_email']];
    $vendor_id = $vendor_ids[$order['vendor_email']];
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, vendor_id, status, delivery_address, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $customer_id, $vendor_id, $order['status'], $order['delivery_address'], $order['created_at']);
    $stmt->execute();
    $order_ids[] = $conn->insert_id;
}

echo "<h2>✅ Created orders</h2>";

// Create Order Items
$order_items = [
    [0, 'Fresh Mangoes', 2, 150.00], // Order 1: 2 mangoes
    [0, 'Fresh Tomatoes', 1, 80.00],  // Order 1: 1 tomato
    [1, 'Fresh Oranges', 3, 120.00], // Order 2: 3 oranges
    [1, 'Green Bell Peppers', 2, 120.00], // Order 2: 2 bell peppers
    [2, 'Sisal Basket', 1, 800.00], // Order 3: 1 sisal basket
    [3, 'Fresh Milk', 2, 120.00], // Order 4: 2 milk
    [4, 'Maize Flour', 1, 200.00], // Order 5: 1 maize flour
    [5, 'Organic Bananas', 2, 80.00],  // Order 6: 2 bananas
    [5, 'Fresh Onions', 1, 60.00],  // Order 6: 1 onion
    [6, 'Wooden Carving', 1, 1200.00], // Order 7: 1 wooden carving
    [7, 'Yogurt', 1, 150.00], // Order 8: 1 yogurt
    [8, 'Pineapples', 2, 200.00], // Order 9: 2 pineapples
    [9, 'Avocados', 1, 100.00] // Order 10: 1 avocado
];

foreach ($order_items as $item) {
    $order_id = $order_ids[$item[0]];
    $product_id = $product_ids[$item[1]];
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $order_id, $product_id, $item[2], $item[3]);
    $stmt->execute();
}

echo "<h2>✅ Created order items</h2>";

// Create Payments
$payments = [
    [0, 380.00, 'MPESA123456', 'completed', 'mpesa', 1, '2025-01-15 10:35:00'],
    [1, 480.00, 'MPESA123457', 'completed', 'mpesa', 1, '2025-01-16 14:25:00'],
    [2, 800.00, 'MPESA123458', 'completed', 'mpesa', 1, '2025-01-17 09:20:00'],
    [3, 240.00, 'MPESA123459', 'completed', 'mpesa', 1, '2025-01-18 16:50:00'],
    [4, 200.00, 'MPESA123460', 'pending', 'mpesa', 1, '2025-01-19 11:35:00'],
    [5, 140.00, 'MPESA123461', 'completed', 'mpesa', 1, '2025-01-20 13:25:00'],
    [6, 1200.00, 'MPESA123462', 'completed', 'mpesa', 1, '2025-01-21 15:15:00'],
    [7, 150.00, 'MPESA123463', 'completed', 'mpesa', 1, '2025-01-22 08:50:00'],
    [8, 400.00, 'MPESA123464', 'completed', 'mpesa', 1, '2025-01-23 12:35:00'],
    [9, 100.00, 'MPESA123465', 'pending', 'mpesa', 1, '2025-01-24 10:20:00']
];

$payment_ids = [];
foreach ($payments as $payment) {
    $order_id = $order_ids[$payment[0]];
    $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, mpesa_transaction_id, status, method, is_escrow, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idsssss", $order_id, $payment[1], $payment[2], $payment[3], $payment[4], $payment[5], $payment[6]);
    $stmt->execute();
    $payment_ids[] = $conn->insert_id;
}

echo "<h2>✅ Created payments</h2>";

// Create Escrow Payments
$escrow_payments = [
    [0, 0, 'held'],
    [1, 1, 'held'],
    [2, 2, 'held'],
    [3, 3, 'held'],
    [4, 4, 'held'],
    [5, 5, 'released_to_vendor'],
    [6, 6, 'released_to_vendor'],
    [7, 7, 'held'],
    [8, 8, 'held'],
    [9, 9, 'held']
];

foreach ($escrow_payments as $escrow) {
    $order_id = $order_ids[$escrow[0]];
    $payment_id = $payment_ids[$escrow[1]];
    $stmt = $conn->prepare("INSERT INTO escrow_payments (order_id, payment_id, escrow_status) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $order_id, $payment_id, $escrow[2]);
    $stmt->execute();
}

echo "<h2>✅ Created escrow payments</h2>";

// Create Verifications
$admin_id = null;
$result = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $admin_id = $row['id'];
}

$verifications = [
    ['email' => 'freshfarm@example.com', 'id_doc_url' => 'uploads/vendor_documents/id1.jpg', 'business_doc_url' => 'uploads/vendor_documents/business1.jpg', 'status' => 'approved', 'verified_by' => $admin_id, 'verified_at' => '2025-01-10 09:00:00'],
    ['email' => 'organicvalley@example.com', 'id_doc_url' => 'uploads/vendor_documents/id2.jpg', 'business_doc_url' => 'uploads/vendor_documents/business2.jpg', 'status' => 'approved', 'verified_by' => $admin_id, 'verified_at' => '2025-01-11 10:00:00'],
    ['email' => 'craftcorner@example.com', 'id_doc_url' => 'uploads/vendor_documents/id3.jpg', 'business_doc_url' => 'uploads/vendor_documents/business3.jpg', 'status' => 'approved', 'verified_by' => $admin_id, 'verified_at' => '2025-01-12 11:00:00'],
    ['email' => 'dairydelights@example.com', 'id_doc_url' => 'uploads/vendor_documents/id4.jpg', 'business_doc_url' => 'uploads/vendor_documents/business4.jpg', 'status' => 'approved', 'verified_by' => $admin_id, 'verified_at' => '2025-01-13 12:00:00'],
    ['email' => 'grainmasters@example.com', 'id_doc_url' => 'uploads/vendor_documents/id5.jpg', 'business_doc_url' => 'uploads/vendor_documents/business5.jpg', 'status' => 'approved', 'verified_by' => $admin_id, 'verified_at' => '2025-01-14 13:00:00']
];

foreach ($verifications as $verification) {
    $vendor_id = $vendor_ids[$verification['email']];
    $stmt = $conn->prepare("INSERT INTO verifications (vendor_id, id_doc_url, business_doc_url, status, verified_by, verified_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $vendor_id, $verification['id_doc_url'], $verification['business_doc_url'], $verification['status'], $verification['verified_by'], $verification['verified_at']);
    $stmt->execute();
}

echo "<h2>✅ Created verifications</h2>";

// Create some cart items
$cart_items = [
    ['customer_email' => 'john@example.com', 'product_name' => 'Fresh Mangoes', 'quantity' => 1],
    ['customer_email' => 'john@example.com', 'product_name' => 'Fresh Tomatoes', 'quantity' => 2],
    ['customer_email' => 'jane@example.com', 'product_name' => 'Fresh Oranges', 'quantity' => 1],
    ['customer_email' => 'mike@example.com', 'product_name' => 'Sisal Basket', 'quantity' => 1],
    ['customer_email' => 'sarah@example.com', 'product_name' => 'Fresh Milk', 'quantity' => 1]
];

foreach ($cart_items as $cart_item) {
    $user_id = $customer_ids[$cart_item['customer_email']];
    $product_id = $product_ids[$cart_item['product_name']];
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $product_id, $cart_item['quantity']);
    $stmt->execute();
}

echo "<h2>✅ Created cart items</h2>";

// Create some wishlist items
$wishlist_items = [
    ['customer_email' => 'john@example.com', 'product_name' => 'Organic Bananas'],
    ['customer_email' => 'john@example.com', 'product_name' => 'Green Bell Peppers'],
    ['customer_email' => 'jane@example.com', 'product_name' => 'Sisal Basket'],
    ['customer_email' => 'mike@example.com', 'product_name' => 'Fresh Milk'],
    ['customer_email' => 'sarah@example.com', 'product_name' => 'Maize Flour']
];

foreach ($wishlist_items as $wishlist_item) {
    $user_id = $customer_ids[$wishlist_item['customer_email']];
    $product_id = $product_ids[$wishlist_item['product_name']];
    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
}

echo "<h2>✅ Created wishlist items</h2>";

echo "<h2>🎉 Sample Data Population Complete!</h2>";

echo "<h3>Summary:</h3>";
echo "<ul>";
echo "<li>✅ 5 Categories created</li>";
echo "<li>✅ 10 Users (5 customers, 5 vendors) created</li>";
echo "<li>✅ 5 Vendors with approved status</li>";
echo "<li>✅ 20 Products with images across all categories</li>";
echo "<li>✅ 10 Orders with realistic dates and statuses</li>";
echo "<li>✅ 13 Order items with proper pricing</li>";
echo "<li>✅ 10 Payments with M-Pesa integration</li>";
echo "<li>✅ 10 Escrow payments with various statuses</li>";
echo "<li>✅ 5 Vendor verifications (all approved)</li>";
echo "<li>✅ 5 Cart items for active shopping</li>";
echo "<li>✅ 5 Wishlist items</li>";
echo "</ul>";

echo "<h3>Test Credentials:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@example.com / password123</li>";
echo "<li><strong>Vendor:</strong> freshfarm@example.com / password123</li>";
echo "<li><strong>Customer:</strong> john@example.com / password123</li>";
echo "</ul>";

echo "<h3>Key Features Ready:</h3>";
echo "<ul>";
echo "<li>📊 Sales Analytics Dashboard with printable reports</li>";
echo "<li>🛒 Shopping cart and checkout process</li>";
echo "<li>📦 Order tracking system</li>";
echo "<li>💳 M-Pesa payment integration</li>";
echo "<li>🏪 Vendor management and verification</li>";
echo "<li>👥 User management and roles</li>";
echo "<li>📱 Account settings and support</li>";
echo "</ul>";

echo "<p><strong>The system is now ready for professional screenshots with realistic data!</strong></p>";
?>