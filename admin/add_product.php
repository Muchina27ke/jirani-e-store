<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Product.php';

$pageTitle = 'Add Product';
$currentPage = 'products';
ob_start();

// Ensure only admins can access this page
$conn = getDbConnection();
$auth = new Auth($conn);
$auth->requireRole('admin');

$productObj = new Product($conn);
$editMode = false;
$product = null;
$productId = null;

// Check if we're in edit mode
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $productId = (int) $_GET['edit'];
    $product = $productObj->get_product_by_id($productId);
    if ($product) {
        $editMode = true;
        $pageTitle = 'Edit Product';
    } else {
        $_SESSION['error'] = "Product not found.";
        header('Location: products.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    try {
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $vendorId = (int) ($_POST['vendor_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $location_lat = !empty($_POST['location_lat']) ? (float) $_POST['location_lat'] : null;
        $location_lng = !empty($_POST['location_lng']) ? (float) $_POST['location_lng'] : null;

        // Validation
        if (empty($name)) {
            throw new Exception("Product name is required.");
        }
        if ($vendorId <= 0) {
            throw new Exception("Please select a valid vendor.");
        }
        // Check if vendor exists and is active/approved
        $stmt = $conn->prepare("SELECT status FROM vendors WHERE user_id = ?");
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Error: Vendor with ID {$vendorId} does not exist.");
        }
        $vendor_status = $result->fetch_assoc()['status'];
        if ($vendor_status !== 'approved') {
            throw new Exception("Error: Vendor with ID {$vendorId} exists but is not active/approved. Status: {$vendor_status}");
        }

        if ($categoryId <= 0) {
            throw new Exception("Please select a valid category.");
        }
        if ($price <= 0) {
            throw new Exception("Price must be greater than zero.");
        }
        if ($stock < 0) {
            throw new Exception("Stock cannot be negative.");
        }
        if (!in_array($status, ['active', 'inactive', 'out_of_stock'])) {
            throw new Exception("Invalid status.");
        }

        // Handle image upload
        $imageUrl = $product['image_url'] ?? null; // Keep existing image if editing
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                throw new Exception("Invalid image format. Only JPG, PNG, GIF, and WebP are allowed.");
            }

            if ($_FILES['image']['size'] > $maxSize) {
                throw new Exception("Image size exceeds 5MB limit.");
            }

            $uploadDir = '../uploads/products/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $targetPath = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                throw new Exception("Failed to upload image.");
            }

            $imageUrl = 'uploads/products/' . $filename;
        }

        if ($editMode) {
            // Update existing product
            $updateData = [
                'name' => $name,
                'vendor_id' => $vendorId,
                'category_id' => $categoryId,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'status' => $status,
                'location_lat' => $location_lat,
                'location_lng' => $location_lng
            ];

            if ($imageUrl) {
                $updateData['image_url'] = $imageUrl;
            }

            if ($productObj->update_product($productId, $updateData)) {
                // Log the action
                $productObj->log_product_action($productId, 'update', [
                    'admin_id' => $_SESSION['user_id'],
                    'changes' => $updateData
                ]);

                $_SESSION['success'] = "Product updated successfully.";
            } else {
                throw new Exception("Failed to update product.");
            }
        } else {
            // Create new product
            if ($productObj->add_product($vendorId, $name, $description, $price, $stock, $location_lat, $location_lng)) {
                $newProductId = $conn->insert_id;

                // Update category and status
                $stmt = $conn->prepare("UPDATE products SET category_id = ?, status = ?, image_url = ? WHERE id = ?");
                $stmt->bind_param("issi", $categoryId, $status, $imageUrl, $newProductId);
                $stmt->execute();

                // Log the action
                $productObj->log_product_action($newProductId, 'create', [
                    'admin_id' => $_SESSION['user_id']
                ]);

                $_SESSION['success'] = "Product added successfully.";
            } else {
                throw new Exception("Failed to add product.");
            }
        }

        header('Location: products.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get vendors for dropdown
$vendors_stmt = $conn->prepare("
    SELECT v.user_id, v.business_name, u.name as owner_name 
    FROM vendors v 
    JOIN users u ON v.user_id = u.id 
    WHERE v.status = 'approved' 
    ORDER BY v.business_name
");
$vendors_stmt->execute();
$vendors = $vendors_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories for dropdown
$categories_stmt = $conn->prepare("
    SELECT id, name, description 
    FROM categories 
    WHERE status = 'active' 
    ORDER BY name
");
$categories_stmt->execute();
$categories = $categories_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!-- Enhanced JavaScript for form handling -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function
        () {
        // Initialize form validation
        initializeFormValidation();

        // Initialize image preview
        initializeImagePreview();

        // Initialize location picker
        initializeLocationPicker();

        // Initialize price formatting
        initializePriceFormatting();
    });

    function initializeFormValidation() {
        $('#productForm').on('submit', function (e) {
            e.preventDefault();

            if (validateForm()) {
                showLoadingState();
                this.submit();
            }
        });

        // Real-time validation
        $('#name, #price, #stock').on('input', function () {
            validateField($(this));
        });
    }

    function validateForm() {
        let isValid = true;

        // Validate required fields
        const requiredFields = ['name', 'vendor_id', 'category_id', 'price', 'stock'];
        requiredFields.forEach(field => {
            if (!validateField($('#' + field))) {
                isValid = false;
            }
        });

        return isValid;
    }

    function validateField(field) {
        const value = field.val().trim();
        const fieldName = field.attr('id');
        const errorDiv = field.siblings('.error-message');

        // Remove existing error styling
        field.removeClass('is-invalid');
        errorDiv.remove();

        let isValid = true;
        let errorMessage = '';

        switch (fieldName) {
            case 'name':
                if (value.length < 3) {
                    errorMessage = 'Product name must be at least 3 characters long.';
                    isValid = false;
                }
                break;
            case 'price':
                if (value <= 0) {
                    errorMessage = 'Price must be greater than zero.';
                    isValid = false;
                }
                break;
            case 'stock':
                if (value < 0) {
                    errorMessage = 'Stock cannot be negative.';
                    isValid = false;
                }
                break;
            case 'vendor_id':
            case 'category_id':
                if (value === '' || value === '0') {
                    errorMessage = 'This field is required.';
                    isValid = false;
                }
                break;
        }

        if (!isValid) {
            field.addClass('is-invalid');
            field.after('<div class="error-message text-danger small">' + errorMessage + '</div>');
        }

        return isValid;
    }

    function initializeImagePreview() {
        $('#image').on('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    $('#imagePreview').attr('src', e.target.result).show();
                    $('#imagePreviewContainer').show();
                };
                reader.readAsDataURL(file);
            }
        });
    }

    function initializeLocationPicker() {
        // Initialize map if location fields are present
        if (typeof L !== 'undefined') {
            const map = L.map('locationMap').setView([-1.2921, 36.8219], 10); // Nairobi coordinates

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            const marker = L.marker([-1.2921, 36.8219], { draggable: true }).addTo(map);

            marker.on('dragend', function (e) {
                const lat = e.target.getLatLng().lat;
                const lng = e.target.getLatLng().lng;
                $('#location_lat').val(lat);
                $('#location_lng').val(lng);
            });

            // Update marker when coordinates are manually entered
            $('#location_lat, #location_lng').on('change', function () {
                const lat = parseFloat($('#location_lat').val());
                const lng = parseFloat($('#location_lng').val());
                if (!isNaN(lat) && !isNaN(lng)) {
                    marker.setLatLng([lat, lng]);
                    map.setView([lat, lng]);
                }
            });
        }
    }

    function initializePriceFormatting() {
        $('#price').on('input', function () {
            let value = $(this).val().replace(/[^\d.]/g, '');
            if (value) {
                value = parseFloat(value).toFixed(2);
                $(this).val(value);
            }
        });
    }

    function showLoadingState() {
        Swal.fire({
            title: 'Processing...',
            html: 'Please wait while we save the product.',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
    }

    function deleteProduct() {
        Swal.fire({
            title: 'Delete Product?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'products.php?delete=<?php echo $productId; ?>';
            }
        });
    }

    function previewProduct() {
        const formData = new FormData($('#productForm')[0]);

        $.ajax({
            url: 'api/product_actions.php?action=preview',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    showProductPreview(response.data);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Failed to generate preview', 'error');
            }
        });
    }

    function showProductPreview(product) {
        const html = `
        <div class="text-left">
            <div class="row">
                <div class="col-md-6">
                    <h6>Product Information</h6>
                    <p><strong>Name:</strong> ${product.name}</p>
                    <p><strong>Price:</strong> KSh ${parseFloat(product.price).toLocaleString()}</p>
                    <p><strong>Stock:</strong> ${product.stock} units</p>
                    <p><strong>Status:</strong> ${product.status}</p>
                </div>
                <div class="col-md-6">
                    <h6>Details</h6>
                    <p><strong>Vendor:</strong> ${product.vendor_name}</p>
                    <p><strong>Category:</strong> ${product.category_name}</p>
                    <p><strong>Description:</strong> ${product.description}</p>
                </div>
            </div>
        </div>
    `;

        Swal.fire({
            title: 'Product Preview',
            html: html,
            width: '800px',
            confirmButtonText: 'Close'
        });
    }
