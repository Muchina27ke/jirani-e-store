
<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Correct authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}

$pageTitle = 'Products Management';
$currentPage = 'products';

// Initialize products array
$products = [];

// Initialize stats with default values
$stats = [
    'total_products' => 0,
    'active_products' => 0,
    'out_of_stock' => 0,
    'total_value' => 0
];

// Filtering logic
$where_clauses = [];
$params = [];
$param_types = '';

$status_filter = $_GET['status'] ?? '';
$vendor_filter = $_GET['vendor_id'] ?? '';
$category_filter = $_GET['category_id'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$search_term = $_GET['search'] ?? '';

if ($status_filter) {
    if ($status_filter === 'out_of_stock') {
        $where_clauses[] = "p.stock <= 0";
    } else {
        $where_clauses[] = "p.status = ?";
        $params[] = $status_filter;
        $param_types .= 's';
    }
}

// Get stats
$stats_query = "SELECT 
    COUNT(p.id) as total_products, 
    SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active_products, 
    SUM(CASE WHEN p.stock <= 0 THEN 1 ELSE 0 END) as out_of_stock, 
    SUM(p.price * p.stock) as total_value 
    FROM products p";

if (!empty($where_clauses)) {
    $stats_query .= " WHERE " . implode(' AND ', $where_clauses);
}

$stats_stmt = $conn->prepare($stats_query);
if (!empty($params)) {
    $stats_stmt->bind_param($param_types, ...$params);
}
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result()->fetch_assoc();

// Safely merge results into the initialized stats array
if ($stats_result) {
    foreach ($stats_result as $key => $value) {
        if (isset($stats[$key])) {
            $stats[$key] = $value ?? 0;
        }
    }
}

// Get vendors for filter
$vendors_stmt = $conn->prepare("SELECT user_id, business_name FROM vendors ORDER BY business_name");
$vendors_stmt->execute();
$vendors = $vendors_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories for filter
$categories_stmt = $conn->prepare("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
$categories_stmt->execute();
$categories = $categories_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Products
$products_query = "
    SELECT 
        p.id, p.name, p.price, p.stock, p.status, p.image_url,
        v.business_name as vendor_name, v.user_id as vendor_id,
        c.name as category_name,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as order_count,
        (SELECT SUM(oi.price * oi.quantity) FROM order_items oi WHERE oi.product_id = p.id) as total_revenue
    FROM 
        products p
    LEFT JOIN 
        vendors v ON p.vendor_id = v.user_id
    LEFT JOIN 
        categories c ON p.category_id = c.id
";

if ($vendor_filter) {
    $where_clauses[] = "p.vendor_id = ?";
    $params[] = $vendor_filter;
    $param_types .= 'i';
}
if ($category_filter) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category_filter;
    $param_types .= 'i';
}
if ($price_min) {
    $where_clauses[] = "p.price >= ?";
    $params[] = $price_min;
    $param_types .= 'd';
}
if ($price_max) {
    $where_clauses[] = "p.price <= ?";
    $params[] = $price_max;
    $param_types .= 'd';
}
if ($search_term) {
    $where_clauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$search_term}%";
    $params[] = "%{$search_term}%";
    $param_types .= 'ss';
}

if (!empty($where_clauses)) {
    $products_query .= " WHERE " . implode(' AND ', $where_clauses);
}

$products_query .= " ORDER BY p.created_at DESC";

