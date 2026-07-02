<?php
require_once 'auth_guard.php';
require_once '../config/config.php';

$pageTitle  = 'Orders';
$activePage = 'orders';

$pdo = getPDO();

// ── Order detail view ──────────────────────────────────────────────────────
$viewOrder = null;
$orderItems = [];
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['view']]);
    $viewOrder = $stmt->fetch();

    if ($viewOrder) {
        $iStmt = $pdo->prepare("SELECT oi.*, p.slug FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :oid");
        $iStmt->execute([':oid' => $viewOrder['id']]);
        $orderItems = $iStmt->fetchAll();
    }
}

// ── Filters ───────────────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['q']      ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 25;
$offset       = ($page - 1) * $perPage;

$sql    = "SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id WHERE 1=1";
$params = [];
if ($filterStatus) {
    $sql .= " AND o.status = :status";
    $params[':status'] = $filterStatus;
}
if ($filterSearch) {
    $sql .= " AND (o.full_name LIKE :q OR o.phone LIKE :q OR o.id LIKE :q OR o.mpesa_ref LIKE :q)";
    $params[':q'] = '%' . $filterSearch . '%';
}
$sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset";

$countSql  = "SELECT COUNT(*) FROM orders o WHERE 1=1";
if ($filterStatus) $countSql .= " AND o.status = :status";
if ($filterSearch) $countSql .= " AND (o.full_name LIKE :q OR o.phone LIKE :q OR o.id LIKE :q OR o.mpesa_ref LIKE :q)";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalOrders = (int)$countStmt->fetchColumn();
$totalPages  = max(1, ceil($totalOrders / $perPage));

$stmt   = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statuses = ['pending','paid','processing','shipped','delivered','cancelled'];

include 'layout.php';
?>

<?php if ($viewOrder): ?>
<!-- ============================================================
     ORDER DETAIL VIEW
     ============================================================ -->
