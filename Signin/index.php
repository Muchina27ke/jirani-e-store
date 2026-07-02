<?php
require_once dirname(__DIR__) . "/config/config.php";

$error   = '';
$success = '';
$forgot_error   = '';
$forgot_success = '';

// Redirect if already logged in
if ($user->is_logged_in()) {
    if ($user->is_admin())   header("Location: ../admin/index.php");
    elseif ($user->is_vendor()) header("Location: ../seller/dashboard.php");
    else header("Location: ../index.php");
    exit;
}

if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
    $success = "Account created! Please sign in.";
}

// Forgot password
if (isset($_POST['forgot_password_submit'])) {
    $identifier = trim($_POST['forgot_email_or_phone'] ?? '');
    if (empty($identifier)) {
        $forgot_error = 'Please enter your email or phone number.';
    } else {
        $result = $auth->generatePasswordResetToken($identifier);
        if ($result['success']) {
            $forgot_success = 'Reset instructions sent to your email (if it exists).';
        } else {
            $forgot_error = $result['message'];
        }
    }
}

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_or_phone'], $_POST['password'])) {
    $email_or_phone = trim($_POST['email_or_phone']);
    $password       = $_POST['password'];
    $remember       = isset($_POST['remember']);

    if (empty($email_or_phone) || empty($password)) {
        $error = 'Email/phone and password are required.';
    } elseif ($user->login($email_or_phone, $password, $remember)) {
        $redirect = $_GET['redirect'] ?? null;
        if ($user->is_admin())    header("Location: ../admin/index.php");
        elseif ($user->is_vendor()) header("Location: ../seller/dashboard.php");
        elseif ($redirect) header("Location: ../" . urldecode($redirect));
        else header("Location: ../index.php");
        exit;
    } else {
        $error = 'Invalid email/phone or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Jirani</title>
    <link rel="icon" type="image/jpeg" href="../title_logo.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/jirani-design-system.css">
    <style>
    html, body { height: 100%; }

    .auth-page {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1fr 1fr;
        font-family: var(--j-font-body);
    }

    /* Brand side */
    .auth-brand {
        background: linear-gradient(145deg, var(--j-primary) 0%, var(--j-primary-dark) 55%, #0a1f10 100%);
        padding: 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .auth-brand::before {
        content:''; position:absolute;
        width:500px;height:500px;border-radius:50%;
        background:rgba(255,255,255,0.03);
        top:-150px;right:-150px;
    }
    .auth-brand::after {
        content:''; position:absolute;
        width:300px;height:300px;border-radius:50%;
        background:rgba(255,167,38,0.07);
        bottom:-80px;left:-60px;
    }

    .auth-brand-inner { position:relative;z-index:1; }

    .auth-brand-logo {
        display:flex;align-items:center;gap:12px;
        font-family:var(--j-font-heading);font-size:1.8rem;font-weight:800;
        color:white;margin-bottom:48px;
    }

    .auth-brand-logo .logo-icon {
        width:44px;height:44px;border-radius:12px;
        background:rgba(255,255,255,0.15);
        display:flex;align-items:center;justify-content:center;
        font-size:1.4rem;
    }

    .auth-brand h2 {
        font-family:var(--j-font-heading);font-size:2.4rem;font-weight:800;
        line-height:1.15;color:white;margin-bottom:20px;
    }

    .auth-brand h2 .highlight { color:var(--j-accent); }

    .auth-brand p {
        color:rgba(255,255,255,0.65);font-size:1rem;
        line-height:1.7;max-width:340px;margin-bottom:48px;
    }

    .auth-features { list-style:none;padding:0;display:flex;flex-direction:column;gap:14px; }
    .auth-features li {
        display:flex;align-items:center;gap:12px;
        font-size:0.92rem;color:rgba(255,255,255,0.8);
    }
    .auth-features li .feat-icon {
        width:34px;height:34px;border-radius:8px;
        background:rgba(255,255,255,0.1);
        display:flex;align-items:center;justify-content:center;
        font-size:0.9rem;flex-shrink:0;
    }

    /* Form side */
    .auth-form-panel {
        background: var(--j-bg);
        display:flex;align-items:center;justify-content:center;
        padding:40px;
    }

    .auth-form-inner { width:100%;max-width:420px; }

    .auth-form-inner h3 {
        font-family:var(--j-font-heading);font-size:1.8rem;font-weight:800;
        margin-bottom:6px;color:var(--j-text);
    }

    .auth-form-inner .subtitle {
        font-size:0.9rem;color:var(--j-text-muted);margin-bottom:32px;
    }

    .auth-form-card {
        background:white;
        border-radius:var(--j-radius-xl);
        padding:36px;
        box-shadow:var(--j-shadow);
        border:1px solid var(--j-border-light);
    }

    .j-input-with-icon { position:relative; }
    .j-input-with-icon .fa-icon {
        position:absolute;left:14px;top:50%;transform:translateY(-50%);
        color:var(--j-text-muted);font-size:0.9rem;pointer-events:none;
    }
    .j-input-with-icon .j-input { padding-left:42px; }

    .j-input-with-icon .toggle-pw {
        position:absolute;right:14px;top:50%;transform:translateY(-50%);
        color:var(--j-text-muted);cursor:pointer;font-size:0.9rem;
        background:none;border:none;padding:0;
    }
    .j-input-with-icon .toggle-pw:hover { color:var(--j-primary); }

    .divider-or {
        display:flex;align-items:center;gap:12px;
        font-size:0.8rem;color:var(--j-text-light);margin:20px 0;
    }
    .divider-or::before,.divider-or::after { content:'';flex:1;height:1px;background:var(--j-border-light); }

    /* Forgot modal */
    .forgot-backdrop {
        position:fixed;inset:0;background:rgba(0,0,0,0.5);
        backdrop-filter:blur(4px);
        display:none;align-items:center;justify-content:center;
        z-index:2000;
    }
    .forgot-backdrop.open { display:flex; }
    .forgot-card {
        background:white;border-radius:var(--j-radius-xl);
        padding:36px;max-width:400px;width:90%;
        position:relative;box-shadow:var(--j-shadow-lg);
    }

    @media(max-width:768px) {
        .auth-page { grid-template-columns:1fr; }
        .auth-brand { display:none; }
        .auth-form-panel { padding:24px 16px;min-height:100vh; }
    }
    </style>
</head>
<body>
<div class="auth-page">

    <!-- ── Brand panel ──────────────────────────────────── -->
    <div class="auth-brand">
        <div class="auth-brand-inner">
            <div class="auth-brand-logo">
                <div class="logo-icon">🌱</div>
                Jirani
            </div>
            <h2>Welcome<br>back to <span class="highlight">local</span></h2>
            <p>Sign in to shop fresh produce and handmade goods from vendors in your community.</p>
            <ul class="auth-features">
                <li>
                    <div class="feat-icon" style="background:rgba(0,166,81,0.2);">💚</div>
                    M-Pesa protected checkout
                </li>
                <li>
                    <div class="feat-icon" style="background:rgba(255,167,38,0.2);">🛡️</div>
                    Escrow-secured payments
                </li>
                <li>
                    <div class="feat-icon" style="background:rgba(59,130,246,0.2);">📍</div>
                    Location-aware delivery
                </li>
            </ul>
        </div>
    </div>

    <!-- ── Form panel ───────────────────────────────────── -->
    <div class="auth-form-panel">
        <div class="auth-form-inner">
            <div style="text-align:center;margin-bottom:28px;">
                <a href="../index.php" style="font-family:var(--j-font-heading);font-size:1.5rem;font-weight:800;color:var(--j-primary);text-decoration:none;">
                    🌱 Jirani
                </a>
            </div>

            <h3 style="text-align:center;">Sign In</h3>
            <p class="subtitle" style="text-align:center;">Welcome back — let's get shopping</p>

            <?php if ($error): ?>
            <div class="j-alert j-alert-danger" style="margin-bottom:20px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="j-alert j-alert-success" style="margin-bottom:20px;">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <div class="auth-form-card">
                <form method="POST" action="">
                    <div class="j-form-group">
                        <label class="j-label">Email or Phone <span class="required">*</span></label>
                        <div class="j-input-with-icon">
                            <i class="fas fa-user fa-icon"></i>
                            <input type="text" name="email_or_phone" id="email_or_phone" class="j-input"
                                   placeholder="name@email.com or 07XXXXXXXX"
                                   value="<?php echo htmlspecialchars($_POST['email_or_phone'] ?? ''); ?>"
                                   required autocomplete="username">
                        </div>
                    </div>

                    <div class="j-form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px;">
                            <label class="j-label" style="margin:0;">Password <span class="required">*</span></label>
                            <button type="button" class="j-btn j-btn-ghost j-btn-sm"
                                    onclick="document.getElementById('forgotModal').classList.add('open')"
                                    style="font-size:0.78rem;padding:4px 8px;color:var(--j-primary);">
                                Forgot password?
                            </button>
                        </div>
                        <div class="j-input-with-icon">
                            <i class="fas fa-lock fa-icon"></i>
                            <input type="password" name="password" id="password" class="j-input"
                                   placeholder="Your password" required autocomplete="current-password"
                                   style="padding-right:44px;">
                            <button type="button" class="toggle-pw" onclick="togglePw()" id="pwToggle">
                                <i class="fas fa-eye" id="pwEye"></i>
                            </button>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                        <input type="checkbox" name="remember" id="remember" style="width:16px;height:16px;cursor:pointer;accent-color:var(--j-primary);">
                        <label for="remember" style="font-size:0.85rem;color:var(--j-text-muted);cursor:pointer;margin:0;">Remember me for 30 days</label>
                    </div>

                    <button type="submit" class="j-btn j-btn-primary j-btn-full j-btn-lg" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>

                <div class="divider-or">or continue with</div>

                <div style="display:flex;gap:10px;">
                    <a href="https://wa.me/" class="j-btn j-btn-ghost" style="flex:1;justify-content:center;gap:6px;">
                        <i class="fab fa-whatsapp" style="color:#25d366;"></i> WhatsApp
                    </a>
                    <a href="https://google.com" class="j-btn j-btn-ghost" style="flex:1;justify-content:center;gap:6px;">
                        <i class="fab fa-google" style="color:#ea4335;"></i> Google
                    </a>
                </div>
            </div>

            <p style="text-align:center;margin-top:20px;font-size:0.88rem;color:var(--j-text-muted);">
                Don't have an account?
                <a href="../Register/index.php" style="font-weight:600;color:var(--j-primary);">Create one free</a>
            </p>
        </div>
    </div>
</div>

<!-- ── Forgot Password Modal ────────────────────────────── -->
<div class="forgot-backdrop" id="forgotModal">
    <div class="forgot-card">
        <button onclick="document.getElementById('forgotModal').classList.remove('open')"
                style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--j-text-muted);">
            <i class="fas fa-times"></i>
        </button>
        <h4 style="font-family:var(--j-font-heading);font-weight:700;margin-bottom:6px;">Reset Password</h4>
        <p style="font-size:0.85rem;color:var(--j-text-muted);margin-bottom:20px;">We'll send reset instructions to your email.</p>

        <?php if ($forgot_error): ?>
        <div class="j-alert j-alert-danger" style="margin-bottom:14px;"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($forgot_error); ?></div>
        <?php endif; ?>
        <?php if ($forgot_success): ?>
        <div class="j-alert j-alert-success" style="margin-bottom:14px;"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($forgot_success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="j-form-group">
                <label class="j-label">Email or Phone</label>
                <div class="j-input-with-icon">
                    <i class="fas fa-envelope fa-icon"></i>
                    <input type="text" name="forgot_email_or_phone" class="j-input" required
                           placeholder="your@email.com or 07XXXXXXXX">
                </div>
            </div>
            <button type="submit" name="forgot_password_submit" class="j-btn j-btn-primary j-btn-full">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>
    </div>
</div>

<script>
function togglePw() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pwEye');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Close forgot modal on backdrop click
document.getElementById('forgotModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});

// Show forgot modal if there was a forgot error/success
<?php if ($forgot_error || $forgot_success): ?>
document.getElementById('forgotModal').classList.add('open');
<?php endif; ?>

// Loading state on form submit
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = '<span style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.4);border-top-color:white;border-radius:50%;animation:spin 0.7s linear infinite;display:inline-block;margin-right:8px;"></span> Signing in…';
    btn.disabled = true;
});
</script>
</body>
</html>