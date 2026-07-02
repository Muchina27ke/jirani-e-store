<?php
session_start();
require_once 'config/config.php';
require_once 'includes/Auth.php';

$pdo = getPDO();

// Fetch featured products
$featuredStmt = $pdo->query("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.stock_qty > 0
    ORDER BY p.featured DESC, p.created_at DESC
    LIMIT 8
");
$featuredProducts = $featuredStmt->fetchAll();

$navBase   = '';
$navActive = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AfriGear.tech — Kenya's #1 networking equipment store for ISPs. MikroTik, Hitheium Batteries, Ubiquiti & more.">
  <title>AfriGear.tech — Kenya's #1 ISP Equipment Store</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%230a1628'/><text x='3' y='24' font-size='20' font-family='sans-serif' font-weight='900' fill='%23f97316'>A</text></svg>">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- ============================================================
     HERO
     ============================================================ -->
<main>
<section class="hero" id="hero" aria-labelledby="hero-heading">
  <div class="container">
    <div class="hero-content">

      <div class="hero-badge">Powering Kenya's ISPs</div>

      <h1 id="hero-heading">
        Kenya's <span class="highlight">#1 Networking</span><br>
        Equipment Store for ISPs
      </h1>

      <p class="hero-sub">
        MikroTik, Hitheium Batteries, Ubiquiti &amp; more — genuine products,
        fast delivery, and M-Pesa payments. Everything your ISP needs, in one place.
      </p>

      <div class="hero-cta">
        <a href="pages/shop.php" class="btn btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
          </svg>
          Shop Now
        </a>
        <a href="#categories" class="btn btn-outline">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
          </svg>
          View Catalog
        </a>
      </div>

      <div class="hero-stats" aria-label="Store statistics">
        <div class="stat-item">
          <div class="stat-number">500+</div>
          <div class="stat-label">Products</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">1,200+</div>
          <div class="stat-label">ISPs Served</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">47</div>
          <div class="stat-label">Counties</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">24/7</div>
          <div class="stat-label">Support</div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ============================================================
     CATEGORIES
     ============================================================ -->
<section class="categories" id="categories" aria-labelledby="categories-heading">
  <div class="container">
    <h2 class="section-title" id="categories-heading">Shop by <span>Category</span></h2>
    <p class="section-subtitle">Everything your ISP needs, organised for you</p>

    <div class="categories-grid">

      <!-- MikroTik Routers -->
      <a href="pages/shop.php?category=mikrotik-routers" class="category-card fade-up" aria-label="Browse MikroTik Routers">
        <div class="category-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
          </svg>
        </div>
        <div class="category-name">MikroTik Routers</div>
        <div class="category-desc">RouterOS, hAP, CCR &amp; wireless series</div>
      </a>

      <!-- Hitheium Batteries -->
      <a href="pages/shop.php?category=hitheium-batteries" class="category-card fade-up" aria-label="Browse Hitheium Batteries">
        <div class="category-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M17 8h1a2 2 0 012 2v4a2 2 0 01-2 2h-1M3 8h14v9a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5-3h4v3H8V5z"/>
          </svg>
        </div>
        <div class="category-name">Hitheium Batteries</div>
        <div class="category-desc">LiFePO4 lithium backup power solutions</div>
      </a>

      <!-- Ubiquiti -->
      <a href="pages/shop.php?category=ubiquiti-equipment" class="category-card fade-up" aria-label="Browse Ubiquiti Equipment">
        <div class="category-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
          </svg>
        </div>
        <div class="category-name">Ubiquiti Equipment</div>
        <div class="category-desc">UniFi, airMAX &amp; EdgeMAX products</div>
      </a>

      <!-- Switches & APs -->
      <a href="pages/shop.php?category=switches-access-points" class="category-card fade-up" aria-label="Browse Switches and Access Points">
        <div class="category-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
          </svg>
        </div>
        <div class="category-name">Switches &amp; APs</div>
        <div class="category-desc">Managed switches &amp; wireless access points</div>
      </a>

      <!-- Cables & Accessories -->
      <a href="pages/shop.php?category=cables-accessories" class="category-card fade-up" aria-label="Browse Cables and Accessories">
        <div class="category-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
        </div>
        <div class="category-name">Cables &amp; Accessories</div>
        <div class="category-desc">Cat6, fibre, SFP modules &amp; tools</div>
      </a>

    </div>
  </div>
</section>

<!-- ============================================================
     FEATURED PRODUCTS
     ============================================================ -->
<section class="featured" id="featured" aria-labelledby="featured-heading">
  <div class="container">
    <h2 class="section-title" id="featured-heading"><span>Featured</span> Products</h2>
    <p class="section-subtitle">Top picks for ISPs — genuine, in-stock, ready to ship</p>

    <div class="products-grid">
      <?php if (empty($featuredProducts)): ?>
        <p style="color:var(--muted);text-align:center;grid-column:1/-1;padding:40px 0;">
          No products yet — check back soon!
        </p>
      <?php else: ?>
        <?php foreach ($featuredProducts as $fp):
          $fpImages = json_decode($fp['images'] ?? '[]', true);
          $fpImg    = $fpImages[0] ?? null;
        ?>
        <article class="product-card fade-up" data-product-id="<?= (int)$fp['id'] ?>">
          <div class="product-image">
            <?php if ($fpImg): ?>
              <img src="<?= htmlspecialchars($fpImg) ?>"
                   alt="<?= htmlspecialchars($fp['name']) ?>"
                   loading="lazy">
            <?php else: ?>
              <div class="product-image-placeholder" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>No image yet</span>
              </div>
            <?php endif; ?>
            <span class="product-brand-badge"><?= htmlspecialchars($fp['brand'] ?? 'Generic') ?></span>
            <?php if ($fp['featured']): ?>
              <span class="product-brand-badge" style="left:auto;right:12px;background:rgba(249,115,22,0.9);border-color:var(--orange);">Featured</span>
            <?php endif; ?>
          </div>
          <div class="product-body">
            <span class="stock-badge in-stock" style="margin-bottom:8px;">In Stock</span>
            <h3 class="product-name">
              <a href="pages/product.php?slug=<?= urlencode($fp['slug']) ?>" style="color:inherit;">
                <?= htmlspecialchars($fp['name']) ?>
              </a>
            </h3>
            <div class="product-price">
              <span class="currency">KES</span><?= number_format($fp['price'], 0) ?>
            </div>
            <button class="btn btn-primary add-to-cart-btn"
                    data-product-id="<?= (int)$fp['id'] ?>"
                    data-product-name="<?= htmlspecialchars($fp['name'], ENT_QUOTES) ?>"
                    aria-label="Add <?= htmlspecialchars($fp['name'], ENT_QUOTES) ?> to cart">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                   viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
              </svg>
              Add to Cart
            </button>
          </div>
        </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div style="text-align:center;margin-top:36px;">
      <a href="pages/shop.php" class="btn btn-outline">
        View All Products
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- ============================================================
     WHY BUY FROM US
     ============================================================ -->
<section class="why-us" id="why-us" aria-labelledby="why-heading">
  <div class="container">
    <h2 class="section-title" id="why-heading">Why Buy From <span>Us?</span></h2>
    <p class="section-subtitle">Built for ISPs, by people who understand the industry</p>

    <div class="features-grid">

      <div class="feature-box fade-up">
        <div class="feature-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
          </svg>
        </div>
        <h3 class="feature-title">Genuine Products</h3>
        <p class="feature-desc">Every item is sourced directly from authorised distributors. No counterfeits, no grey market — just authentic gear with full warranty.</p>
      </div>

      <div class="feature-box fade-up">
        <div class="feature-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
        </div>
        <h3 class="feature-title">Fast Delivery</h3>
        <p class="feature-desc">Same-day dispatch in Nairobi. Nationwide delivery within 24–48 hours via trusted courier partners across all 47 counties.</p>
      </div>

      <div class="feature-box fade-up">
        <div class="feature-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 18h.01M8 21h8a2 2 0 002-2v-1a7 7 0 00-14 0v1a2 2 0 002 2zM12 11a4 4 0 100-8 4 4 0 000 8z"/>
          </svg>
        </div>
        <h3 class="feature-title">M-Pesa Payments</h3>
        <p class="feature-desc">Pay instantly via M-Pesa STK Push. No bank transfers, no waiting — your order is confirmed the moment payment clears.</p>
      </div>

      <div class="feature-box fade-up">
        <div class="feature-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
          </svg>
        </div>
        <h3 class="feature-title">Expert Support</h3>
        <p class="feature-desc">Our team knows networking. Get pre-sales advice, configuration help, and after-sales support from engineers who use the same gear.</p>
      </div>

    </div>
  </div>
</section>
</main>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="footer" id="footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">

      <!-- Brand -->
      <div class="footer-brand">
        <div class="footer-logo">
          <span class="logo-main">AfriGear</span><span class="logo-tld">.tech</span>
        </div>
        <p class="footer-tagline">
          Powering Kenya's ISPs with genuine networking equipment.
          Everything your ISP needs, delivered fast across all 47 counties.
        </p>
        <div class="footer-social" aria-label="Social media links">
          <!-- Twitter/X -->
          <a href="#" class="social-link" aria-label="Follow us on X (Twitter)">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
          </a>
          <!-- WhatsApp -->
          <a href="#" class="social-link" aria-label="Chat with us on WhatsApp">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
          </a>
          <!-- LinkedIn -->
          <a href="#" class="social-link" aria-label="Connect on LinkedIn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
            </svg>
          </a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="pages/shop.php">Shop</a></li>
          <li><a href="pages/cart.php">Cart</a></li>
          <li><a href="pages/checkout.php">Checkout</a></li>
          <li><a href="pages/orders.php">My Orders</a></li>
        </ul>
      </div>

      <!-- Categories -->
      <div class="footer-col">
        <h4>Categories</h4>
        <ul>
          <li><a href="pages/shop.php?category=mikrotik-routers">MikroTik Routers</a></li>
          <li><a href="pages/shop.php?category=hitheium-batteries">Hitheium Batteries</a></li>
          <li><a href="pages/shop.php?category=ubiquiti-equipment">Ubiquiti Equipment</a></li>
          <li><a href="pages/shop.php?category=switches-access-points">Switches &amp; APs</a></li>
          <li><a href="pages/shop.php?category=cables-accessories">Cables &amp; Accessories</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="footer-col">
        <h4>Contact Us</h4>
        <div class="footer-contact">
          <div class="contact-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Nairobi, Kenya
          </div>
          <div class="contact-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            +254 700 000 000
          </div>
          <div class="contact-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            info@afgrigear.tech
          </div>
          <div class="contact-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Mon–Sat, 8am – 6pm
          </div>
        </div>
      </div>

    </div>

    <!-- Bottom bar -->
    <div class="footer-bottom">
      <p>&copy; 2025 <a href="index.php">AfriGear.tech</a> — All rights reserved.</p>
      <nav class="footer-bottom-links" aria-label="Legal links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
        <a href="#">Returns</a>
      </nav>
    </div>

  </div>
</footer>

<script src="assets/js/main.js"></script>
<script src="assets/js/cart.js"></script>

<!-- Toast (needed on homepage for add-to-cart feedback) -->
<div class="toast" id="toast" role="alert" aria-live="polite">
  <svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
  </svg>
  <span id="toastMsg">Added to cart</span>
</div>
</body>
</html>
