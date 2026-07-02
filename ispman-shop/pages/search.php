<?php
session_start();
require_once '../config/config.php';

$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$products    = [];
$totalFound  = 0;
$totalPages  = 1;
$suggestions = [];

if (strlen($q) >= 2) {
    $pdo = getPDO();

    // Count total matches
    $countSql = "
        SELECT COUNT(DISTINCT p.id)
        FROM products p
        WHERE MATCH(p.name, p.brand, p.description, p.sku) AGAINST(:q IN NATURAL LANGUAGE MODE)
           OR p.name LIKE :like OR p.brand LIKE :like2 OR p.sku LIKE :like3
    ";
    $cs = $pdo->prepare($countSql);
    $cs->execute([':q' => $q, ':like' => "%$q%", ':like2' => "%$q%", ':like3' => "%$q%"]);
    $totalFound = (int)$cs->fetchColumn();
    $totalPages = max(1, ceil($totalFound / $perPage));

    // Fetch page of results
    $sql = "
        SELECT p.*, c.name as category_name, c.slug as category_slug,
               MATCH(p.name, p.brand, p.description, p.sku)
                   AGAINST(:q IN NATURAL LANGUAGE MODE) AS relevance
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE MATCH(p.name, p.brand, p.description, p.sku) AGAINST(:q2 IN NATURAL LANGUAGE MODE)
           OR p.name LIKE :like OR p.brand LIKE :like2 OR p.sku LIKE :like3
        ORDER BY relevance DESC, p.featured DESC, p.stock_qty DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':q',      $q,           PDO::PARAM_STR);
    $stmt->bindValue(':q2',     $q,           PDO::PARAM_STR);
    $stmt->bindValue(':like',   "%$q%",       PDO::PARAM_STR);
    $stmt->bindValue(':like2',  "%$q%",       PDO::PARAM_STR);
    $stmt->bindValue(':like3',  "%$q%",       PDO::PARAM_STR);
    $stmt->bindValue(':limit',  $perPage,     PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,      PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();

    // Suggestions: related categories
    if ($totalFound === 0) {
        $sugStmt = $pdo->query("SELECT name, slug FROM categories ORDER BY name");
        $suggestions = $sugStmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $q ? 'Search: ' . htmlspecialchars($q) : 'Search' ?> — AfriGear.tech</title>
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
        <li><a href="shop.php">Shop</a></li>
        <li><a href="../index.php#categories">Categories</a></li>
        <li><a href="../index.php#why-us">About</a></li>
        <li><a href="../index.php#footer">Contact</a></li>
      </ul>
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
    <a href="../index.php#footer">Contact</a>
    <a href="../api/auth/login.php" class="mobile-login">Login</a>
  </nav>
</header>

<!-- PAGE BANNER -->
<section class="page-banner">
  <div class="container">
    <h1>Search <span>Results</span></h1>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="../index.php">Home</a>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      <span>Search<?= $q ? ': ' . htmlspecialchars($q) : '' ?></span>
    </nav>
  </div>
</section>

<main class="shop-layout">
  <div class="container">

    <!-- Search bar (large, prominent) -->
    <form method="GET" action="search.php" class="search-page-form" role="search">
      <div class="search-page-input-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="search" name="q" id="searchPageInput"
               value="<?= htmlspecialchars($q) ?>"
               placeholder="Search products, brands, SKUs…"
               autocomplete="off"
               aria-label="Search products">
        <?php if ($q): ?>
          <a href="search.php" class="search-clear-btn" aria-label="Clear search">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </a>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary search-page-btn">Search</button>
    </form>

    <?php if (strlen($q) >= 2): ?>

      <!-- Results header -->
      <div class="search-results-header">
        <?php if ($totalFound > 0): ?>
          <p>
            <strong><?= number_format($totalFound) ?></strong>
            result<?= $totalFound !== 1 ? 's' : '' ?> for
            "<strong><?= htmlspecialchars($q) ?></strong>"
            <?php if ($totalPages > 1): ?>
              — page <?= $page ?> of <?= $totalPages ?>
            <?php endif; ?>
          </p>
        <?php else: ?>
          <p>No results for "<strong><?= htmlspecialchars($q) ?></strong>"</p>
        <?php endif; ?>
      </div>

      <?php if (!empty($products)): ?>
      <!-- Results grid -->
      <div class="shop-grid" style="margin-bottom:40px;">
        <?php foreach ($products as $p):
          $imgs = json_decode($p['images'] ?? '[]', true);
          $img  = $imgs[0] ?? null;
        ?>
        <article class="product-card">
          <div class="product-image">
            <?php if ($img): ?>
              <img src="<?= htmlspecialchars($img) ?>"
                   alt="<?= htmlspecialchars($p['name']) ?>"
                   loading="lazy">
            <?php else: ?>
              <div class="product-image-placeholder" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>No image</span>
              </div>
            <?php endif; ?>
            <span class="product-brand-badge"><?= htmlspecialchars($p['brand'] ?? 'Generic') ?></span>
          </div>
          <div class="product-body">
            <span class="stock-badge <?= $p['stock_qty'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
              <?= $p['stock_qty'] > 0 ? 'In Stock' : 'Out of Stock' ?>
            </span>
            <h3 class="product-name">
              <a href="product.php?slug=<?= urlencode($p['slug']) ?>" style="color:inherit;">
                <?= htmlspecialchars($p['name']) ?>
              </a>
            </h3>
            <p class="product-desc" style="font-size:0.75rem;color:var(--muted);">
              <?= htmlspecialchars($p['category_name']) ?>
            </p>
            <div class="product-price">
              <span class="currency">KES</span><?= number_format($p['price'], 0) ?>
            </div>
            <button class="btn btn-primary add-to-cart-btn"
                    data-product-id="<?= (int)$p['id'] ?>"
                    data-product-name="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
                    <?= $p['stock_qty'] <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>
                    aria-label="Add <?= htmlspecialchars($p['name'], ENT_QUOTES) ?> to cart">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
              </svg>
              <?= $p['stock_qty'] > 0 ? 'Add to Cart' : 'Out of Stock' ?>
            </button>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <nav class="search-pagination" aria-label="Search results pages">
        <?php if ($page > 1): ?>
          <a href="?q=<?= urlencode($q) ?>&page=<?= $page - 1 ?>" class="btn btn-outline" style="padding:9px 18px;font-size:0.85rem;">
            ← Previous
          </a>
        <?php endif; ?>
        <span style="color:var(--muted);font-size:0.85rem;">Page <?= $page ?> of <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
          <a href="?q=<?= urlencode($q) ?>&page=<?= $page + 1 ?>" class="btn btn-outline" style="padding:9px 18px;font-size:0.85rem;">
            Next →
          </a>
        <?php endif; ?>
      </nav>
      <?php endif; ?>

      <?php else: ?>
      <!-- No results -->
      <div class="empty-state" style="padding:60px 20px;">
        <div class="empty-state-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
        <h3>No products found</h3>
        <p>We couldn't find anything matching "<strong><?= htmlspecialchars($q) ?></strong>".<br>Try a different keyword or browse by category.</p>

        <?php if (!empty($suggestions)): ?>
        <div style="margin-top:24px;">
          <p style="font-size:0.82rem;color:var(--muted);margin-bottom:12px;">Browse categories:</p>
          <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">
            <?php foreach ($suggestions as $cat): ?>
              <a href="shop.php?category=<?= urlencode($cat['slug']) ?>"
                 class="filter-pill" style="text-decoration:none;">
                <?= htmlspecialchars($cat['name']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div style="margin-top:28px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
          <a href="shop.php" class="btn btn-primary">Browse All Products</a>
          <a href="../index.php" class="btn btn-outline">Go Home</a>
        </div>
      </div>
      <?php endif; ?>

    <?php elseif ($q !== ''): ?>
      <p style="color:var(--muted);text-align:center;padding:40px 0;">Please enter at least 2 characters to search.</p>
    <?php else: ?>
      <!-- Empty state — no query yet -->
      <div style="text-align:center;padding:60px 20px;color:var(--muted);">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" width="56" height="56" style="margin:0 auto 16px;display:block;color:var(--border);">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <p>Type a product name, brand, or SKU above to search.</p>
      </div>
    <?php endif; ?>

  </div>
</main>

<!-- FOOTER -->
<footer class="footer" id="footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-logo"><span class="logo-main">AfriGear</span><span class="logo-tld">.tech</span></div>
        <p class="footer-tagline">Powering Kenya's ISPs with genuine networking equipment.</p>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="shop.php">Shop</a></li>
          <li><a href="cart.php">Cart</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <div class="footer-contact">
          <div class="contact-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            info@afgrigear.tech
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 <a href="../index.php">AfriGear.tech</a> — All rights reserved.</p>
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
</body>
</html>
