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
    'total_vendors' => 0,
    'approved_vendors' => 0,
    'pending_vendors' => 0,
    'rejected_vendors' => 0
];
// Total vendors
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM vendors");
$stmt->execute();
$stats['total_vendors'] = $stmt->get_result()->fetch_assoc()['count'];
// Approved vendors
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM vendors WHERE status = 'approved'");
$stmt->execute();
$stats['approved_vendors'] = $stmt->get_result()->fetch_assoc()['count'];
// Pending vendors
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM vendors WHERE status = 'pending'");
$stmt->execute();
$stats['pending_vendors'] = $stmt->get_result()->fetch_assoc()['count'];
// Rejected vendors
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM vendors WHERE status = 'rejected'");
$stmt->execute();
$stats['rejected_vendors'] = $stmt->get_result()->fetch_assoc()['count'];
// Fetch all vendors for the table (add your filters if needed)
$stmt = $conn->prepare("
    SELECT v.*, u.name, u.email, u.phone,
        (SELECT COUNT(*) FROM products p WHERE p.vendor_id = v.user_id) AS product_count,
        (SELECT COUNT(*) FROM orders o WHERE o.vendor_id = v.user_id) AS order_count,
        (SELECT COALESCE(SUM(p.price * oi.quantity), 0)
         FROM orders o
         JOIN order_items oi ON o.id = oi.order_id
         JOIN products p ON oi.product_id = p.id
         WHERE o.vendor_id = v.user_id) AS total_revenue
    FROM vendors v
    JOIN users u ON v.user_id = u.id
    ORDER BY v.created_at DESC
");
$stmt->execute();
$vendors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// Fetch all vendors for the filter dropdown
$stmt = $conn->prepare("SELECT user_id, business_name FROM vendors ORDER BY business_name ASC");
$stmt->execute();
$all_vendors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// Set filter variables if used
$status_filter = $_GET['status'] ?? '';
$vendor_id_filter = $_GET['vendor_id'] ?? '';
// --- END DATA FETCHING LOGIC ---

$pageTitle = 'Vendors Management';
$currentPage = 'vendors';
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
                        <h3><?php echo number_format($stats['total_vendors']); ?></h3>
                        <p>Total Vendors</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($stats['approved_vendors']); ?></h3>
                        <p>Approved Vendors</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($stats['pending_vendors']); ?></h3>
                        <p>Pending Vendors</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo number_format($stats['rejected_vendors']); ?></h3>
                        <p>Rejected Vendors</p>
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
                            <div class="col-md-4">
                                <label for="status">Status</label>
                                <select class="form-control" id="status">
                                    <option value="">All Status</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>
                                        Pending</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="vendor_id">Specific Vendor</label>
                                <select class="form-control" id="vendor_id">
                                    <option value="">All Vendors</option>
                                    <?php foreach ($all_vendors as $vendor): ?>
                                        <option value="<?php echo $vendor['user_id']; ?>" <?php echo $vendor_id_filter == $vendor['user_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vendor['business_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
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

        <!-- Vendors Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Vendors List</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-success" onclick="addVendor()">
                                <i class="fas fa-plus"></i> Add Vendor
                            </button>
                            <button type="button" class="btn btn-info" onclick="exportVendors()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="vendorsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Business Name</th>
                                    <th>Contact Info</th>
                                    <th>Status</th>
                                    <th>Products</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendors as $vendor): ?>
                                    <tr>
                                        <td><?php echo $vendor['user_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($vendor['business_name']); ?></strong>
                                            <br>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($vendor['name']); ?></small>
                                        </td>
                                        <td>
                                            <i class="fas fa-envelope"></i>
                                            <?php echo htmlspecialchars($vendor['email'] ?? 'N/A'); ?><br>
                                            <i class="fas fa-phone"></i>
                                            <?php echo htmlspecialchars($vendor['phone'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                            echo $vendor['status'] === 'approved' ? 'success' :
                                                ($vendor['status'] === 'pending' ? 'warning' : 'danger');
                                            ?>">
                                                <?php echo ucfirst($vendor['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $vendor['product_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary"><?php echo $vendor['order_count']; ?></span>
                                        </td>
                                        <td>KSh <?php echo number_format($vendor['total_revenue'], 2); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($vendor['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info"
                                                    onclick="viewVendor(<?php echo $vendor['user_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="editVendor(<?php echo $vendor['user_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($vendor['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        onclick="approveVendor(<?php echo $vendor['user_id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="rejectVendor(<?php echo $vendor['user_id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php elseif ($vendor['status'] === 'approved'): ?>
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                        onclick="revokeVerification(<?php echo $vendor['user_id']; ?>)">
                                                        <i class="fas fa-ban"></i>
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
    </div>
</section>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>

<!-- Vendor Details Modal -->
<div class="modal fade" id="vendorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vendor Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="vendorModalBody">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Vendor Modal -->
<div class="modal fade" id="vendorFormModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vendorFormModalTitle">Add Vendor</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="vendorForm">
                <div class="modal-body">
                    <input type="hidden" id="vendor_id" name="vendor_id">
                    <div class="form-group">
                        <label for="business_name">Business Name</label>
                        <input type="text" class="form-control" id="business_name" name="business_name" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Contact Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="vendorFormSubmitBtn">Save</button>
                </div>
            </form>
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
        dataTable = $('#vendorsTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [[7, "desc"]], // Sort by joined date
            "pageLength": 25,
            "language": {
                "search": "Search vendors:",
                "lengthMenu": "Show _MENU_ vendors per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ vendors"
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
    }

    function applyFilters() {
        const filters = {
            status: $('#status').val(),
            vendor_id: $('#vendor_id').val()
        };

        // Build query string
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });

        // Redirect with filters
        window.location.href = 'vendors.php?' + params.toString();
    }

    function clearFilters() {
        window.location.href = 'vendors.php';
    }

    function viewVendor(vendorId) {
        $.ajax({
            url: 'api/verification_actions.php?action=details&id=' + vendorId,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    $('#vendorModalBody').html(response.data.html);
                    $('#vendorModal').modal('show');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Failed to load vendor details', 'error');
            }
        });
    }

    function editVendor(vendorId) {
        // Fetch vendor details via AJAX
        $.ajax({
            url: 'api/verification_actions.php?action=details&id=' + vendorId,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    const v = response.data.vendor;
                    $('#vendor_id').val(v.id || v.user_id || vendorId);
                    $('#business_name').val(v.business_name || '');
                    $('#name').val(v.name || '');
                    $('#email').val(v.email || '');
                    $('#phone').val(v.phone || '');
                    $('#location').val(v.location || '');
                    $('#description').val(v.description || '');
                    $('#vendorFormModalTitle').text('Edit Vendor');
                    $('#vendorFormSubmitBtn').text('Save Changes');
                    $('#vendorFormModal').modal('show');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Failed to load vendor details', 'error');
            }
        });
    }

    function approveVendor(vendorId) {
        Swal.fire({
            title: 'Approve Vendor?',
            text: "This will approve the vendor and allow them to sell products.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/verification_actions.php?action=approve',
                    method: 'POST',
                    data: JSON.stringify({ verification_id: vendorId }),
                    contentType: 'application/json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Approved!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to approve vendor', 'error');
                    }
                });
            }
        });
    }

    function rejectVendor(vendorId) {
        Swal.fire({
            title: 'Reject Vendor?',
            text: "This will reject the vendor. They will not be able to sell products.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, reject!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Reason for Rejection',
                    input: 'text',
                    inputPlaceholder: 'Enter reason...',
                    showCancelButton: true,
                    confirmButtonText: 'Submit',
                    cancelButtonText: 'Cancel',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'You must provide a reason!';
                        }
                    }
                }).then((inputResult) => {
                    if (inputResult.isConfirmed && inputResult.value) {
                        $.ajax({
                            url: 'api/verification_actions.php?action=reject',
                            method: 'POST',
                            data: JSON.stringify({ verification_id: vendorId, reason: inputResult.value }),
                            contentType: 'application/json',
                            success: function (response) {
                                if (response.success) {
                                    Swal.fire('Rejected!', response.message, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            },
                            error: function () {
                                Swal.fire('Error', 'Failed to reject vendor', 'error');
                            }
                        });
                    }
                });
            }
        });
    }

    function revokeVerification(vendorId) {
        Swal.fire({
            title: 'Revoke Verification?',
            text: "This will revoke the vendor's approval. They will not be able to sell products until re-approved.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, revoke!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/verification_actions.php?action=revoke',
                    method: 'POST',
                    data: JSON.stringify({ verification_id: vendorId }),
                    contentType: 'application/json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Revoked!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to revoke vendor', 'error');
                    }
                });
            }
        });
    }

    function addVendor() {
        $('#vendorForm')[0].reset();
        $('#vendor_id').val('');
        $('#vendorFormModalTitle').text('Add Vendor');
        $('#vendorFormSubmitBtn').text('Add Vendor');
        $('#vendorFormModal').modal('show');
    }

    function exportVendors() {
        const filters = {
            status: $('#status').val(),
            vendor_id: $('#vendor_id').val()
        };

        // Build query string
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });

        window.open('api/verification_actions.php?action=export&' + params.toString(), '_blank');
    }

    $('#vendorForm').on('submit', function (e) {
        e.preventDefault();
        const vendorId = $('#vendor_id').val();
        const data = {
            business_name: $('#business_name').val(),
            name: $('#name').val(),
            email: $('#email').val(),
            phone: $('#phone').val(),
            location: $('#location').val(),
            description: $('#description').val()
        };
        let ajaxOptions;
        if (vendorId) {
            // Edit
            data.id = vendorId;
            ajaxOptions = {
                url: 'api/verification_actions.php?action=update',
                method: 'PUT',
                data: JSON.stringify(data),
                contentType: 'application/json'
            };
        } else {
            // Add
            ajaxOptions = {
                url: 'api/verification_actions.php?action=add',
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json'
            };
        }
        $.ajax(ajaxOptions)
            .done(function (response) {
                if (response.success) {
                    $('#vendorFormModal').modal('hide');
                    Swal.fire('Success', response.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            })
            .fail(function () {
                Swal.fire('Error', 'Failed to save vendor', 'error');
            });
    });
</script>