</script>
<section class="content">
    <div class="container-fluid">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <i class="fas fa-check mr-2"></i>
                <?php echo $_SESSION['success']; ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $_SESSION['error']; ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row">
            <div class="col-md-8">
        <!-- Product Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-box mr-2"></i>
                    Product Information
                </h3>
            </div>
            <div class="card-body">
                <form id="productForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $editMode ? 'edit' : 'add'; ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>"
                                    placeholder="Enter product name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vendor_id">Vendor *</label>
                                <select class="form-control" id="vendor_id" name="vendor_id" required>
                                    <option value="">Select Vendor</option>
                                    <?php foreach ($vendors as $vendor): ?>
                                        <option value="<?php echo $vendor['user_id']; ?>" <?php echo ($product['vendor_id'] ?? '') == $vendor['user_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vendor['business_name']); ?>
                                                        (<?php echo htmlspecialchars($vendor['owner_name']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category_id">Category *</label>
                                            <select class="form-control" id="category_id" name="category_id" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                        <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" <?php echo ($product['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="out_of_stock" <?php echo ($product['status'] ?? '') === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price">Price (KSh) *</label>
                                <input type="number" class="form-control" id="price" name="price"
                                    value="<?php echo $product['price'] ?? ''; ?>" placeholder="0.00" step="0.01"
                                    min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="stock">Stock Quantity *</label>
                                <input type="number" class="form-control" id="stock" name="stock"
                                    value="<?php echo $product['stock'] ?? ''; ?>" placeholder="0" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"
                            placeholder="Enter product description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="image">Product Image</label>
                                <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                                <small class="form-text text-muted">
                                    Supported formats: JPG, PNG, GIF, WebP (max 5MB)
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="imagePreviewContainer" class="form-group"
                                style="display: <?php echo $product['image_url'] ? 'block' : 'none'; ?>;">
                                <label>Image Preview</label>
                                <div class="mt-2">
                                    <img id="imagePreview"
                                                    src=" <?php echo $product['image_url'] ? '../' . $product['image_url'] : ''; ?>"
                                    class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="location_lat">Latitude</label>
                                <input type="number" class="form-control" id="location_lat" name="location_lat"
                                    value="<?php echo $product['location_lat'] ?? ''; ?>" placeholder="e.g., -1.2921"
                                    step="any">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="location_lng">Longitude</label>
                                <input type="number" class="form-control" id="location_lng" name="location_lng"
                                    value="<?php echo $product['location_lng'] ?? ''; ?>" placeholder="e.g., 36.8219"
                                    step="any">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div id="locationMap" style="height: 300px; width: 100%;"></div>
                        <small class="form-text text-muted">
                            Drag the marker to set the product location
                        </small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo $editMode ? 'Update Product' : 'Add Product'; ?>
                        </button>
                        <button type="button" class="btn btn-info" onclick="previewProduct()">
                            <i class="fas fa-eye mr-2"></i> Preview
                        </button>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </a>
                        <?php if ($editMode): ?>
                            <button type="button" class="btn btn-danger float-right" onclick="deleteProduct()">
                                <i class="fas fa-trash mr-2"></i> Delete Product
                                        </button>
                            <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Quick Stats
                </h3>
            </div>
            <div class="card-body">
                <div class="info-box">
                    <span class="info-box-icon bg-info">
                        <i class="fas fa-box"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Products</span>
                        <span class="info-box-number" id="totalProducts">Loading...</span>
                    </div>
                </div>

                <div class="info-box">
                    <span class="info-box-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Active Products</span>
                        <span class="info-box-number" id="activeProducts">Loading...</span>
                    </div>
                </div>

                <div class="info-box">
                    <span class="info-box-icon bg-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Out of Stock</span>
                        <span class="info-box-number" id="outOfStock">Loading...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Products -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock mr-2"></i>
                    Recent Products
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentProducts">
                            <tr>
                                <td colspan="3" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
</section>


<!-- Leaflet CSS for map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

<!-- Leaflet JS for map -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<script>
    // Load statistics and recent products
    $(document).ready(function () {
        loadStatistics();
        loadRecentProducts();
    });

    function loadStatistics() {
        $.ajax({
            url: 'api/product_actions.php?action=statistics',
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    $('#totalProducts').text(response.data.total_products.toLocaleString());
                    $('#activeProducts').text(response.data.active_products.toLocaleString());
                    $('#outOfStock').text(response.data.out_of_stock.toLocaleString());
                }
            }
        });
    }

    function loadRecentProducts() {
        $.ajax({
            url: 'api/product_actions.php?action=export',
            method: 'GET',
            data: { limit: 5 },
            success: function (response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function (product) {
                        html += `
                        <tr>
                            <td>
                                <div class="font-weight-bold">${product.name}</div>
                                <small class="text-muted">${product.vendor_name}</small>
                            </td>
                            <td>KSh ${parseFloat(product.price).toLocaleString()}</td>
                            <td>
                                <span class="badge badge-${product.status === 'active' ? 'success' : product.status === 'inactive' ? 'secondary' : 'danger'}">
                                    ${product.status}
                                </span>
                            </td>
                        </tr>
                    `;
                    });
                    $('#recentProducts').html(html);
                } else {
                    $('#recentProducts').html('<tr><td colspan="3" class="text-center text-muted">No recent products</td></tr>');
                }
            }
        });
    }
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>