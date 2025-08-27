<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Navigation.php';
$db = getDbConnection();
$auth = new Auth($db);
$currentUser = null;
if ($auth->isLoggedIn()) {
    $currentUser = $auth->getUser();
}
$navigation = new Navigation($db, $currentUser, 'vegetables');
require_once __DIR__ . '/includes/Product.php';
require_once __DIR__ . '/includes/Geolocation.php';
require_once __DIR__ . '/includes/product-card.php';

// Initialize services
$product = new Product($db);
$geolocation = new Geolocation($db);

// Get category ID for vegetables
$stmt = $db->prepare("SELECT id FROM categories WHERE name = 'Vegetables' AND status = 'active'");
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();

if (!$category) {
  die("Vegetables category not found");
}

// Get user's location if available
$lat = $_SESSION['user_location']['lat'] ?? null;
$lon = $_SESSION['user_location']['lon'] ?? null;

// Get products
$products = $product->getProductsByCategory($category['id'], $lat, $lon);

  $base_url = './';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fresh Vegetables | Jirani Market</title>
  <<!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Your Custom CSS -->
  <link rel="stylesheet" href="<?= $base_url ?>css/footer.css">
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
      --jirani-dark: #06402B;
      --jirani-light: #f8f9fa;
      --jirani-text: #444;
    }

    /* Base Elements */
    body {
      background-color: var(--jirani-white) !important;
      color: var(--jirani-gray) !important;
    }

    .navbar {
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      background-color: var(--jirani-white) !important;
      
    }
    .navbar .navbar-brand,
    .navbar .nav-link {
      color: var(--jirani-primary) !important;
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

    .vegetable-card {
      border: none;
      border-radius: 12px;
      transition: transform 0.3s ease;
      background: white;
      overflow: hidden;
    }

    .vegetable-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    .vegetable-image {
      height: 280px;
      object-fit: cover;
      border-bottom: 3px solid var(--jirani-primary);
    }

    .card-body {
      padding: 1.5rem;
    }

    .local-badge {
      background: var(--jirani-primary);
      color: white;
      padding: 0.3rem 1rem;
      border-radius: 20px;
      font-size: 0.9rem;
      display: inline-block;
      margin-bottom: 0.8rem;
    }

    .vendor-info {
      color: var(--jirani-dark);
      font-weight: 500;
      margin: 1rem 0;
      padding: 0.5rem 0;
      border-top: 1px solid #eee;
      border-bottom: 1px solid #eee;
    }

    .price-section {
      font-size: 1.25rem;
      color: var(--jirani-primary);
      font-weight: 600;
      margin: 1rem 0;
    }

    .btn-cart {
     background: var(--jirani-dark); 
      color: white;
      border: none;
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      width: 100%;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

   .btn-cart:hover {
  background: var(--jirani-primary); /* Slightly lighter green on hover */
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Added subtle shadow on hover */
}

    .vegetable-description {
      color: var(--jirani-text);
      line-height: 1.6;
      min-height: 60px;
      margin-bottom: 1rem;
    }

    .search-box {
      border: 2px solid var(--jirani-secondary);
      border-radius: 2rem;
      padding: 1rem 2rem;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      margin: 2rem auto;
      max-width: 800px;
    }
  </style>
</head>

<body class="bg-light">

<?php echo $navigation->renderCustomerNav(); ?>

<main class="py-5">
  <div class="container">
    <h1 class="display-5 fw-bold text-center mb-5" style="color: var(--jirani-dark);">
      Fresh Local Vegetables
    </h1>


    <div class="row g-4" id="vegetableGrid">
      <?php
        if (empty($products)) {
          echo '<div class="col-12 text-center"><p>No vegetables available at the moment.</p></div>';
        } else {
          foreach ($products as $vege) {
            renderProductCard($vege);
          }
        }
        renderLoginModal();
      ?>
    </div>
  </div>
</main>

<?php include 'footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Product Actions JS -->
<script src="assets/js/product-actions.js"></script>

<script>
  let timeout;
  searchInput.addEventListener('input', () => {
    clearTimeout(timeout);
    timeout = setTimeout(filterVegetables, 300);
  });

  filterVegetables();
</script>
</body>

</html>