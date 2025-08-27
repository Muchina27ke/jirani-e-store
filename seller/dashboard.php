<?php
require_once dirname(__DIR__) . '/config/config.php';
ob_start();
// Vendor session/role check
if (!isset($_SESSION['user_id']) || !$auth->hasRole('vendor')) {
  header('Location: ../auth.php?action=login');
  exit();
}

$vendorId = $_SESSION['user_id'];
$db = getDbConnection();
$vendorObj = new Vendor($db);
$productObj = new Product($db);
$orderObj = new Order($db);

// Get vendor details and verification status
$vendorDetails = $vendorObj->get_vendor_by_user_id($vendorId);
$isVendorVerified = ($vendorDetails['status'] ?? '') === 'approved';

// Dashboard stats
// Total Products
$totalProducts = 0;
$stmt = $db->prepare("SELECT COUNT(*) as total_products FROM products WHERE vendor_id = ?");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
  $totalProducts = $row['total_products'];
}
// Pending Orders
$pendingOrders = 0;
$stmt = $db->prepare("SELECT COUNT(*) as pending_orders FROM orders WHERE vendor_id = ? AND status = 'pending'");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
  $pendingOrders = $row['pending_orders'];
}
// Total Sales (delivered orders)
$totalSales = 0;
$stmt = $db->prepare("
    SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total_sales
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.vendor_id = ? AND o.status = 'delivered'
");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
  $totalSales = $row['total_sales'];
}
// Pending Payouts (escrow held)
$pendingPayouts = 0;
$stmt = $db->prepare("
    SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as pending_payouts
    FROM orders o
    JOIN payments p ON o.id = p.order_id
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.vendor_id = ? AND o.status IN ('processing', 'shipped') AND p.is_escrow = 1 AND p.status = 'Paid'
");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
  $pendingPayouts = $row['pending_payouts'];
}
// Recent Orders (last 5)
$recentOrders = [];
$stmt = $db->prepare("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.vendor_id = ? ORDER BY o.created_at DESC LIMIT 5");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $recentOrders[] = $row;
}
// Sales Chart Data (last 7 days)
$salesChartLabels = [];
$salesChartData = [];
$stmt = $db->prepare("
    SELECT DATE(o.created_at) as date, COALESCE(SUM(oi.price * oi.quantity),0) as total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.vendor_id = ? AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(o.created_at)
    ORDER BY date ASC
");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
$chartMap = [];
while ($row = $result->fetch_assoc()) {
  $chartMap[$row['date']] = $row['total'];
}
for ($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $salesChartLabels[] = date('D', strtotime($date));
  $salesChartData[] = isset($chartMap[$date]) ? (float) $chartMap[$date] : 0;
}

function getOrderTotal($db, $orderId)
{
  $stmt = $db->prepare("SELECT SUM(price * quantity) as total FROM order_items WHERE order_id = ?");
  $stmt->bind_param("i", $orderId);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  return $row['total'] ?? 0;
}
?>
<!-- Move all main dashboard HTML content here (from <div class="wrapper"> ... to end of main content) -->
<?php
ob_start();
?>
<div class="row mb-4">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-info">
      <div class="inner">
        <h3><?php echo $totalProducts; ?></h3>
        <p>Total Products</p>
      </div>
      <div class="icon"><i class="fas fa-box"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-warning">
      <div class="inner">
        <h3><?php echo $pendingOrders; ?></h3>
        <p>Pending Orders</p>
      </div>
      <div class="icon"><i class="fas fa-clock"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-success">
      <div class="inner">
        <h3>KSh <?php echo number_format($totalSales, 2); ?></h3>
        <p>Total Sales</p>
      </div>
      <div class="icon"><i class="fas fa-coins"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-primary">
      <div class="inner">
        <h3>KSh <?php echo number_format($pendingPayouts, 2); ?></h3>
        <p>Pending Payouts</p>
      </div>
      <div class="icon"><i class="fas fa-lock"></i></div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-8 mb-4 mb-md-0">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Recent Orders</h3>
        <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($recentOrders)): ?>
                <?php foreach ($recentOrders as $order): ?>
                  <tr>
                    <td>#<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td>
                      <span class="badge badge-status badge-<?php echo strtolower($order['status']); ?>">
                        <?php echo ucfirst($order['status']); ?>
                      </span>
                    </td>
                    <td>KSh <?php echo number_format(getOrderTotal($db, $order['id']), 2); ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center">No recent orders.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">
        <h3 class="card-title mb-0">My Sales (Last 7 Days)</h3>
      </div>
      <div class="card-body">
        <canvas id="salesChart" height="180"></canvas>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('salesChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?php echo json_encode($salesChartLabels); ?>,
      datasets: [{
        label: 'Sales (KSh)',
        data: <?php echo json_encode($salesChartData); ?>,
        backgroundColor: 'rgba(40, 167, 69, 0.2)',
        borderColor: '#28a745',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: '#28a745',
        fill: true,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
</script>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>