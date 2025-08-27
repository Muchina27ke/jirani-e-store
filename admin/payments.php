<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}

$pageTitle = 'Payments & Escrow Management';
$currentPage = 'payments';

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$method_filter = $_GET['method'] ?? '';
$escrow_filter = $_GET['escrow'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($method_filter) {
    $where_conditions[] = "p.method = ?";
    $params[] = $method_filter;
    $param_types .= 's';
}

if ($escrow_filter !== '') {
    $where_conditions[] = "p.is_escrow = ?";
    $params[] = $escrow_filter;
    $param_types .= 'i';
}

if ($date_from) {
    $where_conditions[] = "DATE(p.created_at) >= ?";
    $params[] = $date_from;
    $param_types .= 's';
}

if ($date_to) {
    $where_conditions[] = "DATE(p.created_at) <= ?";
    $params[] = $date_to;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get payments with filters
$payments = [];
$query = "
    SELECT p.*, o.id as order_id, o.status as order_status,
           u.name as customer_name, v.business_name as vendor_name,
           ep.escrow_status
    FROM payments p
    LEFT JOIN orders o ON p.order_id = o.id
    LEFT JOIN users u ON o.customer_id = u.id
    LEFT JOIN vendors v ON o.vendor_id = v.user_id
    LEFT JOIN escrow_payments ep ON p.order_id = ep.order_id
    $where_clause
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}

// Get statistics
$stats = [
    'total_payments' => 0,
    'completed_payments' => 0,
    'pending_payments' => 0,
    'escrow_amount' => 0
];

$stats_query = "
    SELECT 
        COUNT(*) as total_payments,
        SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
        SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
        COALESCE(SUM(CASE WHEN p.is_escrow = 1 AND p.status = 'pending' THEN p.amount ELSE 0 END), 0) as escrow_amount
    FROM payments p
    $where_clause
";

$stats_stmt = $conn->prepare($stats_query);
if (!empty($params)) {
    $stats_stmt->bind_param($param_types, ...$params);
}
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result()->fetch_assoc();
$stats = $stats_result;

ob_start();
?>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($stats['total_payments']); ?></h3>
                        <p>Total Payments</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($stats['completed_payments']); ?></h3>
                        <p>Completed Payments</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($stats['pending_payments']); ?></h3>
                        <p>Pending Payments</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>KSh <?php echo number_format($stats['escrow_amount'], 2); ?></h3>
                        <p>In Escrow</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filters & Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <label for="status">Status</label>
                                <select class="form-control" id="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>
                                        Pending</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>
                                        Failed</option>
                                    <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="method">Method</label>
                                <select class="form-control" id="method">
                                    <option value="">All Methods</option>
                                    <option value="M-Pesa" <?php echo $method_filter === 'M-Pesa' ? 'selected' : ''; ?>>
                                        M-Pesa</option>
                                    <option value="Card" <?php echo $method_filter === 'Card' ? 'selected' : ''; ?>>Card
                                    </option>
                                    <option value="Bank Transfer" <?php echo $method_filter === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="escrow">Escrow</label>
                                <select class="form-control" id="escrow">
                                    <option value="">All</option>
                                    <option value="1" <?php echo $escrow_filter === '1' ? 'selected' : ''; ?>>Escrow Only
                                    </option>
                                    <option value="0" <?php echo $escrow_filter === '0' ? 'selected' : ''; ?>>Direct Only
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from">From Date</label>
                                <input type="date" class="form-control" id="date_from"
                                    value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to">To Date</label>
                                <input type="date" class="form-control" id="date_to"
                                    value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <div class="btn-group w-100">
                                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payments List</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-info" onclick="exportPayments()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="paymentsTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Vendor</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Escrow</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><strong>#<?php echo $payment['id']; ?></strong></td>
                                            <td><a href="#" class="text-primary fw-bold"
                                                    onclick="viewOrder(<?php echo $payment['order_id']; ?>)">#<?php echo $payment['order_id']; ?></a>
                                            </td>
                                            <td><?php echo htmlspecialchars($payment['customer_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($payment['vendor_name'] ?? 'N/A'); ?></td>
                                            <td>KSh <?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><span
                                                    class="badge badge-info"><?php echo htmlspecialchars($payment['method']); ?></span>
                                            </td>
                                            <td><span
                                                    class="badge badge-<?php echo $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'pending' ? 'warning' : ($payment['status'] === 'failed' ? 'danger' : 'secondary')); ?> text-uppercase"><?php echo ucfirst($payment['status']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($payment['is_escrow']): ?>
                                                    <span
                                                        class="badge badge-warning"><?php echo htmlspecialchars(isset($payment['escrow_status']) ? $payment['escrow_status'] : 'Held'); ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Direct</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y H:i', strtotime($payment['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info" title="View"
                                                        onclick="viewPayment(<?php echo $payment['id']; ?>)"><i
                                                            class="fas fa-eye"></i></button>
                                                    <?php if ($payment['is_escrow'] && $payment['status'] === 'pending'): ?>
                                                        <button type="button" class="btn btn-success" title="Release Escrow"
                                                            onclick="releaseEscrow(<?php echo $payment['order_id']; ?>)"><i
                                                                class="fas fa-unlock"></i></button>
                                                        <button type="button" class="btn btn-danger" title="Refund"
                                                            onclick="refundPayment(<?php echo $payment['id']; ?>)"><i
                                                                class="fas fa-undo"></i></button>
                                                    <?php endif; ?>
                                                </div>
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
    </div>
</section>


<!-- Payment Details Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="paymentModalBody">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>
<!-- Enhanced JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let dataTable;

    $(document).ready(function () {
        initializeDataTable();
        initializeEventListeners();
    });

    function initializeDataTable() {
        dataTable = $('#paymentsTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [[8, "desc"]], // Sort by date
            "pageLength": 25,
            "language": {
                "search": "Search payments:",
                "lengthMenu": "Show _MENU_ payments per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ payments"
            },
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });
    }

    function initializeEventListeners() {
        // Real-time filter updates
        $('#status, #method, #escrow').on('change', function () {
            applyFilters();
        });

        // Date range filters
        $('#date_from, #date_to').on('change', function () {
            applyFilters();
        });
    }

    function applyFilters() {
        const filters = {
            status: $('#status').val(),
            method: $('#method').val(),
            escrow: $('#escrow').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        };

        // Build query string
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });

        // Redirect with filters
        window.location.href = 'payments.php?' + params.toString();
    }

    function clearFilters() {
        window.location.href = 'payments.php';
    }

    function viewPayment(paymentId) {
        $.ajax({
            url: 'api/payment_actions.php?action=details&id=' + paymentId,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    $('#paymentModalBody').html(response.data.html);
                    $('#paymentModal').modal('show');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Failed to load payment details', 'error');
            }
        });
    }

    function viewOrder(orderId) {
        window.location.href = 'orders.php?order_id=' + orderId;
    }

    function releaseEscrow(orderId) {
        Swal.fire({
            title: 'Release Escrow?',
            text: "This will release the payment to the vendor.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, release!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/payment_actions.php?action=release_escrow',
                    method: 'POST',
                    data: JSON.stringify({ order_id: orderId }),
                    contentType: 'application/json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Released!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to release escrow', 'error');
                    }
                });
            }
        });
    }

    function refundPayment(paymentId) {
        Swal.fire({
            title: 'Refund Payment?',
            text: "This will refund the payment to the customer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, refund!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/payment_actions.php?action=refund',
                    method: 'POST',
                    data: JSON.stringify({ payment_id: paymentId }),
                    contentType: 'application/json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Refunded!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to refund payment', 'error');
                    }
                });
            }
        });
    }

    function exportPayments() {
        const filters = {
            status: $('#status').val(),
            method: $('#method').val(),
            escrow: $('#escrow').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        };

        // Build query string
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });

        window.open('api/payment_actions.php?action=export&' + params.toString(), '_blank');
    }
</script>