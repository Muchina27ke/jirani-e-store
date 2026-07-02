<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/Product.php';
require_once dirname(__DIR__) . '/includes/Vendor.php';

// Vendor session/role check
if (!isset($_SESSION['user_id']) || !$auth->hasRole('vendor')) {
    header('Location: ../auth.php?action=login');
    exit();
}

$db = getDbConnection();
$vendorId = $_SESSION['user_id'];
$productObj = new Product($db);
$vendorObj = new Vendor($db);

// Handle form submission
$success = false;
$error = '';
$categories = [];
// Fetch active categories for dropdown
$stmt = $db->prepare("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $location_lat = isset($_POST['location_lat']) ? floatval($_POST['location_lat']) : null;
    $location_lng = isset($_POST['location_lng']) ? floatval($_POST['location_lng']) : null;
    $image_url = null;

    // Validate vendor is approved
    $vendorStatus = $vendorObj->get_vendor_by_user_id($vendorId)['status'] ?? '';
    if ($vendorStatus !== 'approved') {
        $error = 'Your vendor account is not approved. You cannot add products.';
    } elseif (empty($name)) {
        $error = 'Product name is required.';
    } elseif ($category_id <= 0) {
        $error = 'Please select a valid category.';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than zero.';
    } elseif ($stock < 0) {
        $error = 'Stock cannot be negative.';
    } else {
        // Validate category exists
        $catCheck = $db->prepare("SELECT id FROM categories WHERE id = ? AND status = 'active'");
        $catCheck->bind_param('i', $category_id);
        $catCheck->execute();
        $catCheckResult = $catCheck->get_result();
        if ($catCheckResult->num_rows === 0) {
            $error = 'Selected category does not exist.';
        } else {
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                    $error = 'Invalid image format. Only JPG, PNG, GIF, and WebP are allowed.';
                } elseif ($_FILES['image']['size'] > $maxSize) {
                    $error = 'Image size exceeds 5MB limit.';
                } else {
                    $uploadDir = dirname(__DIR__) . '/uploads/products/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $targetPath = $uploadDir . $filename;
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $error = 'Failed to upload image.';
                    } else {
                        $image_url = 'uploads/products/' . $filename;
                    }
                }
            } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $error = 'Image upload failed. Please try again.';
            }
            // If no error so far, insert product
            if (empty($error)) {
                $result = $productObj->add_product($vendorId, $name, $description, $price, $stock, $location_lat, $location_lng);
                if ($result) {
                    $newProductId = $db->insert_id;
                    // Update category and image
                    $stmt = $db->prepare("UPDATE products SET category_id = ?, image_url = ? WHERE id = ?");
                    $stmt->bind_param('isi', $category_id, $image_url, $newProductId);
                    $stmt->execute();
                    $success = true;
                } else {
                    $error = 'Failed to add product. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Add Product';
$currentPage = 'products';
ob_start();
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: var(--j-radius-lg);">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h2 class="h4 mb-0" style="font-family: var(--j-font-heading); font-weight: 700; color: var(--j-primary);">Add New Product</h2>
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 rounded" role="alert">
                            <strong>Success!</strong> Product added successfully.
                            <a href="products.php" class="btn btn-sm btn-success ml-2">Back to Products</a>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 rounded" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="mt-2" enctype="multipart/form-data">
                        <div class="form-group mb-3">
                            <label for="name" style="font-weight: 600; font-size: 0.9rem;">Product Name</label>
                            <input type="text" class="form-control j-input" id="name" name="name" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label for="category_id" style="font-weight: 600; font-size: 0.9rem;">Category</label>
                                <select class="form-control j-input" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="price" style="font-weight: 600; font-size: 0.9rem;">Price (KSh)</label>
                                <input type="number" class="form-control j-input" id="price" name="price" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="stock" style="font-weight: 600; font-size: 0.9rem;">Stock</label>
                                <input type="number" class="form-control j-input" id="stock" name="stock" min="0" required>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="description" style="font-weight: 600; font-size: 0.9rem;">Description</label>
                            <textarea class="form-control j-input" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group mb-4">
                            <label for="image" style="font-weight: 600; font-size: 0.9rem;">Product Image</label>
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                            <small class="form-text text-muted mt-2">Supported formats: JPG, PNG, GIF, WebP (max 5MB)</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 form-group mb-4">
                                <label for="location_lat" style="font-weight: 600; font-size: 0.9rem;">Latitude (Optional)</label>
                                <input type="number" class="form-control j-input" id="location_lat" name="location_lat" step="any" placeholder="e.g., -1.2921">
                            </div>
                            <div class="col-md-6 form-group mb-4">
                                <label for="location_lng" style="font-weight: 600; font-size: 0.9rem;">Longitude (Optional)</label>
                                <input type="number" class="form-control j-input" id="location_lng" name="location_lng" step="any" placeholder="e.g., 36.8219">
                            </div>
                        </div>

                        <hr class="mb-4">
                        <div class="d-flex justify-content-end" style="gap: 12px;">
                            <a href="products.php" class="btn btn-light px-4" style="border-radius: 50px; font-weight: 600;">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4" style="background: var(--j-primary); border-color: var(--j-primary); border-radius: 50px; font-weight: 600;">Add Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>