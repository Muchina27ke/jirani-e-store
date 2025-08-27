<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Product.php';
require_once __DIR__ . '/includes/Geolocation.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Navigation.php';
require_once __DIR__ . '/includes/product-card.php';

$db = getDbConnection();

$product = new Product($db);
$geolocation = new Geolocation($db);
$auth = new Auth($db);

// Get current user if logged in for navigation
$currentUser = null;
if ($auth->isLoggedIn()) {
    $currentUser = $auth->getUser();
}
// Initialize navigation with current user and current page (e.g., 'home' for index)
$navigation = new Navigation($db, $currentUser, 'home');

// Get user's location if available (from session, set by location.js or similar)
$lat = $_SESSION['user_location']['lat'] ?? null;
$lon = $_SESSION['user_location']['lon'] ?? null;

// Determine which products to display initially
$initialProducts = [];
$sectionTitle = "Fresh From Kakamega";

if ($lat && $lon) {
    // Try to get products near the user's location
    $initialProducts = $geolocation->findProductsNearLocation($lat, $lon, 20); // 20 km radius
    $sectionTitle = "Products Near You";
}

// If no nearby products or no location, get a general set of products
if (empty($initialProducts)) {
    $initialProducts = $product->get_all_products();
    // Shuffle and limit to 12 for initial display
    shuffle($initialProducts);
    $initialProducts = array_slice($initialProducts, 0, 15);
    $sectionTitle = "Fresh From Kakamega"; // Default title if no location-based products
}

// Ensure all elements in $initialProducts are associative arrays
$initialProducts = array_map(function ($item) {
    if (is_object($item)) {
        return (array) $item;
    }
    return $item;
}, $initialProducts);

