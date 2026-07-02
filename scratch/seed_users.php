<?php
require_once dirname(__DIR__) . '/config/config.php';

$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);

$users = [
    ['name' => 'Admin User', 'email' => 'admin@jirani.com', 'phone' => '0700000001', 'role' => 'admin'],
    ['name' => 'Vendor Shop', 'email' => 'seller@jirani.com', 'phone' => '0700000002', 'role' => 'vendor'],
    ['name' => 'Test Customer', 'email' => 'customer@jirani.com', 'phone' => '0700000003', 'role' => 'customer'],
    ['name' => 'Gideon Muchina', 'email' => 'gideon@gmail.com', 'phone' => '0700000004', 'role' => 'customer'] // Added to reset existing too
];

foreach ($users as $u) {
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $u['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ?, role = ? WHERE email = ?");
        $stmt->bind_param("sss", $hash, $u['role'], $u['email']);
        $stmt->execute();
        echo "Updated " . $u['email'] . "\n";
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $u['name'], $u['email'], $u['phone'], $hash, $u['role']);
        $stmt->execute();
        
        $userId = $conn->insert_id;
        echo "Inserted " . $u['email'] . "\n";
        
        // If vendor, also add to vendors table
        if ($u['role'] === 'vendor') {
            $stmt2 = $conn->prepare("INSERT INTO vendors (user_id, business_name, description, status) VALUES (?, 'Test Shop', 'A test vendor shop', 'approved')");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
        }
    }
}
echo "Done.\n";