<div style="margin-bottom:16px;">
  <a href="orders.php" class="btn btn-outline" style="padding:8px 16px;font-size:0.82rem;">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    Back to Orders
  </a>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">
      Order #<?= str_pad($viewOrder['id'], 6, '0', STR_PAD_LEFT) ?>
      <span class="status-badge status-<?= $viewOrder['status'] ?>" style="margin-left:8px;"><?= ucfirst($viewOrder['status']) ?></span>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
      <label style="font-size:0.8rem;color:var(--muted);">Update Status:</label>
      <select class="admin-filter-select status-update-select"
              data-order-id="<?= $viewOrder['id'] ?>"
              style="padding:7px 28px 7px 10px;">
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $viewOrder['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div style="padding:20px;">
    <div class="order-detail-grid">
      <!-- Customer info -->
      <div class="order-detail-box">
        <h4>Customer Details</h4>
        <div class="order-detail-row"><span class="key">Name</span><span class="val"><?= htmlspecialchars($viewOrder['full_name']) ?></span></div>
        <div class="order-detail-row"><span class="key">Phone</span><span class="val"><?= htmlspecialchars($viewOrder['phone']) ?></span></div>
        <?php if ($viewOrder['email']): ?>
        <div class="order-detail-row"><span class="key">Email</span><span class="val"><?= htmlspecialchars($viewOrder['email']) ?></span></div>
        <?php endif; ?>
      </div>

      <!-- Delivery info -->
      <div class="order-detail-box">
        <h4>Delivery Address</h4>
        <div class="order-detail-row"><span class="key">Address</span><span class="val"><?= htmlspecialchars($viewOrder['delivery_address']) ?></span></div>
        <div class="order-detail-row"><span class="key">Town</span><span class="val"><?= htmlspecialchars($viewOrder['town'] ?? '—') ?></span></div>
        <div class="order-detail-row"><span class="key">County</span><span class="val"><?= htmlspecialchars($viewOrder['county'] ?? '—') ?></span></div>
      </div>

      <!-- Payment info -->
      <div class="order-detail-box">
        <h4>Payment</h4>
        <div class="order-detail-row"><span class="key">Method</span><span class="val">M-Pesa</span></div>
        <div class="order-detail-row"><span class="key">Receipt</span><span class="val" style="color:#4ade80;"><?= htmlspecialchars($viewOrder['mpesa_ref'] ?? 'Pending') ?></span></div>
        <div class="order-detail-row"><span class="key">Status</span><span class="val"><span class="status-badge status-<?= $viewOrder['status'] ?>"><?= ucfirst($viewOrder['status']) ?></span></span></div>
      </div>

      <!-- Order meta -->
      <div class="order-detail-box">
        <h4>Order Info</h4>
        <div class="order-detail-row"><span class="key">Order #</span><span class="val"><?= str_pad($viewOrder['id'], 6, '0', STR_PAD_LEFT) ?></span></div>
        <div class="order-detail-row"><span class="key">Date</span><span class="val"><?= date('d M Y, H:i', strtotime($viewOrder['created_at'])) ?></span></div>
        <div class="order-detail-row"><span class="key">Items</span><span class="val"><?= count($orderItems) ?></span></div>
      </div>
    </div>

    <!-- Items table -->
    <div class="admin-card" style="margin-top:0;border:1px solid var(--border);">
      <div class="admin-card-header">
        <div class="admin-card-title">Items Ordered</div>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>Product</th><th>Brand</th><th>Unit Price</th><th>Qty</th><th>Subtotal</th></tr>
          </thead>
          <tbody>
            <?php foreach ($orderItems as $item): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                <?php if ($item['slug']): ?>
                  <a href="../pages/product.php?slug=<?= urlencode($item['slug']) ?>" target="_blank"
                     style="font-size:0.72rem;color:var(--orange);margin-left:6px;">↗</a>
                <?php endif; ?>
              </td>
              <td style="color:var(--muted);"><?= htmlspecialchars($item['brand'] ?? '—') ?></td>
              <td>KES <?= number_format($item['unit_price'], 0) ?></td>
              <td><?= (int)$item['qty'] ?></td>
              <td><strong>KES <?= number_format($item['unit_price'] * $item['qty'], 0) ?></strong></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="border-top:2px solid var(--border);">
              <td colspan="4" style="text-align:right;color:var(--muted);font-size:0.82rem;padding:12px 16px;">Subtotal</td>
              <td style="padding:12px 16px;"><strong>KES <?= number_format($viewOrder['subtotal'], 0) ?></strong></td>
            </tr>
            <tr>
              <td colspan="4" style="text-align:right;color:var(--muted);font-size:0.82rem;padding:4px 16px;">Delivery Fee</td>
              <td style="padding:4px 16px;">KES <?= number_format($viewOrder['delivery_fee'], 0) ?></td>
            </tr>
            <tr>
              <td colspan="4" style="text-align:right;font-weight:700;padding:8px 16px;">Total Paid</td>
              <td style="padding:8px 16px;color:var(--orange);font-size:1.1rem;font-weight:800;">KES <?= number_format($viewOrder['total_amount'], 0) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ============================================================
     ORDERS LIST
     ============================================================ -->
<div class="admin-card" style="margin-bottom:20px;">
  <div class="admin-card-header">
    <div class="admin-toolbar" style="flex:1;">
      <form method="GET" action="orders.php" style="display:contents;">
        <div class="admin-search">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" name="q" placeholder="Search by name, phone, receipt…" value="<?= htmlspecialchars($filterSearch) ?>">
        </div>
        <select name="status" class="admin-filter-select" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline" style="padding:8px 16px;font-size:0.82rem;">Filter</button>
      </form>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      All Orders
    </div>
    <span style="font-size:0.8rem;color:var(--muted);"><?= $totalOrders ?> total</span>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table" id="ordersTable">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Phone</th>
          <th>Items</th>
          <th>Total</th>
          <th>M-Pesa Receipt</th>
          <th>Status</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:40px;">No orders found</td></tr>
        <?php else: ?>
        <?php foreach ($orders as $order): ?>
        <tr>
          <td><strong>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
          <td><?= htmlspecialchars($order['full_name']) ?></td>
          <td style="color:var(--muted);"><?= htmlspecialchars($order['phone']) ?></td>
          <td><?= (int)$order['item_count'] ?></td>
          <td><strong>KES <?= number_format($order['total_amount'], 0) ?></strong></td>
          <td style="font-size:0.8rem;color:<?= $order['mpesa_ref'] ? '#4ade80' : 'var(--muted)' ?>;">
            <?= htmlspecialchars($order['mpesa_ref'] ?? '—') ?>
          </td>
          <td>
            <select class="admin-filter-select status-update-select"
                    data-order-id="<?= $order['id'] ?>"
                    style="padding:5px 24px 5px 8px;font-size:0.78rem;border-radius:6px;">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td style="color:var(--muted);font-size:0.8rem;white-space:nowrap;">
            <?= date('d M Y', strtotime($order['created_at'])) ?><br>
            <span style="font-size:0.72rem;"><?= date('H:i', strtotime($order['created_at'])) ?></span>
          </td>
          <td>
            <a href="orders.php?view=<?= $order['id'] ?>" class="action-btn view" title="View details">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="admin-pagination">
    <span>Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalOrders) ?> of <?= $totalOrders ?></span>
    <div class="pagination-btns">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&q=<?= urlencode($filterSearch) ?>&status=<?= urlencode($filterStatus) ?>"
           class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php include 'layout_end.php'; ?>
