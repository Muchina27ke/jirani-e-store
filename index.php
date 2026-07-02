<?php
require_once __DIR__ . '/config/config.php';

// Get user geolocation from session
$lat = $_SESSION['user_location']['lat'] ?? null;
$lon = $_SESSION['user_location']['lon'] ?? null;

// Determine which products to show
$initialProducts = [];
$sectionTitle    = 'Fresh From Kakamega';
$nearbyMode      = false;

if ($lat && $lon) {
    $initialProducts = $geolocation->findProductsNearLocation($lat, $lon, 20);
    if (!empty($initialProducts)) {
        $sectionTitle = 'Products Near You';
        $nearbyMode   = true;
    }
}

if (empty($initialProducts)) {
    $initialProducts = $product->get_all_products();
    shuffle($initialProducts);
    $initialProducts = array_slice($initialProducts, 0, 15);
}

// Normalize to arrays
$initialProducts = array_map(fn($p) => is_object($p) ? (array)$p : $p, $initialProducts);

// Page meta
$pageTitle = 'Jirani · Local Marketplace';
$currentPage = 'home';
?>
<?php require_once __DIR__ . '/navbar.php'; ?>

<style>
/* ── Homepage-specific styles ─── */
.hero-section {
    background: linear-gradient(145deg, var(--j-primary) 0%, var(--j-primary-dark) 55%, #0d2318 100%);
    color: white;
    padding: 80px 0 100px;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content:'';
    position:absolute;
    width:600px;height:600px;
    border-radius:50%;
    background: rgba(255,255,255,0.03);
    top:-200px;right:-200px;
}

.hero-section::after {
    content:'';
    position:absolute;
    width:350px;height:350px;
    border-radius:50%;
    background: rgba(255,167,38,0.07);
    bottom:-100px;left:-80px;
}

.hero-content { position:relative; z-index:1; }

.hero-eyebrow {
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(255,255,255,0.1);
    border:1px solid rgba(255,255,255,0.2);
    color:rgba(255,255,255,0.9);
    padding:6px 14px;border-radius:99px;
    font-size:0.82rem;font-weight:500;
    margin-bottom:24px;
    backdrop-filter:blur(8px);
}

.hero-title {
    font-family:var(--j-font-heading);
    font-size:clamp(2.2rem,5vw,3.8rem);
    font-weight:800;
    line-height:1.12;
    color:white;
    margin-bottom:18px;
}

.hero-title .highlight { color:var(--j-accent); }

.hero-sub {
    font-size:1.05rem;
    color:rgba(255,255,255,0.72);
    max-width:520px;
    margin-bottom:36px;
    line-height:1.7;
}

.hero-search-bar {
    display:flex;
    max-width:560px;
    background:rgba(255,255,255,0.12);
    border:1.5px solid rgba(255,255,255,0.25);
    border-radius:var(--j-radius-pill);
    overflow:hidden;
    backdrop-filter:blur(12px);
    transition:var(--j-transition);
}

.hero-search-bar:focus-within {
    border-color:var(--j-accent);
    background:rgba(255,255,255,0.18);
    box-shadow:0 0 0 4px rgba(255,167,38,0.15);
}

.hero-search-bar input {
    flex:1;padding:14px 20px;
    background:transparent;border:none;outline:none;
    color:white;font-size:0.95rem;font-family:var(--j-font-body);
}

.hero-search-bar input::placeholder { color:rgba(255,255,255,0.55); }

.hero-search-bar button {
    background:var(--j-accent);
    border:none;padding:14px 24px;
    color:white;font-weight:600;
    font-size:0.9rem;cursor:pointer;
    display:flex;align-items:center;gap:7px;
    transition:var(--j-transition);
    flex-shrink:0;
}
.hero-search-bar button:hover { background:var(--j-accent-dark); }

.hero-stats {
    display:flex;gap:32px;margin-top:40px;flex-wrap:wrap;
}

.hero-stat { text-align:left; }
.hero-stat-num {
    font-family:var(--j-font-heading);
    font-size:1.6rem;font-weight:800;color:var(--j-accent);
}
.hero-stat-label { font-size:0.78rem;color:rgba(255,255,255,0.55);margin-top:2px; }

/* Category strip */
.categories-strip {
    background:white;
    border-bottom:1px solid var(--j-border-light);
    padding:16px 0;
    overflow:hidden;
}

/* Product grid section */
.products-section { padding:56px 0; }

/* Feature strip */
.features-strip {
    background:linear-gradient(135deg,var(--j-primary-glass),rgba(255,167,38,0.04));
    border-top:1px solid var(--j-border-light);
    border-bottom:1px solid var(--j-border-light);
    padding:40px 0;
}
.feature-item {
    display:flex;flex-direction:column;align-items:center;
    text-align:center;gap:12px;
    padding:0 20px;
}
.feature-icon {
    width:56px;height:56px;border-radius:16px;
    background:var(--j-primary-glass);
    color:var(--j-primary);
    display:flex;align-items:center;justify-content:center;
    font-size:1.4rem;
}
.feature-item h4 { font-size:0.95rem;font-weight:700;color:var(--j-text); }
.feature-item p  { font-size:0.82rem;color:var(--j-text-muted);max-width:160px;line-height:1.5; }

/* Scroll reveal */
.reveal {
    opacity:0;transform:translateY(24px);
    transition:opacity 0.6s ease,transform 0.6s ease;
}
.reveal.visible { opacity:1;transform:translateY(0); }
</style>

<!-- ══ HERO ═════════════════════════════════════════════════ -->
<section class="hero-section">
    <div class="j-container hero-content" style="display:flex; flex-direction:column; align-items:center; text-align:center;">
        <div style="max-width:660px; display:flex; flex-direction:column; align-items:center;">
            <div class="hero-eyebrow">
                <i class="fas fa-map-marker-alt" style="color:var(--j-accent);"></i>
                Kakamega, Kenya's Local Marketplace
            </div>
            <h1 class="hero-title">
                Shop <span class="highlight">Local</span>,<br>
                Grow Together
            </h1>
            <p class="hero-sub" style="margin: 0 auto 36px;">Fresh produce, handmade goods, and local products — straight from your community. Pay with M-Pesa, get delivered fast.</p>

            <div class="hero-stats" style="justify-content: center; margin-top: 20px;">
                <div class="hero-stat" style="text-align: center;">
                    <div class="hero-stat-num">500+</div>
                    <div class="hero-stat-label">Local Products</div>
                </div>
                <div class="hero-stat" style="text-align: center;">
                    <div class="hero-stat-num">80+</div>
                    <div class="hero-stat-label">Verified Vendors</div>
                </div>
                <div class="hero-stat" style="text-align: center;">
                    <div class="hero-stat-num">2,400+</div>
                    <div class="hero-stat-label">Happy Customers</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ CATEGORY CHIPS ═══════════════════════════════════════ -->
<div class="categories-strip">
    <div class="j-container">
        <div class="j-category-chips">
            <a href="<?php echo SITE_URL; ?>index.php" class="j-chip active">
                <span class="chip-icon">🏠</span> All
            </a>
            <a href="<?php echo SITE_URL; ?>fruits.php" class="j-chip">
                <span class="chip-icon">🍎</span> Fruits
            </a>
            <a href="<?php echo SITE_URL; ?>vegetables.php" class="j-chip">
                <span class="chip-icon">🥦</span> Vegetables
            </a>
            <a href="<?php echo SITE_URL; ?>handcrafts.php" class="j-chip">
                <span class="chip-icon">🪵</span> Handcrafts
            </a>
            <a href="<?php echo SITE_URL; ?>search.php?q=honey" class="j-chip">
                <span class="chip-icon">🍯</span> Honey
            </a>
            <a href="<?php echo SITE_URL; ?>search.php?q=eggs" class="j-chip">
                <span class="chip-icon">🥚</span> Eggs
            </a>
            <a href="<?php echo SITE_URL; ?>search.php?q=dairy" class="j-chip">
                <span class="chip-icon">🥛</span> Dairy
            </a>
            <a href="<?php echo SITE_URL; ?>search.php?q=grains" class="j-chip">
                <span class="chip-icon">🌾</span> Grains
            </a>
        </div>
    </div>
</div>

<!-- ══ PRODUCTS ══════════════════════════════════════════════ -->
<section class="products-section">
    <div class="j-container">
        <div class="j-flex-between" style="margin-bottom:28px;">
            <div>
                <?php if ($nearbyMode): ?>
                <span class="j-section-label"><i class="fas fa-map-marker-alt"></i> Based on your location</span>
                <?php else: ?>
                <span class="j-section-label"><i class="fas fa-fire"></i> Featured</span>
                <?php endif; ?>
                <h2 class="j-section-title" style="margin:0;"><?php echo htmlspecialchars($sectionTitle); ?></h2>
            </div>
            <a href="<?php echo SITE_URL; ?>search.php" class="j-btn j-btn-ghost j-btn-sm">
                View All <i class="fas fa-arrow-right" style="font-size:0.8rem;"></i>
            </a>
        </div>

        <?php if (!empty($initialProducts)): ?>
        <div class="j-product-grid" id="productGrid">
            <?php foreach ($initialProducts as $p):
                $pId    = $p['id'] ?? 0;
                $pName  = htmlspecialchars($p['name'] ?? 'Product');
                $pPrice = number_format((float)($p['price'] ?? 0), 2);
                $pDesc  = htmlspecialchars(substr($p['description'] ?? '', 0, 80));
                $pImg = '';
                if (!empty($p['image'])) {
                    $pImg = SITE_URL . 'uploads/' . htmlspecialchars($p['image']);
                } elseif (!empty($p['image_url'])) {
                    $pImg = strpos($p['image_url'], 'uploads/') !== false ? SITE_URL . htmlspecialchars($p['image_url']) : SITE_URL . 'uploads/' . htmlspecialchars($p['image_url']);
                }
                $pVendor= htmlspecialchars($p['vendor_name'] ?? $p['business_name'] ?? 'Local Vendor');
                $pDist  = isset($p['distance']) ? round($p['distance'], 1) . ' km' : '';
                $pStock = (int)($p['stock_quantity'] ?? $p['stock'] ?? 99);
                $pCat   = htmlspecialchars($p['category_name'] ?? $p['category'] ?? 'Product');
            ?>
            <div class="j-product-card reveal" id="product-<?php echo $pId; ?>">
                <!-- Image -->
                <div class="j-product-image">
                    <?php if ($pImg): ?>
                        <img src="<?php echo $pImg; ?>" alt="<?php echo $pName; ?>" loading="lazy">
                    <?php else: ?>
                        <div class="j-product-image-placeholder"><i class="fas fa-seedling"></i></div>
                    <?php endif; ?>

                    <!-- Badges -->
                    <div class="j-product-badges">
                        <?php if ($pDist): ?>
                        <span class="j-badge j-badge-info" style="font-size:0.7rem;">
                            <i class="fas fa-map-marker-alt"></i> <?php echo $pDist; ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($pStock <= 5 && $pStock > 0): ?>
                        <span class="j-badge j-badge-warning" style="font-size:0.7rem;">Low Stock</span>
                        <?php endif; ?>
                        <?php if ($pStock <= 0): ?>
                        <span class="j-badge j-badge-danger" style="font-size:0.7rem;">Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <!-- Wishlist -->
                    <button class="j-product-wishlist" onclick="toggleWishlist(<?php echo $pId; ?>, this)" title="Add to wishlist" aria-label="Wishlist">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="j-product-body">
                    <div class="j-product-category"><?php echo $pCat; ?></div>
                    <div class="j-product-name"><?php echo $pName; ?></div>
                    <div class="j-product-vendor">
                        <i class="fas fa-store"></i> <?php echo $pVendor; ?>
                    </div>
                    <div class="j-product-footer">
                        <div class="j-product-price">
                            <span class="currency">KSh</span><?php echo $pPrice; ?>
                        </div>
                        <?php if ($pStock > 0): ?>
                        <button class="j-add-cart-btn" onclick="addToCart(<?php echo $pId; ?>, 1, this)" title="Add to cart" aria-label="Add to cart">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                        <?php else: ?>
                        <button class="j-add-cart-btn" disabled style="opacity:.4;cursor:not-allowed;" title="Out of stock">
                            <i class="fas fa-ban"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="j-empty-state">
            <div class="j-empty-icon"><i class="fas fa-seedling"></i></div>
            <h3>No products yet</h3>
            <p>Be the first vendor to list something amazing!</p>
            <a href="<?php echo SITE_URL; ?>Register/index.php?role=vendor" class="j-btn j-btn-primary">
                <i class="fas fa-store"></i> Sell on Jirani
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ══ FEATURES STRIP ════════════════════════════════════════ -->
<div class="features-strip">
    <div class="j-container">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:24px;">
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h4>Location-Aware</h4>
                <p>Products from vendors near you surface first</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background:rgba(0,166,81,0.1);color:#00a651;"><i class="fas fa-mobile-alt"></i></div>
                <h4>M-Pesa Payments</h4>
                <p>Pay securely with mobile money — instant confirmation</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background:rgba(255,167,38,0.1);color:var(--j-accent);"><i class="fas fa-shield-alt"></i></div>
                <h4>Escrow Protected</h4>
                <p>Money held safely until you confirm delivery</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;"><i class="fas fa-truck-fast"></i></div>
                <h4>Fast Local Delivery</h4>
                <p>Same-day delivery from nearby vendors</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background:rgba(139,92,246,0.1);color:#7c3aed;"><i class="fas fa-leaf"></i></div>
                <h4>100% Local</h4>
                <p>Every vendor is from your community</p>
            </div>
        </div>
    </div>
</div>

<!-- ══ CTA SECTION ═══════════════════════════════════════════ -->
<?php if (!isset($_SESSION['user_id'])): ?>
<section style="padding:64px 0;background:var(--j-bg);">
    <div class="j-container" style="text-align:center;">
        <span class="j-section-label" style="justify-content:center;display:block;">Join the Community</span>
        <h2 class="j-section-title" style="margin:0 auto 16px;">Ready to shop or sell locally?</h2>
        <p style="color:var(--j-text-muted);max-width:440px;margin:0 auto 32px;font-size:0.95rem;">
            Join hundreds of buyers and sellers already connecting through Jirani
        </p>
        <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
            <a href="<?php echo SITE_URL; ?>Register/index.php" class="j-btn j-btn-primary j-btn-lg">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <a href="<?php echo SITE_URL; ?>Register/index.php?role=vendor" class="j-btn j-btn-outline j-btn-lg">
                <i class="fas fa-store"></i> Start Selling
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>

<script>
// Scroll reveal
const revealObserver = new IntersectionObserver(entries => {
    entries.forEach((e, i) => {
        if (e.isIntersecting) {
            setTimeout(() => e.target.classList.add('visible'), i * 60);
            revealObserver.unobserve(e.target);
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
</script>