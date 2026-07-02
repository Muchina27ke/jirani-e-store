<?php
require_once '../config/config.php';

// Get filters from URL
$categorySlug = $_GET['category'] ?? null;
$minPrice     = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice     = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$brands       = isset($_GET['brands']) ? explode(',', $_GET['brands']) : [];
$sortBy       = $_GET['sort'] ?? 'featured';

// Fetch categories with product counts
$pdo = getPDO();
$categoriesStmt = $pdo->query("
  SELECT c.*, COUNT(p.id) as product_count
  FROM categories c
  LEFT JOIN products p ON c.id = p.category_id
  GROUP BY c.id
  ORDER BY c.name
");
$categories = $categoriesStmt->fetchAll();

// Build product query
$sql = "SELECT p.*, c.name as category_name FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE 1=1";
$params = [];

if ($categorySlug) {
  $sql .= " AND c.slug = :category_slug";
  $params[':category_slug'] = $categorySlug;
}

if ($minPrice !== null) {
  $sql .= " AND p.price >= :min_price";
  $params[':min_price'] = $minPrice;
}

if ($maxPrice !== null) {
  $sql .= " AND p.price <= :max_price";
  $params[':max_price'] = $maxPrice;
}

if (!empty($brands)) {
  $placeholders = [];
  foreach ($brands as $i => $brand) {
    $key = ":brand_$i";
    $placeholders[] = $key;
    $params[$key] = $brand;
  }
  $sql .= " AND p.brand IN (" . implode(',', $placeholders) . ")";
}

// Sorting
switch ($sortBy) {
  case 'price_low':  $sql .= " ORDER BY p.price ASC"; break;
  case 'price_high': $sql .= " ORDER BY p.price DESC"; break;
  case 'newest':     $sql .= " ORDER BY p.created_at DESC"; break;
  case 'featured':
  default:           $sql .= " ORDER BY p.featured DESC, p.created_at DESC"; break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get unique brands
$brandsStmt = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL ORDER BY brand");
$availableBrands = $brandsStmt->fetchAll(PDO::FETCH_COLUMN);

// Active category name
$activeCategoryName = 'All Products';
if ($categorySlug) {
  foreach ($categories as $cat) {
    if ($cat['slug'] === $categorySlug) {
      $activeCategoryName = $cat['name'];
      break;
    }
  }
}

// Format price helper
function formatPrice($price) {
  return 'KES ' . number_format($price, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Shop networking equipment for ISPs at AfriGear.tech — MikroTik, Ubiquiti, Hitheium batteries & more.">
  <title>Shop — AfriGear.tech</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%230a1628'/><text x='3' y='24' font-size='20' font-family='sans-serif' font-weight='900' fill='%23f97316'>A</text></svg>">
</head>
<body>

<!-- ============================================================
     NAVBAR (same as index)
     ============================================================ -->
<header class="navbar" role="banner">
  <div class="container">
    <nav class="navbar-inner" aria-label="Main navigation">
      <a href="../index.php" class="navbar-logo" aria-label="AfriGear.tech Home">
        <span class="logo-main">AfriGear</span><span class="logo-tld">.tech</span>
      </a>

      <ul class="navbar-links" role="list">
        <li><a href="../index.php">Home</a></li>
        <li><a href="shop.php" class="active">Shop</a></li>
        <li><a href="../index.php#categories">Categories</a></li>
        <li><a href="../index.php#why-us">About</a></li>
        <li><a href="../index.php#footer">Contact</a></li>
      </ul>

      <div class="navbar-actions">
        <a href="cart.php" class="cart-btn" aria-label="Shopping cart">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
          </svg>
          <span class="cart-count" aria-label="0 items in cart">0</span>
        </a>
        <a href="../api/auth/login.php" class="btn btn-primary login-btn">Login</a>

        <button class="hamburger" aria-label="Toggle menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>
  </div>

  <nav class="mobile-nav" aria-label="Mobile navigation">
    <a href="../index.php">Home</a>
    <a href="shop.php">Shop</a>
    <a href="../index.php#categories">Categories</a>
    <a href="../index.php#why-us">About</a>
    <a href="../index.php#footer">Contact</a>
    <a href="../api/auth/login.php" class="mobile-login">Login</a>
  </nav>
</header>

<!-- ============================================================
     PAGE BANNER
     ============================================================ -->
<section class="page-banner">
  <div class="container">
    <h1><span>Shop</span> Networking Equipment</h1>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="../index.php">Home</a>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
      </svg>
      <span>Shop</span>
      <?php if ($categorySlug): ?>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span><?= htmlspecialchars($activeCategoryName) ?></span>
      <?php endif; ?>
    </nav>
  </div>
</section>

<!-- ============================================================
     SHOP LAYOUT
     ============================================================ -->
<main class="shop-layout">
  <div class="container">

    <!-- Mobile sidebar toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-expanded="false" aria-controls="shopSidebar">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M3 8h12M3 12h9"/>
      </svg>
      Filters
    </button>

    <div class="shop-inner">

      <!-- ======================================================
           SIDEBAR
           ====================================================== -->
      <aside class="shop-sidebar" id="shopSidebar" aria-label="Product filters">

        <!-- Categories -->
        <div class="sidebar-card">
          <h3>Categories</h3>
          <div class="cat-list" role="list">
            <a href="shop.php<?= $sortBy !== 'featured' ? '?sort='.$sortBy : '' ?>"
               class="cat-item <?= !$categorySlug ? 'active' : '' ?>"
               role="listitem">
              All Products
              <span class="cat-count"><?= array_sum(array_column($categories, 'product_count')) ?></span>
            </a>
            <?php foreach ($categories as $cat): ?>
              <a href="shop.php?category=<?= urlencode($cat['slug']) ?><?= $sortBy !== 'featured' ? '&sort='.$sortBy : '' ?>"
                 class="cat-item <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>"
                 role="listitem">
                <?= htmlspecialchars($cat['name']) ?>
                <span class="cat-count"><?= (int)$cat['product_count'] ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Price Range -->
        <div class="sidebar-card">
          <h3>Price Range (KES)</h3>
          <form method="GET" action="shop.php" id="priceForm">
            <?php if ($categorySlug): ?>
              <input type="hidden" name="category" value="<?= htmlspecialchars($categorySlug) ?>">
            <?php endif; ?>
            <?php if (!empty($brands)): ?>
              <input type="hidden" name="brands" value="<?= htmlspecialchars(implode(',', $brands)) ?>">
            <?php endif; ?>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sortBy) ?>">

            <div class="price-inputs">
              <div class="price-input-wrap">
                <label for="min_price">Min</label>
                <input type="number" id="min_price" name="min_price"
                       value="<?= $minPrice !== null ? (int)$minPrice : '' ?>"
                       placeholder="0" min="0" step="500">
              </div>
              <div class="price-input-wrap">
                <label for="max_price">Max</label>
                <input type="number" id="max_price" name="max_price"
                       value="<?= $maxPrice !== null ? (int)$maxPrice : '' ?>"
                       placeholder="200,000" min="0" step="500">
              </div>
            </div>

            <div class="range-slider" aria-hidden="true">
              <div class="range-track" id="rangeTrack"></div>
              <input type="range" id="rangeMin" min="0" max="200000" step="500"
                     value="<?= $minPrice !== null ? (int)$minPrice : 0 ?>">
              <input type="range" id="rangeMax" min="0" max="200000" step="500"
                     value="<?= $maxPrice !== null ? (int)$maxPrice : 200000 ?>">
            </div>

            <button type="submit" class="btn btn-primary apply-price-btn">Apply Price Filter</button>
          </form>
        </div>

        <!-- Brand Filter -->
        <div class="sidebar-card">
          <h3>Brand</h3>
          <div class="brand-list">
            <?php
            $filterBrands = ['MikroTik', 'Hitheium', 'Ubiquiti', 'TP-Link'];
            foreach ($filterBrands as $brand):
              $checked = in_array($brand, $brands);
            ?>
              <label class="brand-check">
                <input type="checkbox" class="brand-checkbox"
                       value="<?= htmlspecialchars($brand) ?>"
                       <?= $checked ? 'checked' : '' ?>>
                <span class="check-box">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                </span>
                <?= htmlspecialchars($brand) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Clear filters -->
        <?php if ($categorySlug || $minPrice !== null || $maxPrice !== null || !empty($brands)): ?>
          <a href="shop.php" class="clear-filters" role="button">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Clear all filters
          </a>
        <?php endif; ?>

      </aside>

      <!-- ======================================================
           MAIN PRODUCT AREA
           ====================================================== -->
      <div class="shop-main">

        <!-- Active filter pills -->
        <?php if ($categorySlug || $minPrice !== null || $maxPrice !== null || !empty($brands)): ?>
          <div class="active-filters" aria-label="Active filters">
            <?php if ($categorySlug): ?>
              <span class="filter-pill">
                <?= htmlspecialchars($activeCategoryName) ?>
                <a href="shop.php?<?= http_build_query(array_filter(['sort' => $sortBy !== 'featured' ? $sortBy : null, 'min_price' => $minPrice, 'max_price' => $maxPrice, 'brands' => !empty($brands) ? implode(',', $brands) : null])) ?>"
                   aria-label="Remove category filter">×</a>
              </span>
            <?php endif; ?>
            <?php if ($minPrice !== null || $maxPrice !== null): ?>
              <span class="filter-pill">
                <?= formatPrice($minPrice ?? 0) ?> – <?= formatPrice($maxPrice ?? 200000) ?>
                <a href="shop.php?<?= http_build_query(array_filter(['category' => $categorySlug, 'sort' => $sortBy !== 'featured' ? $sortBy : null, 'brands' => !empty($brands) ? implode(',', $brands) : null])) ?>"
                   aria-label="Remove price filter">×</a>
              </span>
            <?php endif; ?>
            <?php foreach ($brands as $b): ?>
              <span class="filter-pill">
                <?= htmlspecialchars($b) ?>
                <a href="shop.php?<?= http_build_query(array_filter(['category' => $categorySlug, 'sort' => $sortBy !== 'featured' ? $sortBy : null, 'min_price' => $minPrice, 'max_price' => $maxPrice, 'brands' => implode(',', array_filter($brands, fn($x) => $x !== $b))])) ?>"
                   aria-label="Remove brand filter">×</a>
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Top bar -->
        <div class="shop-topbar">
          <p class="results-count">
            <strong><?= count($products) ?></strong>
            <?= count($products) === 1 ? 'product' : 'products' ?> found
            <?php if ($categorySlug): ?>
              in <strong><?= htmlspecialchars($activeCategoryName) ?></strong>
            <?php endif; ?>
          </p>

          <div class="sort-wrap">
            <label for="sortSelect">Sort by:</label>
            <select id="sortSelect" class="sort-select" aria-label="Sort products">
              <option value="featured"   <?= $sortBy === 'featured'   ? 'selected' : '' ?>>Featured</option>
              <option value="price_low"  <?= $sortBy === 'price_low'  ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
              <option value="newest"     <?= $sortBy === 'newest'     ? 'selected' : '' ?>>Newest First</option>
            </select>
          </div>
        </div>

        <!-- Product grid -->
        <div class="shop-grid" id="productGrid">

          <?php if (empty($products)): ?>
            <!-- Empty state -->
            <div class="empty-state">
              <div class="empty-state-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
              </div>
              <h3>No products found</h3>
              <p>
                <?php if ($categorySlug || $minPrice !== null || $maxPrice !== null || !empty($brands)): ?>
                  No products match your current filters. Try adjusting or clearing them.
                <?php else: ?>
                  No products have been added yet. Check back soon!
                <?php endif; ?>
              </p>
              <?php if ($categorySlug || $minPrice !== null || $maxPrice !== null || !empty($brands)): ?>
                <a href="shop.php" class="btn btn-primary">Clear Filters</a>
              <?php endif; ?>
            </div>

          <?php else: ?>
            <?php foreach ($products as $product): ?>
              <article class="product-card" data-product-id="<?= (int)$product['id'] ?>">

                <!-- Image -->
                <div class="product-image">
                  <?php
                  $images = json_decode($product['images'] ?? '[]', true);
                  $firstImage = !empty($images) ? $images[0] : null;
                  ?>
                  <?php if ($firstImage): ?>
                    <img src="<?= htmlspecialchars($firstImage) ?>"
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         loading="lazy">
                  <?php else: ?>
                    <div class="product-image-placeholder" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                           stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                      </svg>
                      <span>No image</span>
                    </div>
                  <?php endif; ?>
                  <span class="product-brand-badge"><?= htmlspecialchars($product['brand'] ?? 'Generic') ?></span>
                  <?php if ($product['featured']): ?>
                    <span class="product-brand-badge" style="left:auto;right:12px;background:rgba(249,115,22,0.9);border-color:var(--orange);">Featured</span>
                  <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="product-body">
                  <span class="stock-badge <?= $product['stock_qty'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                    <?= $product['stock_qty'] > 0 ? 'In Stock' : 'Out of Stock' ?>
                  </span>

                  <h3 class="product-name">
                    <a href="product.php?slug=<?= urlencode($product['slug']) ?>"
                       style="color:inherit;">
                      <?= htmlspecialchars($product['name']) ?>
                    </a>
                  </h3>

                  <?php if (!empty($product['description'])): ?>
                    <p class="product-desc"><?= htmlspecialchars($product['description']) ?></p>
                  <?php endif; ?>

                  <div class="product-price">
                    <span class="currency">KES</span><?= number_format($product['price'], 0) ?>
                  </div>

                  <button class="btn btn-primary add-to-cart-btn"
                          data-product-id="<?= (int)$product['id'] ?>"
                          data-product-name="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>"
                          <?= $product['stock_qty'] <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>
                          aria-label="Add <?= htmlspecialchars($product['name'], ENT_QUOTES) ?> to cart">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                    </svg>
                    <?= $product['stock_qty'] > 0 ? 'Add to Cart' : 'Out of Stock' ?>
                  </button>
                </div>

              </article>
            <?php endforeach; ?>
          <?php endif; ?>

        </div><!-- /shop-grid -->
      </div><!-- /shop-main -->
    </div><!-- /shop-inner -->
  </div><!-- /container -->
</main>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="footer" id="footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-logo">
          <span class="logo-main">AfriGear</span><span class="logo-tld">.tech</span>
        </div>
        <p class="footer-tagline">
          Powering Kenya's ISPs with genuine networking equipment.
          Everything your ISP needs, delivered fast across all 47 counties.
        </p>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="shop.php">Shop</a></li>
          <li><a href="cart.php">Cart</a></li>
          <li><a href="checkout.php">Checkout</a></li>
          <li><a href="orders.php">My Orders</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Categories</h4>
        <ul>
          <?php foreach ($categories as $cat): ?>
            <li><a href="shop.php?category=<?= urlencode($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact Us</h4>
        <div class="footer-contact">
          <div class="contact-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Nairobi, Kenya
          </div>
          <div class="contact-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            info@afgrigear.tech
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 <a href="../index.php">AfriGear.tech</a> — All rights reserved.</p>
      <nav class="footer-bottom-links" aria-label="Legal links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
      </nav>
    </div>
  </div>
</footer>

<!-- Toast -->
<div class="toast" id="toast" role="alert" aria-live="polite">
  <svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
  </svg>
  <span id="toastMsg">Added to cart</span>
</div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/cart.js"></script>
<script>
/* ---- Shop-specific JS ---- */
document.addEventListener('DOMContentLoaded', () => {

  /* Sort dropdown — auto-submit on change */
  const sortSelect = document.getElementById('sortSelect');
  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      const url = new URL(window.location.href);
      url.searchParams.set('sort', sortSelect.value);
      window.location.href = url.toString();
    });
  }
  /* Brand checkboxes — auto-submit on change */
  document.querySelectorAll('.brand-checkbox').forEach(cb => {
    cb.addEventListener('change', () => {
      const checked = [...document.querySelectorAll('.brand-checkbox:checked')]
        .map(el => el.value);
      const url = new URL(window.location.href);
      if (checked.length > 0) {
        url.searchParams.set('brands', checked.join(','));
      } else {
        url.searchParams.delete('brands');
      }
      window.location.href = url.toString();
    });
  });

  /* Price range slider sync */
  const rangeMin   = document.getElementById('rangeMin');
  const rangeMax   = document.getElementById('rangeMax');
  const inputMin   = document.getElementById('min_price');
  const inputMax   = document.getElementById('max_price');
  const track      = document.getElementById('rangeTrack');

  function updateTrack() {
    if (!rangeMin || !rangeMax || !track) return;
    const min = parseInt(rangeMin.min);
    const max = parseInt(rangeMin.max);
    const valMin = parseInt(rangeMin.value);
    const valMax = parseInt(rangeMax.value);
    const pctMin = ((valMin - min) / (max - min)) * 100;
    const pctMax = ((valMax - min) / (max - min)) * 100;
    track.style.left  = pctMin + '%';
    track.style.width = (pctMax - pctMin) + '%';
  }

  if (rangeMin && rangeMax) {
    rangeMin.addEventListener('input', () => {
      if (parseInt(rangeMin.value) > parseInt(rangeMax.value)) {
        rangeMin.value = rangeMax.value;
      }
      if (inputMin) inputMin.value = rangeMin.value;
      updateTrack();
    });
    rangeMax.addEventListener('input', () => {
      if (parseInt(rangeMax.value) < parseInt(rangeMin.value)) {
        rangeMax.value = rangeMin.value;
      }
      if (inputMax) inputMax.value = rangeMax.value;
      updateTrack();
    });
    if (inputMin) inputMin.addEventListener('input', () => { rangeMin.value = inputMin.value; updateTrack(); });
    if (inputMax) inputMax.addEventListener('input', () => { rangeMax.value = inputMax.value; updateTrack(); });
    updateTrack();
  }

  /* Mobile sidebar toggle */
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('shopSidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      const open = sidebar.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open);
      toggle.innerHTML = open
        ? `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg> Hide Filters`
        : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M3 8h12M3 12h9"/></svg> Filters`;
    });
  }

});
</script>
</body>
</html>
