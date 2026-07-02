<?php
session_start();
require_once '../config/config.php';

$pdo = getPDO();

// Fetch product by slug or id
$slug = $_GET['slug'] ?? null;
$id   = isset($_GET['id']) ? (int)$_GET['id'] : null;

$product = false;
if ($slug) {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug
                           FROM products p JOIN categories c ON p.category_id = c.id
                           WHERE p.slug = :slug");
    $stmt->execute([':slug' => $slug]);
    $product = $stmt->fetch();
} elseif ($id) {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug
                           FROM products p JOIN categories c ON p.category_id = c.id
                           WHERE p.id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();
}

// Related products (same category, exclude current)
$related = [];
if ($product) {
    $relStmt = $pdo->prepare("SELECT * FROM products
                               WHERE category_id = :cid AND id != :pid
                               ORDER BY featured DESC, created_at DESC
                               LIMIT 4");
    $relStmt->execute([':cid' => $product['category_id'], ':pid' => $product['id']]);
    $related = $relStmt->fetchAll();
}

$images = $product ? json_decode($product['images'] ?? '[]', true) : [];
$pageTitle = $product ? htmlspecialchars($product['name']) . ' — AfriGear.tech' : '404 Not Found — AfriGear.tech';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if ($product): ?>
  <meta name="description" content="<?= htmlspecialchars(substr($product['description'] ?? '', 0, 155)) ?>">
  <?php endif; ?>
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%230a1628'/><text x='3' y='24' font-size='20' font-family='sans-serif' font-weight='900' fill='%23f97316'>A</text></svg>">
</head>
<body>

<!-- NAVBAR -->
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

      <!-- Navbar search — collapsible -->
      <div class="navbar-search" id="navSearchWrap" role="search">
        <button class="navbar-search-icon-btn" id="navSearchIcon"
                aria-label="Open search" aria-expanded="false">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </button>
        <div class="navbar-search-expand">
          <svg class="navbar-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input type="search" class="navbar-search-input" id="navSearch"
                 placeholder="Search products…" autocomplete="off"
                 aria-label="Search products" aria-expanded="false">
          <button class="navbar-search-clear" id="navSearchClear" aria-label="Clear search">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2.5" width="13" height="13">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div class="search-dropdown" id="navSearchDropdown" role="listbox"
             aria-label="Search suggestions"></div>
      </div>
      <div class="navbar-actions">
        <a href="cart.php" class="cart-btn" aria-label="Shopping cart">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
          </svg>
          <span class="cart-count">0</span>
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

<?php if (!$product): ?>
<!-- ============================================================
     404 NOT FOUND
     ============================================================ -->
<main>
  <section class="not-found-section">
    <div class="not-found-box">
      <div class="not-found-code">404</div>
      <h2>Product Not Found</h2>
      <p>The product you're looking for doesn't exist or may have been removed.</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="shop.php" class="btn btn-primary">Browse Shop</a>
        <a href="../index.php" class="btn btn-outline">Go Home</a>
      </div>
    </div>
  </section>
</main>

<?php else: ?>
<!-- ============================================================
     PAGE BANNER
     ============================================================ -->
<section class="page-banner">
  <div class="container">
    <h1><span><?= htmlspecialchars($product['brand'] ?? '') ?></span> <?= htmlspecialchars($product['name']) ?></h1>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="../index.php">Home</a>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      <a href="shop.php">Shop</a>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      <a href="shop.php?category=<?= urlencode($product['category_slug']) ?>"><?= htmlspecialchars($product['category_name']) ?></a>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      <span><?= htmlspecialchars($product['name']) ?></span>
    </nav>
  </div>
</section>

<!-- ============================================================
     PRODUCT DETAIL
     ============================================================ -->
<main>
<section class="product-detail-section">
  <div class="container">
    <div class="product-detail-grid">

      <!-- LEFT: Image Gallery -->
      <div class="product-gallery">
        <div class="gallery-main" id="galleryMain">
          <?php if (!empty($images)): ?>
            <img src="<?= htmlspecialchars($images[0]) ?>"
                 alt="<?= htmlspecialchars($product['name']) ?>"
                 id="mainImage">
          <?php else: ?>
            <div class="product-image-placeholder">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span>No image available</span>
            </div>
          <?php endif; ?>
        </div>

        <?php if (count($images) > 1): ?>
        <div class="gallery-thumbs" role="list" aria-label="Product images">
          <?php foreach ($images as $i => $img): ?>
            <button class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
                    data-src="<?= htmlspecialchars($img) ?>"
                    aria-label="View image <?= $i + 1 ?>"
                    role="listitem">
              <img src="<?= htmlspecialchars($img) ?>" alt="Product image <?= $i + 1 ?>">
            </button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: Product Info -->
      <div class="product-info">

        <!-- Brand + category -->
        <div class="product-meta">
          <span class="badge" style="background:rgba(249,115,22,0.12);color:var(--orange);border:1px solid rgba(249,115,22,0.3);">
            <?= htmlspecialchars($product['brand'] ?? 'Generic') ?>
          </span>
          <a href="shop.php?category=<?= urlencode($product['category_slug']) ?>" class="product-category-link">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <?= htmlspecialchars($product['category_name']) ?>
          </a>
        </div>

        <h1 class="product-detail-name"><?= htmlspecialchars($product['name']) ?></h1>

        <span class="stock-badge <?= $product['stock_qty'] > 0 ? 'in-stock' : 'out-of-stock' ?>" style="width:fit-content;">
          <?= $product['stock_qty'] > 0 ? 'In Stock (' . (int)$product['stock_qty'] . ' available)' : 'Out of Stock' ?>
        </span>

        <div class="product-detail-price">
          <span class="currency">KES</span><?= number_format($product['price'], 0) ?>
        </div>

        <?php if (!empty($product['description'])): ?>
        <p class="product-short-desc"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <?php endif; ?>

        <?php if ($product['stock_qty'] > 0): ?>
        <!-- Quantity + Add to Cart -->
        <div>
          <p class="qty-label">Quantity</p>
          <div class="qty-selector" role="group" aria-label="Quantity selector">
            <button class="qty-btn" id="qtyMinus" aria-label="Decrease quantity">−</button>
            <input type="number" class="qty-input" id="qtyInput"
                   value="1" min="1" max="<?= (int)$product['stock_qty'] ?>"
                   aria-label="Quantity">
            <button class="qty-btn" id="qtyPlus" aria-label="Increase quantity">+</button>
          </div>
        </div>

        <button class="btn btn-primary add-to-cart-detail"
                id="addToCartBtn"
                data-product-id="<?= (int)$product['id'] ?>"
                aria-label="Add <?= htmlspecialchars($product['name'], ENT_QUOTES) ?> to cart">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
          </svg>
          Add to Cart
        </button>
        <?php else: ?>
        <button class="btn btn-primary add-to-cart-detail" disabled style="opacity:0.5;cursor:not-allowed;">
          Out of Stock
        </button>
        <?php endif; ?>

        <a href="shop.php" class="continue-shopping">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
          Continue Shopping
        </a>

        <!-- Meta info -->
        <div class="product-meta-row">
          <?php if (!empty($product['sku'])): ?>
          <div class="meta-row-item">
            <span class="meta-key">SKU</span>
            <span class="meta-val"><?= htmlspecialchars($product['sku']) ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($product['brand'])): ?>
          <div class="meta-row-item">
            <span class="meta-key">Brand</span>
            <span class="meta-val"><?= htmlspecialchars($product['brand']) ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($product['weight_kg'])): ?>
          <div class="meta-row-item">
            <span class="meta-key">Weight</span>
            <span class="meta-val"><?= number_format($product['weight_kg'], 3) ?> kg</span>
          </div>
          <?php endif; ?>
          <div class="meta-row-item">
            <span class="meta-key">Category</span>
            <span class="meta-val"><?= htmlspecialchars($product['category_name']) ?></span>
          </div>
        </div>

      </div><!-- /product-info -->
    </div><!-- /product-detail-grid -->

    <!-- ============================================================
         TABS
         ============================================================ -->
    <div class="product-tabs-section">
      <div class="tabs-nav" role="tablist">
        <button class="tab-btn active" role="tab" aria-selected="true"  aria-controls="tab-desc"  id="btn-desc">Description</button>
        <button class="tab-btn"        role="tab" aria-selected="false" aria-controls="tab-specs" id="btn-specs">Specifications</button>
        <button class="tab-btn"        role="tab" aria-selected="false" aria-controls="tab-ship"  id="btn-ship">Shipping Info</button>
      </div>

      <div id="tab-desc" class="tab-panel active" role="tabpanel" aria-labelledby="btn-desc">
        <?php if (!empty($product['description'])): ?>
          <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <?php else: ?>
          <p>No description available for this product.</p>
        <?php endif; ?>
      </div>

      <div id="tab-specs" class="tab-panel" role="tabpanel" aria-labelledby="btn-specs">
        <table class="spec-table">
          <tbody>
            <?php if (!empty($product['brand'])): ?>
            <tr><td>Brand</td><td><?= htmlspecialchars($product['brand']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($product['sku'])): ?>
            <tr><td>SKU / Model</td><td><?= htmlspecialchars($product['sku']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($product['weight_kg'])): ?>
            <tr><td>Weight</td><td><?= number_format($product['weight_kg'], 3) ?> kg</td></tr>
            <?php endif; ?>
            <tr><td>Category</td><td><?= htmlspecialchars($product['category_name']) ?></td></tr>
            <tr><td>Stock</td><td><?= $product['stock_qty'] > 0 ? (int)$product['stock_qty'] . ' units available' : 'Out of stock' ?></td></tr>
            <tr><td>Price</td><td>KES <?= number_format($product['price'], 0) ?></td></tr>
          </tbody>
        </table>
      </div>

      <div id="tab-ship" class="tab-panel" role="tabpanel" aria-labelledby="btn-ship">
        <h4>Delivery Options</h4>
        <ul>
          <li>Same-day dispatch for orders placed before 2pm (Nairobi)</li>
          <li>Nationwide delivery within 24–48 hours via G4S / Wells Fargo courier</li>
          <li>Free delivery on orders above KES 50,000</li>
          <li>Flat delivery fee of KES 500 for orders below KES 50,000</li>
        </ul>
        <h4>Payment</h4>
        <ul>
          <li>M-Pesa STK Push — instant confirmation</li>
          <li>Bank transfer (order held until payment clears)</li>
        </ul>
        <h4>Returns</h4>
        <ul>
          <li>7-day return policy for unopened items in original packaging</li>
          <li>Defective items replaced within 14 days</li>
          <li>Contact us at info@afgrigear.tech to initiate a return</li>
        </ul>
      </div>
    </div>

    <!-- ============================================================
         RELATED PRODUCTS
         ============================================================ -->
    <?php if (!empty($related)): ?>
    <div class="related-section">
      <h2 class="section-title">Related <span>Products</span></h2>
      <p class="section-subtitle">More from <?= htmlspecialchars($product['category_name']) ?></p>
      <div class="products-grid">
        <?php foreach ($related as $rel):
          $relImages = json_decode($rel['images'] ?? '[]', true);
          $relImg    = $relImages[0] ?? null;
        ?>
        <article class="product-card">
          <div class="product-image">
            <?php if ($relImg): ?>
              <img src="<?= htmlspecialchars($relImg) ?>" alt="<?= htmlspecialchars($rel['name']) ?>" loading="lazy">
            <?php else: ?>
              <div class="product-image-placeholder" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>No image</span>
              </div>
            <?php endif; ?>
            <span class="product-brand-badge"><?= htmlspecialchars($rel['brand'] ?? 'Generic') ?></span>
          </div>
          <div class="product-body">
            <span class="stock-badge <?= $rel['stock_qty'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
              <?= $rel['stock_qty'] > 0 ? 'In Stock' : 'Out of Stock' ?>
            </span>
            <h3 class="product-name">
              <a href="product.php?slug=<?= urlencode($rel['slug']) ?>" style="color:inherit;">
                <?= htmlspecialchars($rel['name']) ?>
              </a>
            </h3>
            <div class="product-price">
              <span class="currency">KES</span><?= number_format($rel['price'], 0) ?>
            </div>
            <button class="btn btn-primary add-to-cart-btn"
                    data-product-id="<?= (int)$rel['id'] ?>"
                    <?= $rel['stock_qty'] <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>
                    aria-label="Add <?= htmlspecialchars($rel['name'], ENT_QUOTES) ?> to cart">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
              </svg>
              <?= $rel['stock_qty'] > 0 ? 'Add to Cart' : 'Out of Stock' ?>
            </button>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /container -->
