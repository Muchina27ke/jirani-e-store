<?php
require_once dirname(__DIR__) . '/config/config.php';
// Vendor session/role check
if (!isset($_SESSION['user_id']) || !$auth->hasRole('vendor')) {
    header('Location: ../auth.php?action=login');
    exit();
}
$db = getDbConnection();
$vendorId = $_SESSION['user_id'];

$pageTitle = 'Payments';
$currentPage = 'payments';

// Get payment statistics
$stats = [
    'total_revenue' => 0,
    'pending_escrow' => 0,
    'available_balance' => 0
];

// Get total revenue (delivered orders)
$stmt = $db->prepare("
    SELECT COALESCE(SUM(p.amount), 0) as total_revenue
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE o.vendor_id = ? AND o.status = 'delivered' AND p.status IN ('completed', 'paid')
");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['total_revenue'] = $row['total_revenue'];

// Get pending escrow (held escrow payments)
$stmt = $db->prepare("
    SELECT COALESCE(SUM(p.amount), 0) as pending_escrow
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN escrow_payments ep ON p.order_id = ep.order_id
    WHERE o.vendor_id = ? AND p.is_escrow = 1 AND ep.escrow_status = 'held' AND p.status = 'paid'
");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['pending_escrow'] = $row['pending_escrow'];

// Get available balance (released escrow or direct payments)
$stmt = $db->prepare("
    SELECT COALESCE(SUM(p.amount), 0) as available_balance
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    LEFT JOIN escrow_payments ep ON p.order_id = ep.order_id
    WHERE o.vendor_id = ? AND (
        (p.is_escrow = 1 AND ep.escrow_status = 'released_to_vendor' AND p.status = 'paid')
        OR (p.is_escrow = 0 AND p.status = 'completed')
    )
");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['available_balance'] = $row['available_balance'];

// Get recent payments
$stmt = $db->prepare("
    SELECT p.*, o.id as order_id, o.status as order_status, u.name as customer_name, 
           SUM(oi.price * oi.quantity) as order_total
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.customer_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.vendor_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$recentPayments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


ob_start();
// Ensure payouts and stats keys are always defined
if (!isset($recentPayouts)) $recentPayouts = [];
if (!isset($stats['total_payouts'])) $stats['total_payouts'] = 0;
?>

<!-- Statistics -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>KSh <?php echo number_format($stats['total_revenue'], 2); ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>KSh <?php echo number_format($stats['pending_escrow'], 2); ?></h3>
                <p>Pending Escrow</p>
            </div>
            <div class="icon">
                <i class="fas fa-lock"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>KSh <?php echo number_format($stats['available_balance'], 2); ?></h3>
                <p>Available Balance</p>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>KSh <?php echo number_format($stats['total_payouts'], 2); ?></h3>
                <p>Total Payouts</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main row -->
<div class="row">
    <!-- Recent Payments -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Payments</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayments as $payment): ?>
                                <tr>
                                    <td>#<?php echo str_pad($payment['order_id'], 8, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                    <td>KSh <?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo ucfirst($payment['method']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
                                        echo match ($payment['status']) {
                                            'paid' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                        <?php if ($payment['is_escrow']): ?>
                                            <span class="badge badge-info">Escrow</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payouts -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Payouts</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPayouts)): ?>
                                <tr><td colspan="3" class="text-center">No recent payouts found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentPayouts as $payout): ?>
                                    <tr>
                                        <td>KSh <?php echo number_format($payout['amount'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php
                                            echo match ($payout['status']) {
                                                'completed' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>">
                                                <?php echo ucfirst($payout['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($payout['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($stats['available_balance'] > 0): ?>
                <div class="card-footer">
                    <button type="button" class="btn btn-primary btn-block" data-toggle="modal"
                        data-target="#requestPayoutModal">
                        Request Payout
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Request Payout Modal -->
<div class="modal fade" id="requestPayoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/vendor/request_payout.php">
                <div class="modal-header">
                    <h5 class="modal-title">Request Payout</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Available Balance</label>
                        <p class="form-control-static">KSh <?php echo number_format($stats['available_balance'], 2); ?>
                        </p>
                    </div>
                    <div class="form-group">
                        <label for="amount">Payout Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount"
                            max="<?php echo $stats['available_balance']; ?>" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="mpesa_number">M-Pesa Number</label>
                        <input type="tel" class="form-control" id="mpesa_number" name="mpesa_number"
                            pattern="^(?:254|\+254|0)?([7-9]{1}[0-9]{8})$" required>
                        <small class="form-text text-muted">
                            Enter the M-Pesa number where you want to receive the payout.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Request Payout</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Format M-Pesa number input
    document.getElementById('mpesa_number').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.startsWith('0')) {
            value = '254' + value.substring(1);
        } else if (!value.startsWith('254')) {
            value = '254' + value;
        }
        e.target.value = value;
    });

    // Validate payout amount
    document.getElementById('amount').addEventListener('input', function (e) {
        const max = <?php echo $stats['available_balance']; ?>;
        if (parseFloat(e.target.value) > max) {
            e.target.value = max;
        }
    });
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>
<?php include 'footer_simple.php'; ?>