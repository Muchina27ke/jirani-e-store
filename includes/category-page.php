<?php
/**
 * Category page renderer — shared by fruits.php, vegetables.php, handcrafts.php
 * config.php is loaded by the caller (e.g. fruits.php), so $conn, $product etc. are already available.
 * Expects: $categoryName, $categorySlug, $categoryIcon, $categoryEmoji, $heroGradient
 */

if (!isset($categoryName)) die('Category not configured.');


// Look up the category
$stmt = $conn->prepare("SELECT id, name, description FROM categories WHERE name = ? AND status = 'active'");
$stmt->bind_param("s", $categoryName);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();

if (!$category) {
    // Fallback: try case-insensitive
    $stmt2 = $conn->prepare("SELECT id, name, description FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $stmt2->bind_param("s", $categoryName);
    $stmt2->execute();
    $category = $stmt2->get_result()->fetch_assoc();
}

if (!$category) {
    // Category doesn't exist yet in DB — show empty state gracefully
    $category = ['id' => 0, 'name' => $categoryName, 'description' => ''];
}

// Get products
$products = [];
if ($category['id']) {
    $products = $product->getProductsByCategory(
        $category['id'],
        $_SESSION['user_location']['lat'] ?? null,
        $_SESSION['user_location']['lon'] ?? null
    );
}

$nearbyMode = false;
if (!empty($products) && !empty($_SESSION['user_location']['lat'])) {
    $nearbyMode = true;
}

// Sort options
$sortBy = $_GET['sort'] ?? 'default';
if ($sortBy === 'price_asc')  usort($products, fn($a,$b) => $a['price'] <=> $b['price']);
if ($sortBy === 'price_desc') usort($products, fn($a,$b) => $b['price'] <=> $a['price']);
if ($sortBy === 'name')       usort($products, fn($a,$b) => strcmp($a['name'], $b['name']));

// Search filter
$searchQ = trim($_GET['q'] ?? '');
if ($searchQ) {
    $products = array_filter($products, fn($p) => stripos($p['name'] . ' ' . ($p['description'] ?? ''), $searchQ) !== false);
}

$products = array_values($products);

$pageTitle   = $categoryName . ' — Jirani';
$currentPage = $categorySlug;
?>
<?php require_once dirname(__DIR__) . '/navbar.php'; ?>


<style>
/* Category hero */
.cat-hero {
    background: <?php echo $heroGradient; ?>;
    padding: 56px 0 64px;
    color: white;
    position: relative;
    overflow: hidden;
}
.cat-hero::before {
    content:''; position:absolute;
    width:400px; height:400px; border-radius:50%;
    background:rgba(255,255,255,0.04);
    top:-120px; right:-80px;
}
.cat-hero-inner { position:relative; z-index:1; display:flex; align-items:center; gap:28px; flex-wrap:wrap; }
.cat-emoji { font-size:4rem; line-height:1; filter:drop-shadow(0 4px 12px rgba(0,0,0,0.2)); }
.cat-hero h1 { font-family:var(--j-font-heading); font-size:clamp(2rem,5vw,3rem); font-weight:800; color:white; margin-bottom:8px; }
.cat-hero p  { font-size:1rem; color:rgba(255,255,255,0.75); max-width:480px; margin:0; line-height:1.7; }
.cat-stat-pill {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(255,255,255,0.12);
    border:1px solid rgba(255,255,255,0.2);
    padding:6px 14px; border-radius:99px;
    font-size:0.82rem; font-weight:600; color:rgba(255,255,255,0.9);
    margin-top:16px; backdrop-filter:blur(8px);
}

/* Toolbar */
.cat-toolbar {
    background:white;
    border-bottom:1px solid var(--j-border-light);
    padding:14px 0;
    position:sticky; top:68px; z-index:100;
    box-shadow:0 2px 8px rgba(0,0,0,0.04);
}
.cat-toolbar-inner {
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
}

/* Scroll reveal */
.reveal { opacity:0; transform:translateY(20px); transition:opacity 0.5s ease, transform 0.5s ease; }
.reveal.visible { opacity:1; transform:translateY(0); }

/* Products grid */
.cat-products-section { padding:40px 0 80px; }
</style>

<!-- Hero -->
<div class="cat-hero">
    <div class="j-container">
        <div class="cat-hero-inner">
            <div class="cat-emoji"><?php echo $categoryEmoji; ?></div>
            <div>
                <div style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.6);margin-bottom:8px;">
                    Jirani › <?php echo htmlspecialchars($categoryName); ?>
                </div>
                <h1>Fresh Local <?php echo htmlspecialchars($categoryName); ?></h1>
                <p>
                    <?php echo $category['description']
                        ? htmlspecialchars($category['description'])
                        : "Handpicked " . strtolower($categoryName) . " from trusted local vendors in your community."; ?>
                </p>
                <div>
                    <span class="cat-stat-pill">
                        <i class="fas <?php echo $categoryIcon; ?>"></i>
                        <?php echo count($products); ?> product<?php echo count($products) !== 1 ? 's' : ''; ?> available
                    </span>
                    <?php if ($nearbyMode): ?>
                    <span class="cat-stat-pill" style="margin-left:8px;">
                        <i class="fas fa-map-marker-alt"></i> Near you
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toolbar: search + sort -->
<div class="cat-toolbar">
    <div class="j-container">
        <div class="cat-toolbar-inner">
            <!-- Search within category -->
            <form method="GET" action="" style="display:flex;align-items:center;gap:0;background:var(--j-bg);border:1.5px solid var(--j-border);border-radius:var(--j-radius);overflow:hidden;flex:1;max-width:340px;">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
                <i class="fas fa-search" style="padding:0 12px;color:var(--j-text-muted);font-size:0.85rem;flex-shrink:0;"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($searchQ); ?>"
                       placeholder="Search <?php echo strtolower($categoryName); ?>…"
                       style="border:none;background:transparent;padding:10px 12px 10px 0;outline:none;font-size:0.88rem;font-family:var(--j-font-body);flex:1;">
            </form>

            <!-- Sort -->
            <div style="display:flex;align-items:center;gap:8px;margin-left:auto;">
                <span style="font-size:0.8rem;color:var(--j-text-muted);font-weight:600;white-space:nowrap;">Sort by:</span>
                <?php
                $sorts = ['default' => 'Default', 'name' => 'Name A–Z', 'price_asc' => 'Price ↑', 'price_desc' => 'Price ↓'];
                foreach ($sorts as $key => $label):
                    $qs = http_build_query(array_merge($_GET, ['sort' => $key]));
                ?>
                <a href="?<?php echo $qs; ?>"
                   style="padding:7px 14px;border-radius:99px;font-size:0.8rem;font-weight:600;text-decoration:none;transition:all .2s;
                   <?php echo $sortBy === $key ? 'background:var(--j-primary);color:white;' : 'background:var(--j-bg);color:var(--j-text-muted);border:1px solid var(--j-border-light);'; ?>">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Products -->
