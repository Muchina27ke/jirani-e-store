<?php
// fix_qa_issues.php
// Run this ONCE to fix seller system QA issues: vendor status sync, add payment_method column, fix empty order status
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id']) || !$auth->hasRole('admin')) {
    die('Access denied. Admins only.');
}

$db = getDbConnection();
$results = [];

// 1. Sync vendor and verification status
$results['vendor_sync'] = [];
$sql = "SELECT v.user_id as vendor_id, v.status as vendor_status, ver.status as verification_status FROM vendors v LEFT JOIN verifications ver ON v.user_id = ver.vendor_id WHERE v.status != ver.status OR v.status IS NULL OR ver.status IS NULL";
$res = $db->query($sql);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $vid = $row['vendor_id'];
        $sync_status = $row['verification_status'] ?: 'pending';
        // Set both to the verification status or 'pending' if missing
        $db->query("UPDATE vendors SET status='" . $db->real_escape_string($sync_status) . "' WHERE user_id=$vid");
        $db->query("UPDATE verifications SET status='" . $db->real_escape_string($sync_status) . "' WHERE vendor_id=$vid");
        $results['vendor_sync'][] = "Synced vendor $vid to status '$sync_status'";
    }
} else {
    $results['vendor_sync'][] = "No vendor status inconsistencies found.";
}

// 2. Add payment_method column if missing
$colCheck = $db->query("SHOW COLUMNS FROM payments LIKE 'payment_method'");
if ($colCheck && $colCheck->num_rows == 0) {
    $alter = $db->query("ALTER TABLE payments ADD COLUMN payment_method VARCHAR(32) NOT NULL DEFAULT 'mpesa'");
    if ($alter) {
        $results['payment_method'] = "Added 'payment_method' column to payments table.";
    } else {
        $results['payment_method'] = "Failed to add payment_method column: " . $db->error;
    }
} else {
    $results['payment_method'] = "'payment_method' column already exists.";
}

// 3. Fix empty order status
$orderFix = $db->query("UPDATE orders SET status='pending' WHERE status IS NULL OR status = ''");
if ($orderFix) {
    $affected = $db->affected_rows;
    $results['order_status'] = "Updated $affected orders with empty status to 'pending'.";
} else {
    $results['order_status'] = "Failed to update order statuses: " . $db->error;
}

// Output results
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller System QA Fix Results</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">Seller System QA Fix Results</div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item"><b>Vendor Status Sync:</b><br><?php foreach($results['vendor_sync'] as $msg) echo htmlspecialchars($msg) . '<br>'; ?></li>
                <li class="list-group-item"><b>Payments Table:</b> <?php echo htmlspecialchars($results['payment_method']); ?></li>
                <li class="list-group-item"><b>Order Status Fix:</b> <?php echo htmlspecialchars($results['order_status']); ?></li>
            </ul>
        </div>
    </div>
    <div class="mt-3 text-muted">You may now delete this file for security.</div>
</div>
</body>
</html>
