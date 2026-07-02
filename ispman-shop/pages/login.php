<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Cart.php';

// Already logged in → redirect
if (!empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error      = '';
$registered = isset($_GET['registered']);
$redirect   = $_GET['redirect'] ?? $_POST['redirect'] ?? '../index.php';

// Safety: only allow relative redirects within the site
if (str_contains($redirect, '://') || str_starts_with($redirect, '//')) {
    $redirect = '../index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        try {
            $pdo  = getPDO();
            $auth = new Auth($pdo);
            $user = $auth->login($email, $password);

            if ($user) {
                $auth->setSession($user);

                // Merge guest cart into user cart
                if (!empty($_SESSION['cart_session_id'])) {
                    $cart = new Cart($pdo);
                    $cart->mergeSessionCart($_SESSION['cart_session_id'], $user['id']);
                    unset($_SESSION['cart_session_id']); // clean up guest session
                }

                // Admins go to admin panel
                if ($user['role'] === 'admin') {
                    header('Location: ../admin/index.php');
                    exit;
                }

                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Throwable $e) {
            $error = 'Login failed. Please try again.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — AfriGear.tech</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%230a1628'/><text x='3' y='24' font-size='20' font-family='sans-serif' font-weight='900' fill='%23f97316'>A</text></svg>">
</head>
<body class="auth-body">

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
        <li><a href="../index.php#footer">Contact</a></li>
      </ul>
      <div class="navbar-actions">
        <a href="cart.php" class="cart-btn" aria-label="Shopping cart">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
          </svg>
          <span class="cart-count">0</span>
        </a>
        <button class="hamburger" aria-label="Toggle menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>
  </div>
  <nav class="mobile-nav" aria-label="Mobile navigation">
    <a href="../index.php">Home</a>
    <a href="shop.php">Shop</a>
    <a href="register.php" class="mobile-login">Create Account</a>
  </nav>
</header>

<main class="auth-section">
  <div class="auth-card">

    <div class="auth-logo">
      <span class="logo-main">AfriGear</span><span class="logo-tld">.tech</span>
    </div>
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Sign in to your AfriGear.tech account</p>

    <?php if ($registered): ?>
    <div class="auth-alert auth-alert-success" role="alert">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
      Account created! You can now sign in.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="auth-alert auth-alert-error" role="alert">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="auth-form" novalidate>
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-input"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="john@example.com" autocomplete="email" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="password-toggle">
          <input type="password" id="password" name="password" class="form-input"
                 placeholder="••••••••" autocomplete="current-password" required>
          <button type="button" class="password-toggle-btn" data-target="password" aria-label="Toggle password visibility">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="eye-icon">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary auth-submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
        </svg>
        Sign In
      </button>
    </form>

    <p class="auth-footer">
      Don't have an account? <a href="register.php">Create one free</a>
    </p>

    <div class="auth-divider">or</div>

    <p style="text-align:center;font-size:0.8rem;color:var(--muted);">
      <a href="../index.php" style="color:var(--muted);">← Continue as guest</a>
    </p>

  </div>
</main>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.password-toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.target);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      const icon = btn.querySelector('.eye-icon');
      if (icon) {
        icon.innerHTML = show
          ? `<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`
          : `<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
      }
    });
  });
});
</script>
</body>
</html>
