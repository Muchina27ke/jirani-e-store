<?php
require_once dirname(__DIR__) . "/config/config.php";

$error       = '';
$defaultRole = $_GET['role'] ?? 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname        = trim($_POST['firstname'] ?? '');
    $lastname         = trim($_POST['lastname'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $role             = trim($_POST['role'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!in_array($role, ['customer', 'vendor'])) {
        $error = 'Please select a valid account type.';
    } else {
        $name = "$firstname $lastname";
        if ($user->register($name, $phone, $email, $password, $role)) {
            header("Location: ../Signin/index.php?registered=true");
            exit;
        } else {
            $error = 'Registration failed. Email or phone may already be in use.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — Jirani</title>
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

    .auth-brand {
        background: linear-gradient(145deg, #0a1f10 0%, var(--j-primary-dark) 40%, var(--j-primary) 100%);
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
        bottom:-150px;right:-150px;
    }
    .auth-brand::after {
        content:''; position:absolute;
        width:280px;height:280px;border-radius:50%;
        background:rgba(255,167,38,0.08);
        top:-80px;left:-60px;
    }

    .auth-brand-inner { position:relative;z-index:1; }

    .auth-brand-logo {
        display:flex;align-items:center;gap:12px;
        font-family:var(--j-font-heading);font-size:1.8rem;font-weight:800;
        color:white;margin-bottom:48px;
    }
    .logo-icon {
        width:44px;height:44px;border-radius:12px;
        background:rgba(255,255,255,0.15);
        display:flex;align-items:center;justify-content:center;font-size:1.4rem;
    }

    .auth-brand h2 {
        font-family:var(--j-font-heading);font-size:2.2rem;font-weight:800;
        color:white;margin-bottom:18px;line-height:1.2;
    }
    .auth-brand h2 .highlight { color:var(--j-accent); }
    .auth-brand p { color:rgba(255,255,255,0.65);font-size:0.95rem;line-height:1.7;max-width:340px;margin-bottom:40px; }

    .steps-preview { list-style:none;padding:0;display:flex;flex-direction:column;gap:16px; }
    .steps-preview li {
        display:flex;align-items:center;gap:12px;color:rgba(255,255,255,0.8);font-size:0.9rem;
    }
    .step-pill {
        background:rgba(255,255,255,0.12);border-radius:99px;
        padding:4px 12px;font-size:0.72rem;font-weight:700;
        color:rgba(255,255,255,0.9);white-space:nowrap;
    }

    .auth-form-panel {
        background: var(--j-bg);
        display:flex;align-items:center;justify-content:center;
        padding:40px;overflow-y:auto;
    }

    .auth-form-inner { width:100%;max-width:460px; }

    .auth-form-card {
        background:white;border-radius:var(--j-radius-xl);
        padding:36px;box-shadow:var(--j-shadow);
        border:1px solid var(--j-border-light);
    }

    /* Role toggle */
    .role-toggle {
        display:grid;grid-template-columns:1fr 1fr;
        background:var(--j-bg);border-radius:var(--j-radius);
        padding:4px;margin-bottom:24px;gap:4px;
    }

    .role-btn {
        padding:10px;border-radius:var(--j-radius-sm);
        cursor:pointer;border:2px solid transparent;
        background:transparent;
        font-family:var(--j-font-body);font-size:0.88rem;font-weight:600;
        color:var(--j-text-muted);
        display:flex;align-items:center;justify-content:center;gap:6px;
        transition:var(--j-transition);
    }

    .role-btn.active {
        background:white;color:var(--j-primary);
        border-color:var(--j-primary);
        box-shadow:var(--j-shadow-sm);
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

    .vendor-extras { display:none; }
    .vendor-extras.show { display:block; }

    @media(max-width:768px) {
        .auth-page { grid-template-columns:1fr; }
        .auth-brand { display:none; }
        .auth-form-panel { padding:24px 16px;min-height:100vh; }
    }
    </style>
</head>
<body>
<div class="auth-page">

    <!-- Brand side -->
    <div class="auth-brand">
        <div class="auth-brand-inner">
            <div class="auth-brand-logo">
                <div class="logo-icon">🌱</div>
                Jirani
            </div>
            <h2>Join your <span class="highlight">local</span><br>marketplace</h2>
            <p>Create an account in under 2 minutes and start shopping or selling locally.</p>
            <ul class="steps-preview">
                <li>
                    <span class="step-pill">Step 1</span>
                    Fill in your details below
                </li>
                <li>
                    <span class="step-pill">Step 2</span>
                    Verify your email address
                </li>
                <li>
                    <span class="step-pill">Step 3</span>
                    Start shopping or listing products
                </li>
            </ul>
        </div>
    </div>

    <!-- Form side -->
    <div class="auth-form-panel">
        <div class="auth-form-inner">
            <div style="text-align:center;margin-bottom:28px;">
                <a href="../index.php" style="font-family:var(--j-font-heading);font-size:1.5rem;font-weight:800;color:var(--j-primary);text-decoration:none;">
                    🌱 Jirani
                </a>
            </div>

            <h3 style="font-family:var(--j-font-heading);font-size:1.6rem;font-weight:800;text-align:center;margin-bottom:4px;">Create Account</h3>
            <p style="text-align:center;font-size:0.88rem;color:var(--j-text-muted);margin-bottom:24px;">Free — no credit card required</p>

            <?php if ($error): ?>
            <div class="j-alert j-alert-danger" style="margin-bottom:20px;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <div class="auth-form-card">
                <!-- Account type toggle -->
                <div class="role-toggle" id="roleToggle">
                    <button type="button" class="role-btn <?php echo $defaultRole !== 'vendor' ? 'active' : ''; ?>"
                            onclick="setRole('customer')" id="btnCustomer">
                        <i class="fas fa-user"></i> Customer
                    </button>
                    <button type="button" class="role-btn <?php echo $defaultRole === 'vendor' ? 'active' : ''; ?>"
                            onclick="setRole('vendor')" id="btnVendor">
                        <i class="fas fa-store"></i> Vendor / Seller
                    </button>
                </div>

                <form method="POST" action="" id="regForm">
                    <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($defaultRole); ?>">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="j-form-group">
                            <label class="j-label">First Name <span class="required">*</span></label>
                            <input type="text" name="firstname" class="j-input" required
                                   value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>"
                                   placeholder="John">
                        </div>
                        <div class="j-form-group">
                            <label class="j-label">Last Name <span class="required">*</span></label>
                            <input type="text" name="lastname" class="j-input" required
                                   value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>"
                                   placeholder="Doe">
                        </div>
                    </div>

                    <div class="j-form-group">
                        <label class="j-label">Email Address <span class="required">*</span></label>
                        <div class="j-input-with-icon">
                            <i class="fas fa-envelope fa-icon"></i>
                            <input type="email" name="email" class="j-input" required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   placeholder="your@email.com">
                        </div>
                    </div>

                    <div class="j-form-group">
                        <label class="j-label">Phone Number <span class="required">*</span></label>
                        <div class="j-input-with-icon">
                            <i class="fas fa-phone fa-icon"></i>
                            <input type="tel" name="phone" class="j-input" required
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                   placeholder="07XXXXXXXX">
                        </div>
                        <div class="j-form-hint">Used for M-Pesa and delivery updates</div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="j-form-group">
                            <label class="j-label">Password <span class="required">*</span></label>
                            <div class="j-input-with-icon">
                                <i class="fas fa-lock fa-icon"></i>
                                <input type="password" name="password" id="password" class="j-input" required
                                       placeholder="Min. 6 characters" style="padding-right:44px;">
                                <button type="button" class="toggle-pw" onclick="togglePw('password','eye1')">
                                    <i class="fas fa-eye" id="eye1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="j-form-group">
                            <label class="j-label">Confirm Password <span class="required">*</span></label>
                            <div class="j-input-with-icon">
                                <i class="fas fa-lock fa-icon"></i>
                                <input type="password" name="confirm_password" id="confirm_password" class="j-input" required
                                       placeholder="Repeat password" style="padding-right:44px;">
                                <button type="button" class="toggle-pw" onclick="togglePw('confirm_password','eye2')">
                                    <i class="fas fa-eye" id="eye2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Vendor-specific fields -->
                    <div class="vendor-extras" id="vendorExtras">
                        <div style="padding:14px;background:var(--j-primary-glass);border-radius:var(--j-radius);margin-bottom:16px;font-size:0.82rem;color:var(--j-primary);">
                            <i class="fas fa-info-circle"></i>
                            As a vendor you'll set up your store profile after registration. You'll also need to submit verification documents.
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:20px;">
                        <input type="checkbox" id="terms" required style="width:16px;height:16px;margin-top:3px;cursor:pointer;accent-color:var(--j-primary);">
                        <label for="terms" style="font-size:0.82rem;color:var(--j-text-muted);cursor:pointer;">
                            I agree to Jirani's <a href="../about.php" style="color:var(--j-primary);font-weight:600;">Terms of Use</a> and
                            <a href="#" style="color:var(--j-primary);font-weight:600;">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="j-btn j-btn-primary j-btn-full j-btn-lg" id="regBtn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
            </div>

            <p style="text-align:center;margin-top:20px;font-size:0.88rem;color:var(--j-text-muted);">
                Already have an account?
                <a href="../Signin/index.php" style="font-weight:600;color:var(--j-primary);">Sign in</a>
            </p>
        </div>
    </div>
</div>

<script>
let currentRole = <?php echo json_encode($defaultRole !== 'vendor' ? 'customer' : 'vendor'); ?>;

function setRole(role) {
    currentRole = role;
    document.getElementById('roleInput').value = role;

    document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('btn' + role.charAt(0).toUpperCase() + role.slice(1)).classList.add('active');

    const vendorExtras = document.getElementById('vendorExtras');
    vendorExtras.classList.toggle('show', role === 'vendor');
}

function togglePw(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(eyeId);
    if (input.type === 'password') {
        input.type = 'text'; icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password'; icon.className = 'fas fa-eye';
    }
}

// Init role UI
setRole(currentRole);

// Password strength feedback
document.getElementById('password').addEventListener('input', function() {
    // Could add strength meter here in future
});

// Submit loading state
document.getElementById('regForm').addEventListener('submit', function(e) {
    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    if (pw !== cpw) {
        e.preventDefault();
        alert('Passwords do not match!');
        return;
    }
    const btn = document.getElementById('regBtn');
    btn.innerHTML = '<span style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50%;animation:spin 0.7s linear infinite;display:inline-block;margin-right:8px;"></span> Creating account…';
    btn.disabled = true;
});
</script>
</body>
</html>