</section>
</main>
<?php endif; ?>

<!-- FOOTER -->
<footer class="footer" id="footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-logo"><span class="logo-main">AfriGear</span><span class="logo-tld">.tech</span></div>
        <p class="footer-tagline">Powering Kenya's ISPs with genuine networking equipment. Everything your ISP needs, delivered fast across all 47 counties.</p>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="shop.php">Shop</a></li>
          <li><a href="cart.php">Cart</a></li>
          <li><a href="checkout.php">Checkout</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Support</h4>
        <ul>
          <li><a href="#">Returns Policy</a></li>
          <li><a href="#">Shipping Info</a></li>
          <li><a href="#">Track Order</a></li>
          <li><a href="../index.php#footer">Contact Us</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <div class="footer-contact">
          <div class="contact-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            info@afgrigear.tech
          </div>
          <div class="contact-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            +254 700 000 000
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 <a href="../index.php">AfriGear.tech</a> — All rights reserved.</p>
      <nav class="footer-bottom-links"><a href="#">Privacy Policy</a><a href="#">Terms of Service</a></nav>
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
document.addEventListener('DOMContentLoaded', () => {
  /* Gallery thumbnails */
  document.querySelectorAll('.gallery-thumb').forEach(thumb => {
    thumb.addEventListener('click', () => {
      const mainImg = document.getElementById('mainImage');
      if (mainImg) mainImg.src = thumb.dataset.src;
      document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });

  /* Quantity selector */
  const qtyInput = document.getElementById('qtyInput');
  const qtyMinus = document.getElementById('qtyMinus');
  const qtyPlus  = document.getElementById('qtyPlus');

  if (qtyInput && qtyMinus && qtyPlus) {
    const max = parseInt(qtyInput.max) || 99;
    qtyMinus.addEventListener('click', () => {
      const v = parseInt(qtyInput.value) || 1;
      if (v > 1) qtyInput.value = v - 1;
      qtyMinus.disabled = parseInt(qtyInput.value) <= 1;
    });
    qtyPlus.addEventListener('click', () => {
      const v = parseInt(qtyInput.value) || 1;
      if (v < max) qtyInput.value = v + 1;
    });
    qtyInput.addEventListener('change', () => {
      let v = parseInt(qtyInput.value) || 1;
      v = Math.max(1, Math.min(v, max));
      qtyInput.value = v;
      qtyMinus.disabled = v <= 1;
    });
  }

  /* Add to cart — detail button */
  const addBtn = document.getElementById('addToCartBtn');
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      const productId = addBtn.dataset.productId;
      const qty = parseInt(qtyInput?.value || '1');
      AfriCart.add(productId, qty, addBtn);
    });
  }

  /* Add to cart — related product buttons */
  document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', () => AfriCart.add(btn.dataset.productId, 1, btn));
  });
});
</script>
</body>
</html>
