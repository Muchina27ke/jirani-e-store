<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Cart.php';

if (empty($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = bin2hex(random_bytes(16));
}
$sessionId = $_SESSION['cart_session_id'];
$userId    = $_SESSION['user_id'] ?? null;

$pdo   = getPDO();
$cart  = new Cart($pdo);
$items = $cart->getItems($userId, $sessionId);
$subtotal     = $cart->getTotal($userId, $sessionId);
$deliveryFee  = 500;
$total        = $subtotal + $deliveryFee;
$itemCount    = $cart->getCount($userId, $sessionId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cart (<?= $itemCount ?>) — AfriGear.tech</title>
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
          <span class="cart-count"><?= $itemCount ?></span>
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

<!-- PAGE BANNER -->
<section class="page-banner">
  <div class="container">
    <h1>Your <span>Cart</span></h1>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="../index.php">Home</a>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      <span>Cart</span>
    </nav>
  </div>
</section>

<!-- CART SECTION -->
<main class="cart-section">
  <div class="container">
    <div class="cart-layout" id="cartLayout">

      <!-- LEFT: Cart items -->
      <div class="cart-items-wrap">

        <?php if (empty($items)): ?>
        <!-- Empty state -->
        <div class="cart-items-card">
          <div class="cart-empty" id="cartEmpty">
            <div class="cart-empty-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
              </svg>
            </div>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added anything yet. Browse our products and find what you need.</p>
            <a href="shop.php" class="btn btn-primary">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
              Go Shopping
            </a>
          </div>
        </div>

        <?php else: ?>
        <div class="cart-items-card" id="cartItemsCard">
          <!-- Table header -->
          <div class="cart-items-header" aria-hidden="true">
            <span>Product</span>
            <span>Unit Price</span>
            <span>Quantity</span>
            <span>Subtotal</span>
            <span></span>
          </div>

          <!-- Items -->
          <div id="cartItemsList">
          <?php foreach ($items as $item):
            $itemImages = json_decode($item['images'] ?? '[]', true);
            $itemImg    = $itemImages[0] ?? null;
            $rowSubtotal = $item['price'] * $item['qty'];
          ?>
          <div class="cart-item" id="cartItem-<?= (int)$item['cart_id'] ?>"
               data-cart-id="<?= (int)$item['cart_id'] ?>">

            <!-- Product -->
            <div class="cart-item-product">
              <div class="cart-item-img">
                <?php if ($itemImg): ?>
                  <img src="<?= htmlspecialchars($itemImg) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                <?php else: ?>
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                <?php endif; ?>
              </div>
              <div>
                <div class="cart-item-name">
                  <a href="product.php?slug=<?= urlencode($item['slug']) ?>" style="color:inherit;">
                    <?= htmlspecialchars($item['name']) ?>
                  </a>
                </div>
                <div class="cart-item-brand"><?= htmlspecialchars($item['brand'] ?? 'Generic') ?></div>
              </div>
            </div>

            <!-- Unit price -->
            <div class="cart-item-price">KES <?= number_format($item['price'], 0) ?></div>

            <!-- Qty controls -->
            <div class="cart-item-qty">
              <div class="cart-qty" role="group" aria-label="Quantity for <?= htmlspecialchars($item['name'], ENT_QUOTES) ?>">
                <button class="cart-qty-btn cart-qty-minus"
                        data-cart-id="<?= (int)$item['cart_id'] ?>"
                        aria-label="Decrease quantity">−</button>
                <input type="number" class="cart-qty-input"
                       value="<?= (int)$item['qty'] ?>"
                       min="1" max="<?= (int)$item['stock_qty'] ?>"
                       data-cart-id="<?= (int)$item['cart_id'] ?>"
                       aria-label="Quantity">
                <button class="cart-qty-btn cart-qty-plus"
                        data-cart-id="<?= (int)$item['cart_id'] ?>"
                        aria-label="Increase quantity">+</button>
              </div>
            </div>

            <!-- Subtotal -->
            <div class="cart-item-subtotal" id="subtotal-<?= (int)$item['cart_id'] ?>">
              KES <?= number_format($rowSubtotal, 0) ?>
            </div>

            <!-- Remove -->
            <div class="cart-item-remove">
              <button class="cart-remove-btn"
                      data-cart-id="<?= (int)$item['cart_id'] ?>"
                      aria-label="Remove <?= htmlspecialchars($item['name'], ENT_QUOTES) ?> from cart">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>

          </div>
          <?php endforeach; ?>
          </div><!-- /cartItemsList -->
        </div><!-- /cart-items-card -->
        <?php endif; ?>

        <!-- Continue shopping link -->
        <div style="margin-top:16px;">
          <a href="shop.php" class="continue-shopping">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Continue Shopping
          </a>
        </div>
      </div><!-- /cart-items-wrap -->

      <!-- RIGHT: Order summary -->
      <aside class="order-summary" id="orderSummary" aria-label="Order summary">
        <h3>Order Summary</h3>

        <div class="summary-rows">
          <div class="summary-row">
            <span class="label">Subtotal (<span id="summaryCount"><?= $itemCount ?></span> items)</span>
            <span class="value" id="summarySubtotal">KES <?= number_format($subtotal, 0) ?></span>
          </div>
          <div class="summary-row">
            <span class="label">Delivery Fee</span>
            <span class="value">KES <?= number_format($deliveryFee, 0) ?></span>
          </div>
          <div class="summary-row total-row">
            <span class="label">Total</span>
            <span class="value" id="summaryTotal">KES <?= number_format($total, 0) ?></span>
          </div>
        </div>

        <a href="checkout.php" class="btn btn-primary checkout-btn"
           id="checkoutBtn"
           <?= empty($items) ? 'style="opacity:0.5;pointer-events:none;"' : '' ?>>
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Proceed to Checkout
        </a>

        <p class="summary-note">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          Secure checkout via M-Pesa STK Push
        </p>
      </aside>

    </div><!-- /cart-layout -->
  </div><!-- /container -->
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
          <li><a href="checkout.php">Checkout</a></li>
          <li><a href="orders.php">My Orders</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Support</h4>
        <ul>
          <li><a href="#">Returns Policy</a></li>
          <li><a href="#">Shipping Info</a></li>
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
  <span id="toastMsg">Done</span>
</div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const DELIVERY_FEE = 500;

  function fmt(n) {
    return 'KES ' + Math.round(n).toLocaleString('en-KE');
  }

  function updateSummary(count, subtotal) {
    const el = id => document.getElementById(id);
    if (el('summaryCount'))    el('summaryCount').textContent    = count;
    if (el('summarySubtotal')) el('summarySubtotal').textContent = fmt(subtotal);
    if (el('summaryTotal'))    el('summaryTotal').textContent    = fmt(subtotal + DELIVERY_FEE);
    const btn = el('checkoutBtn');
    if (btn) {
      btn.style.opacity        = count > 0 ? '1' : '0.5';
      btn.style.pointerEvents  = count > 0 ? 'auto' : 'none';
    }
  }

  function showEmpty() {
    const card = document.getElementById('cartItemsCard');
    if (card) {
      card.innerHTML = `
        <div class="cart-empty">
          <div class="cart-empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
            </svg>
          </div>
          <h3>Your cart is empty</h3>
          <p>All items have been removed.</p>
          <a href="shop.php" class="btn btn-primary">Go Shopping</a>
        </div>`;
    }
    updateSummary(0, 0);
  }

  /* ---- Remove item ---- */
  document.addEventListener('click', e => {
    const btn = e.target.closest('.cart-remove-btn');
    if (!btn) return;
    const cartId = btn.dataset.cartId;
    const row    = document.getElementById('cartItem-' + cartId);
    if (row) row.classList.add('updating');

    fetch('../api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=remove&cart_item_id=${cartId}`
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        row?.remove();
        AfriCart.updateBadge(data.count);
        const remaining = document.querySelectorAll('.cart-item').length;
        if (remaining === 0) {
          showEmpty();
        } else {
          updateSummary(data.count, data.subtotal);
        }
        AfriCart.toast('Item removed from cart', 'success');
      }
    })
    .catch(() => { if (row) row.classList.remove('updating'); });
  });

  /* ---- Update quantity ---- */
  function doUpdate(cartId, qty) {
    const row = document.getElementById('cartItem-' + cartId);
    if (row) row.classList.add('updating');

    fetch('../api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=update&cart_item_id=${cartId}&qty=${qty}`
    })
    .then(r => r.json())
    .then(data => {
      if (row) row.classList.remove('updating');
      if (data.success) {
        const subtotalEl = document.getElementById('subtotal-' + cartId);
        if (subtotalEl) subtotalEl.textContent = fmt(data.row_subtotal);
        AfriCart.updateBadge(data.count);
        updateSummary(data.count, data.cart_total);
      }
    })
    .catch(() => { if (row) row.classList.remove('updating'); });
  }

  /* Minus / Plus buttons */
  document.addEventListener('click', e => {
    const minus = e.target.closest('.cart-qty-minus');
    const plus  = e.target.closest('.cart-qty-plus');
    if (!minus && !plus) return;

    const cartId = (minus || plus).dataset.cartId;
    const input  = document.querySelector(`.cart-qty-input[data-cart-id="${cartId}"]`);
    if (!input) return;

    let val = parseInt(input.value) || 1;
    if (minus) val = Math.max(1, val - 1);
    if (plus)  val = Math.min(parseInt(input.max) || 99, val + 1);
    input.value = val;
    doUpdate(cartId, val);
  });

  /* Direct input change */
  document.addEventListener('change', e => {
    const input = e.target.closest('.cart-qty-input');
    if (!input) return;
    const cartId = input.dataset.cartId;
    let val = parseInt(input.value) || 1;
    val = Math.max(1, Math.min(val, parseInt(input.max) || 99));
    input.value = val;
    doUpdate(cartId, val);
  });
});
</script>
</body>
</html>
