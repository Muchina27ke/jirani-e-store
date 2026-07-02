<?php
/**
 * AfriGear.tech — Shared Navbar Partial
 * Include after session_start() on any page.
 *
 * Variables expected (set before including):
 *   $navActive  — string: 'home'|'shop'|'cart'|'orders' etc.
 *   $navBase    — string: '' (root) or '../' (pages/)
 */
$navBase   = $navBase   ?? '';
$navActive = $navActive ?? '';

$isLoggedIn  = !empty($_SESSION['user_id']);
$userName    = $_SESSION['user_name']  ?? '';
$userEmail   = $_SESSION['user_email'] ?? '';
$userInitial = $userName ? strtoupper(mb_substr($userName, 0, 1)) : 'U';
$firstName   = explode(' ', $userName)[0];

// Cart count from session
$cartCount = 0;
if ($isLoggedIn || !empty($_SESSION['cart_session_id'])) {
    try {
        if (!isset($pdo)) $pdo = getPDO();
        require_once __DIR__ . '/Cart.php';
        $navCart   = new Cart($pdo);
        $navSid    = $_SESSION['cart_session_id'] ?? null;
        $navUid    = $isLoggedIn ? (int)$_SESSION['user_id'] : null;
        $cartCount = $navCart->getCount($navUid, $navSid);
    } catch (Throwable $e) { /* silent */ }
}
?>
<header class="navbar" role="banner">
  <div class="container">
    <nav class="navbar-inner" aria-label="Main navigation">

      <!-- Logo -->
      <a href="<?= $navBase ?>index.php" class="navbar-logo" aria-label="AfriGear.tech Home">
        <span class="logo-main">AfriGear</span><span class="logo-tld">.tech</span>
      </a>

      <!-- Desktop nav links -->
      <ul class="navbar-links" role="list">
        <li><a href="<?= $navBase ?>index.php" <?= $navActive==='home' ? 'class="active"' : '' ?>>Home</a></li>
        <li><a href="<?= $navBase ?>pages/shop.php" <?= $navActive==='shop' ? 'class="active"' : '' ?>>Shop</a></li>
        <li><a href="<?= $navBase ?>index.php#categories">Categories</a></li>
        <li><a href="<?= $navBase ?>index.php#why-us">About</a></li>
        <li><a href="<?= $navBase ?>index.php#footer">Contact</a></li>
      </ul>


      <!-- Right actions -->
      <div class="navbar-actions">

        <!-- Cart -->
        <a href="<?= $navBase ?>pages/cart.php" class="cart-btn" aria-label="Shopping cart">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
          </svg>
          <span class="cart-count" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span>
        </a>

        <?php if ($isLoggedIn): ?>
        <!-- User menu -->
        <div class="user-menu" id="userMenu">
          <button class="user-menu-btn" id="userMenuBtn"
                  aria-haspopup="true" aria-expanded="false"
                  aria-label="Account menu for <?= htmlspecialchars($firstName) ?>">
            <div class="user-menu-avatar" aria-hidden="true"><?= htmlspecialchars($userInitial) ?></div>
            <span><?= htmlspecialchars($firstName) ?></span>
            <svg class="user-menu-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <div class="user-dropdown" id="userDropdown" role="menu">
            <div class="user-dropdown-header">
              <div class="user-dropdown-name"><?= htmlspecialchars($userName) ?></div>
              <div class="user-dropdown-email"><?= htmlspecialchars($userEmail) ?></div>
            </div>

            <a href="<?= $navBase ?>pages/orders.php" class="user-dropdown-item" role="menuitem">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
              My Orders
            </a>

            <a href="<?= $navBase ?>pages/account.php" class="user-dropdown-item" role="menuitem">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              My Profile
            </a>

            <div class="user-dropdown-divider"></div>

            <a href="<?= $navBase ?>pages/logout.php" class="user-dropdown-item danger" role="menuitem">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
              </svg>
              Logout
            </a>
          </div>
        </div>

        <?php else: ?>
        <!-- Guest: Login button -->
        <a href="<?= $navBase ?>pages/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '') ?>"
           class="btn btn-primary login-btn">Login</a>
        <?php endif; ?>

        <!-- Hamburger -->
        <button class="hamburger" aria-label="Toggle menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>

    </nav>
  </div>

  <!-- Mobile nav -->
  <nav class="mobile-nav" aria-label="Mobile navigation">
    <a href="<?= $navBase ?>index.php">Home</a>
    <a href="<?= $navBase ?>pages/shop.php">Shop</a>
    <a href="<?= $navBase ?>index.php#categories">Categories</a>
    <a href="<?= $navBase ?>index.php#footer">Contact</a>
    <?php if ($isLoggedIn): ?>
      <a href="<?= $navBase ?>pages/orders.php">My Orders</a>
      <a href="<?= $navBase ?>pages/logout.php" class="mobile-login">Logout</a>
    <?php else: ?>
      <a href="<?= $navBase ?>pages/login.php" class="mobile-login">Login</a>
    <?php endif; ?>
  </nav>
</header>
