<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Product.php';
require_once __DIR__ . '/includes/Geolocation.php';

$productObj = new Product($conn);
$geolocation = new Geolocation($conn);

$searchQ = trim($_GET['q'] ?? '');
$sortBy  = $_GET['sort'] ?? 'default';

$products = [];
if ($searchQ !== '') {
    // We could use a dedicated search method, but for now we'll get all active and filter.
    // Or we can query the DB directly to be safer if there are many products.
    $stmt = $conn->prepare("
        SELECT p.*, v.business_name AS vendor_name, c.name AS category_name
        FROM products p
        JOIN vendors v ON p.vendor_id = v.user_id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active'
          AND (p.name LIKE CONCAT('%', ?, '%') OR p.description LIKE CONCAT('%', ?, '%'))
    ");
    $stmt->bind_param("ss", $searchQ, $searchQ);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Sort options
if ($sortBy === 'price_asc')  usort($products, fn($a,$b) => $a['price'] <=> $b['price']);
if ($sortBy === 'price_desc') usort($products, fn($a,$b) => $b['price'] <=> $a['price']);
if ($sortBy === 'name')       usort($products, fn($a,$b) => strcmp($a['name'], $b['name']));

$pageTitle   = 'Search Results — Jirani';
$currentPage = 'search';
?>
<?php require_once __DIR__ . '/navbar.php'; ?>

<style>
/* Search hero */
.search-hero {
    background: linear-gradient(135deg, var(--j-primary) 0%, var(--j-primary-dark) 100%);
    padding: 56px 0 64px;
    color: white;
    position: relative;
    overflow: hidden;
}
.search-hero::before {
    content:''; position:absolute;
    width:400px; height:400px; border-radius:50%;
    background:rgba(255,255,255,0.04);
    top:-120px; right:-80px;
}
.search-hero-inner { position:relative; z-index:1; display:flex; flex-direction:column; align-items:center; text-align:center; }
.search-hero h1 { font-family:var(--j-font-heading); font-size:clamp(1.8rem,4vw,2.5rem); font-weight:800; color:white; margin-bottom:16px; }

/* Search input */
.search-bar-large {
    width:100%; max-width:600px;
    background:white; border-radius:99px;
    display:flex; align-items:center; padding:6px 6px 6px 20px;
    box-shadow:var(--j-shadow-lg);
}
.search-bar-large input {
    flex:1; border:none; outline:none; background:transparent;
    font-size:1rem; font-family:var(--j-font-body); color:var(--j-text);
}
.search-bar-large button {
    background:var(--j-primary); color:white; border:none;
    width:46px; height:46px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:var(--j-transition);
}
.search-bar-large button:hover { background:var(--j-primary-dark); transform:scale(1.05); }

/* Toolbar */
.cat-toolbar {
    background:white; border-bottom:1px solid var(--j-border-light);
    padding:14px 0; position:sticky; top:68px; z-index:100;
    box-shadow:0 2px 8px rgba(0,0,0,0.04);
}
.cat-toolbar-inner { display:flex; align-items:center; gap:12px; flex-wrap:wrap; justify-content:space-between; }

/* Scroll reveal */
.reveal { opacity:0; transform:translateY(20px); transition:opacity 0.5s ease, transform 0.5s ease; }
.reveal.visible { opacity:1; transform:translateY(0); }

/* Products grid */
.cat-products-section { padding:40px 0 80px; }
</style>

<!-- Hero -->
<div class="search-hero">
    <div class="j-container">
        <div class="search-hero-inner">
            <h1>Search Products</h1>
            <form method="GET" action="" class="search-bar-large">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
                <input type="text" name="q" value="<?php echo htmlspecialchars($searchQ); ?>" placeholder="What are you looking for?" required>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
</div>

<?php if ($searchQ !== ''): ?>
<!-- Toolbar: sort -->
<div class="cat-toolbar">
    <div class="j-container">
        <div class="cat-toolbar-inner">
            <div style="font-size:0.9rem;color:var(--j-text-muted);">
                Found <strong><?php echo count($products); ?></strong> result<?php echo count($products) !== 1 ? 's' : ''; ?> for "<span style="color:var(--j-text);font-weight:600;"><?php echo htmlspecialchars($searchQ); ?></span>"
            </div>

            <!-- Sort -->
            <div style="display:flex;align-items:center;gap:8px;">
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
                $pVendor= htmlspecialchars($p['vendor_name'] ?? 'Local Vendor');
                $pStock = (int)($p['stock_quantity'] ?? $p['stock'] ?? 99);
                $pCat   = htmlspecialchars($p['category_name'] ?? 'Product');
            ?>
            <div class="j-product-card reveal" id="product-<?php echo $pId; ?>">
                <div class="j-product-image">
                    <?php if ($pImg): ?>
                    <img src="<?php echo $pImg; ?>" alt="<?php echo $pName; ?>" loading="lazy"
                         onerror="this.parentElement.innerHTML='<div class=\'j-product-image-placeholder\'><i class=\'fas fa-box\'></i></div>'">
                    <?php else: ?>
                    <div class="j-product-image-placeholder"><i class="fas fa-box"></i></div>
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
                    <div class="j-product-category"><?php echo $pCat; ?></div>
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
            <div class="j-empty-icon">🔍</div>
            <h3>No results found</h3>
            <p>We couldn't find any products matching "<?php echo htmlspecialchars($searchQ); ?>".</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo SITE_URL; ?>index.php" class="j-btn j-btn-primary">
                    <i class="fas fa-home"></i> Browse All Products
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>

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