// Debugging: Log the contents of $initialProducts before the loop
error_log("Initial Products before loop: " . print_r($initialProducts, true));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jirani - E-Store</title>
    <link rel="icon" type="image/jpeg" href="title_logo.jpg">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/hpgIndex.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            /* Primary Color Palette */
            --jirani-primary: #2A5C3D;
            /* Forest Green */
            --jirani-secondary: #FFA726;
            /* Warm Orange */
            --jirani-white: #FFFFFF;
            /* White */
            --jirani-gray: #333333;
            /* Dark Gray */
        }

        /* Animations */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }

        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .logo {
                width: 70px;
            }

            .hero-search {
                width: 100% !important;
            }

            .card-img-top {
                height: 150px;
            }

            h1.display-6 {
                font-size: 2rem;
            }
        }

        /* Base Elements */
        body {
            background-color: var(--jirani-white);
            color: var(--jirani-gray);
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: var(--jirani-white) !important;
        }

        .logo {
            width: 100px;
        }

        /* Cards */
        .card {
            border-color: rgba(42, 92, 61, 0.1);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .card:hover {
            transform: translateY(-5px);
            transition: 0.3s;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Buttons */
        .btn-jirani {
            background-color: var(--jirani-primary);
            border: none;
            color: var(--jirani-white);
        }

        .btn-jirani:hover {
            background-color: var(--jirani-secondary);
            color: var(--jirani-gray);
        }

        /* Badges */
        .local-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--jirani-secondary);
            color: var(--jirani-gray);
        }

        /* Search Suggestions */
        .suggestions-container {
            background-color: var(--jirani-white);
            border: 1px solid var(--jirani-primary);
        }

        .suggestion-item:hover {
            background-color: rgba(42, 92, 61, 0.1);
        }

        /* Footer */
        .site-footer {
            background: linear-gradient(45deg, rgba(42, 92, 61, 0.03), rgba(42, 92, 61, 0.08));
        }

        .hover-jirani-secondary:hover {
            color: var(--jirani-secondary) !important;
        }

        .dropdown-menu-jirani {
            background: var(--jirani-primary);
            border: 2px solid var(--jirani-secondary);
        }

        .dropdown-menu-jirani .dropdown-item {
            color: var(--jirani-white) !important;
            transition: all 0.3s ease;
        }

        .dropdown-menu-jirani .dropdown-item:hover {
            background: var(--jirani-secondary) !important;
            color: var(--jirani-gray) !important;
        }

        .btn-jirani-outline {
            border: 2px solid var(--jirani-primary);
            color: var(--jirani-gray);
            transition: all 0.3s ease;
        }

        .btn-jirani-outline:hover {
            background: var(--jirani-primary);
            color: var(--jirani-white);
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 30px;
            margin: 5px;
            transition: all 0.3s ease;
        }

        .social-link span {
            margin-left: 8px;
            font-size: 0.9rem;
        }

        .social-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 92, 61, 0.1);
        }

        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }

        /* Dropdowns */
        .dropdown-menu {
            background-color: var(--jirani-primary);
            border: 1px solid var(--jirani-secondary);
        }

        .dropdown-item {
            color: var(--jirani-white) !important;
        }

        .dropdown-item:hover {
            background-color: var(--jirani-secondary) !important;
        }

        /* Social Icons */
        .social-icon {
            color: var(--jirani-white) !important;
        }

        .social-icon:hover {
            color: var(--jirani-secondary) !important;
        }

        /* Text Elements */
        .navbar-brand,
        .nav-link {
            color: var(--jirani-primary) !important;
        }

        h1,
        h2,
        h3 {
            color: var(--jirani-primary);
        }

        /* Spinner */
        .spinner-border {
            border-color: var(--jirani-primary);
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <!-- Navbar -->
        <?php echo $navigation->renderCustomerNav(); ?>

        <!-- Hero Section -->
        <section class="py-5" id="hero-root" style="background-color: var(--jirani-light);">
            <div class="container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8">
                        <h1 class="display-4 fw-bold mb-4 animate-on-scroll" style="color: var(--jirani-dark);">
                            Shop Local, Grow Together
                        </h1>
                           <!-- Search bar -->
                    <form class="d-flex me-3" action="<?php echo SITE_URL; ?>search.php" method="GET">
                        <div class="input-group">
                            <input class="form-control" type="search" name="q" placeholder="Search products..."
                                value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                        <p class="lead mb-5 animate-on-scroll" style="color: var(--jirani-medium);">
                            Discover fresh produce and handmade goods from your neighbors
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Products Grid -->
        <main class="container py-5" id="products">
            <h2 class="h4 mb-4 animate-on-scroll"><?php echo $sectionTitle; ?></h2>
            <div class="row g-4">
                <?php
                foreach ($initialProducts as $productData) {
                    $productData = (array) $productData; // Explicitly cast to array
                    renderProductCard($productData);
                }
                renderLoginModal();
                ?>
            </div>

        </main>


        <!-- Local Features Section -->
        <section class="bg-light py-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6 feature-animate">
                        <h2 class="h4 mb-4">Why Shop with Jirani?</h2>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="fa-solid fa-truck-fast text-jirani-green me-2"></i>
                                Same-day local delivery
                            </li>
                            <li class="mb-3">
                                <i class="fa-solid fa-phone text-jirani-green me-2"></i>
                                M-Pesa & cash payments
                            </li>
                            <li class="mb-3">
                                <i class="fa-solid fa-users text-jirani-green me-2"></i>
                                Directly support local economy
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6 feature-animate">
                        <img src="./Images/kakamega.jpeg" alt="Kakamega Local Market" class="img-fluid rounded-3">
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <?php
        // Define base URL at the top of your page
        $base_url = './'; // Adjust based on your directory structure
        include 'footer.php';
        ?>
    </div>



    <script>
        // Intersection Observer for scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        // Animate product cards and sections
        document.querySelectorAll('.animate-on-scroll').forEach((element) => {
            observer.observe(element);
        });

        // Add animation to feature section
        document.querySelectorAll('.feature-animate').forEach((element) => {
            observer.observe(element);
        });
    </script>



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Product Actions JS -->
    <script src="assets/js/product-actions.js"></script>
</body>

</html>