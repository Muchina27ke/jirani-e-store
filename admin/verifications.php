<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/VendorVerification.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}
$pageTitle = 'Verifications';
$currentPage = 'verifications';
ob_start();
?>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <?php
        // Initialize VendorVerification class
        $conn = getDbConnection();
        $verificationObj = new VendorVerification($conn);
        
        // Get filter parameters
        $status_filter = $_GET['status'] ?? '';
        $vendor_filter = $_GET['vendor_id'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';

        // Build query with filters - using direct SQL that matches actual schema
        $where_conditions = [];
        $params = [];
        $param_types = '';

        if ($status_filter) {
            $where_conditions[] = "v.status = ?";
            $params[] = $status_filter;
            $param_types .= 's';
        }

        if ($vendor_filter) {
            $where_conditions[] = "v.vendor_id = ?";
            $params[] = $vendor_filter;
            $param_types .= 'i';
        }

        if ($date_from) {
            $where_conditions[] = "DATE(v.created_at) >= ?";
            $params[] = $date_from;
            $param_types .= 's';
        }

        if ($date_to) {
            $where_conditions[] = "DATE(v.created_at) <= ?";
            $params[] = $date_to;
            $param_types .= 's';
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        // Get verifications with correct column names
        $verifications = [];
        $query = "
            SELECT 
                v.vendor_id,
                v.id_doc_url as document_url,
                v.business_doc_url,
                v.status,
                v.rejection_reason,
                v.verified_by,
                v.verified_at,
                v.created_at,
                v.updated_at,
                vend.business_name,
                vend.status AS actual_status,
                u.name as vendor_name,
                u.phone,
                u.email
            FROM verifications v
            LEFT JOIN vendors vend ON v.vendor_id = vend.user_id
            LEFT JOIN users u ON vend.user_id = u.id
            $where_clause
            ORDER BY v.created_at DESC
        ";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $verifications[] = $row;
        }

        // Get statistics with correct column names
        $stats = [
            'total_verifications' => 0,
            'pending_verifications' => 0,
            'approved_verifications' => 0,
            'rejected_verifications' => 0
        ];

        $stats_query = "
            SELECT 
                COUNT(*) as total_verifications,
                SUM(CASE WHEN v.status = 'pending' THEN 1 ELSE 0 END) as pending_verifications,
                SUM(CASE WHEN v.status = 'approved' THEN 1 ELSE 0 END) as approved_verifications,
                SUM(CASE WHEN v.status = 'rejected' THEN 1 ELSE 0 END) as rejected_verifications
            FROM verifications v
            LEFT JOIN vendors vend ON v.vendor_id = vend.user_id
            $where_clause
        ";

        $stats_stmt = $conn->prepare($stats_query);
        if (!empty($params)) {
            $stats_stmt->bind_param($param_types, ...$params);
        }
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result()->fetch_assoc();
        if ($stats_result) {
            $stats = $stats_result;
        }

        // Get vendors for filter
        $vendors_stmt = $conn->prepare("SELECT user_id, business_name FROM vendors ORDER BY business_name");
        $vendors_stmt->execute();
        $vendors = $vendors_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        ?>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($stats['total_verifications']); ?></h3>
                        <p>Total Verifications</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($stats['pending_verifications']); ?></h3>
                        <p>Pending Verifications</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($stats['approved_verifications']); ?></h3>
                        <p>Approved Verifications</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo number_format($stats['rejected_verifications']); ?></h3>
                        <p>Rejected Verifications</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
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
                            <div class="col-md-3">
                                <label for="status">Status</label>
                                <select class="form-control" id="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>
                                        Pending</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
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

        <!-- Verifications Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Verifications List</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-info" onclick="exportVerifications()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="verificationsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Vendor</th>
                                    <th>Business Info</th>
                                    <th>Documents</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Reviewed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($verifications as $verification): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo $verification['vendor_id']; ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($verification['business_name']); ?></strong>
                                            <br>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($verification['vendor_name']); ?></small>
                                        </td>
                                        <td>
                                            <i class="fas fa-phone"></i>
                                            <?php echo htmlspecialchars($verification['phone'] ?? 'N/A'); ?><br>
                                            <i class="fas fa-envelope"></i>
                                            <?php echo htmlspecialchars($verification['email'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($verification['document_url'])): ?>
                                                <a href="../<?php echo htmlspecialchars($verification['document_url']); ?>"
                                                    target="_blank" class="btn btn-sm btn-info">
                                                    <i class="fas fa-file"></i> View
                                                </a>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">No Document</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                            echo $verification['actual_status'] === 'pending' ? 'warning' :
                                                ($verification['actual_status'] === 'approved' ? 'success' : 'danger');
                                            ?>">
                                                <?php echo ucfirst($verification['actual_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($verification['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($verification['status'] !== 'pending'): ?>
                                                <?php echo date('M j, Y H:i', strtotime($verification['updated_at'])); ?>
                                                <br>
                                                <small class="text-muted">by Admin</small>
                                            <?php else: ?>
                                                <span class="text-muted">Not reviewed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info"
                                                    onclick="viewVerification(<?php echo $verification['vendor_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($verification['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        onclick="approveVerification(<?php echo $verification['vendor_id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="rejectVerification(<?php echo $verification['vendor_id']; ?>)">
                                                        <i class="fas fa-times"></i>
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
</section>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>

<!-- Verification Details Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verification Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="verificationModalBody">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Approval/Rejection Modal -->
<div class="modal fade" id="actionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle">Action</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="actionForm">
                    <input type="hidden" id="actionVerificationId">
                    <input type="hidden" id="actionType">
                    <div class="form-group">
                        <label for="actionReason">Reason/Comment</label>
                        <textarea class="form-control" id="actionReason" rows="3"
                            placeholder="Enter reason for approval/rejection..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmAction()">Submit</button>
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
        dataTable = $('#verificationsTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [[5, "desc"]], // Sort by submitted date
            "pageLength": 25,
            "language": {
                "search": "Search verifications:",
                "lengthMenu": "Show _MENU_ verifications per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ verifications"
            },
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });
    }

    function initializeEventListeners() {
        // Real-time filter updates
        $('#status, #vendor_id').on('change', function () {
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
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        };

        // Build query string
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });

        // Redirect with filters
        window.location.href = 'verifications.php?' + params.toString();
    }

    function clearFilters() {
        window.location.href = 'verifications.php';
    }

    function viewVerification(verificationId) {
        $.ajax({
            url: 'api/verification_actions.php?action=details&id=' + verificationId,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    $('#verificationModalBody').html(response.data.html);
                    $('#verificationModal').modal('show');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Failed to load verification details', 'error');
            }
        });
    }

    function approveVerification(verificationId) {
        $('#actionVerificationId').val(verificationId);
        $('#actionType').val('approve');
        $('#actionModalTitle').text('Approve Verification');
        $('#actionReason').attr('placeholder', 'Enter approval reason (optional)...');
        $('#actionModal').modal('show');
    }

    function rejectVerification(verificationId) {
        $('#actionVerificationId').val(verificationId);
        $('#actionType').val('reject');
        $('#actionModalTitle').text('Reject Verification');
        $('#actionReason').attr('placeholder', 'Enter rejection reason...');
        $('#actionModal').modal('show');
    }

    function confirmAction() {
        const verificationId = $('#actionVerificationId').val();
        const actionType = $('#actionType').val();
        const reason = $('#actionReason').val();

        if (actionType === 'reject' && !reason.trim()) {
            Swal.fire('Error', 'Please provide a reason for rejection', 'error');
            return;
        }

        $.ajax({
            url: 'api/verification_actions.php?action=' + actionType,
            method: 'POST',
            data: JSON.stringify({
                verification_id: verificationId,
                reason: reason
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
                Swal.fire('Error', 'Failed to process verification', 'error');
            }
        });

        $('#actionModal').modal('hide');
    }

    function exportVerifications() {
        const filters = {
            status: $('#status').val(),
            vendor_id: $('#vendor_id').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        };

        // Build query string
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });

        window.open('api/verification_actions.php?action=export&' + params.toString(), '_blank');
    }

    function revokeVerification(vendorId) {
        Swal.fire({
            title: "Revoke Approval?",
            text: "This will set the verification status back to pending.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ffc107",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, revoke!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "api/verification_actions.php?action=update_status",
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({ verification_id: vendorId, status: "pending" }),
                    success: function (response) {
                        if (response.success) {
                            Swal.fire("Revoked!", response.message, "success").then(() => { location.reload(); });
                        } else {
                            Swal.fire("Error", response.message, "error");
                        }
                    },
                    error: function () {
                        Swal.fire("Error", "Failed to revoke approval", "error");
                    }
                });
            }
        });
    }
</script>