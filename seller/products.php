<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$pageTitle = 'Products';
$currentPage = 'products';

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/Vendor.php';
require_once dirname(__DIR__) . '/includes/Product.php';
require_once dirname(__DIR__) . '/includes/Auth.php'; // Ensure Auth is included for session checks

// Vendor session/role check
if (!isset($_SESSION['user_id']) || !$auth->hasRole('vendor')) {
    header('Location: ../auth.php?action=login');
    exit();
}

$db = getDbConnection();
$vendorId = $_SESSION['user_id'];

// Initialize database connection and classes
$vendorObj = new Vendor($db);
$productObj = new Product($db);

// Check vendor verification status
$vendorDetails = $vendorObj->get_vendor_by_user_id($vendorId);
$isVendorVerified = ($vendorDetails['status'] ?? '') === 'approved';

// Handle product status updates - Only if vendor is verified
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$isVendorVerified) {
        $_SESSION['error'] = 'Your account is not yet verified. Please complete verification to manage products.';
        header('Location: /seller/verification.php');
        exit;
    }
    $productId = $_POST['product_id'] ?? null;
    $action = $_POST['action'];

    if ($productId) {
        try {
            // Verify product belongs to vendor (using Product class where possible)
            // For now, retaining direct DB interaction as Product class lacks specific update methods
            $stmt = $db->prepare("SELECT id FROM products WHERE id = ? AND vendor_id = ?");
            $stmt->bind_param("ii", $productId, $vendorId);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($id);
            if ($stmt->fetch()) {
                switch ($action) {
                    case 'active':
                        $productObj->update_product_status($productId, 'active');
                        break;
                    case 'inactive':
                        $productObj->update_product_status($productId, 'inactive');
                        if ($productObj->get_product_by_id($productId)['vendor_id'] == $vendorId) {
                            $productObj->update_product_status($productId, 'inactive');
                        }
                        break;
                    case 'delete':
                        if ($productObj->get_product_by_id($productId)['vendor_id'] == $vendorId) {
                            $productObj->delete_product($productId);
                        }
                        break;
                }

                $_SESSION['success'] = 'Product updated successfully.';
            }

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error updating product: ' . $e->getMessage();
        }
    }

    header('Location: products.php');
    exit;
}

// Get products with category names (using Product class where possible)
$products = $productObj->get_products_by_vendor($vendorId); // Assuming this method exists

