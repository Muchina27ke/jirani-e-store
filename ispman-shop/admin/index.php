<?php
require_once 'auth_guard.php';
require_once '../config/config.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$pdo = getPDO();

// ── Stats ──────────────────────────────────────────────────────────────────
$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCustomers= $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();

$revenueToday  = $pdo->query("
    SELECT COALESCE(SUM(total_amount),0) FROM orders
    WHERE status IN ('paid','processing','shipped','delivered')
    AND DATE(created_at) = CURDATE()
")->fetchColumn();

$revenueMonth  = $pdo->query("
    SELECT COALESCE(SUM(total_amount),0) FROM orders
    WHERE status IN ('paid','processing','shipped','delivered')
    AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
")->fetchColumn();

// ── Recent orders ──────────────────────────────────────────────────────────
$recentOrders = $pdo->query("
    SELECT o.id, o.full_name, o.phone, o.total_amount, o.status, o.created_at,
           COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetchAll();

// ── Low stock ──────────────────────────────────────────────────────────────
$lowStock = $pdo->query("
    SELECT p.id, p.name, p.brand, p.stock_qty, c.name as category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.stock_qty < 5
    ORDER BY p.stock_qty ASC
    LIMIT 8
")->fetchAll();

include 'layout.php';
?>

<!-- ── Stat cards ── -->
<div class="stat-cards">
  <div class="stat-card">
    <div class="stat-icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalOrders) ?></div>
      <div class="stat-label">Total Orders</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-value" style="font-size:1.2rem;">KES <?= number_format($revenueToday, 0) ?></div>
      <div class="stat-label">Revenue Today</div>
      <div class="stat-change up">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" width="10" height="10"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
        KES <?= number_format($revenueMonth, 0) ?> this month
      </div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalProducts) ?></div>
      <div class="stat-label">Total Products</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalCustomers) ?></div>
      <div class="stat-label">Customers</div>
    </div>
  </div>
</div>

<!-- ── Recent Orders ── -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Recent Orders
    </div>
    <a href="orders.php" class="btn btn-outline" style="padding:7px 16px;font-size:0.8rem;">View All</a>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table" id="recentOrdersTable">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Phone</th>
          <th>Items</th>
          <th>Total</th>
          <th>Status</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentOrders)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:32px;">No orders yet</td></tr>
        <?php else: ?>
        <?php foreach ($recentOrders as $order): ?>
        <tr>
          <td><strong>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
          <td><?= htmlspecialchars($order['full_name']) ?></td>
          <td style="color:var(--muted);"><?= htmlspecialchars($order['phone']) ?></td>
          <td><?= (int)$order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?></td>
          <td><strong>KES <?= number_format($order['total_amount'], 0) ?></strong></td>
          <td><span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
          <td style="color:var(--muted);font-size:0.8rem;"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></td>
          <td>
            <a href="orders.php?view=<?= $order['id'] ?>" class="action-btn view" title="View order">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Low Stock Alert ── -->
<?php if (!empty($lowStock)): ?>
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title" style="color:#facc15;">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#facc15;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      Low Stock Alert
      <span style="background:rgba(234,179,8,0.15);border:1px solid rgba(234,179,8,0.3);color:#facc15;font-size:0.7rem;padding:2px 8px;border-radius:10px;font-weight:700;"><?= count($lowStock) ?> products</span>
    </div>
    <a href="products.php" class="btn btn-outline" style="padding:7px 16px;font-size:0.8rem;">Manage Products</a>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>Product</th><th>Brand</th><th>Category</th><th>Stock</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($lowStock as $p): ?>
        <tr class="low-stock-row">
          <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
          <td><?= htmlspecialchars($p['brand']) ?></td>
          <td><?= htmlspecialchars($p['category_name']) ?></td>
          <td><span class="<?= $p['stock_qty'] == 0 ? 'stock-zero' : 'stock-low' ?>"><?= (int)$p['stock_qty'] ?> left</span></td>
          <td>
            <a href="products.php?edit=<?= $p['id'] ?>" class="action-btn edit" title="Edit product">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include 'layout_end.php'; ?>
