<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}
$pageTitle = 'Sales Analytics';
$currentPage = 'analytics_sales';

// Get date range from query parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')); // Last 30 days
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Check if we have data in the selected range
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$orders_in_range = $stmt->get_result()->fetch_assoc()['count'];

// If no data in selected range, use all available data
if ($orders_in_range == 0) {
    $stmt = $conn->prepare("SELECT MIN(created_at) as min_date, MAX(created_at) as max_date FROM orders");
    $stmt->execute();
    $date_range = $stmt->get_result()->fetch_assoc();

    if ($date_range['min_date'] && $date_range['max_date']) {
        $start_date = $date_range['min_date'];
        $end_date = $date_range['max_date'];
    }
}

// Sales Overview Data
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        SUM(oi.quantity * oi.price) as total_revenue,
        AVG(oi.quantity * oi.price) as avg_order_value,
        COUNT(DISTINCT o.customer_id) as unique_customers
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.created_at BETWEEN ? AND ?
    AND o.status != 'cancelled'
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$overview = $stmt->get_result()->fetch_assoc();

// Daily Sales Data for Chart
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

// Top Products
$stmt = $conn->prepare("
    SELECT 
        p.name as product_name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN ? AND ?
    AND o.status != 'cancelled'
    GROUP BY p.id, p.name
    ORDER BY total_revenue DESC
    LIMIT 10
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Top Vendors
$stmt = $conn->prepare("
    SELECT 
        v.business_name,
        COUNT(DISTINCT o.id) as total_orders,
        SUM(oi.quantity * oi.price) as total_revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN vendors v ON p.vendor_id = v.user_id
    WHERE o.created_at BETWEEN ? AND ?
    AND o.status != 'cancelled'
    GROUP BY v.user_id, v.business_name
    ORDER BY total_revenue DESC
    LIMIT 10
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_vendors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Order Status Distribution
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

// Start output buffering
ob_start();
?>

<!-- Debug Information -->
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info">
            <h5>Debug Info:</h5>
            <p>Date Range: <?php echo $start_date; ?> to <?php echo $end_date; ?></p>
            <p>Daily Sales Data Points: <?php echo count($daily_sales); ?></p>
            <p>Order Status Types: <?php echo count($order_status); ?></p>
            <p>Total Orders: <?php echo $overview['total_orders'] ?? 0; ?></p>
            <p>Total Revenue: KSh <?php echo number_format($overview['total_revenue'] ?? 0, 2); ?></p>
            <?php if (isset($_GET['start_date']) && $orders_in_range == 0): ?>
                <div class="alert alert-warning mt-2">
                    <strong>Note:</strong> No data found in the selected date range. Showing all available data instead.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sales Analytics Dashboard</h3>
                <div class="card-tools">
                    <form class="form-inline d-inline-block mr-2">
                        <div class="form-group mr-2">
                            <label class="mr-2">Date Range:</label>
                            <input type="date" class="form-control form-control-sm" name="start_date"
                                value="<?php echo $start_date; ?>">
                        </div>
                        <div class="form-group mr-2">
                            <label class="mr-2">to:</label>
                            <input type="date" class="form-control form-control-sm" name="end_date"
                                value="<?php echo $end_date; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Generate Report</button>
                    </form>
                    <button class="btn btn-success btn-sm" onclick="printReport()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Header for Print -->
<div class="print-header" style="display: none;">
    <div class="text-center">
        <img src="../title_logo.jpg" alt="Jirani Logo" style="height: 60px; margin-bottom: 10px;">
        <h2>Jirani E-Commerce Platform</h2>
        <h3>Sales Analytics Report</h3>
        <p>Period: <?php echo date('M d, Y', strtotime($start_date)); ?> -
            <?php echo date('M d, Y', strtotime($end_date)); ?>
        </p>
        <p>Generated on: <?php echo date('M d, Y H:i:s'); ?></p>
        <hr>
    </div>
</div>

<!-- Overview Cards -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($overview['total_orders'] ?? 0); ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>KSh <?php echo number_format($overview['total_revenue'] ?? 0, 2); ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>KSh <?php echo number_format($overview['avg_order_value'] ?? 0, 2); ?></h3>
                <p>Average Order Value</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo number_format($overview['unique_customers'] ?? 0); ?></h3>
                <p>Unique Customers</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daily Sales Trend</h3>
            </div>
            <div class="card-body">
                <canvas id="salesChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Order Status Distribution</h3>
            </div>
            <div class="card-body">
                <canvas id="statusChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Performers Row -->
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Products by Revenue</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo number_format($product['total_sold']); ?></td>
                                <td>KSh <?php echo number_format($product['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Vendors by Revenue</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_vendors as $vendor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vendor['business_name']); ?></td>
                                <td><?php echo number_format($vendor['total_orders']); ?></td>
                                <td>KSh <?php echo number_format($vendor['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Print Summary Section -->
<div class="print-summary" style="display: none;">
    <div class="row mt-4">
        <div class="col-12">
            <h4>Executive Summary</h4>
            <p>This report covers the sales performance for Jirani E-Commerce Platform from
                <?php echo date('M d, Y', strtotime($start_date)); ?> to
                <?php echo date('M d, Y', strtotime($end_date)); ?>.
            </p>

            <h5>Key Highlights:</h5>
            <ul>
                <li>Total Orders: <?php echo number_format($overview['total_orders'] ?? 0); ?></li>
                <li>Total Revenue: KSh <?php echo number_format($overview['total_revenue'] ?? 0, 2); ?></li>
                <li>Average Order Value: KSh <?php echo number_format($overview['avg_order_value'] ?? 0, 2); ?></li>
                <li>Unique Customers: <?php echo number_format($overview['unique_customers'] ?? 0); ?></li>
            </ul>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        .print-header,
        .print-header * {
            visibility: visible;
        }

        .print-header {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .card,
        .card * {
            visibility: visible;
        }

        .card {
            position: relative;
            left: 0;
            top: 0;
            width: 100%;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .card-header {
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #ddd;
        }

        .small-box {
            border: 1px solid #ddd;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .print-summary,
        .print-summary * {
            visibility: visible;
        }

        .print-summary {
            position: relative;
            left: 0;
            top: 0;
            width: 100%;
            margin-top: 20px;
        }

        .btn,
        .card-tools {
            display: none !important;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .table th {
            background-color: #f8f9fa;
        }

        canvas {
            max-width: 100%;
            height: auto !important;
        }
    }
</style>

<?php
// Move chart scripts to pageScripts variable
$pageScripts = "
    // Debug information
    console.log('Analytics page loaded');
    console.log('Chart.js available:', typeof Chart);
    console.log('Sales chart canvas:', document.getElementById('salesChart'));
    console.log('Status chart canvas:', document.getElementById('statusChart'));
    
    // Check if we have data
    console.log('Daily sales data:', " . json_encode($daily_sales) . ");
    console.log('Order status data:', " . json_encode($order_status) . ");
    
    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (!salesCtx) {
        console.error('Sales chart canvas not found!');
    } else {
        console.log('Creating sales chart...');
        const salesChart = new Chart(salesCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: " . json_encode(array_column($daily_sales, 'date')) . ",
                datasets: [{
                    label: 'Revenue (KSh)',
                    data: " . json_encode(array_column($daily_sales, 'revenue')) . ",
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Orders',
                    data: " . json_encode(array_column($daily_sales, 'orders')) . ",
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
        console.log('Sales chart created:', salesChart);
    }

    // Status Chart
    const statusCtx = document.getElementById('statusChart');
    if (!statusCtx) {
        console.error('Status chart canvas not found!');
    } else {
        console.log('Creating status chart...');
        const statusChart = new Chart(statusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: " . json_encode(array_column($order_status, 'status')) . ",
                datasets: [{
                    data: " . json_encode(array_column($order_status, 'count')) . ",
                    backgroundColor: [
                        '#28a745', // delivered
                        '#ffc107', // pending
                        '#17a2b8', // processing
                        '#6c757d', // cancelled
                        '#dc3545'  // disputed
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
        console.log('Status chart created:', statusChart);
    }

    // Print function
    function printReport() {
        // Show print elements
        document.querySelector('.print-header').style.display = 'block';
        document.querySelector('.print-summary').style.display = 'block';

        // Wait a moment for elements to render, then print
        setTimeout(() => {
            window.print();

            // Hide print elements after printing
            setTimeout(() => {
                document.querySelector('.print-header').style.display = 'none';
                document.querySelector('.print-summary').style.display = 'none';
            }, 1000);
        }, 500);
    }
";

$content = ob_get_clean();
require_once 'layout.php';
?>