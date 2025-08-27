<?php
require_once 'config/config.php';

// Get date range from query parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

echo "<h1>Analytics Debug</h1>";
echo "<p>Date Range: $start_date to $end_date</p>";

// Test database connection
if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Database connected</p>";
}

// Test daily sales query
$stmt = $conn->prepare("
    SELECT 
        DATE(o.created_at) as date,
        COUNT(DISTINCT o.id) as orders,
        SUM(oi.quantity * oi.price) as revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.created_at BETWEEN ? AND ?
    AND o.status != 'cancelled'
    GROUP BY DATE(o.created_at)
    ORDER BY date
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$daily_sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h2>Daily Sales Data</h2>";
echo "<p>Number of data points: " . count($daily_sales) . "</p>";
if (count($daily_sales) > 0) {
    echo "<p>Sample data:</p>";
    echo "<pre>" . json_encode($daily_sales, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ No sales data found for this date range</p>";
}

// Test order status query
$stmt = $conn->prepare("
    SELECT 
        o.status,
        COUNT(*) as count
    FROM orders o
    WHERE o.created_at BETWEEN ? AND ?
    GROUP BY o.status
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$order_status = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "<h2>Order Status Data</h2>";
echo "<p>Number of status types: " . count($order_status) . "</p>";
if (count($order_status) > 0) {
    echo "<p>Status data:</p>";
    echo "<pre>" . json_encode($order_status, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ No order status data found for this date range</p>";
}

// Check if we have any orders at all
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders");
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['count'];
echo "<h2>Total Orders in Database</h2>";
echo "<p>Total orders: $total_orders</p>";

// Check order dates
$stmt = $conn->prepare("SELECT MIN(created_at) as min_date, MAX(created_at) as max_date FROM orders");
$stmt->execute();
$date_range = $stmt->get_result()->fetch_assoc();
echo "<h2>Order Date Range</h2>";
echo "<p>Earliest order: " . ($date_range['min_date'] ?? 'None') . "</p>";
echo "<p>Latest order: " . ($date_range['max_date'] ?? 'None') . "</p>";

// Test Chart.js data generation
echo "<h2>Chart.js Data Generation</h2>";
$labels = array_column($daily_sales, 'date');
$revenue_data = array_column($daily_sales, 'revenue');
$orders_data = array_column($daily_sales, 'orders');

echo "<p>Labels JSON:</p>";
echo "<pre>" . json_encode($labels) . "</pre>";

echo "<p>Revenue Data JSON:</p>";
echo "<pre>" . json_encode($revenue_data) . "</pre>";

echo "<p>Orders Data JSON:</p>";
echo "<pre>" . json_encode($orders_data) . "</pre>";

// Test status chart data
$status_labels = array_column($order_status, 'status');
$status_counts = array_column($order_status, 'count');

echo "<p>Status Labels JSON:</p>";
echo "<pre>" . json_encode($status_labels) . "</pre>";

echo "<p>Status Counts JSON:</p>";
echo "<pre>" . json_encode($status_counts) . "</pre>";
?>

<!DOCTYPE html>
<html>

<head>
    <title>Analytics Debug</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <h2>Test Charts</h2>

    <div style="width: 800px; height: 400px;">
        <canvas id="testChart"></canvas>
    </div>

    <script>
        console.log('Chart.js loaded:', typeof Chart);

        const ctx = document.getElementById('testChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Revenue (KSh)',
                    data: <?php echo json_encode($revenue_data); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Orders',
                    data: <?php echo json_encode($orders_data); ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (KSh)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                },
            },
        });

        console.log('Chart created:', chart);
    </script>
</body>

</html>