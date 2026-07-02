<?php
// navbar.php — must be included AFTER config.php is loaded by the parent page
// (config.php already calls session_start and provides $conn, $cart, $auth etc.)

// Get cart count — reuse existing $cart object from config if available
$_cartCount = 0;
if (isset($_SESSION['user_id'])) {
    try {
        // $cart is already instantiated in config.php
        $_cartCount = isset($cart) ? $cart->getCartCount() : 0;
    } catch (Exception $e) { $_cartCount = 0; }
}

// Current page for active states
$_currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');

// User initials for avatar
$_userInitials = '';
if (isset($_SESSION['user_id'])) {
    $nameStr = $_SESSION['name'] ?? '';
    if (empty($nameStr) && isset($auth) && method_exists($auth, 'getUser')) {
        $u = $auth->getUser();
        $nameStr = $u['name'] ?? 'U';
    }
    if (empty($nameStr)) $nameStr = 'U';
    $parts = explode(' ', trim($nameStr));
    $_userInitials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Jirani' : 'Jirani · Local Marketplace'; ?></title>
    <link rel="icon" type="image/jpeg" href="<?php echo SITE_URL; ?>title_logo.jpg">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Jirani Design System -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/jirani-design-system.css">

    <!-- Page-specific CSS -->
    <?php if (isset($additionalCSS)): foreach ($additionalCSS as $css): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
    <?php endforeach; endif; ?>

    <!-- Page-specific head content -->
    <?php echo $pageHead ?? ''; ?>
</head>
<body>

<!-- ══ NAVBAR ══════════════════════════════════════════════ -->
<nav class="j-navbar" id="mainNavbar">
    <div class="j-container" style="display:flex;align-items:center;gap:16px;height:68px;">

        <!-- Brand -->
        <a href="<?php echo SITE_URL; ?>index.php" class="j-navbar-brand">
            <div class="brand-icon"><i class="fas fa-seedling"></i></div>
            Jirani
        </a>

        <!-- Search -->
        <form class="j-nav-search" action="<?php echo SITE_URL; ?>search.php" method="GET" role="search">
            <i class="fas fa-search search-icon"></i>
            <input type="text" name="q" placeholder="Search products, vendors…"
                   value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                   autocomplete="off" id="globalSearch">
        </form>

        <!-- Category Links (hidden on mobile) -->
        <ul class="j-nav-links" style="flex-shrink:0;">
            <li><a href="<?php echo SITE_URL; ?>fruits.php" class="<?php echo $_currentPage==='fruits'?'active':''; ?>">
                <i class="fas fa-apple-alt"></i> Fruits
            </a></li>
            <li><a href="<?php echo SITE_URL; ?>vegetables.php" class="<?php echo $_currentPage==='vegetables'?'active':''; ?>">
                <i class="fas fa-carrot"></i> Veggies
            </a></li>
            <li><a href="<?php echo SITE_URL; ?>handcrafts.php" class="<?php echo $_currentPage==='handcrafts'?'active':''; ?>">
                <i class="fas fa-hands"></i> Crafts
            </a></li>
        </ul>

        <!-- Actions -->
        <div class="j-nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>

                <!-- Wishlist -->
                <a href="<?php echo SITE_URL; ?>wishlist.php" class="j-nav-icon-btn" title="Wishlist"
                   style="<?php echo $_currentPage==='wishlist'?'color:var(--j-primary);background:var(--j-primary-glass)':''; ?>">
                    <i class="fas fa-heart"></i>
                </a>

                <!-- Cart -->
                <a href="<?php echo SITE_URL; ?>cart.php" class="j-nav-icon-btn" title="Cart" id="navCartBtn"
                   style="<?php echo $_currentPage==='cart'?'color:var(--j-primary);background:var(--j-primary-glass)':''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($_cartCount > 0): ?>
                        <span class="j-nav-badge" id="navCartCount"><?php echo $_cartCount; ?></span>
                    <?php else: ?>
                        <span class="j-nav-badge" id="navCartCount" style="display:none;">0</span>
                    <?php endif; ?>
                </a>

                <!-- User dropdown -->
                <div style="position:relative;">
                    <div class="j-user-avatar" id="userAvatarBtn" onclick="toggleDropdown()" title="Account">
                        <?php echo htmlspecialchars($_userInitials ?: 'U'); ?>
                    </div>
                    <div class="j-dropdown" id="userDropdown">
                        <div style="padding:12px 16px 8px;border-bottom:1px solid var(--j-border-light);margin-bottom:4px;">
                            <div style="font-weight:700;font-size:0.9rem;color:var(--j-text);"><?php echo htmlspecialchars($_SESSION['name'] ?? 'My Account'); ?></div>
                            <div style="font-size:0.78rem;color:var(--j-text-muted);"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                        </div>
                        <a href="<?php echo SITE_URL; ?>profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="<?php echo SITE_URL; ?>orders.php"><i class="fas fa-box"></i> My Orders</a>
                        <a href="<?php echo SITE_URL; ?>wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                        <a href="<?php echo SITE_URL; ?>account-settings.php"><i class="fas fa-cog"></i> Account Settings</a>
                        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['vendor'])): ?>
                            <hr>
                            <a href="<?php echo SITE_URL; ?>seller/dashboard.php" style="color:var(--j-primary);"><i class="fas fa-store"></i> Seller Dashboard</a>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin','super_admin'])): ?>
                            <hr>
                            <a href="<?php echo SITE_URL; ?>admin/index.php" style="color:var(--j-accent);"><i class="fas fa-shield-alt"></i> Admin Panel</a>
                        <?php endif; ?>
                        <hr>
                        <a href="<?php echo SITE_URL; ?>Signin/logout.php" style="color:var(--j-danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>

            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>Signin/index.php" class="j-btn j-btn-ghost j-btn-sm">Sign In</a>
                <a href="<?php echo SITE_URL; ?>Register/index.php" class="j-btn j-btn-primary j-btn-sm">Get Started</a>
            <?php endif; ?>

            <!-- Mobile toggle -->
            <button class="j-nav-toggle" id="mobileNavToggle" onclick="toggleMobileNav()" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobileNav" style="display:none;border-top:1px solid var(--j-border-light);padding:16px;background:white;">
        <form action="<?php echo SITE_URL; ?>search.php" method="GET" style="margin-bottom:16px;">
            <div class="j-input-icon-wrap">
                <i class="fas fa-search input-icon"></i>
                <input type="text" name="q" class="j-input" placeholder="Search products…" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
            </div>
        </form>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <a href="<?php echo SITE_URL; ?>index.php" style="padding:10px 14px;border-radius:8px;color:var(--j-text);display:flex;align-items:center;gap:10px;"><i class="fas fa-home" style="width:16px;color:var(--j-text-muted);"></i> Home</a>
            <a href="<?php echo SITE_URL; ?>fruits.php" style="padding:10px 14px;border-radius:8px;color:var(--j-text);display:flex;align-items:center;gap:10px;"><i class="fas fa-apple-alt" style="width:16px;color:var(--j-text-muted);"></i> Fruits</a>
            <a href="<?php echo SITE_URL; ?>vegetables.php" style="padding:10px 14px;border-radius:8px;color:var(--j-text);display:flex;align-items:center;gap:10px;"><i class="fas fa-carrot" style="width:16px;color:var(--j-text-muted);"></i> Vegetables</a>
            <a href="<?php echo SITE_URL; ?>handcrafts.php" style="padding:10px 14px;border-radius:8px;color:var(--j-text);display:flex;align-items:center;gap:10px;"><i class="fas fa-hands" style="width:16px;color:var(--j-text-muted);"></i> Handcrafts</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <hr style="border-color:var(--j-border-light);">
                <a href="<?php echo SITE_URL; ?>cart.php" style="padding:10px 14px;border-radius:8px;color:var(--j-text);display:flex;align-items:center;gap:10px;"><i class="fas fa-shopping-cart" style="width:16px;color:var(--j-text-muted);"></i> Cart (<?php echo $_cartCount; ?>)</a>
                <a href="<?php echo SITE_URL; ?>orders.php" style="padding:10px 14px;border-radius:8px;color:var(--j-text);display:flex;align-items:center;gap:10px;"><i class="fas fa-box" style="width:16px;color:var(--j-text-muted);"></i> My Orders</a>
                <a href="<?php echo SITE_URL; ?>Signin/logout.php" style="padding:10px 14px;border-radius:8px;color:var(--j-danger);display:flex;align-items:center;gap:10px;"><i class="fas fa-sign-out-alt" style="width:16px;"></i> Logout</a>
            <?php else: ?>
                <hr style="border-color:var(--j-border-light);">
                <a href="<?php echo SITE_URL; ?>Signin/index.php" class="j-btn j-btn-outline j-btn-full" style="margin-bottom:8px;">Sign In</a>
                <a href="<?php echo SITE_URL; ?>Register/index.php" class="j-btn j-btn-primary j-btn-full">Get Started</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ══ Toast Container ══════════════════════════════════════ -->
