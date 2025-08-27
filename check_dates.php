<?php
require_once 'config/config.php';

echo "<h1>Sample Data Date Check</h1>";

// Check orders table dates
$stmt = $conn->prepare("SELECT MIN(created_at) as min_date, MAX(created_at) as max_date, COUNT(*) as total FROM orders");
$stmt->execute();
$order_dates = $stmt->get_result()->fetch_assoc();

echo "<h2>Orders Table</h2>";
echo "<p>Total orders: " . $order_dates['total'] . "</p>";
echo "<p>Date range: " . $order_dates['min_date'] . " to " . $order_dates['max_date'] . "</p>";

// Check some sample order dates
$stmt = $conn->prepare("SELECT id, created_at, status FROM orders ORDER BY created_at LIMIT 10");
$stmt->execute();
$sample_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h3>Sample Orders:</h3>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Created At</th><th>Status</th></tr>";
foreach ($sample_orders as $order) {
    echo "<tr><td>" . $order['id'] . "</td><td>" . $order['created_at'] . "</td><td>" . $order['status'] . "</td></tr>";
}
echo "</table>";

// Check if there are any orders in the requested date range
$start_date = '2024-07-01';
$end_date = '2025-07-05';

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$orders_in_range = $stmt->get_result()->fetch_assoc()['count'];

echo "<h2>Orders in Date Range ($start_date to $end_date)</h2>";
echo "<p>Orders found: $orders_in_range</p>";

if ($orders_in_range == 0) {
    echo "<p style='color: red;'>❌ No orders found in the requested date range!</p>";
    echo "<p>This is why the charts are not showing data.</p>";

    // Suggest a better date range
    $stmt = $conn->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM orders GROUP BY DATE(created_at) ORDER BY date LIMIT 5");
    $stmt->execute();
    $available_dates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo "<h3>Available Dates in Database:</h3>";
    echo "<ul>";
    foreach ($available_dates as $date) {
        echo "<li>" . $date['date'] . " (" . $date['count'] . " orders)</li>";
    }
    echo "</ul>";

    echo "<h3>Suggested Fix:</h3>";
    echo "<p>Use one of these date ranges instead:</p>";
    if (count($available_dates) > 0) {
        $first_date = $available_dates[0]['date'];
        $last_date = $available_dates[count($available_dates) - 1]['date'];
        echo "<p><a href='admin/analytics-sales.php?start_date=$first_date&end_date=$last_date'>Click here to use available date range</a></p>";
    }
}
?>