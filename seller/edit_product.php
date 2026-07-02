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

// Get product ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    header('Location: products.php');
    exit();
}

// Fetch product and validate ownership
$product = $productObj->get_product_by_id($product_id);
if (!$product || $product['vendor_id'] != $vendorId) {
    $_SESSION['error'] = 'Product not found or access denied.';
    header('Location: products.php');
    exit();
}

// Fetch categories
$categories = [];
$stmt = $db->prepare("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $location_lat = isset($_POST['location_lat']) ? floatval($_POST['location_lat']) : null;
    $location_lng = isset($_POST['location_lng']) ? floatval($_POST['location_lng']) : null;
    $image_url = $product['image_url'];

    // Validate vendor is approved
    $vendorStatus = $vendorObj->get_vendor_by_user_id($vendorId)['status'] ?? '';
    if ($vendorStatus !== 'approved') {
        $error = 'Your vendor account is not approved. You cannot edit products.';
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
            // Handle image upload if any
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
            // If no error so far, update product
            if (empty($error)) {
                $stmt = $db->prepare("UPDATE products SET name=?, category_id=?, price=?, stock=?, description=?, location_lat=?, location_lng=?, image_url=? WHERE id=? AND vendor_id=?");
                $stmt->bind_param('sididddsii', $name, $category_id, $price, $stock, $description, $location_lat, $location_lng, $image_url, $product_id, $vendorId);
                if ($stmt->execute()) {
                    $success = true;
                    // Refresh product data
                    $product = $productObj->get_product_by_id($product_id);
                } else {
                    $error = 'Failed to update product. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Edit Product';
$currentPage = 'products';
ob_start();
?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: var(--j-radius-lg);">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h2 class="h4 mb-0" style="font-family: var(--j-font-heading); font-weight: 700; color: var(--j-primary);">Edit Product</h2>
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success border-0 rounded">Product updated successfully.</div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger border-0 rounded"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="mt-2" enctype="multipart/form-data">
                        <div class="form-group mb-3">
                            <label for="name" style="font-weight: 600; font-size: 0.9rem;">Product Name</label>
                            <input type="text" class="form-control j-input" id="name" name="name" required value="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label for="category_id" style="font-weight: 600; font-size: 0.9rem;">Category</label>
                                <select class="form-control j-input" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php if ($product['category_id'] == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="price" style="font-weight: 600; font-size: 0.9rem;">Price (KSh)</label>
                                <input type="number" class="form-control j-input" id="price" name="price" min="0" step="0.01" required value="<?php echo htmlspecialchars($product['price']); ?>">
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="stock" style="font-weight: 600; font-size: 0.9rem;">Stock</label>
                                <input type="number" class="form-control j-input" id="stock" name="stock" min="0" required value="<?php echo htmlspecialchars($product['stock']); ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="description" style="font-weight: 600; font-size: 0.9rem;">Description</label>
                            <textarea class="form-control j-input" id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        <div class="form-group mb-4">
                            <label for="image" style="font-weight: 600; font-size: 0.9rem;">Product Image</label><br>
                            <?php if ($product['image_url']): 
                                $pImg = strpos($product['image_url'], 'uploads/') !== false ? SITE_URL . htmlspecialchars($product['image_url']) : SITE_URL . 'uploads/' . htmlspecialchars($product['image_url']);
                            ?>
                                <div style="margin-bottom: 12px; border-radius: 8px; overflow: hidden; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                    <img src="<?php echo $pImg; ?>" alt="Product Image" style="width: 120px; height: 120px; object-fit: cover; display: block;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                            <small class="form-text text-muted mt-2">Supported formats: JPG, PNG, GIF, WebP (max 5MB). Leave blank to keep current image.</small>
                        </div>
                        
                        <!-- Map Location could go here if needed -->
                        <div class="row">
                            <div class="col-md-6 form-group mb-4">
                                <label for="location_lat" style="font-weight: 600; font-size: 0.9rem;">Latitude (Optional)</label>
                                <input type="number" class="form-control j-input" id="location_lat" name="location_lat" step="any" placeholder="e.g., -1.2921" value="<?php echo htmlspecialchars($product['location_lat']); ?>">
                            </div>
                            <div class="col-md-6 form-group mb-4">
                                <label for="location_lng" style="font-weight: 600; font-size: 0.9rem;">Longitude (Optional)</label>
                                <input type="number" class="form-control j-input" id="location_lng" name="location_lng" step="any" placeholder="e.g., 36.8219" value="<?php echo htmlspecialchars($product['location_lng']); ?>">
                            </div>
                        </div>

                        <hr class="mb-4">
                        <div class="d-flex justify-content-end" style="gap: 12px;">
                            <a href="products.php" class="btn btn-light px-4" style="border-radius: 50px; font-weight: 600;">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4" style="background: var(--j-primary); border-color: var(--j-primary); border-radius: 50px; font-weight: 600;">Update Product</button>
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
