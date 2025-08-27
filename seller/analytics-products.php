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
$pageTitle = 'Product Analytics';
$currentPage = 'analytics';

// Get top-selling products
$topProducts = [];
$stmt = $db->prepare("
    SELECT p.id, p.name, SUM(oi.quantity) as units_sold, SUM(oi.price * oi.quantity) as revenue, p.stock
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    WHERE p.vendor_id = ?
    GROUP BY p.id, p.name, p.stock
    ORDER BY revenue DESC
    LIMIT 10
");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $topProducts[] = $row;
}

// Prepare chart data for top 5 products
$chartLabels = array_map(function ($p) {
    return $p['name']; }, array_slice($topProducts, 0, 5));
$chartData = array_map(function ($p) {
    return (float) $p['revenue']; }, array_slice($topProducts, 0, 5));

// Low stock alerts
$lowStock = array_filter($topProducts, function ($p) {
    return $p['stock'] !== null && $p['stock'] <= 5; });

ob_start();
?>
<div class="container-fluid mt-4">
    <h2 class="mb-4">Product Analytics</h2>
    <form class="form-inline mb-3">
        <label class="mr-2">Category:</label>
        <select class="form-control mr-2" name="category" disabled>
            <option value="">All Categories (Demo)</option>
        </select>
        <button class="btn btn-primary" disabled>Filter (Demo)</button>
    </form>
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><strong>Top 5 Products by Revenue</strong></div>
                <div class="card-body">
                    <canvas id="productsChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong>Low Stock Alerts</strong></div>
                <div class="card-body p-0">
                    <?php if (count($lowStock) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($lowStock as $p): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                    <span class="badge badge-danger badge-pill">Stock: <?php echo $p['stock']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-muted">No low stock products.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><strong>Top-Selling Products</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue (KSh)</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['name']); ?></td>
                                <td><?php echo $p['units_sold'] ?? 0; ?></td>
                                <td>KSh <?php echo number_format($p['revenue'] ?? 0, 2); ?></td>
                                <td><?php echo $p['stock'] ?? '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('productsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Revenue (KSh)',
                data: <?php echo json_encode($chartData); ?>,
                backgroundColor: 'rgba(255, 167, 38, 0.7)',
                borderColor: '#FFA726',
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