<?php
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'Signin/index.php?redirect=orders.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Fetch orders with payment info
$stmt = $conn->prepare("
    SELECT
        o.id, o.status, o.total_amount, o.created_at, o.updated_at,
        v.business_name AS vendor_name,
        p.status AS payment_status, p.method AS payment_method
    FROM orders o
    JOIN vendors v ON o.vendor_id = v.user_id
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Order status colour map
$statusColors = [
    'pending'   => ['bg' => 'rgba(245,158,11,0.1)','color' => '#d97706','icon' => 'fa-clock'],
    'confirmed' => ['bg' => 'rgba(59,130,246,0.1)', 'color' => '#2563eb','icon' => 'fa-check-circle'],
    'shipped'   => ['bg' => 'rgba(139,92,246,0.1)', 'color' => '#7c3aed','icon' => 'fa-truck'],
    'delivered' => ['bg' => 'rgba(22,163,74,0.1)',  'color' => '#16a34a','icon' => 'fa-box-open'],
    'cancelled' => ['bg' => 'rgba(239,68,68,0.1)',  'color' => '#dc2626','icon' => 'fa-times-circle'],
];

$pageTitle   = 'My Orders';
$currentPage = 'orders';
?>
<?php require_once __DIR__ . '/navbar.php'; ?>

<style>
.orders-page { padding: 40px 0 80px; }

.order-card {
    background: white;
    border-radius: var(--j-radius-lg);
    border: 1px solid var(--j-border-light);
    box-shadow: var(--j-shadow-card);
    margin-bottom: 16px;
    overflow: hidden;
    transition: var(--j-transition);
}

.order-card:hover { box-shadow: var(--j-shadow); transform: translateY(-1px); }

.order-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid var(--j-border-light);
    flex-wrap: wrap;
    gap: 12px;
}

.order-id-group { display:flex;flex-direction:column;gap:2px; }
.order-id-group .order-num { font-weight:800;font-size:1.05rem;color:var(--j-text); }
.order-id-group .order-date { font-size:0.78rem;color:var(--j-text-muted); }

.order-status-badge {
    display: inline-flex;align-items:center;gap:6px;
    padding: 6px 14px;border-radius: 99px;
    font-size: 0.8rem;font-weight: 700;
    text-transform: capitalize;
}

.order-card-body { padding: 20px 24px; }

