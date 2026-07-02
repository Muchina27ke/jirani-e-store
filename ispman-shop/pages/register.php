<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';

// Already logged in → go home
if (!empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$errors  = [];
$success = '';
$old     = []; // repopulate form on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';
    $old      = compact('name', 'email', 'phone');

    // Validate
    if (strlen($name) < 2)
        $errors['name'] = 'Please enter your full name (at least 2 characters).';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Please enter a valid email address.';

    $cleanPhone = preg_replace('/\D/', '', $phone);
    if (strlen($cleanPhone) < 9 || strlen($cleanPhone) > 13)
        $errors['phone'] = 'Please enter a valid Kenyan phone number.';

    if (strlen($password) < 8)
        $errors['password'] = 'Password must be at least 8 characters.';

    if ($password !== $confirm)
        $errors['confirm'] = 'Passwords do not match.';

    if (empty($errors)) {
        try {
            $pdo  = getPDO();
            $auth = new Auth($pdo);
            $id   = $auth->register($name, $email, $phone, $password);

            if ($id === false) {
                $errors['email'] = 'An account with this email already exists.';
            } else {
                // Auto-login after registration
                $user = $auth->login($email, $password);
                if ($user) {
                    $auth->setSession($user);
                    // Merge any guest cart
                    if (!empty($_SESSION['cart_session_id'])) {
                        require_once '../includes/Cart.php';
                        $cart = new Cart($pdo);
                        $cart->mergeSessionCart($_SESSION['cart_session_id'], $id);
                    }
                }
                header('Location: login.php?registered=1');
                exit;
            }
        } catch (Throwable $e) {
            $errors['general'] = 'Registration failed. Please try again.';
            error_log('Register error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — AfriGear.tech</title>
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
        <a href="login.php" class="btn btn-primary login-btn">Login</a>
        <button class="hamburger" aria-label="Toggle menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>
  </div>
  <nav class="mobile-nav" aria-label="Mobile navigation">
    <a href="../index.php">Home</a>
    <a href="shop.php">Shop</a>
    <a href="login.php" class="mobile-login">Login</a>
  </nav>
</header>

<main class="auth-section">
  <div class="auth-card">

    <div class="auth-logo">
      <span class="logo-main">AfriGear</span><span class="logo-tld">.tech</span>
    </div>
    <h1 class="auth-title">Create your account</h1>
    <p class="auth-subtitle">Join thousands of ISPs shopping on AfriGear.tech</p>

    <?php if (!empty($errors['general'])): ?>
    <div class="auth-alert auth-alert-error" role="alert">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <?= htmlspecialchars($errors['general']) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="register.php" class="auth-form" novalidate>

      <!-- Full name -->
      <div class="form-group">
        <label class="form-label" for="name">Full Name <span class="required">*</span></label>
        <input type="text" id="name" name="name" class="form-input <?= isset($errors['name']) ? 'error' : '' ?>"
               value="<?= htmlspecialchars($old['name'] ?? '') ?>"
               placeholder="e.g. John Kamau" autocomplete="name" required autofocus>
        <?php if (isset($errors['name'])): ?>
          <span class="form-error-msg show"><?= htmlspecialchars($errors['name']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Email -->
      <div class="form-group">
        <label class="form-label" for="email">Email Address <span class="required">*</span></label>
        <input type="email" id="email" name="email" class="form-input <?= isset($errors['email']) ? 'error' : '' ?>"
               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
               placeholder="john@example.com" autocomplete="email" required>
        <?php if (isset($errors['email'])): ?>
          <span class="form-error-msg show"><?= htmlspecialchars($errors['email']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Phone -->
      <div class="form-group">
        <label class="form-label" for="phone">Phone Number <span class="required">*</span></label>
        <input type="tel" id="phone" name="phone" class="form-input <?= isset($errors['phone']) ? 'error' : '' ?>"
               value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
               placeholder="07XX XXX XXX" autocomplete="tel" required>
        <?php if (isset($errors['phone'])): ?>
          <span class="form-error-msg show"><?= htmlspecialchars($errors['phone']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label class="form-label" for="password">Password <span class="required">*</span></label>
        <div class="password-toggle">
          <input type="password" id="password" name="password"
                 class="form-input <?= isset($errors['password']) ? 'error' : '' ?>"
                 placeholder="At least 8 characters" autocomplete="new-password" required>
          <button type="button" class="password-toggle-btn" data-target="password" aria-label="Toggle password visibility">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="eye-icon">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </div>
        <div class="password-strength" aria-hidden="true">
          <div class="password-strength-bar" id="strengthBar"></div>
        </div>
        <?php if (isset($errors['password'])): ?>
          <span class="form-error-msg show"><?= htmlspecialchars($errors['password']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Confirm password -->
      <div class="form-group">
        <label class="form-label" for="confirm">Confirm Password <span class="required">*</span></label>
        <div class="password-toggle">
          <input type="password" id="confirm" name="confirm"
                 class="form-input <?= isset($errors['confirm']) ? 'error' : '' ?>"
                 placeholder="Repeat your password" autocomplete="new-password" required>
          <button type="button" class="password-toggle-btn" data-target="confirm" aria-label="Toggle password visibility">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="eye-icon">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </div>
        <?php if (isset($errors['confirm'])): ?>
          <span class="form-error-msg show"><?= htmlspecialchars($errors['confirm']) ?></span>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary auth-submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        Create Account
      </button>

    </form>

    <p class="auth-footer">
      Already have an account? <a href="login.php">Sign in</a>
    </p>

  </div>
</main>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  /* Password visibility toggles */
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

  /* Password strength meter */
  const pwInput   = document.getElementById('password');
  const strengthBar = document.getElementById('strengthBar');
  if (pwInput && strengthBar) {
    pwInput.addEventListener('input', () => {
      const v = pwInput.value;
      let score = 0;
      if (v.length >= 8)                    score++;
      if (/[A-Z]/.test(v))                  score++;
      if (/[0-9]/.test(v))                  score++;
      if (/[^A-Za-z0-9]/.test(v))           score++;
      strengthBar.className = 'password-strength-bar ' +
        (score <= 1 ? 'strength-weak' : score <= 2 ? 'strength-medium' : 'strength-strong');
    });
  }

  /* Clear field errors on input */
  document.querySelectorAll('.form-input').forEach(el => {
    el.addEventListener('input', () => {
      el.classList.remove('error');
      el.nextElementSibling?.classList?.remove('show');
    });
  });

});
</script>
</body>
</html>
