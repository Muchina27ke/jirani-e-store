<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
  header('Location: ../auth.php?action=login');
  exit;
}

// --- DATA FETCHING LOGIC MOVED TO TOP ---
$stats = [
  'total_orders' => 0,
  'total_revenue' => 0,
  'pending_verifications' => 0,
  'active_vendors' => 0,
  'escrow_amount' => 0,
  'total_products' => 0,
  'pending_orders' => 0,
  'total_customers' => 0
];
// Total orders and revenue (last 30 days)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(p.amount), 0) as total_revenue
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id AND p.status = 'completed'
    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats['total_orders'] = $result['total_orders'];
$stats['total_revenue'] = $result['total_revenue'];
// Pending verifications
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM verifications
    WHERE status = 'pending'");
$stmt->execute();
$stats['pending_verifications'] = $stmt->get_result()->fetch_assoc()['count'];
// Active vendors
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM vendors
    WHERE status = 'approved'");
$stmt->execute();
$stats['active_vendors'] = $stmt->get_result()->fetch_assoc()['count'];
// Total products
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM products p
    JOIN vendors v ON p.vendor_id = v.user_id
    WHERE p.status = 'active' AND p.stock > 0 AND v.status = 'approved'");
$stmt->execute();
$stats['total_products'] = $stmt->get_result()->fetch_assoc()['count'];
// Pending orders
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM orders
    WHERE status = 'pending'");
$stmt->execute();
$stats['pending_orders'] = $stmt->get_result()->fetch_assoc()['count'];
// Total customers
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM users
    WHERE role = 'customer'");