$products_stmt = $conn->prepare($products_query);
if (!empty($params)) {
    $products_stmt->bind_param($param_types, ...$params);
}
$products_stmt->execute();
$products_result = $products_stmt->get_result();
$products = $products_result->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($stats['total_products']); ?></h3>
                <p>Total Products</p>
            </div>
            <div class="icon">
                <i class="fas fa-box"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo number_format($stats['active_products']); ?></h3>
                <p>Active Products</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo number_format($stats['out_of_stock']); ?></h3>
                <p>Out of Stock</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>KSh <?php echo number_format($stats['total_value'], 2); ?></h3>
                <p>Inventory Value</p>
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
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>
                                Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="out_of_stock" <?php echo $status_filter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="vendor_id">Vendor</label>
                        <select class="form-control" id="vendor_id">
                            <option value="">All Vendors</option>
                            <?php foreach (
                                $vendors as $vendor): ?>
                                <option value="<?php echo $vendor['user_id']; ?>" <?php echo $vendor_filter == $vendor['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vendor['business_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="category_id">Category</label>
                        <select class="form-control" id="category_id">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="price_min">Min Price</label>
                        <input type="number" class="form-control" id="price_min" placeholder="Min"
                            value="<?php echo htmlspecialchars($price_min); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="price_max">Max Price</label>
                        <input type="number" class="form-control" id="price_max" placeholder="Max"
                            value="<?php echo htmlspecialchars($price_max); ?>">
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

<!-- Products Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Products List</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success" onclick="addProduct()">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                    <button type="button" class="btn btn-info" onclick="exportProducts()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table id="productsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><input type="checkbox" class="product-checkbox"
                                        value="<?php echo $product['id']; ?>"></td>
                                <td>
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="../<?php echo htmlspecialchars($product['image_url']); ?>"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                                            class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <br>
                                    <small class="text-muted">ID: <?php echo $product['id']; ?></small>
                                </td>
                                <td>
                                    <a href="#" onclick="viewVendor(<?php echo $product['vendor_id']; ?>);
                                    return false;">
                                        <?php echo htmlspecialchars($product['vendor_name'] ?? 'N/A'); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?>
                                </td>
                                <td>KSh <?php echo number_format($product['price'], 2); ?>
                                </td>
                                <td>
                                    <span
                                        class="badge badge-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php
                                    echo $product['status'] === 'active' ? 'success' :
                                        ($product['status'] === 'inactive' ? 'secondary' : 'danger');
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $product['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $product['order_count']; ?>
                                </td>
                                <td>KSh
                                    <?php echo number_format($product['total_revenue'], 2); ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info"
                                            onclick="viewProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="editProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning"
                                            onclick="updateStatus(<?php echo $product['id']; ?>, '<?php echo $product['status']; ?>')">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
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


<!-- Product Details Modal -->
<div class="modal fade" id="productModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="productModalBody">
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
                <h5 class="modal-title">Update Product Status</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="statusForm">
                    <input type="hidden" id="statusProductId">
                    <div class="form-group">
                        <label for="newStatus">New Status</label>
                        <select class="form-control" id="newStatus" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmStatusUpdate()">Update
                    Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Vendor Details Modal -->
<div class="modal fade" id="vendorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down" role="document">
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
</section>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('#vendor_id').select2({
            width: '100%',
            placeholder: 'All Vendors',
            allowClear: true
        });
    });
