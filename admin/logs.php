<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}
$pageTitle = 'System Logs';
$currentPage = 'logs';
ob_start();

$db = getDbConnection();

function fetchLogs($db, $table, $limit = 100) {
    $query = "SELECT * FROM $table ORDER BY created_at DESC LIMIT ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

$logs = fetchLogs($db, 'logs', 50);
$orderLogs = fetchLogs($db, 'order_logs', 50);
$productLogs = fetchLogs($db, 'product_logs', 50);
$systemLogs = fetchLogs($db, 'system_logs', 50);
$inventoryLogs = fetchLogs($db, 'inventory_logs', 50);
$verificationLogs = fetchLogs($db, 'vendor_verification_logs', 50);
?>
<!-- Main content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>System Logs</h1>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <script src="/assets/js/admin-logs.js"></script>
            <ul class="nav nav-tabs" id="logTabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" id="logs-tab" data-toggle="tab" href="#logs" role="tab">System Logs</a></li>
    <li class="nav-item"><a class="nav-link" id="order-tab" data-toggle="tab" href="#order" role="tab">Order Logs</a></li>
    <li class="nav-item"><a class="nav-link" id="product-tab" data-toggle="tab" href="#product" role="tab">Product Logs</a></li>
    <li class="nav-item"><a class="nav-link" id="system-tab" data-toggle="tab" href="#system" role="tab">App Events</a></li>
    <li class="nav-item"><a class="nav-link" id="inventory-tab" data-toggle="tab" href="#inventory" role="tab">Inventory Logs</a></li>
    <li class="nav-item"><a class="nav-link" id="verification-tab" data-toggle="tab" href="#verification" role="tab">Verification Logs</a></li>
</ul>
            <div class="tab-content mt-3" id="logTabsContent">
    <div class="tab-pane fade show active" id="logs" role="tabpanel">
        <input type="text" class="form-control log-search mb-2" placeholder="Search System Logs..." data-tab="logs">
        <?php if ($logs): ?>
        <div class="table-responsive"><table class="table table-striped table-bordered table-sm log-table" id="logs-table"><thead><tr><th>ID</th><th>User</th><th>Action</th><th>Details</th><th>IP</th><th>Created</th></tr></thead><tbody>
        <?php foreach ($logs as $log): ?>
            <tr><td><?= $log['id'] ?></td><td><?= $log['user_id'] ?></td><td><?= $log['action'] ?></td><td><pre class="log-details-short" style="max-height:60px;overflow:auto;"><?= htmlspecialchars($log['details']) ?></pre></td><td><?= $log['ip_address'] ?></td><td><?= $log['created_at'] ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <?php else: ?><p>No system logs found.</p><?php endif; ?>
    </div>
    <div class="tab-pane fade" id="order" role="tabpanel">
        <input type="text" class="form-control log-search mb-2" placeholder="Search Order Logs..." data-tab="order">
        <?php if ($orderLogs): ?>
        <div class="table-responsive"><table class="table table-striped table-bordered table-sm log-table" id="order-table"><thead><tr><th>ID</th><th>Order ID</th><th>Action</th><th>Details</th><th>Admin</th><th>Created</th></tr></thead><tbody>
        <?php foreach ($orderLogs as $log): ?>
            <tr><td><?= $log['id'] ?></td><td><?= $log['order_id'] ?></td><td><?= $log['action'] ?></td><td><pre class="log-details-short" style="max-height:60px;overflow:auto;"><?= htmlspecialchars($log['details']) ?></pre></td><td><?= $log['admin_id'] ?></td><td><?= $log['created_at'] ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <?php else: ?><p>No order logs found.</p><?php endif; ?>
    </div>
    <div class="tab-pane fade" id="product" role="tabpanel">
        <input type="text" class="form-control log-search mb-2" placeholder="Search Product Logs..." data-tab="product">
        <?php if ($productLogs): ?>
        <div class="table-responsive"><table class="table table-striped table-bordered table-sm log-table" id="product-table"><thead><tr><th>ID</th><th>Product ID</th><th>Action</th><th>Details</th><th>Admin</th><th>Created</th></tr></thead><tbody>
        <?php foreach ($productLogs as $log): ?>
            <tr><td><?= $log['id'] ?></td><td><?= $log['product_id'] ?></td><td><?= $log['action'] ?></td><td><pre class="log-details-short" style="max-height:60px;overflow:auto;"><?= htmlspecialchars($log['details']) ?></pre></td><td><?= $log['admin_id'] ?></td><td><?= $log['created_at'] ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <?php else: ?><p>No product logs found.</p><?php endif; ?>
    </div>
    <div class="tab-pane fade" id="system" role="tabpanel">
        <input type="text" class="form-control log-search mb-2" placeholder="Search App Events..." data-tab="system">
        <?php if ($systemLogs): ?>
        <div class="table-responsive"><table class="table table-striped table-bordered table-sm log-table" id="system-table"><thead><tr><th>ID</th><th>User</th><th>Level</th><th>Message</th><th>Context</th><th>IP</th><th>User Agent</th><th>Created</th></tr></thead><tbody>
        <?php foreach ($systemLogs as $log): ?>
            <tr><td><?= $log['id'] ?></td><td><?= $log['user_id'] ?></td><td><?= $log['level'] ?></td><td><?= $log['message'] ?></td><td><pre class="log-details-short" style="max-height:60px;overflow:auto;"><?= htmlspecialchars($log['context']) ?></pre></td><td><?= $log['ip_address'] ?></td><td><?= $log['user_agent'] ?></td><td><?= $log['created_at'] ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <?php else: ?><p>No app events found.</p><?php endif; ?>
    </div>
    <div class="tab-pane fade" id="inventory" role="tabpanel">
        <input type="text" class="form-control log-search mb-2" placeholder="Search Inventory Logs..." data-tab="inventory">
        <?php if ($inventoryLogs): ?>
        <div class="table-responsive"><table class="table table-striped table-bordered table-sm log-table" id="inventory-table"><thead><tr><th>ID</th><th>Product ID</th><th>Old Stock</th><th>New Stock</th><th>Reason</th><th>Admin</th><th>Created</th></tr></thead><tbody>
        <?php foreach ($inventoryLogs as $log): ?>
            <tr><td><?= $log['id'] ?></td><td><?= $log['product_id'] ?></td><td><?= $log['old_stock'] ?></td><td><?= $log['new_stock'] ?></td><td><?= $log['change_reason'] ?></td><td><?= $log['admin_id'] ?></td><td><?= $log['created_at'] ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <?php else: ?><p>No inventory logs found.</p><?php endif; ?>
    </div>
    <div class="tab-pane fade" id="verification" role="tabpanel">
        <input type="text" class="form-control log-search mb-2" placeholder="Search Verification Logs..." data-tab="verification">
        <?php if ($verificationLogs): ?>
        <div class="table-responsive"><table class="table table-striped table-bordered table-sm log-table" id="verification-table"><thead><tr><th>ID</th><th>Vendor</th><th>Action</th><th>Details</th><th>Admin</th><th>Created</th></tr></thead><tbody>
        <?php foreach ($verificationLogs as $log): ?>
            <tr><td><?= $log['id'] ?></td><td><?= $log['vendor_id'] ?></td><td><?= $log['action'] ?></td><td><pre class="log-details-short" style="max-height:60px;overflow:auto;"><?= htmlspecialchars($log['details']) ?></pre></td><td><?= $log['admin_id'] ?></td><td><?= $log['created_at'] ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <?php else: ?><p>No verification logs found.</p><?php endif; ?>
    </div>
</div>
        </div>
    </section>
</div>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>
