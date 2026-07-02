<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        try {
            $pdo  = getPDO();
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email AND role = 'admin' LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']    = $user['id'];
                $_SESSION['admin_name']  = $user['name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_role']  = $user['role'];

                $redirect = $_GET['redirect'] ?? 'index.php';
                // Safety: only allow relative redirects
                if (!str_starts_with($redirect, '/') && !str_contains($redirect, '://')) {
                    header('Location: ' . $redirect);
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Throwable $e) {
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — AfriGear.tech</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%230a1628'/><text x='3' y='24' font-size='20' font-family='sans-serif' font-weight='900' fill='%23f97316'>A</text></svg>">
</head>
<body class="admin-login-body">

<div class="admin-login-wrap">
  <div class="admin-login-card">

    <!-- Logo -->
    <div class="admin-login-logo">
      <span class="logo-main">AfriGear</span><span class="logo-tld">.tech</span>
    </div>
    <p class="admin-login-sub">Admin Panel — Sign in to continue</p>

    <?php if ($error): ?>
    <div class="admin-alert admin-alert-error" role="alert">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
      <div class="form-group" style="margin-bottom:16px;">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-input"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="admin@afgrigear.tech" required autofocus>
      </div>
      <div class="form-group" style="margin-bottom:24px;">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-input"
               placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px;">
        Sign In to Admin Panel
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:0.8rem;color:var(--muted);">
      <a href="../index.php" style="color:var(--orange);">← Back to Store</a>
    </p>

  </div>
</div>

</body>
</html>