<div class="j-toast-container" id="jToastContainer"></div>

<!-- ══ MAIN CONTENT ════════════════════════════════════════ -->
<main id="mainContent">

<?php
// Show session flash messages as toasts (they get shown via JS after DOM load)
$_flashMessages = [];
foreach (['success','error','warning','info'] as $_ft) {
    if (isset($_SESSION[$_ft])) {
        $_flashMessages[] = ['type' => $_ft, 'msg' => $_SESSION[$_ft]];
        unset($_SESSION[$_ft]);
    }
}
?>

<!-- Page-specific scripts will close </main></body></html> via footer.php -->

<script>
// ── Dropdown toggle ───────────────────────────────────────
function toggleDropdown() {
    const d = document.getElementById('userDropdown');
    d.classList.toggle('show');
}
document.addEventListener('click', e => {
    const btn = document.getElementById('userAvatarBtn');
    const dd  = document.getElementById('userDropdown');
    if (dd && btn && !btn.contains(e.target) && !dd.contains(e.target)) {
        dd.classList.remove('show');
    }
});

// ── Mobile nav ────────────────────────────────────────────
function toggleMobileNav() {
    const nav = document.getElementById('mobileNav');
    const isOpen = nav.style.display !== 'none';
    nav.style.display = isOpen ? 'none' : 'block';
    const spans = document.querySelectorAll('.j-nav-toggle span');
    if (!isOpen) {
        spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
        spans[1].style.opacity = '0';
        spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
    } else {
        spans.forEach(s => { s.style.transform = ''; s.style.opacity = ''; });
    }
}

