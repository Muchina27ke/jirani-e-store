<?php
session_start();
require_once '../config/config.php';

$orderId = (int)($_GET['order_id'] ?? 0);

if (!$orderId) {
    header('Location: shop.php');
    exit;
}

$pdo = getPDO();

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: shop.php');
    exit;
}

// Fetch order items
$itemsStmt = $pdo->prepare("
    SELECT oi.*, p.slug
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = :oid
    ORDER BY oi.id
");
$itemsStmt->execute([':oid' => $orderId]);
$orderItems = $itemsStmt->fetchAll();

// Clear pending order from session
unset($_SESSION['pending_order_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmed #<?= $orderId ?> — AfriGear.tech</title>
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
    <a href="../api/auth/login.php" class="mobile-login">Login</a>
  </nav>
</header>

<main class="success-section">
  <div class="container">
    <div class="success-card">

      <!-- Checkmark -->
      <div class="success-checkmark" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
      </div>

      <h1>Order <span>Confirmed!</span></h1>
      <p style="color:var(--muted);margin-bottom:16px;">
        Thank you, <strong style="color:var(--white);"><?= htmlspecialchars($order['full_name']) ?></strong>.
        Your payment has been received.
      </p>
      <div class="success-order-num">Order #<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></div>

      <!-- Items ordered -->
      <div class="success-items">
        <div class="success-items-header">Items Ordered</div>
        <?php foreach ($orderItems as $item): ?>
        <div class="success-item">
          <div>
            <div class="success-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
            <div class="success-item-meta">
              <?= htmlspecialchars($item['brand'] ?? '') ?>
              &nbsp;·&nbsp; Qty: <?= (int)$item['qty'] ?>
            </div>
          </div>
          <div class="success-item-price">
            KES <?= number_format($item['unit_price'] * $item['qty'], 0) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Totals -->
      <div class="success-totals">
        <div class="summary-row">
          <span class="label">Subtotal</span>
          <span class="value">KES <?= number_format($order['subtotal'], 0) ?></span>
        </div>
        <div class="summary-row">
          <span class="label">Delivery Fee</span>
          <span class="value">KES <?= number_format($order['delivery_fee'], 0) ?></span>
        </div>
        <div class="summary-row total-row">
          <span class="label">Total Paid</span>
          <span class="value">KES <?= number_format($order['total_amount'], 0) ?></span>
        </div>
        <?php if ($order['mpesa_ref']): ?>
        <div class="summary-row">
          <span class="label">M-Pesa Receipt</span>
          <span class="value" style="color:#4ade80;"><?= htmlspecialchars($order['mpesa_ref']) ?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Delivery note -->
      <div class="success-delivery-note">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="2" width="16" height="16"
             style="display:inline;vertical-align:middle;color:#4ade80;margin-right:6px;">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Delivering to: <strong><?= htmlspecialchars($order['delivery_address']) ?>,
        <?= htmlspecialchars($order['town']) ?>, <?= htmlspecialchars($order['county']) ?> County</strong>
        <br><br>
        We will contact you on <strong><?= htmlspecialchars($order['phone']) ?></strong> once your
        order is dispatched. Expected delivery: <strong>24–48 hours</strong>.
      </div>

      <!-- Actions -->
      <div class="success-actions">
        <a href="orders.php" class="btn btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          View My Orders
        </a>
        <a href="shop.php" class="btn btn-outline">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
          </svg>
          Continue Shopping
        </a>
      </div>

    </div>
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
          <li><a href="orders.php">My Orders</a></li>
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
</body>
</html>
