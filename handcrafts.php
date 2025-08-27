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
$navigation = new Navigation($db, $currentUser, 'handcrafts');
require_once __DIR__ . '/includes/Product.php';
require_once __DIR__ . '/includes/Geolocation.php';
require_once __DIR__ . '/includes/product-card.php';

// Initialize services
$product = new Product($db);
$geolocation = new Geolocation($db);

// Get category ID for handcrafts
$stmt = $db->prepare("SELECT id FROM categories WHERE name = 'Handcrafts' AND status = 'active'");
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();

if (!$category) {
  die("Handcrafts category not found");
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
  <title>Handcrafted Goods | Jirani Market</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= $base_url ?>css/footer.css">
  <style>
    :root {
      /* Primary Color Palette */
      --jirani-primary: #2A5C3D;
      --jirani-secondary: #FFA726;
      --jirani-white: #FFFFFF;
      --jirani-gray: #333333;
      --jirani-dark: #06402B;
      --jirani-light: #f8f9fa;
      --jirani-text: #444;
    }

    /* Base Elements */
    body {
      background-color: var(--jirani-white) !important;
      color: var(--jirani-gray) !important;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .navbar {
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
      background-color: var(--jirani-white) !important;
    }

    /* Text Elements */
    .navbar .navbar-brand,
    .navbar .nav-link {
      color: var(--jirani-primary) !important;
      font-weight: 500;
    }

    h1, h2, h3, h4, h5, h6 {
      color: var(--jirani-primary);
      font-family: 'Georgia', serif;
    }

    /* Buttons */
    .btn-jirani {
      background-color: var(--jirani-primary);
      border: none;
      color: var(--jirani-white);
      font-weight: 500;
    }

    .btn-jirani:hover {
      background-color: var(--jirani-secondary);
      color: var(--jirani-gray);
    }

    /* Cards */
    .handicraft-card {
      border: none;
      border-radius: 12px;
      transition: all 0.3s ease;
      background: white;
      overflow: hidden;
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .handicraft-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .card-img-container {
      height: 280px;
      overflow: hidden;
    }

    .craft-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .handicraft-card:hover .craft-image {
      transform: scale(1.05);
    }

    .card-body {
      padding: 1.5rem;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .local-badge {
      background: var(--jirani-primary);
      color: white;
      padding: 0.3rem 1rem;
      border-radius: 20px;
      font-size: 0.9rem;
      display: inline-block;
      margin-bottom: 0.8rem;
      align-self: flex-start;
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
      margin-top: auto;
    }

    .btn-cart:hover {
  background: var(--jirani-primary); /* Slightly lighter green on hover */
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Added subtle shadow on hover */
}
    .craft-description {
      color: var(--jirani-text);
      line-height: 1.6;
      margin-bottom: 1rem;
      flex-grow: 1;
    }

    .search-box {
      border: 2px solid var(--jirani-secondary);
      border-radius: 2rem;
      padding: 1rem 2rem;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      margin: 2rem auto;
      max-width: 800px;
      width: 100%;
    }

    .search-box:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(255, 167, 38, 0.3);
      border-color: var(--jirani-primary);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .card-img-container {
        height: 200px;
      }
      
      .craft-description {
        min-height: auto;
      }
    }
  </style>
</head>

<body class="bg-light">

<?php echo $navigation->renderCustomerNav(); ?>

<main class="py-5">
  <div class="container">
    <h1 class="display-5 fw-bold text-center mb-5" style="color: var(--jirani-dark);">
      Handcrafted Local Goods
    </h1>


    <div class="row g-4" id="craftGrid">
      <?php
        if (empty($products)) {
          echo '<div class="col-12 text-center"><p>No handcrafts available at the moment.</p></div>';
        } else {
          foreach ($products as $craft) {
            renderProductCard($craft);
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
<script src="<?= $base_url ?>assets/js/product-actions.js"></script>

<script>
  // Wait for DOM to be fully loaded
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput'); // Make sure you have this element
    let timeout;
    
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(filterHandicrafts, 300);
      });
    }
    
    if (typeof filterHandicrafts === 'function') {
      filterHandicrafts();
    }
  });
</script>
</body>

</html>