// ── Toast system ──────────────────────────────────────────
function jToast(message, type = 'success', duration = 3500) {
    const c = document.getElementById('jToastContainer');
    if (!c) return;
    const icons = { success:'fa-check-circle', error:'fa-exclamation-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle' };
    const t = document.createElement('div');
    t.className = `j-toast ${type}`;
    t.innerHTML = `<i class="fas ${icons[type]||icons.info}" style="flex-shrink:0;font-size:1rem;"></i><span style="flex:1;">${message}</span><button onclick="this.parentElement.remove()" style="background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;font-size:1rem;padding:0;margin-left:8px;">×</button>`;
    c.appendChild(t);
    setTimeout(() => { t.classList.add('hiding'); setTimeout(() => t.remove(), 300); }, duration);
}

// ── Cart count updater ────────────────────────────────────
function updateCartCount(count) {
    const badge = document.getElementById('navCartCount');
    if (!badge) return;
    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function refreshCartCount() {
    fetch('<?php echo SITE_URL; ?>api/cart_api.php?action=count')
        .then(r => r.json())
        .then(d => { if (d.count !== undefined) updateCartCount(d.count); })
        .catch(() => {});
}

// ── Add to cart global function ───────────────────────────
function addToCart(productId, qty = 1, btn) {
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>'; }

    fetch('<?php echo SITE_URL; ?>api/cart_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add', product_id: productId, quantity: qty })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            jToast('Added to cart!', 'success');
            refreshCartCount();
            if (btn) {
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => { btn.innerHTML = origHtml; btn.disabled = false; }, 2000);
            }
        } else {
            jToast(d.message || 'Could not add to cart', 'error');
            if (btn) { btn.innerHTML = origHtml; btn.disabled = false; }
        }
    })
    .catch(() => {
        jToast('Network error. Please try again.', 'error');
        if (btn) { btn.innerHTML = origHtml; btn.disabled = false; }
    });
}

// ── Toggle wishlist ───────────────────────────────────────
function toggleWishlist(productId, btn) {
    fetch('<?php echo SITE_URL; ?>api/wishlist/toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            if (btn) btn.classList.toggle('active');
            jToast(d.message || 'Wishlist updated', 'success');
        } else if (d.redirect) {
            window.location.href = d.redirect;
        } else {
            jToast(d.message || 'Error', 'error');
        }
    })
    .catch(() => jToast('Network error', 'error'));
}

// ── Flash messages via toast ──────────────────────────────
<?php foreach ($_flashMessages as $fm): ?>
document.addEventListener('DOMContentLoaded', () => jToast(<?php echo json_encode($fm['msg']); ?>, <?php echo json_encode($fm['type']); ?>));
<?php endforeach; ?>

// Refresh cart count on load
document.addEventListener('DOMContentLoaded', refreshCartCount);

// Navbar scroll effect
window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNavbar');
    if (window.scrollY > 10) {
        nav.style.boxShadow = '0 4px 24px rgba(0,0,0,0.1)';
    } else {
        nav.style.boxShadow = '0 2px 20px rgba(0,0,0,0.06)';
    }
});
</script>