</script>
<script>
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
</script>
<script>
    // Add Product
    function addProduct() {
        window.location.href = 'add_product.php';
    }

    // Export Products
    function exportProducts() {
        const params = new URLSearchParams(window.location.search);
        window.location.href = 'api/product_actions.php?action=export&' + params.toString();
    }

    // 1. View Product
    function viewProduct(productId) {
        $.ajax({
            url: 'api/product_actions.php?action=details&id=' + productId,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    const p = response.data;
                    let html = `
                        <div class="row">
                            <div class="col-md-4">
                                <img src="${p.image || p.image_url || 'https://via.placeholder.com/200x200?text=No+Image'}" class="img-fluid rounded mb-3" alt="${p.name}">
                            </div>
                            <div class="col-md-8">
                                <h4>${p.name}</h4>
                                <p><strong>Vendor:</strong> ${p.vendor_name || ''}</p>
                                <p><strong>Category:</strong> ${p.category_name || ''}</p>
                                <p><strong>Price:</strong> KSh ${Number(p.price).toLocaleString()}</p>
                                <p><strong>Stock:</strong> ${p.stock}</p>
                                <p><strong>Status:</strong> <span class="badge badge-${p.status === 'active' ? 'success' : (p.status === 'inactive' ? 'secondary' : 'danger')}">${p.status}</span></p>
                                <p><strong>Description:</strong> ${p.description || ''}</p>
                                <p><strong>Created:</strong> ${p.created_at}</p>
                                <p><strong>Updated:</strong> ${p.updated_at}</p>
                                <p><strong>Total Orders:</strong> ${p.order_count}</p>
                                <p><strong>Total Revenue:</strong> KSh ${Number(p.total_revenue).toLocaleString()}</p>
                            </div>
                        </div>
                    `;

                    // Sales History
                    if (p.sales_history && p.sales_history.length > 0) {
                        html += `<hr><h5>Sales History</h5>
                            <div class="table-responsive"><table class="table table-sm table-bordered">
                            <thead><tr><th>Order ID</th><th>Date</th><th>Qty</th><th>Price</th><th>Status</th></tr></thead><tbody>`;
                        p.sales_history.forEach(s => {
                            html += `<tr>
                                <td>#${s.order_id}</td>
                                <td>${s.created_at}</td>
                                <td>${s.quantity}</td>
                                <td>KSh ${Number(s.price).toLocaleString()}</td>
                                <td>${s.status}</td>
                            </tr>`;
                        });
                        html += `</tbody></table></div>`;
                    }

                    // Reviews
                    if (p.reviews && p.reviews.length > 0) {
                        html += `<hr><h5>Reviews</h5>
                            <div class="table-responsive"><table class="table table-sm table-bordered">
                            <thead><tr><th>Customer</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead><tbody>`;
                        p.reviews.forEach(r => {
                            html += `<tr>
                                <td>${r.customer_name || ''}</td>
                                <td>${r.rating || ''}</td>
                                <td>${r.comment || ''}</td>
                                <td>${r.created_at || ''}</td>
                            </tr>`;
                        });
                        html += `</tbody></table></div>`;
                    }

                    // Inventory History
                    if (p.inventory_history && p.inventory_history.length > 0) {
                        html += `<hr><h5>Inventory History</h5>
                            <div class="table-responsive"><table class="table table-sm table-bordered">
                            <thead><tr><th>Date</th><th>Change</th><th>Note</th></tr></thead><tbody>`;
                        p.inventory_history.forEach(i => {
                            html += `<tr>
                                <td>${i.created_at || ''}</td>
                                <td>${i.change || ''}</td>
                                <td>${i.note || ''}</td>
                            </tr>`;
                        });
                        html += `</tbody></table></div>`;
                    }

                    $('#productModalBody').html(html);
                    $('#productModal').modal('show');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Failed to load product details', 'error');
            }
        });
    }

    // 2. Edit Product (open add/edit modal, pre-fill, save via AJAX)
    function editProduct(productId) {
        window.location.href = 'add_product.php?edit=' + productId;
        // For full AJAX modal editing, you would fetch product details and show a modal form here.
    }

    // 3. Update Status
    function updateStatus(productId, currentStatus) {
        $('#statusProductId').val(productId);
        $('#newStatus').val(currentStatus);
        $('#statusModal').modal('show');
    }
    function confirmStatusUpdate() {
        var productId = $('#statusProductId').val();
        var newStatus = $('#newStatus').val();
        $.ajax({
            url: 'api/product_actions.php?action=update_status',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId, status: newStatus }),
            success: function (response) {
                if (response.success) {
                    $('#statusModal').modal('hide');
                    Swal.fire('Success', response.message, 'success').then(() => { location.reload(); });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Failed to update product status', 'error');
            }
        });
    }

    // 4. Delete Product
    function deleteProduct(productId) {
        Swal.fire({
            title: 'Delete Product?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/product_actions.php?action=delete&id=' + productId,
                    method: 'DELETE',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => { location.reload(); });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Failed to delete product', 'error');
                    }
                });
            }
        });
    }
</script>
<script>
function applyFilters() {
    const params = new URLSearchParams();
    const status = $('#status').val();
    const vendor_id = $('#vendor_id').val();
    const category_id = $('#category_id').val();
    const price_min = $('#price_min').val();
    const price_max = $('#price_max').val();
    if (status) params.append('status', status);
    if (vendor_id) params.append('vendor_id', vendor_id);
    if (category_id) params.append('category_id', category_id);
    if (price_min) params.append('price_min', price_min);
    if (price_max) params.append('price_max', price_max);
    window.location.href = 'products.php?' + params.toString();
}
function clearFilters() {
    window.location.href = 'products.php';
}
</script>