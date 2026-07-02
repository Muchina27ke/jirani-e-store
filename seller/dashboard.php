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
<div class="row mb-4 g-4">
  <div class="col-lg-3 col-6">
    <div class="card shadow-sm border-0 h-100" style="border-radius: var(--j-radius-lg); background: linear-gradient(135deg, #17a2b8, #117a8b); color: white;">
      <div class="card-body position-relative overflow-hidden p-4">
        <h3 class="display-5 font-weight-bold mb-1" style="font-family: var(--j-font-heading);"><?php echo $totalProducts; ?></h3>
        <p class="mb-0 font-weight-bold" style="opacity: 0.9; font-size: 1.1rem;">Total Products</p>
        <i class="fas fa-box position-absolute" style="font-size: 4rem; right: -10px; bottom: -10px; opacity: 0.2; transform: rotate(-15deg);"></i>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="card shadow-sm border-0 h-100" style="border-radius: var(--j-radius-lg); background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
      <div class="card-body position-relative overflow-hidden p-4">
        <h3 class="display-5 font-weight-bold mb-1" style="font-family: var(--j-font-heading);"><?php echo $pendingOrders; ?></h3>
        <p class="mb-0 font-weight-bold" style="opacity: 0.9; font-size: 1.1rem;">Pending Orders</p>
        <i class="fas fa-clock position-absolute" style="font-size: 4rem; right: -10px; bottom: -10px; opacity: 0.2; transform: rotate(-15deg);"></i>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="card shadow-sm border-0 h-100" style="border-radius: var(--j-radius-lg); background: linear-gradient(135deg, #10b981, #059669); color: white;">
      <div class="card-body position-relative overflow-hidden p-4">
        <h3 class="display-6 font-weight-bold mb-1" style="font-family: var(--j-font-heading);">KSh <?php echo number_format($totalSales, 0); ?></h3>
        <p class="mb-0 font-weight-bold" style="opacity: 0.9; font-size: 1.1rem;">Total Sales</p>
        <i class="fas fa-coins position-absolute" style="font-size: 4rem; right: -10px; bottom: -10px; opacity: 0.2; transform: rotate(-15deg);"></i>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="card shadow-sm border-0 h-100" style="border-radius: var(--j-radius-lg); background: linear-gradient(135deg, var(--j-primary, #6366f1), #4f46e5); color: white;">
      <div class="card-body position-relative overflow-hidden p-4">
        <h3 class="display-6 font-weight-bold mb-1" style="font-family: var(--j-font-heading);">KSh <?php echo number_format($pendingPayouts, 0); ?></h3>
        <p class="mb-0 font-weight-bold" style="opacity: 0.9; font-size: 1.1rem;">Pending Payouts</p>
        <i class="fas fa-lock position-absolute" style="font-size: 4rem; right: -10px; bottom: -10px; opacity: 0.2; transform: rotate(-15deg);"></i>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-8 mb-4 mb-md-0">
    <div class="card shadow-sm border-0" style="border-radius: var(--j-radius-lg);">
      <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0 h4" style="font-family: var(--j-font-heading); font-weight: 700; color: var(--j-primary);">Recent Orders</h3>
        <a href="orders.php" class="btn btn-sm text-white px-3" style="background: var(--j-primary); border-radius: 50px; font-weight: 600;">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 border-0" style="border-bottom-left-radius: var(--j-radius-lg); border-bottom-right-radius: var(--j-radius-lg); overflow: hidden;">
            <thead style="background-color: rgba(99, 102, 241, 0.05);">
              <tr>
                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Order ID</th>
                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Customer</th>
                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Status</th>
                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Amount</th>
                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($recentOrders)): ?>
                <?php foreach ($recentOrders as $order): ?>
                  <tr>
                    <td class="px-4 py-3 align-middle font-weight-bold" style="color: var(--j-primary);">#<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></td>
                    <td class="px-4 py-3 align-middle"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td class="px-4 py-3 align-middle">
                      <span class="badge" style="padding: 6px 12px; border-radius: 50px; font-weight: 600; <?php 
                        switch(strtolower($order['status'])) {
                          case 'pending': echo 'background: rgba(245, 158, 11, 0.15); color: #d97706;'; break;
                          case 'processing': echo 'background: rgba(14, 165, 233, 0.15); color: #0284c7;'; break;
                          case 'shipped': echo 'background: rgba(99, 102, 241, 0.15); color: #4f46e5;'; break;
                          case 'delivered': echo 'background: rgba(16, 185, 129, 0.15); color: #059669;'; break;
                          case 'cancelled': echo 'background: rgba(239, 68, 68, 0.15); color: #dc2626;'; break;
                          default: echo 'background: #f3f4f6; color: #374151;';
                        }
                      ?>">
                        <?php echo ucfirst($order['status']); ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 align-middle font-weight-bold text-dark">KSh <?php echo number_format(getOrderTotal($db, $order['id']), 2); ?></td>
                    <td class="px-4 py-3 align-middle text-muted"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center px-4 py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                    No recent orders.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm border-0 h-100" style="border-radius: var(--j-radius-lg);">
      <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
        <h3 class="card-title mb-0 h5" style="font-family: var(--j-font-heading); font-weight: 700; color: var(--j-primary);">My Sales (Last 7 Days)</h3>
      </div>
      <div class="card-body" style="position: relative; min-height: 300px;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; padding: 0.5rem 1.5rem 1.5rem 1.5rem;">
          <canvas id="salesChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('salesChart').getContext('2d');
  
  // Premium gradient for the chart
  let gradient = ctx.createLinearGradient(0, 0, 0, 400);
  gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
  gradient.addColorStop(1, 'rgba(16, 185, 129, 0.05)');

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?php echo json_encode($salesChartLabels); ?>,
      datasets: [{
        label: 'Sales (KSh)',
        data: <?php echo json_encode($salesChartData); ?>,
        backgroundColor: gradient,
        borderColor: '#10b981',
        borderWidth: 3,
        pointRadius: 5,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#10b981',
        pointBorderWidth: 2,
        pointHoverRadius: 7,
        fill: true,
        tension: 0.4 // smoother curve
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleFont: { size: 13, family: 'Inter, sans-serif' },
            bodyFont: { size: 14, weight: 'bold', family: 'Inter, sans-serif' },
            padding: 12,
            cornerRadius: 8,
            displayColors: false,
        }
      },
      scales: {
        x: {
            grid: { display: false, drawBorder: false },
            ticks: { font: { family: 'Inter, sans-serif' }, color: '#6b7280' }
        },
        y: { 
            beginAtZero: true,
            grid: { color: 'rgba(243, 244, 246, 1)', drawBorder: false },
            ticks: { font: { family: 'Inter, sans-serif' }, color: '#6b7280', maxTicksLimit: 5 }
        }
      }
    }
  });
</script>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>