.order-meta-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.order-meta-item {}
.order-meta-label { font-size:0.72rem;font-weight:600;color:var(--j-text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px; }
.order-meta-value { font-size:0.92rem;font-weight:600;color:var(--j-text); }

.order-actions { display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid var(--j-border-light); }
</style>

<div class="j-page-header">
    <div class="j-container">
        <h1><i class="fas fa-box" style="margin-right:12px;opacity:.8;"></i>My Orders</h1>
        <p style="opacity:.7;margin:8px 0 0;font-size:0.95rem;">Track your purchases and manage returns</p>
    </div>
</div>

<div class="j-container orders-page">

    <!-- Tabs / filter -->
    <div style="display:flex;gap:8px;margin-bottom:24px;overflow-x:auto;padding-bottom:4px;">
        <?php
        $allStatuses = ['all' => 'All Orders'] + array_fill_keys(array_keys($statusColors), '');
        $allStatuses['pending']   = 'Pending';
        $allStatuses['confirmed'] = 'Confirmed';
        $allStatuses['shipped']   = 'Shipped';
        $allStatuses['delivered'] = 'Delivered';
        $allStatuses['cancelled'] = 'Cancelled';

        $filterStatus = $_GET['status'] ?? 'all';
        foreach ($allStatuses as $st => $label):
            $active = $filterStatus === $st;
            $count = $st === 'all' ? count($orders) : count(array_filter($orders, fn($o) => $o['status'] === $st));
        ?>
        <a href="?status=<?php echo $st; ?>"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:99px;font-size:0.83rem;font-weight:600;white-space:nowrap;transition:all .2s;
           <?php echo $active ? 'background:var(--j-primary);color:white;box-shadow:var(--j-shadow-sm);' : 'background:white;color:var(--j-text-muted);border:1px solid var(--j-border-light);'; ?>">
            <?php echo $label; ?>
            <?php if ($count > 0): ?>
            <span style="background:<?php echo $active ? 'rgba(255,255,255,0.2)' : 'var(--j-bg)'; ?>;border-radius:99px;padding:1px 7px;font-size:0.72rem;">
                <?php echo $count; ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php
    $filtered = $filterStatus === 'all' ? $orders : array_filter($orders, fn($o) => $o['status'] === $filterStatus);
    ?>

    <?php if (empty($filtered)): ?>
    <div class="j-empty-state" style="padding:60px 0;">
        <div class="j-empty-icon"><i class="fas fa-box-open"></i></div>
        <h3>No <?php echo $filterStatus === 'all' ? '' : $filterStatus . ' '; ?>orders yet</h3>
        <p>You haven't placed any orders<?php echo $filterStatus !== 'all' ? " with $filterStatus status" : ''; ?>.</p>
        <a href="<?php echo SITE_URL; ?>index.php" class="j-btn j-btn-primary j-btn-lg">
            <i class="fas fa-seedling"></i> Start Shopping
        </a>
    </div>

    <?php else: ?>

    <?php foreach ($filtered as $order):
        $st     = $order['status'] ?? 'pending';
        $sc     = $statusColors[$st] ?? $statusColors['pending'];
        $total  = number_format((float)($order['total_amount'] ?? 0), 2);
        $date   = date('M j, Y', strtotime($order['created_at']));
        $time   = date('g:i A', strtotime($order['created_at']));
        $pmSt   = $order['payment_status'] ?? 'Unknown';
        $pmMeth = $order['payment_method'] ?? 'N/A';
    ?>
    <div class="order-card">
        <div class="order-card-header">
            <div class="order-id-group">
                <span class="order-num">Order #<?php echo $order['id']; ?></span>
                <span class="order-date"><?php echo $date; ?> at <?php echo $time; ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="order-status-badge" style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>;">
                    <i class="fas <?php echo $sc['icon']; ?>"></i>
                    <?php echo ucfirst($st); ?>
                </span>
                <?php if ($pmSt === 'Completed'): ?>
                <span class="order-status-badge" style="background:rgba(22,163,74,0.1);color:#16a34a;">
                    <i class="fas fa-check-circle"></i> Paid
                </span>
                <?php elseif ($pmSt === 'Pending'): ?>
                <span class="order-status-badge" style="background:rgba(245,158,11,0.1);color:#d97706;">
                    <i class="fas fa-clock"></i> Payment Pending
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="order-card-body">
            <div class="order-meta-row">
                <div class="order-meta-item">
                    <div class="order-meta-label">Vendor</div>
                    <div class="order-meta-value">
                        <i class="fas fa-store" style="color:var(--j-primary);margin-right:4px;"></i>
                        <?php echo htmlspecialchars($order['vendor_name'] ?? 'Unknown'); ?>
                    </div>
                </div>
                <div class="order-meta-item">
                    <div class="order-meta-label">Total</div>
                    <div class="order-meta-value" style="color:var(--j-primary);font-size:1.05rem;">KSh <?php echo $total; ?></div>
                </div>
                <div class="order-meta-item">
                    <div class="order-meta-label">Payment</div>
                    <div class="order-meta-value"><?php echo htmlspecialchars($pmMeth); ?></div>
                </div>
                <div class="order-meta-item">
                    <div class="order-meta-label">Order ID</div>
                    <div class="order-meta-value">#<?php echo $order['id']; ?></div>
                </div>
            </div>

            <div class="order-actions">
                <a href="<?php echo SITE_URL; ?>order_details.php?order_id=<?php echo $order['id']; ?>" class="j-btn j-btn-primary j-btn-sm">
                    <i class="fas fa-eye"></i> View Details
                </a>

                <?php if ($st === 'pending' && $pmSt !== 'Completed'): ?>
                <a href="<?php echo SITE_URL; ?>checkout.php?reorder=<?php echo $order['id']; ?>" class="j-btn j-btn-ghost j-btn-sm">
                    <i class="fas fa-credit-card"></i> Pay Now
                </a>
                <?php endif; ?>

                <?php if ($st === 'delivered' && $pmSt === 'Completed'): ?>
                <button class="j-btn j-btn-ghost j-btn-sm" onclick="confirmDelivery(<?php echo $order['id']; ?>)">
                    <i class="fas fa-box-open"></i> Confirm Receipt
                </button>
                <?php endif; ?>

                <?php if (in_array($st, ['delivered', 'confirmed'])): ?>
                <a href="<?php echo SITE_URL; ?>search.php" class="j-btn j-btn-ghost j-btn-sm">
                    <i class="fas fa-redo"></i> Reorder
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>

<script>
async function confirmDelivery(orderId) {
    if (!confirm('Confirm you received this order? This will release payment to the vendor.')) return;
    try {
        const res = await fetch(<?php echo json_encode(SITE_URL); ?> + 'api/orders/confirm-delivery.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId })
        });
        const data = await res.json();
        if (data.success) {
            jToast('Delivery confirmed! Thank you.', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            jToast(data.message || 'Failed to confirm delivery', 'error');
        }
    } catch (e) {
        jToast('Network error', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>