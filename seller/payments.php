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

<div class="row mb-4 g-4">
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm border-0 h-100" style="border-radius: var(--j-radius-lg); background: linear-gradient(135deg, #10b981, #059669); color: white;">
            <div class="card-body position-relative overflow-hidden p-4">
                <h3 class="display-6 font-weight-bold mb-1" style="font-family: var(--j-font-heading);">KSh <?php echo number_format($stats['total_revenue'], 0); ?></h3>
                <p class="mb-0 font-weight-bold" style="opacity: 0.9; font-size: 1.1rem;">Total Revenue</p>
                <i class="fas fa-money-bill position-absolute" style="font-size: 4rem; right: -10px; bottom: -10px; opacity: 0.2; transform: rotate(-15deg);"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm border-0 h-100" style="border-radius: var(--j-radius-lg); background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
            <div class="card-body position-relative overflow-hidden p-4">
                <h3 class="display-6 font-weight-bold mb-1" style="font-family: var(--j-font-heading);">KSh <?php echo number_format($stats['pending_escrow'], 0); ?></h3>
                <p class="mb-0 font-weight-bold" style="opacity: 0.9; font-size: 1.1rem;">Pending Escrow</p>
                <i class="fas fa-lock position-absolute" style="font-size: 4rem; right: -10px; bottom: -10px; opacity: 0.2; transform: rotate(-15deg);"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm border-0 h-100" style="border-radius: var(--j-radius-lg); background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
            <div class="card-body position-relative overflow-hidden p-4">
                <h3 class="display-6 font-weight-bold mb-1" style="font-family: var(--j-font-heading);">KSh <?php echo number_format($stats['available_balance'], 0); ?></h3>
                <p class="mb-0 font-weight-bold" style="opacity: 0.9; font-size: 1.1rem;">Available Balance</p>
                <i class="fas fa-wallet position-absolute" style="font-size: 4rem; right: -10px; bottom: -10px; opacity: 0.2; transform: rotate(-15deg);"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm border-0 h-100" style="border-radius: var(--j-radius-lg); background: linear-gradient(135deg, var(--j-primary, #6366f1), #4f46e5); color: white;">
            <div class="card-body position-relative overflow-hidden p-4">
                <h3 class="display-6 font-weight-bold mb-1" style="font-family: var(--j-font-heading);">KSh <?php echo number_format($stats['total_payouts'], 0); ?></h3>
                <p class="mb-0 font-weight-bold" style="opacity: 0.9; font-size: 1.1rem;">Total Payouts</p>
                <i class="fas fa-hand-holding-usd position-absolute" style="font-size: 4rem; right: -10px; bottom: -10px; opacity: 0.2; transform: rotate(-15deg);"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main row -->
<div class="row">
    <!-- Recent Payments -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0" style="border-radius: var(--j-radius-lg);">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                <h3 class="card-title mb-0 h4" style="font-family: var(--j-font-heading); font-weight: 700; color: var(--j-primary);">Recent Payments</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 border-0" style="border-bottom-left-radius: var(--j-radius-lg); border-bottom-right-radius: var(--j-radius-lg); overflow: hidden;">
                        <thead style="background-color: rgba(99, 102, 241, 0.05);">
                            <tr>
                                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Order ID</th>
                                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Customer</th>
                                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Amount</th>
                                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Method</th>
                                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Status</th>
                                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPayments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center px-4 py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                        No recent payments found.
                                    </td>
                                </tr>
                            <?php else: ?>
                            <?php foreach ($recentPayments as $payment): ?>
                                <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                                    <td class="px-4 py-3 align-middle font-weight-bold" style="color: var(--j-primary);">#<?php echo str_pad($payment['order_id'], 8, '0', STR_PAD_LEFT); ?></td>
                                    <td class="px-4 py-3 align-middle"><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                    <td class="px-4 py-3 align-middle font-weight-bold">KSh <?php echo number_format($payment['amount'], 2); ?></td>
                                    <td class="px-4 py-3 align-middle"><?php echo ucfirst($payment['method']); ?></td>
                                    <td class="px-4 py-3 align-middle">
                                        <span class="badge" style="padding: 6px 12px; border-radius: 50px; font-weight: 600; <?php
                                        echo match (strtolower($payment['status'])) {
                                            'paid', 'completed' => 'background: rgba(16, 185, 129, 0.15); color: #059669;',
                                            'pending' => 'background: rgba(245, 158, 11, 0.15); color: #d97706;',
                                            'failed' => 'background: rgba(239, 68, 68, 0.15); color: #dc2626;',
                                            default => 'background: #f3f4f6; color: #374151;'
                                        };
                                        ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                        <?php if ($payment['is_escrow']): ?>
                                            <span class="badge" style="padding: 6px 12px; border-radius: 50px; font-weight: 600; background: rgba(14, 165, 233, 0.15); color: #0284c7; margin-left: 4px;">Escrow</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 align-middle text-muted"><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payouts -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100" style="border-radius: var(--j-radius-lg);">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                <h3 class="card-title mb-0 h5" style="font-family: var(--j-font-heading); font-weight: 700; color: var(--j-primary);">Recent Payouts</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 border-0" style="border-bottom-left-radius: var(--j-radius-lg); border-bottom-right-radius: var(--j-radius-lg); overflow: hidden;">
                        <thead style="background-color: rgba(99, 102, 241, 0.05);">
                            <tr>
                                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Amount</th>
                                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Status</th>
                                <th class="border-0 px-4 py-3" style="font-weight: 600; color: #4b5563;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPayouts)): ?>
                                <tr>
                                    <td colspan="3" class="text-center px-4 py-5 text-muted">
                                        <i class="fas fa-receipt fa-2x mb-3 d-block" style="opacity: 0.3;"></i>
                                        No recent payouts.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPayouts as $payout): ?>
                                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                                        <td class="px-4 py-3 align-middle font-weight-bold">KSh <?php echo number_format($payout['amount'], 2); ?></td>
                                        <td class="px-4 py-3 align-middle">
                                            <span class="badge" style="padding: 6px 12px; border-radius: 50px; font-weight: 600; <?php
                                            echo match (strtolower($payout['status'])) {
                                                'completed' => 'background: rgba(16, 185, 129, 0.15); color: #059669;',
                                                'pending' => 'background: rgba(245, 158, 11, 0.15); color: #d97706;',
                                                'failed' => 'background: rgba(239, 68, 68, 0.15); color: #dc2626;',
                                                default => 'background: #f3f4f6; color: #374151;'
                                            };
                                            ?>">
                                                <?php echo ucfirst($payout['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 align-middle text-muted"><?php echo date('M d, Y', strtotime($payout['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($stats['available_balance'] > 0): ?>
                <div class="card-footer bg-white border-0 pb-4 px-4 text-center" style="border-bottom-left-radius: var(--j-radius-lg); border-bottom-right-radius: var(--j-radius-lg);">
                    <button type="button" class="btn btn-primary btn-block py-2" data-toggle="modal"
                        data-target="#requestPayoutModal" style="background: var(--j-primary); border-radius: 50px; font-weight: 600; border: none;">
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