ob_start();
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h1 class="h3 mb-0">Products</h1>
    </div>
    <div class="col-md-6 text-right">
        <?php if ($isVendorVerified): ?>
            <a href="add_product.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Your account is not yet verified. Please complete verification to add products.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isVendorVerified): ?>
    <div class="alert alert-info" role="alert">
        Your account is currently undergoing verification or has not yet been submitted for verification. You will not be
        able to manage products until your account is approved.
        <a href="/seller/verification.php" class="alert-link">Go to Verification Page</a>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Orders</th>
                        <th>Revenue</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No products found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if ($product['image_url']): 
                                    $pImg = strpos($product['image_url'], 'uploads/') !== false ? SITE_URL . htmlspecialchars($product['image_url']) : SITE_URL . 'uploads/' . htmlspecialchars($product['image_url']);
                                ?>
                                    <img src="<?php echo $pImg; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <?php else: ?>
                                    <div class="text-center text-muted" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 8px; border: 1px dashed #ccc;">
                                        <i class="fas fa-image fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td>KSh <?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo number_format($product['stock']); ?></td>
                            <td><?php echo number_format($product['total_orders']); ?></td>
                            <td>KSh <?php echo number_format($product['total_revenue'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php
                                echo $product['status'] === 'active' ? 'success' : 'danger';
                                ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center" style="gap: 8px;">
                                    <button type="button" class="btn btn-sm text-white" style="background-color: #17a2b8; border: none; border-radius: 6px; width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" data-toggle="modal"
                                        data-target="#viewProductModal<?php echo $product['id']; ?>" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($isVendorVerified): ?>
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>"
                                            class="btn btn-sm text-white" style="background-color: var(--j-primary, #28a745); border: none; border-radius: 6px; width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($product['status'] === 'active'): ?>
                                            <button type="button" class="btn btn-sm text-dark" style="background-color: #ffc107; border: none; border-radius: 6px; width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" data-toggle="modal"
                                                data-target="#deactivateProductModal<?php echo $product['id']; ?>" title="Pause Product">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        <?php elseif ($product['status'] === 'inactive'): ?>
                                            <button type="button" class="btn btn-sm text-white" style="background-color: #28a745; border: none; border-radius: 6px; width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" data-toggle="modal"
                                                data-target="#activateProductModal<?php echo $product['id']; ?>" title="Activate Product">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm text-white" style="background-color: #dc3545; border: none; border-radius: 6px; width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" data-toggle="modal"
                                            data-target="#deleteProductModal<?php echo $product['id']; ?>" title="Delete Product">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- View Product Modal -->
                                <div class="modal fade" id="viewProductModal<?php echo $product['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Product Details</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <?php if ($product['image_url']): 
                                                            $pImg = strpos($product['image_url'], 'uploads/') !== false ? SITE_URL . htmlspecialchars($product['image_url']) : SITE_URL . 'uploads/' . htmlspecialchars($product['image_url']);
                                                        ?>
                                                            <img src="<?php echo $pImg; ?>"
                                                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                                class="img-fluid" style="border-radius: 8px; max-height: 250px; object-fit: contain; width: 100%;">
                                                        <?php else: ?>
                                                            <div class="text-center text-muted">
                                                                <i class="fas fa-image fa-3x"></i>
                                                                <p>No image available</p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                        <p class="text-muted">
                                                            Category:
                                                            <?php echo htmlspecialchars($product['category_name']); ?>
                                                        </p>
                                                        <hr>
                                                        <h5>Description</h5>
                                                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?>
                                                        </p>
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <h6>Price</h6>
                                                                <p>KSh <?php echo number_format($product['price'], 2); ?>
                                                                </p>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <h6>Stock</h6>
                                                                <p><?php echo number_format($product['stock']); ?></p>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <h6>Status</h6>
                                                                <p>
                                                                    <span class="badge badge-<?php
                                                                    echo $product['status'] === 'active' ? 'success' : 'danger';
                                                                    ?>">
                                                                        <?php echo ucfirst($product['status']); ?>
                                                                    </span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="info-box">
                                                            <span class="info-box-icon bg-info">
                                                                <i class="fas fa-shopping-cart"></i>
                                                            </span>
                                                            <div class="info-box-content">
                                                                <span class="info-box-text">Total Orders</span>
                                                                <span class="info-box-number">
                                                                    <?php echo number_format($product['total_orders']); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="info-box">
                                                            <span class="info-box-icon bg-success">
                                                                <i class="fas fa-box"></i>
                                                            </span>
                                                            <div class="info-box-content">
                                                                <span class="info-box-text">Units Sold</span>
                                                                <span class="info-box-number">
                                                                    <?php echo number_format($product['total_quantity_sold']); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="info-box">
                                                            <span class="info-box-icon bg-warning">
                                                                <i class="fas fa-money-bill"></i>
                                                            </span>
                                                            <div class="info-box-content">
                                                                <span class="info-box-text">Total Revenue</span>
                                                                <span class="info-box-number">
                                                                    KSh
                                                                    <?php echo number_format($product['total_revenue'], 2); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activate Product Modal -->
                                <div class="modal fade" id="activateProductModal<?php echo $product['id']; ?>"
                                    tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form method="POST" class="modal-content">
                                                <input type="hidden" name="product_id"
                                                    value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="action" value="active">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Activate Product</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to activate
                                                        "<?php echo htmlspecialchars($product['name']); ?>"?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Activate</button>
                                                </div>
                                            </form>
                                    </div>
                                </div>

                                <!-- Deactivate Product Modal -->
                                <div class="modal fade" id="deactivateProductModal<?php echo $product['id']; ?>"
                                    tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form method="POST" class="modal-content">
                                                <input type="hidden" name="product_id"
                                                    value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="action" value="inactive">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Deactivate Product</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to deactivate
                                                        "<?php echo htmlspecialchars($product['name']); ?>"?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-warning">Deactivate</button>
                                                </div>
                                            </form>
                                    </div>
                                </div>

                                <!-- Delete Product Modal -->
                                <div class="modal fade" id="deleteProductModal<?php echo $product['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form method="POST" class="modal-content">
                                                <input type="hidden" name="product_id"
                                                    value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Delete Product</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-danger">Are you sure you want to delete
                                                        "<?php echo htmlspecialchars($product['name']); ?>"?
                                                        This action cannot be undone.
                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                </div>
                                            </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>