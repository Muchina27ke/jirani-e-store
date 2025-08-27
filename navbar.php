<?php
require_once __DIR__ . '/config/config.php';

$db = getDbConnection();

require_once __DIR__ . '/includes/Navigation.php';
require_once __DIR__ . '/includes/Auth.php';

// Get current user if logged in
$auth = new Auth($db);
$currentUser = null;
if ($auth->isLoggedIn()) {
    $currentUser = $auth->getUser();
}

// Initialize navigation
$navigation = new Navigation($db, $currentUser, $currentPage ?? '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Jirani - Local Marketplace'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="css/hpgIndex.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Additional CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        /* Enhanced navbar styles */
        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, .08);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 6px;
            margin: 0 2px;
        }

        .nav-link:hover {
            background-color: rgba(0, 123, 255, 0.1);
            color: 0 2px 4px rgba(0, 123, 255, 0.3) !important;
        }

        .nav-link.active {
            background-color: 0 2px 4px rgba(0, 123, 255, 0.3);
            color: #ffffff !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
            border-radius: 8px;
        }

        .dropdown-item {
            padding: 8px 16px;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: 0 2px 4px rgba(0, 123, 255, 0.3);
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.25em 0.5em;
        }

        .btn-outline-primary {
            border-width: 2px;
            font-weight: 600;
        }

        .btn-primary {
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
        }

        .input-group .form-control {
            border-right: none;
        }

        .input-group .btn {
            border-left: none;
        }

        /* Product card enhancements */
        .hover-card {
            transition: all 0.3s ease;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border-color: 0 2px 4px rgba(0, 123, 255, 0.3);
        }

        .product-image {
            border-radius: 8px;
            object-fit: cover;
        }

        .price-tag {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
        }

        /* Loading spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        /* Alert enhancements */
        .alert {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
        }

        /* Footer enhancements */
        footer {
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem;
            }

            .nav-link {
                margin: 2px 0;
            }

            .hover-card:hover {
                transform: none;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php echo $navigation->renderCustomerNav(); ?>

    <!-- Main Content -->
    <main>
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="container mt-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="container mt-3">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php
                    echo $_SESSION['warning'];
                    unset($_SESSION['warning']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['info'])): ?>
            <div class="container mt-3">
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php
                    echo $_SESSION['info'];
                    unset($_SESSION['info']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Content -->
        <?php echo $content ?? ''; ?>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Custom JS -->
    <script src="js/location.js"></script>
    <script src="assets/js/payment.js"></script>

    <!-- Additional JS -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Page-specific scripts -->
    <?php if (isset($pageScripts)): ?>
        <script>
            <?php echo $pageScripts; ?>
        </script>
    <?php endif; ?>

    <script>
        // Enhanced cart functionality
        function addToCart(productId, quantity = 1) {
            const btn = event.target;
            const originalText = btn.innerHTML;

            // Show loading state
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';
            btn.disabled = true;

            fetch('api/cart/add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count in navbar
                        updateCartCount();

                        // Show success message
                        showToast('Product added to cart!', 'success');

                        // Reset button
                        btn.innerHTML = '<i class="fas fa-check me-1"></i>Added!';
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-success');

                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-primary');
                            btn.disabled = false;
                        }, 2000);
                    } else {
                        showToast(data.message || 'Failed to add to cart', 'error');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    showToast('An error occurred', 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }

        function addToWishlist(productId) {
            const btn = event.target;
            const icon = btn.querySelector('i');

            fetch('api/wishlist/add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        btn.classList.add('text-danger');
                        showToast('Added to wishlist!', 'success');
                    } else {
                        showToast(data.message || 'Failed to add to wishlist', 'error');
                    }
                });
        }

        function updateCartCount() {
            fetch('api/cart/count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.querySelector('.nav-link .badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.count;
                        if (data.count > 0) {
                            cartBadge.style.display = 'inline';
                        }
                    }
                });
        }

        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();

            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;

            toastContainer.appendChild(toast);

            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            // Remove toast element after it's hidden
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1055';
            document.body.appendChild(container);
            return container;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            updateCartCount();

            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>

</html>