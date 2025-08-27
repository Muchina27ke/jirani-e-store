<?php
/**
 * Unified Navigation Component for Jirani Platform
 * Provides consistent navigation across customer, admin, and vendor interfaces
 */

class Navigation
{
    private $conn;
    private $currentUser;
    private $currentPage;

    public function __construct($conn, $currentUser = null, $currentPage = '')
    {
        $this->conn = $conn;
        $this->currentUser = $currentUser;
        $this->currentPage = $currentPage;
    }

    /**
     * Render customer navigation bar
     */
    public function renderCustomerNav()
    {
        $cartCount = $this->getCartCount();
        $wishlistCount = $this->getWishlistCount();

        ob_start();
        ?>
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
            <div class="container">
                <!-- Brand -->
                <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>">
                    <img src="<?php echo SITE_URL; ?>title_logo.jpg" alt="Jirani" height="40" class="me-2">
                    <span class="fw-bold text-primary fs-4">Jirani</span>
                </a>

                <!-- Mobile toggle -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation items -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Left side navigation -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $this->isActive('home'); ?>" href="<?php echo SITE_URL; ?>">
                                <i class="fas fa-home me-1"></i>Home
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button"
                                data-bs-toggle="dropdown">
                                <i class="fas fa-th-large me-1"></i>Categories
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>fruits.php">
                                        <i class="fas fa-apple-alt me-2"></i>Fruits
                                    </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>vegetables.php">
                                        <i class="fas fa-carrot me-2"></i>Vegetables
                                    </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>handcrafts.php">
                                        <i class="fas fa-palette me-2"></i>Handcrafts
                                    </a></li>
                               
                               
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $this->isActive('about'); ?>" href="<?php echo SITE_URL; ?>about.php">
                                <i class="fas fa-info-circle me-1"></i>About
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $this->isActive('contact'); ?>"
                                href="<?php echo SITE_URL; ?>contact_us.php">
                                <i class="fas fa-envelope me-1"></i>Contact
                            </a>
                        </li>
                    </ul>

                 

                    <!-- Right side navigation -->
                    <ul class="navbar-nav">
                        <!-- Location -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="locationDropdown" role="button"
                                data-bs-toggle="dropdown">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <span id="currentLocation">Detect Location</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 300px;">
                                <h6 class="dropdown-header">Your Location</h6>
                                <div class="mb-2">
                                    <button class="btn btn-sm btn-outline-primary w-100" onclick="detectLocation()">
                                        <i class="fas fa-crosshairs me-1"></i>Use Current Location
                                    </button>
                                </div>
                                <div class="mb-2">
                                    <input type="text" class="form-control form-control-sm" placeholder="Enter your address..."
                                        id="manualLocation">
                                </div>
                                <button class="btn btn-sm btn-primary w-100" onclick="setLocation()">
                                    Set Location
                                </button>
                            </div>
                        </li>

                        <!-- Wishlist -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>wishlist.php">
                                <i class="fas fa-heart"></i>
                                <?php if ($wishlistCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $wishlistCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <!-- Cart -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>cart.php">
                                <i class="fas fa-shopping-cart"></i>
                                <?php if ($cartCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                        <?php echo $cartCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <!-- User menu -->
                        <?php if ($this->currentUser): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                    data-bs-toggle="dropdown">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($this->currentUser['name']); ?>&background=007bff&color=fff"
                                        class="rounded-circle me-1" width="24" height="24" alt="User">
                                    <?php echo htmlspecialchars($this->currentUser['name']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php if ($this->currentUser['role'] !== 'customer'): ?>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>dashboard.php">
                                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                            </a></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>orders.php">
                                            <i class="fas fa-shopping-bag me-2"></i>My Orders
                                        </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>profile.php">
                                            <i class="fas fa-user me-2"></i>Profile
                                        </a></li>
                                    <?php if ($this->currentUser['role'] === 'vendor'): ?>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>seller/">
                                                <i class="fas fa-store me-2"></i>Vendor Dashboard
                                            </a></li>
                                    <?php endif; ?>
                                    <?php if ($this->currentUser['role'] === 'admin'): ?>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/">
                                                <i class="fas fa-cog me-2"></i>Admin Panel
                                            </a></li>
                                    <?php endif; ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>auth.php?action=logout">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>login.php">
                                    <i class="fas fa-sign-in-alt me-1"></i>Login
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>Register/">
                                    <i class="fas fa-user-plus me-1"></i>Sign Up
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Location detection script -->
        <script>
            function detectLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        // Reverse geocoding to get address
                        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                            .then(response => response.json())
                            .then(data => {
                                const address = data.display_name;
                                document.getElementById('currentLocation').textContent = address.substring(0, 30) + '...';

                                // Save location to session
                                fetch('<?php echo SITE_URL; ?>api/save_location.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ lat: lat, lng: lng, address: address })
                                });
                            });
                    });
                }
            }

            function setLocation() {
                const manualLocation = document.getElementById('manualLocation').value;
                if (manualLocation) {
                    document.getElementById('currentLocation').textContent = manualLocation.substring(0, 30) + '...';

                    // Geocode the address
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(manualLocation)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                const lat = data[0].lat;
                                const lng = data[0].lon;

                                fetch('<?php echo SITE_URL; ?>api/save_location.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ lat: lat, lng: lng, address: manualLocation })
                                });
                            }
                        });
                }
            }

            // Load saved location on page load
            document.addEventListener('DOMContentLoaded', function () {
                const savedLocation = localStorage.getItem('userLocation');
                if (savedLocation) {
                    const location = JSON.parse(savedLocation);
                    document.getElementById('currentLocation').textContent = location.address.substring(0, 30) + '...';
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if current page is active
     */
    private function isActive($page)
    {
        return $this->currentPage === $page ? 'active' : '';
    }

    /**
     * Get cart item count for current user
     */
    private function getCartCount()
    {
        if (!$this->currentUser)
            return 0;

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM cart 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $this->currentUser['id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['count'] ?? 0;
    }

    /**
     * Get wishlist item count for current user
     */
    private function getWishlistCount()
    {
        if (!$this->currentUser)
            return 0;

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM wishlist 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $this->currentUser['id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['count'] ?? 0;
    }
}

/**
 * Unified Footer Component
 */
class Footer
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Render unified footer
     */
    public function render()
    {
        ob_start();
        ?>
        <footer class="bg-dark text-light py-5 mt-5">
            <div class="container">
                <div class="row">
                    <!-- Company Info -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo SITE_URL; ?>title_logo.jpg" alt="Jirani" height="40" class="me-2">
                            <h5 class="mb-0 text-primary">Jirani</h5>
                        </div>
                        <p class="text-muted">
                            Your trusted local marketplace connecting communities through commerce.
                            Discover fresh products from verified vendors in your neighborhood.
                        </p>
                        <div class="d-flex gap-3">
                            <a href="#" class="text-light fs-5"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="text-light fs-5"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-light fs-5"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="text-light fs-5"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h6 class="text-primary mb-3">Quick Links</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>" class="text-muted text-decoration-none">Home</a>
                            </li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>about.php"
                                    class="text-muted text-decoration-none">About Us</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>categories.php"
                                    class="text-muted text-decoration-none">Categories</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>../../../Register/index.php"
                                    class="text-muted text-decoration-none">Vendors</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>contact_us.php"
                                    class="text-muted text-decoration-none">Contact</a></li>
                        </ul>
                    </div>

                    <!-- Categories -->
                    <div class="col-lg-2 col-md-2 mb-2">
                        <h6 class="text-primary mb-3">Categories</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>fruits.php"
                                    class="text-muted text-decoration-none">Fruits</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>vegetables.php"
                                    class="text-muted text-decoration-none">Vegetables</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>handcrafts.php"
                                    class="text-muted text-decoration-none">Handcrafts</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>electronics.php"
                                    class="text-muted text-decoration-none">Electronics</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>fashion.php"
                                    class="text-muted text-decoration-none">Fashion</a></li>
                        </ul>
                    </div>

                    <!-- Support -->
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h6 class="text-primary mb-3">Support</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>help.php"
                                    class="text-muted text-decoration-none">Help Center</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>faq.php"
                                    class="text-muted text-decoration-none">FAQ</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>shipping.php"
                                    class="text-muted text-decoration-none">Shipping Info</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>returns.php"
                                    class="text-muted text-decoration-none">Returns</a></li>
                            <li class="mb-2"><a href="<?php echo SITE_URL; ?>track-order.php"
                                    class="text-muted text-decoration-none">Track Order</a></li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h6 class="text-primary mb-3">Contact Info</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2 text-muted">
                                <i class="fas fa-phone me-2"></i>
                                +254 700 000 000
                            </li>
                            <li class="mb-2 text-muted">
                                <i class="fas fa-envelope me-2"></i>
                                support@jirani.com
                            </li>
                            <li class="mb-2 text-muted">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                Nairobi, Kenya
                            </li>
                            <li class="mb-2 text-muted">
                                <i class="fas fa-clock me-2"></i>
                                24/7 Support
                            </li>
                        </ul>
                    </div>
                </div>

                <hr class="my-4 border-secondary">

                <!-- Bottom Footer -->
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0 text-muted">
                            © <?php echo date('Y'); ?> Jirani. All rights reserved.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href="<?php echo SITE_URL; ?>privacy.php" class="text-muted text-decoration-none">Privacy
                                    Policy</a>
                            </li>
                            <li class="list-inline-item">
                                <a href="<?php echo SITE_URL; ?>terms.php" class="text-muted text-decoration-none">Terms of
                                    Service</a>
                            </li>
                            <li class="list-inline-item">
                                <a href="<?php echo SITE_URL; ?>cookies.php" class="text-muted text-decoration-none">Cookie
                                    Policy</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Back to top button -->
        <button class="btn btn-primary position-fixed bottom-0 end-0 m-3 rounded-circle" id="backToTop"
            style="display: none; z-index: 1000;" onclick="scrollToTop()">
            <i class="fas fa-arrow-up"></i>
        </button>

        <script>
            // Back to top functionality
            window.addEventListener('scroll', function () {
                const backToTop = document.getElementById('backToTop');
                if (window.pageYOffset > 300) {
                    backToTop.style.display = 'block';
                } else {
                    backToTop.style.display = 'none';
                }
            });

            function scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>