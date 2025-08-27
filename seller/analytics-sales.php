<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/Auth.php';

// Vendor session/role check
if (!isset($_SESSION['user_id']) || !$auth->hasRole('vendor')) {
    header('Location: ../auth.php?action=login');
    exit();
}

$db = getDbConnection();
$vendorId = $_SESSION['user_id'];
$pageTitle = 'Sales Analytics';
$currentPage = 'analytics';

// Get sales data for last 30 days
$salesLabels = [];
$salesData = [];
$topDays = [];
$stmt = $db->prepare("
    SELECT DATE(o.created_at) as date, COALESCE(SUM(oi.price * oi.quantity),0) as total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.vendor_id = ? AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(o.created_at)
    ORDER BY date ASC
");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
$chartMap = [];
while ($row = $result->fetch_assoc()) {
    $chartMap[$row['date']] = $row['total'];
    $topDays[] = $row;
}
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $salesLabels[] = date('M d', strtotime($date));
    $salesData[] = isset($chartMap[$date]) ? (float) $chartMap[$date] : 0;
}
$totalSales = array_sum($salesData);

ob_start();
?>
<div class="container-fluid mt-4">
    <h2 class="mb-4">Sales Analytics</h2>
    <form class="form-inline mb-3">
        <label class="mr-2">Date Range:</label>
        <input type="date" class="form-control mr-2" name="from"
            value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
        <input type="date" class="form-control mr-2" name="to" value="<?php echo date('Y-m-d'); ?>">
        <button class="btn btn-primary" disabled>Filter (Demo)</button>
    </form>
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><strong>Sales (Last 30 Days)</strong></div>
                <div class="card-body">
                    <canvas id="salesChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong>Total Sales</strong></div>
                <div class="card-body">
                    <h3>KSh <?php echo number_format($totalSales, 2); ?></h3>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header"><strong>Top Sales Days</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Sales (KSh)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse(array_slice($topDays, -5)) as $day): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                                    <td><?php echo number_format($day['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($salesLabels); ?>,
            datasets: [{
                label: 'Sales (KSh)',
                data: <?php echo json_encode($salesData); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.5)',
                borderColor: '#28a745',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
<?php
$content = ob_get_clean();
include 'layout.php';