$stmt->execute();
$stats['total_customers'] = $stmt->get_result()->fetch_assoc()['count'];
// Funds in Escrow
require_once dirname(__DIR__) . '/includes/Order.php';
$orderObj = new Order($conn);
$stats['escrow_amount'] = $orderObj->get_total_escrow_amount();
// Recent orders (with total_amount)
$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name, v.business_name as vendor_name
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    JOIN vendors v ON o.vendor_id = v.user_id
    ORDER BY o.created_at DESC
    LIMIT 5");
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($recent_orders as &$order) {
  $stmt2 = $conn->prepare("SELECT SUM(quantity * price) as total_amount FROM order_items WHERE order_id = ?");
  $stmt2->bind_param("i", $order['id']);
  $stmt2->execute();
  $order['total_amount'] = $stmt2->get_result()->fetch_assoc()['total_amount'] ?? 0;
  $stmt2->close();
}
unset($order);
// Recent verifications
$stmt = $conn->prepare("
    SELECT v.*, vend.business_name
    FROM verifications v
    JOIN vendors vend ON v.vendor_id = vend.user_id
    ORDER BY v.created_at DESC
    LIMIT 5");
$stmt->execute();
$recent_verifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// Recent products (latest 5, in stock, active, vendor approved)
$stmt = $conn->prepare("
    SELECT p.*, v.business_name as vendor_name
    FROM products p
    JOIN vendors v ON p.vendor_id = v.user_id
    WHERE p.status = 'active' AND p.stock > 0 AND v.status = 'approved'
    ORDER BY p.created_at DESC
    LIMIT 5");
$stmt->execute();
$recent_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// Recent vendors (latest 5)
$stmt = $conn->prepare("
    SELECT * FROM vendors
    ORDER BY created_at DESC
    LIMIT 5");
$stmt->execute();
$recent_vendors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// Sales trends (last 7 days)
$sales_trends = [];
$stmt = $conn->prepare("
    SELECT DATE(o.created_at) as day, COUNT(o.id) as orders, COALESCE(SUM(p.amount), 0) as revenue
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id AND p.status = 'completed'
    WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY day
    ORDER BY day ASC");
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$days = [];
for ($i = 6; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-$i days"));
  $days[$d] = ['day' => $d, 'orders' => 0, 'revenue' => 0];
}
foreach ($res as $row) {
  $days[$row['day']] = $row;
}
$sales_trends = array_values($days);
// --- END DATA FETCHING LOGIC ---

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
ob_start();
?>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <!-- Info boxes -->
    <div class="row">
      <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
          <div class="inner">
            <h3><?php echo number_format($stats['total_orders']); ?></h3>
            <p>Total Orders (30 days)</p>
          </div>
          <div class="icon">
            <i class="fas fa-shopping-cart"></i>
          </div>
          <a href="orders.php" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
          <div class="inner">
            <h3><?php echo number_format($stats['active_vendors']); ?></h3>
            <p>Active Vendors</p>
          </div>
          <div class="icon">
            <i class="fas fa-store"></i>
          </div>
          <a href="vendors.php" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
          <div class="inner">
            <h3><?php echo number_format($stats['pending_verifications']); ?></h3>
            <p>Pending Verifications</p>
          </div>
          <div class="icon">
            <i class="fas fa-clipboard-check"></i>
          </div>
          <a href="verifications.php" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
          <div class="inner">
            <h3>KSh <?php echo number_format($stats['escrow_amount'], 2); ?></h3>
            <p>Funds in Escrow</p>
          </div>
          <div class="icon">
            <i class="fas fa-lock"></i>
          </div>
          <a href="payments.php" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Additional Statistics -->
    <div class="row">
      <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
          <div class="inner">
            <h3><?php echo number_format($stats['total_products']); ?></h3>
            <p>Active Products</p>
          </div>
          <div class="icon">
            <i class="fas fa-box"></i>
          </div>
          <a href="products.php" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
          <div class="inner">
            <h3><?php echo number_format($stats['total_customers']); ?></h3>
            <p>Total Customers</p>
          </div>
          <div class="icon">
            <i class="fas fa-users"></i>
          </div>
          <a href="manage_users.php" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
          <div class="inner">
            <h3><?php echo number_format($stats['pending_orders']); ?></h3>
            <p>Pending Orders</p>
          </div>
          <div class="icon">
            <i class="fas fa-clock"></i>
          </div>
          <a href="orders.php?status=pending" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
          <div class="inner">
            <h3>KSh <?php echo number_format($stats['total_revenue'], 2); ?></h3>
            <p>Total Revenue (30 days)</p>
          </div>
          <div class="icon">
            <i class="fas fa-money-bill-wave"></i>
          </div>
          <a href="analytics-sales.php" class="small-box-footer">
            More info <i class="fas fa-arrow-circle-right"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Sales Chart -->
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header border-0">
            <div class="d-flex justify-content-between">
              <h3 class="card-title">
                <i class="fas fa-chart-line mr-1"></i>
                Sales Analytics
              </h3>
              <a href="analytics-sales.php" class="btn btn-sm btn-outline-secondary">View Details</a>
            </div>
          </div>
          <div class="card-body">
            <div class="position-relative mb-4">
              <canvas id="sales-chart" height="200"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header border-0">
            <h3 class="card-title">
              <i class="fas fa-tasks mr-1"></i>
              Quick Actions
            </h3>
          </div>
          <div class="card-body">
            <div class="d-grid gap-2">
              <a href="add_product.php" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>Add Product
              </a>
              <a href="add_vendor.php" class="btn btn-success">
                <i class="fas fa-user-plus mr-2"></i>Add Vendor
              </a>
              <a href="verifications.php" class="btn btn-warning">
                <i class="fas fa-clipboard-check mr-2"></i>Review Verifications
              </a>
              <a href="orders.php?status=pending" class="btn btn-info">
                <i class="fas fa-clock mr-2"></i>Process Orders
              </a>
              <a href="payments.php" class="btn btn-danger">
                <i class="fas fa-lock mr-2"></i>Manage Escrow
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header border-0">
            <h3 class="card-title">
              <i class="fas fa-shopping-cart mr-1"></i>
              Recent Orders
            </h3>
            <div class="card-tools">
              <a href="orders.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table m-0">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_orders as $order): ?>
                  <tr>
                    <td>
                      <a href="orders.php?order_id=<?php echo $order['id']; ?>">
                        #<?php echo $order['id']; ?>
                      </a>
                    </td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td>KSh <?php echo number_format($order['total_amount'], 2); ?></td>
                    <td>
                      <span class="badge badge-<?php
                      echo $order['status'] === 'completed' ? 'success' :
                        ($order['status'] === 'pending' ? 'warning' : 'info');
                      ?>">
                        <?php echo ucfirst($order['status']); ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header border-0">
            <h3 class="card-title">
              <i class="fas fa-clipboard-check mr-1"></i>
              Recent Verifications
            </h3>
            <div class="card-tools">
              <a href="verifications.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table m-0">
              <thead>
                <tr>
                  <th>Vendor</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_verifications as $verification): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($verification['business_name']); ?></td>
                    <td>
                      <span class="badge badge-<?php
                      echo $verification['status'] === 'approved' ? 'success' :
                        ($verification['status'] === 'pending' ? 'warning' : 'danger');
                      ?>">
                        <?php echo ucfirst($verification['status']); ?>
                      </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($verification['created_at'])); ?></td>
                    <td>
                      <a href="verifications.php?id=<?php echo $verification['id']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Products and Vendors -->
    <div class="row">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header border-0">
            <h3 class="card-title">
              <i class="fas fa-box mr-1"></i>
              Recent Products
            </h3>
            <div class="card-tools">
              <a href="products.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table m-0">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Vendor</th>
                  <th>Price</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_products as $product): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['vendor_name']); ?></td>
                    <td>KSh <?php echo number_format($product['price'], 2); ?></td>
                    <td>
                      <span class="badge badge-<?php
                      echo $product['status'] === 'active' ? 'success' : 'secondary';
                      ?>">
                        <?php echo ucfirst($product['status']); ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header border-0">
            <h3 class="card-title">
              <i class="fas fa-store mr-1"></i>
              Recent Vendors
            </h3>
            <div class="card-tools">
              <a href="vendors.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table m-0">
              <thead>
                <tr>
                  <th>Business</th>
                  <th>Status</th>
                  <th>Joined</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_vendors as $vendor): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($vendor['business_name']); ?></td>
                    <td>
                      <span class="badge badge-<?php
                      echo $vendor['status'] === 'approved' ? 'success' :
                        ($vendor['status'] === 'pending' ? 'warning' : 'danger');
                      ?>">
                        <?php echo ucfirst($vendor['status']); ?>
                      </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($vendor['created_at'])); ?></td>
                    <td>
                      <a href="vendors.php?vendor_id=<?php echo $vendor['user_id']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
require_once 'layout.php';

// Chart.js
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  $(document).ready(function () {
    // Sales Chart
    var salesChart = new Chart(document.getElementById('sales-chart'), {
      type: 'line',
      data: {
        labels: <?php echo json_encode(array_column($sales_trends, 'day')); ?>,
        datasets: [{
          label: 'Orders',
          data: <?php echo json_encode(array_column($sales_trends, 'orders')); ?>,
          borderColor: 'rgb(75, 192, 192)',
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          tension: 0.1
        }, {
          label: 'Revenue (KSh)',
          data: <?php echo json_encode(array_column($sales_trends, 'revenue')); ?>,
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
              text: 'Orders'
            }
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            title: {
              display: true,
              text: 'Revenue (KSh)'
            },
            grid: {
              drawOnChartArea: false,
            },
          }
        }
      }
    });
  });
</script>