<div class="cat-products-section">
    <div class="j-container">
        <?php if (!empty($searchQ)): ?>
        <div style="margin-bottom:20px;font-size:0.9rem;color:var(--j-text-muted);">
            <?php echo count($products); ?> result<?php echo count($products) !== 1 ? 's' : ''; ?> for
            <strong style="color:var(--j-text);">"<?php echo htmlspecialchars($searchQ); ?>"</strong>
            <a href="<?php echo $categorySlug; ?>.php" style="color:var(--j-primary);font-weight:600;margin-left:8px;">
                <i class="fas fa-times" style="font-size:0.75rem;"></i> Clear
            </a>
        </div>
        <?php endif; ?>

        <?php if (!empty($products)): ?>
        <div class="j-product-grid">
            <?php foreach ($products as $p):
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
            ?>
            <div class="j-product-card reveal" id="product-<?php echo $pId; ?>">
                <div class="j-product-image">
                    <?php if ($pImg): ?>
                    <img src="<?php echo $pImg; ?>" alt="<?php echo $pName; ?>" loading="lazy"
                         onerror="this.parentElement.innerHTML='<div class=\'j-product-image-placeholder\'><i class=\'fas <?php echo $categoryIcon; ?>\'></i></div>'">
                    <?php else: ?>
                    <div class="j-product-image-placeholder"><i class="fas <?php echo $categoryIcon; ?>"></i></div>
                    <?php endif; ?>

                    <?php if ($pDist): ?>
                    <div class="j-product-badges">
                        <span class="j-badge j-badge-info" style="font-size:0.7rem;">
                            <i class="fas fa-map-marker-alt"></i> <?php echo $pDist; ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if ($pStock <= 5 && $pStock > 0): ?>
                    <div class="j-product-badges" style="top:auto;bottom:10px;">
                        <span class="j-badge j-badge-warning" style="font-size:0.7rem;">Low Stock</span>
                    </div>
                    <?php endif; ?>

                    <button class="j-product-wishlist" onclick="toggleWishlist(<?php echo $pId; ?>, this)" title="Wishlist">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>

                <div class="j-product-body">
                    <div class="j-product-category"><?php echo htmlspecialchars($categoryName); ?></div>
                    <div class="j-product-name"><?php echo $pName; ?></div>
                    <?php if ($pDesc): ?>
                    <div style="font-size:0.78rem;color:var(--j-text-muted);margin-bottom:8px;line-height:1.5;">
                        <?php echo $pDesc; ?>…
                    </div>
                    <?php endif; ?>
                    <div class="j-product-vendor"><i class="fas fa-store"></i> <?php echo $pVendor; ?></div>
                    <div class="j-product-footer">
                        <div class="j-product-price">
                            <span class="currency">KSh</span><?php echo $pPrice; ?>
                        </div>
                        <?php if ($pStock > 0): ?>
                        <button class="j-add-cart-btn" onclick="addToCart(<?php echo $pId; ?>, 1, this)" title="Add to cart">
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
            <div class="j-empty-icon"><?php echo $categoryEmoji; ?></div>
            <h3>No <?php echo strtolower($categoryName); ?> available yet</h3>
            <p>Be the first vendor to list fresh <?php echo strtolower($categoryName); ?> from your farm or garden!</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo SITE_URL; ?>Register/index.php?role=vendor" class="j-btn j-btn-primary">
                    <i class="fas fa-store"></i> Sell on Jirani
                </a>
                <a href="<?php echo SITE_URL; ?>index.php" class="j-btn j-btn-ghost">
                    <i class="fas fa-home"></i> Browse All Products
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>



<script>
const revealObs = new IntersectionObserver(entries => {
    entries.forEach((e, i) => {
        if (e.isIntersecting) {
            setTimeout(() => e.target.classList.add('visible'), i * 50);
            revealObs.unobserve(e.target);
        }
    });
}, { threshold: 0.08 });
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));
</script>

<?php require_once dirname(__DIR__) . '/footer.php'; ?>
