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

// Redirect to cart if empty
if (empty($items)) {
    header('Location: cart.php');
    exit;
}

$subtotal    = $cart->getTotal($userId, $sessionId);
$deliveryFee = DELIVERY_FEE;
$total       = $subtotal + $deliveryFee;
$itemCount   = $cart->getCount($userId, $sessionId);

// 47 Kenya counties
$counties = [
    'Baringo','Bomet','Bungoma','Busia','Elgeyo-Marakwet','Embu','Garissa',
    'Homa Bay','Isiolo','Kajiado','Kakamega','Kericho','Kiambu','Kilifi',
    'Kirinyaga','Kisii','Kisumu','Kitui','Kwale','Laikipia','Lamu','Machakos',
    'Makueni','Mandera','Marsabit','Meru','Migori','Mombasa','Murang\'a',
    'Nairobi','Nakuru','Nandi','Narok','Nyamira','Nyandarua','Nyeri',
    'Samburu','Siaya','Taita-Taveta','Tana River','Tharaka-Nithi','Trans Nzoia',
    'Turkana','Uasin Gishu','Vihiga','Wajir','West Pokot',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout — AfriGear.tech</title>
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
    <a href="cart.php">Cart</a>
    <a href="../api/auth/login.php" class="mobile-login">Login</a>
  </nav>
</header>

<!-- PAGE BANNER -->
<section class="page-banner">
  <div class="container">
    <h1>Secure <span>Checkout</span></h1>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="../index.php">Home</a>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      <a href="cart.php">Cart</a>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      <span>Checkout</span>
    </nav>
  </div>
</section>

<!-- CHECKOUT SECTION -->
<main class="checkout-section">
  <div class="container">
    <div class="checkout-layout">

      <!-- LEFT: Form -->
      <div class="checkout-form-card">

        <!-- Section 1: Your Details -->
        <div class="form-section">
          <div class="form-section-title">
            <span class="form-section-num">1</span>
            Your Details
          </div>
          <div class="form-grid">
            <div class="form-group full">
              <label class="form-label" for="full_name">Full Name <span class="required">*</span></label>
              <input type="text" id="full_name" name="full_name" class="form-input"
                     placeholder="e.g. John Kamau" autocomplete="name" required>
              <span class="form-error-msg" id="err_full_name">Please enter your full name</span>
            </div>
            <div class="form-group">
              <label class="form-label" for="email">Email Address</label>
              <input type="email" id="email" name="email" class="form-input"
                     placeholder="john@example.com" autocomplete="email">
            </div>
            <div class="form-group">
              <label class="form-label" for="phone">M-Pesa Phone <span class="required">*</span></label>
              <input type="tel" id="phone" name="phone" class="form-input"
                     placeholder="07XX XXX XXX" autocomplete="tel" required>
              <span class="form-error-msg" id="err_phone">Enter a valid Kenyan phone number</span>
            </div>
          </div>
        </div>

        <!-- Section 2: Delivery Address -->
        <div class="form-section">
          <div class="form-section-title">
            <span class="form-section-num">2</span>
            Delivery Address
          </div>
          <div class="form-grid">
            <div class="form-group full">
              <label class="form-label" for="delivery_address">Street / Building <span class="required">*</span></label>
              <input type="text" id="delivery_address" name="delivery_address" class="form-input"
                     placeholder="e.g. Tom Mboya St, Rehema House, 3rd Floor" required>
              <span class="form-error-msg" id="err_delivery_address">Please enter your delivery address</span>
            </div>
            <div class="form-group">
              <label class="form-label" for="town">Town / City <span class="required">*</span></label>
              <input type="text" id="town" name="town" class="form-input"
                     placeholder="e.g. Nairobi" required>
              <span class="form-error-msg" id="err_town">Please enter your town</span>
            </div>
            <div class="form-group">
              <label class="form-label" for="county">County <span class="required">*</span></label>
              <select id="county" name="county" class="form-select" required>
                <option value="">— Select County —</option>
                <?php foreach ($counties as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </select>
              <span class="form-error-msg" id="err_county">Please select your county</span>
            </div>
          </div>
        </div>

        <!-- Section 3: Payment -->
        <div class="form-section">
          <div class="form-section-title">
            <span class="form-section-num">3</span>
            Payment Method
          </div>

          <div class="payment-option" role="radio" aria-checked="true" tabindex="0">
            <div class="mpesa-logo">
              <div class="mpesa-logo-mark">M-<br>PESA</div>
            </div>
            <div class="mpesa-label">
              <strong>Pay via M-Pesa</strong>
              <span>STK Push — you'll receive a prompt on your phone</span>
            </div>
            <div class="mpesa-selected">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
          </div>

          <div style="margin-top:24px;">
            <button type="button" id="payBtn" class="btn btn-primary pay-btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
              Pay KES <?= number_format($total, 0) ?> via M-Pesa
            </button>
          </div>
        </div>

      </div><!-- /checkout-form-card -->

      <!-- RIGHT: Order Summary -->
      <aside class="checkout-summary" aria-label="Order summary">
        <div class="checkout-summary-header">
          Order Summary
          <span style="color:var(--muted);font-weight:400;font-size:0.82rem;margin-left:6px;">(<?= $itemCount ?> items)</span>
        </div>

        <div class="checkout-summary-items">
          <?php foreach ($items as $item):
            $imgs = json_decode($item['images'] ?? '[]', true);
            $img  = $imgs[0] ?? null;
          ?>
          <div class="summary-item">
            <div class="summary-item-img">
              <?php if ($img): ?>
                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              <?php endif; ?>
              <span class="summary-item-qty-badge"><?= (int)$item['qty'] ?></span>
            </div>
            <div class="summary-item-info">
              <div class="summary-item-name"><?= htmlspecialchars($item['name']) ?></div>
              <div class="summary-item-brand"><?= htmlspecialchars($item['brand'] ?? 'Generic') ?></div>
            </div>
            <div class="summary-item-price">KES <?= number_format($item['price'] * $item['qty'], 0) ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="checkout-summary-totals">
          <div class="summary-row">
            <span class="label">Subtotal</span>
            <span class="value">KES <?= number_format($subtotal, 0) ?></span>
          </div>
          <div class="summary-row">
            <span class="label">Delivery Fee</span>
            <span class="value">KES <?= number_format($deliveryFee, 0) ?></span>
          </div>
          <div class="summary-row total-row">
            <span class="label">Total</span>
            <span class="value">KES <?= number_format($total, 0) ?></span>
          </div>
        </div>
      </aside>

    </div><!-- /checkout-layout -->
  </div><!-- /container -->
</main>

<!-- ============================================================
     PAYMENT OVERLAY
     ============================================================ -->
<div class="payment-overlay" id="paymentOverlay" role="dialog" aria-modal="true" aria-labelledby="overlayTitle">
  <div class="payment-modal" id="paymentModal">

    <!-- Loading state -->
    <div id="stateLoading">
      <div class="payment-modal-icon loading" aria-hidden="true">
        <div class="spinner-ring"></div>
      </div>
      <h3 id="overlayTitle">Sending Payment Request…</h3>
      <p>A payment prompt is being sent to</p>
      <p class="phone-highlight" id="overlayPhone"></p>
      <p>Enter your M-Pesa PIN when prompted.</p>
      <p class="payment-timer">Waiting for confirmation… <span id="timerDisplay">2:00</span></p>
    </div>

    <!-- Success state (hidden) -->
    <div id="stateSuccess" style="display:none;">
      <div class="payment-modal-icon success" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
      <h3>Payment Confirmed!</h3>
      <p>Redirecting to your order confirmation…</p>
    </div>

    <!-- Error state (hidden) -->
    <div id="stateError" style="display:none;">
      <div class="payment-modal-icon error" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </div>
      <h3>Payment Not Completed</h3>
      <p id="errorMsg">Payment was not completed. Please try again or contact support.</p>
      <div class="payment-modal-actions">
        <button class="btn btn-primary" id="retryBtn">Try Again</button>
        <a href="cart.php" class="btn btn-outline">Back to Cart</a>
      </div>
    </div>

  </div>
</div>

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

<script src="../assets/js/main.js"></script>
<script src="../assets/js/cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  const payBtn  = document.getElementById('payBtn');
  const overlay = document.getElementById('paymentOverlay');
  const retryBtn = document.getElementById('retryBtn');

  let pollInterval = null;
  let pollTimeout  = null;
  let currentOrderId = null;
  let currentCheckoutId = null;

  /* ── Helpers ── */
  function showState(name) {
    ['Loading','Success','Error'].forEach(s => {
      document.getElementById('state' + s).style.display = s === name ? '' : 'none';
    });
  }

  function showOverlay() { overlay.classList.add('show'); }
  function hideOverlay() {
    overlay.classList.remove('show');
    stopPolling();
  }

  function stopPolling() {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    if (pollTimeout)  { clearTimeout(pollTimeout);   pollTimeout  = null; }
  }

  /* ── Countdown timer ── */
  function startTimer(seconds) {
    const el = document.getElementById('timerDisplay');
    let remaining = seconds;
    const tick = () => {
      const m = Math.floor(remaining / 60);
      const s = remaining % 60;
      if (el) el.textContent = m + ':' + String(s).padStart(2, '0');
      remaining--;
    };
    tick();
    return setInterval(tick, 1000);
  }

  /* ── Form validation ── */
  function validate() {
    let ok = true;
    const fields = [
      { id: 'full_name',        errId: 'err_full_name',        check: v => v.trim().length >= 2 },
      { id: 'phone',            errId: 'err_phone',            check: v => /^(\+?254|0)[17]\d{8}$/.test(v.replace(/\s/g,'')) },
      { id: 'delivery_address', errId: 'err_delivery_address', check: v => v.trim().length >= 5 },
      { id: 'town',             errId: 'err_town',             check: v => v.trim().length >= 2 },
      { id: 'county',           errId: 'err_county',           check: v => v !== '' },
    ];
    fields.forEach(f => {
      const input = document.getElementById(f.id);
      const err   = document.getElementById(f.errId);
      const valid = f.check(input?.value || '');
      input?.classList.toggle('error', !valid);
      err?.classList.toggle('show', !valid);
      if (!valid) ok = false;
    });
    return ok;
  }

  /* ── Poll for payment status ── */
  function startPolling(checkoutRequestId, orderId) {
    currentCheckoutId = checkoutRequestId;
    currentOrderId    = orderId;

    const timerInterval = startTimer(120);

    // Timeout after 2 minutes
    pollTimeout = setTimeout(() => {
      stopPolling();
      clearInterval(timerInterval);
      showState('Error');
      document.getElementById('errorMsg').textContent =
        'Payment timed out. Please try again or contact support.';
    }, 120000);

    pollInterval = setInterval(() => {
      fetch('../api/mpesa/check_payment.php?checkout_request_id=' + encodeURIComponent(checkoutRequestId))
        .then(r => r.json())
        .then(data => {
          if (data.status === 'completed') {
            stopPolling();
            clearInterval(timerInterval);
            showState('Success');
            // Clear cart in session then redirect
            fetch('../api/cart.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'action=clear'
            }).finally(() => {
              setTimeout(() => {
                window.location.href = 'order_success.php?order_id=' + data.order_id;
              }, 1200);
            });
          } else if (data.status === 'failed') {
            stopPolling();
            clearInterval(timerInterval);
            showState('Error');
            document.getElementById('errorMsg').textContent =
              data.message || 'Payment failed. Please try again.';
          }
          // 'pending' → keep polling
        })
        .catch(() => { /* network hiccup — keep polling */ });
    }, 3000);
  }

  /* ── Pay button click ── */
  payBtn.addEventListener('click', async () => {
    if (!validate()) return;

    const phone   = document.getElementById('phone').value.trim();
    const fullName = document.getElementById('full_name').value.trim();

    // Show overlay immediately
    document.getElementById('overlayPhone').textContent = phone;
    showState('Loading');
    showOverlay();
    payBtn.disabled = true;

    try {
      // Step 1: Create order
      const formData = new FormData();
      formData.append('full_name',        fullName);
      formData.append('email',            document.getElementById('email').value.trim());
      formData.append('phone',            phone);
      formData.append('delivery_address', document.getElementById('delivery_address').value.trim());
      formData.append('town',             document.getElementById('town').value.trim());
      formData.append('county',           document.getElementById('county').value);

      const orderRes  = await fetch('../api/orders/create.php', { method: 'POST', body: formData });
      const orderData = await orderRes.json();

      if (!orderData.success) {
        hideOverlay();
        payBtn.disabled = false;
        alert(orderData.message || 'Could not create order. Please try again.');
        return;
      }

      currentOrderId = orderData.order_id;

      // Step 2: Initiate STK Push
      const stkBody = new URLSearchParams({
        phone:    phone,
        amount:   orderData.total,
        order_id: orderData.order_id,
      });
      const stkRes  = await fetch('../api/mpesa/stk_push.php', { method: 'POST', body: stkBody });
      const stkData = await stkRes.json();

      if (!stkData.success) {
        hideOverlay();
        payBtn.disabled = false;
        showState('Error');
        showOverlay();
        document.getElementById('errorMsg').textContent =
          stkData.message || 'Could not initiate M-Pesa payment. Please try again.';
        return;
      }

      // Step 3: Start polling
      startPolling(stkData.checkout_request_id, orderData.order_id);

    } catch (err) {
      hideOverlay();
      payBtn.disabled = false;
      alert('Network error. Please check your connection and try again.');
    }
  });

  /* Retry button */
  retryBtn?.addEventListener('click', () => {
    hideOverlay();
    payBtn.disabled = false;
  });

  /* Clear field errors on input */
  document.querySelectorAll('.form-input, .form-select').forEach(el => {
    el.addEventListener('input', () => {
      el.classList.remove('error');
      const errEl = document.getElementById('err_' + el.id);
      if (errEl) errEl.classList.remove('show');
    });
  });

});
</script>
</body>
</html>
