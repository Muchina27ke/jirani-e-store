<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}
$pageTitle = 'Orders';
$currentPage = 'orders';
ob_start();
?>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <!-- Existing orders content here -->
    <?php
    // Get filter parameters
    $status_filter = $_GET['status'] ?? '';
    $vendor_filter = $_GET['vendor_id'] ?? '';
    $customer_filter = $_GET['customer_id'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';

    // Build query with filters
    $where_conditions = [];
    $params = [];
    $param_types = '';

    if ($status_filter) {
        $where_conditions[] = "o.status = ?";
        $params[] = $status_filter;
        $param_types .= 's';
    }

    if ($vendor_filter) {
        $where_conditions[] = "o.vendor_id = ?";
        $params[] = $vendor_filter;
        $param_types .= 'i';
    }

    if ($customer_filter) {
        $where_conditions[] = "o.customer_id = ?";
        $params[] = $customer_filter;
        $param_types .= 'i';
    }

    if ($date_from) {
        $where_conditions[] = "DATE(o.created_at) >= ?";
        $params[] = $date_from;
        $param_types .= 's';
    }

    if ($date_to) {
        $where_conditions[] = "DATE(o.created_at) <= ?";
        $params[] = $date_to;
        $param_types .= 's';
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get orders with filters
    $orders = [];
    $query = "
        SELECT o.*, 
               u.name as customer_name, u.phone as customer_phone,
               v.business_name as vendor_name,
               (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count,
               (SELECT COALESCE(SUM(oi.quantity * oi.price), 0) FROM order_items oi WHERE oi.order_id = o.id) as total_amount,
               p.status as payment_status, p.method as payment_method
        FROM orders o
        LEFT JOIN users u ON o.customer_id = u.id
        LEFT JOIN vendors v ON o.vendor_id = v.user_id
        LEFT JOIN payments p ON o.id = p.order_id
        $where_clause
        ORDER BY o.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    // Get statistics
    $stats = [
        'total_orders' => 0,
        'pending_orders' => 0,
        'completed_orders' => 0,
        'total_revenue' => 0
    ];

    $stats_query = "
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        $where_clause
    ";

    $stats_stmt = $conn->prepare($stats_query);
    if (!empty($params)) {
        $stats_stmt->bind_param($param_types, ...$params);
    }
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result()->fetch_assoc();
    $stats = $stats_result;

    // Get vendors for filter
    $vendors_stmt = $conn->prepare("SELECT user_id, business_name FROM vendors ORDER BY business_name");
    $vendors_stmt->execute();
    $vendors = $vendors_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get customers for filter
    $customers_stmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'customer' ORDER BY name");
    $customers_stmt->execute();
    $customers = $customers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    ?>


        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($stats['total_orders']); ?></h3>
                        <p>Total Orders</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($stats['pending_orders']); ?></h3>
                        <p>Pending Orders</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($stats['completed_orders']); ?></h3>
                        <p>Completed Orders</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>KSh <?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
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
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="vendor_id">Vendor</label>
                                <select class="form-control" id="vendor_id">
                                    <option value="">All Vendors</option>
                                    <?php foreach ($vendors as $vendor): ?>
                                        <option value="<?php echo $vendor['user_id']; ?>" <?php echo $vendor_filter == $vendor['user_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vendor['business_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="customer_id">Customer</label>
                                <select class="form-control" id="customer_id">
                                    <option value="">All Customers</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>" <?php echo $customer_filter == $customer['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
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

        <!-- Orders Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Orders List</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-info" onclick="exportOrders()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="ordersTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Vendor</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo $order['id']; ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                            <br>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['vendor_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $order['item_count']; ?>
                                                items</span>
                                        </td>
                                        <td>KSh <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php
                                            echo $order['status'] === 'pending' ? 'warning' :
                                                ($order['status'] === 'processing' ? 'info' :
                                                    ($order['status'] === 'shipped' ? 'primary' :
                                                        ($order['status'] === 'delivered' ? 'success' : 'danger')));
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                            echo $order['payment_status'] === 'completed' ? 'success' :
                                                ($order['payment_status'] === 'pending' ? 'warning' : 'secondary');
                                            ?>">
                                                <?php echo ucfirst($order['payment_status'] ?? 'N/A'); ?>
                                            </span>
                                            <br>
                                            <small
                                                class="text-muted"><?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info"
                                                    onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($order['payment_status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        onclick="releaseEscrow(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-unlock"></i>
                                                    </button>
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

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="orderModalBody">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="statusForm">
                        <input type="hidden" id="statusOrderId">
                        <div class="form-group">
                            <label for="newStatus">New Status</label>
                            <select class="form-control" id="newStatus" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmStatusUpdate()">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let dataTable;

        $(document).ready(function () {
            initializeDataTable();
            initializeEventListeners();
        });

        function initializeDataTable() {
            dataTable = $('#ordersTable').DataTable({
                "responsive": true,
                "autoWidth": false,
                "order": [[7, "desc"]], // Sort by date
                "pageLength": 25,
                "language": {
                    "search": "Search orders:",
                    "lengthMenu": "Show _MENU_ orders per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ orders"
                },
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });
        }

        function initializeEventListeners() {
            // Real-time filter updates
            $('#status, #vendor_id, #customer_id').on('change', function () {
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
                vendor_id: $('#vendor_id').val(),
                customer_id: $('#customer_id').val(),
                date_from: $('#date_from').val(),
                date_to: $('#date_to').val()
            };

            // Build query string
            const params = new URLSearchParams();
            Object.keys(filters).forEach(key => {
                if (filters[key]) params.append(key, filters[key]);
            });

            // Redirect with filters
            window.location.href = 'orders.php?' + params.toString();
        }

        function clearFilters() {
            window.location.href = 'orders.php';
        }

        function viewOrder(orderId) {
            $.ajax({
                url: 'api/order_actions.php?action=details&id=' + orderId,
                method: 'GET',
                success: function (response) {
                    if (response.success) {
                        $('#orderModalBody').html(response.data.html);
                        $('#orderModal').modal('show');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to load order details', 'error');
                }
            });
        }

        function updateOrderStatus(orderId, currentStatus) {
            $('#statusOrderId').val(orderId);
            $('#newStatus').val(currentStatus);
            $('#statusModal').modal('show');
        }

        function confirmStatusUpdate() {
            const orderId = $('#statusOrderId').val();
            const newStatus = $('#newStatus').val();

            $.ajax({
                url: 'api/order_actions.php?action=update_status',
                method: 'POST',
                data: JSON.stringify({
                    order_id: orderId,
                    status: newStatus
                }),
                contentType: 'application/json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Success', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to update order status', 'error');
                }
            });

            $('#statusModal').modal('hide');
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
                        url: 'api/order_actions.php?action=release_escrow',
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

        function exportOrders() {
            const filters = {
                status: $('#status').val(),
                vendor_id: $('#vendor_id').val(),
                customer_id: $('#customer_id').val(),
                date_from: $('#date_from').val(),
                date_to: $('#date_to').val()
            };

            // Build query string
            const params = new URLSearchParams();
            Object.keys(filters).forEach(key => {
                if (filters[key]) params.append(key, filters[key]);
            });

            window.open('api/order_actions.php?action=export&' + params.toString(), '_blank');
        }
    </script>
    <?php
    $content = ob_get_clean();
    require_once 'layout.php';
?>