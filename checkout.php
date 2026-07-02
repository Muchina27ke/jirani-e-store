<?php
require_once __DIR__ . '/config/config.php';

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'Signin/index.php?redirect=checkout.php');
    exit;
}

// Get cart
$cartObj   = new Cart($conn, $_SESSION['user_id']);
$cartResult = $cartObj->getCartItems();
if (!$cartResult['success'] || empty($cartResult['items'])) {
    header('Location: ' . SITE_URL . 'cart.php');
    exit;
}

$cartItems = $cartResult['items'];
$total     = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));

// User details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Is M-Pesa sandbox simulation active?
$isSandbox = defined('MPESA_SIMULATE') && MPESA_SIMULATE;

$pageTitle   = 'Checkout';
$currentPage = 'checkout';
?>
<?php require_once __DIR__ . '/navbar.php'; ?>

<style>
.checkout-wrap {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 24px;
    align-items: start;
    padding: 40px 0 80px;
}

.checkout-panel {
    background: white;
    border-radius: var(--j-radius-lg);
    border: 1px solid var(--j-border-light);
    box-shadow: var(--j-shadow-card);
    overflow: hidden;
}

.checkout-panel-header {
    padding: 24px 28px;
    border-bottom: 1px solid var(--j-border-light);
    display: flex;
    align-items: center;
    gap: 12px;
}

.checkout-panel-header h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
}

.checkout-panel-header .step-num {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: var(--j-primary);
    color: white;
    display: flex;align-items:center;justify-content:center;
    font-size: 0.82rem; font-weight: 700;
    flex-shrink: 0;
}

.checkout-panel-body { padding: 28px; }

/* Payment method cards */
.payment-option {
    border: 2px solid var(--j-border);
    border-radius: var(--j-radius);
    padding: 18px 20px;
    cursor: pointer;
    transition: var(--j-transition);
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 12px;
    position: relative;
}

.payment-option:hover { border-color: var(--j-primary); background: var(--j-primary-glass); }
.payment-option.selected { border-color: var(--j-primary); background: var(--j-primary-glass); }

.payment-option input[type="radio"] { display: none; }

.payment-option .pay-radio {
    width: 20px; height: 20px;
    border-radius: 50%;
    border: 2px solid var(--j-border);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: var(--j-transition);
}
.payment-option.selected .pay-radio {
    border-color: var(--j-primary);
    background: var(--j-primary);
}
.payment-option.selected .pay-radio::after {
    content:'';
    width:8px;height:8px;
    border-radius:50%;
    background:white;
}

.mpesa-logo-badge {
    background: #00a651;
    color: white;
    font-weight: 800;
    font-size: 0.8rem;
    padding: 4px 10px;
    border-radius: 6px;
    letter-spacing: 0.5px;
}

/* Sandbox indicator */
.sandbox-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(245,158,11,0.12);
    color: #d97706;
    border: 1px solid rgba(245,158,11,0.3);
    padding: 4px 10px; border-radius: 99px;
    font-size: 0.72rem; font-weight: 600;
}

/* Steps indicator */
.steps-bar {
    display: flex;
    background: white;
    border-radius: var(--j-radius-lg);
    padding: 20px 28px;
    margin-bottom: 24px;
    border: 1px solid var(--j-border-light);
    box-shadow: var(--j-shadow-card);
    position: relative;
    overflow: hidden;
}
.steps-bar::before {
    content:'';
    position:absolute;
    top:50%;left:0;right:0;height:2px;
    background: var(--j-border-light);
    transform:translateY(-50%);
    z-index:0;
}
.step-node {
    display:flex;flex-direction:column;align-items:center;
    flex:1;position:relative;z-index:1;
}
.step-dot {
    width:36px;height:36px;border-radius:50%;
    background:var(--j-bg);
    border:2px solid var(--j-border);
    display:flex;align-items:center;justify-content:center;
    font-weight:700;font-size:0.85rem;color:var(--j-text-muted);
    margin-bottom:8px;transition:var(--j-transition);
}
.step-node.active .step-dot { background:var(--j-primary);border-color:var(--j-primary);color:white;box-shadow:0 0 0 4px var(--j-primary-glow); }
.step-node.done .step-dot { background:var(--j-primary);border-color:var(--j-primary);color:white; }
.step-label { font-size:0.75rem;font-weight:600;color:var(--j-text-muted);white-space:nowrap; }
.step-node.active .step-label { color:var(--j-primary); }
.step-node.done .step-label { color:var(--j-primary); }

/* Success confirmation */
.confirm-success {
    text-align:center;
    padding:48px 24px;
}
.confirm-icon-wrap {
    width:100px;height:100px;border-radius:50%;
    background:linear-gradient(135deg,var(--j-success),#16a34a);
    color:white;font-size:3rem;
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 24px;
    animation:checkPop 0.5s cubic-bezier(0.68,-0.55,0.27,1.55);
}
@keyframes checkPop { 0%{transform:scale(0)}100%{transform:scale(1)} }

@media(max-width:768px){
    .checkout-wrap { grid-template-columns:1fr; }
    .j-order-summary { position:static; }
}
</style>

<!-- Page header -->
<div class="j-page-header">
    <div class="j-container">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <a href="<?php echo SITE_URL; ?>cart.php" style="color:rgba(255,255,255,0.7);font-size:0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Back to Cart
            </a>
            <?php if ($isSandbox): ?>
            <span class="sandbox-badge">
                <i class="fas fa-flask"></i> M-Pesa Sandbox Mode — Payments are simulated locally
            </span>
            <?php endif; ?>
        </div>
        <h1 style="margin-top:12px;">Secure Checkout</h1>
    </div>
</div>

<div class="j-container">
    <!-- Steps bar -->
    <div class="steps-bar" id="stepsBar" style="margin-top:32px;">
        <div class="step-node active" id="sn1">
            <div class="step-dot">1</div>
            <div class="step-label">Review</div>
        </div>
        <div class="step-node" id="sn2">
            <div class="step-dot">2</div>
            <div class="step-label">Delivery</div>
        </div>
        <div class="step-node" id="sn3">
            <div class="step-dot">3</div>
            <div class="step-label">Payment</div>
        </div>
        <div class="step-node" id="sn4">
            <div class="step-dot"><i class="fas fa-check" style="font-size:0.8rem;"></i></div>
            <div class="step-label">Confirmed</div>
        </div>
    </div>

    <div class="checkout-wrap">
        <!-- Left: Step panels -->
        <div id="checkoutSteps">

            <!-- Step 1: Order Review -->
            <div class="checkout-panel" id="step1">
                <div class="checkout-panel-header">
                    <span class="step-num">1</span>
                    <h3><i class="fas fa-receipt" style="color:var(--j-primary);margin-right:8px;"></i>Order Review</h3>
                </div>
                <div class="checkout-panel-body">
                    <?php
                    // Group by vendor
                    $vendorGroups = [];
                    foreach ($cartItems as $item) {
                        $vid = $item['vendor_id'] ?? 0;
                        $vendorGroups[$vid]['name']  = $item['vendor_name'] ?? $item['business_name'] ?? 'Vendor';
                        $vendorGroups[$vid]['items'][] = $item;
                    }
                    foreach ($vendorGroups as $vid => $vg): ?>
                    <div style="margin-bottom:20px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                            <i class="fas fa-store" style="color:var(--j-primary);font-size:0.85rem;"></i>
                            <span style="font-weight:600;font-size:0.9rem;color:var(--j-text);"><?php echo htmlspecialchars($vg['name']); ?></span>
                        </div>
                        <?php foreach ($vg['items'] as $item):
                            $imgSrc = !empty($item['image']) ? SITE_URL . 'uploads/' . htmlspecialchars($item['image']) : '';
                        ?>
                        <div style="display:grid;grid-template-columns:72px 1fr auto;gap:14px;align-items:center;padding:12px 0;border-bottom:1px solid var(--j-border-light);">
                            <div style="width:72px;height:64px;border-radius:10px;overflow:hidden;background:var(--j-bg);flex-shrink:0;">
                                <?php if ($imgSrc): ?>
                                <img src="<?php echo $imgSrc; ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
                                <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--j-text-light);"><i class="fas fa-seedling"></i></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:0.9rem;color:var(--j-text);"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div style="font-size:0.8rem;color:var(--j-text-muted);">Qty: <?php echo $item['quantity']; ?> × KSh <?php echo number_format($item['price'], 2); ?></div>
                            </div>
                            <div style="font-weight:700;color:var(--j-primary);">KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                    <button class="j-btn j-btn-primary j-btn-full" onclick="goToStep(2)" style="margin-top:12px;">
                        Continue to Delivery <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: Delivery Info -->
            <div class="checkout-panel" id="step2" style="display:none;">
                <div class="checkout-panel-header">
                    <span class="step-num">2</span>
                    <h3><i class="fas fa-truck" style="color:var(--j-primary);margin-right:8px;"></i>Delivery Information</h3>
                </div>
                <div class="checkout-panel-body">
                    <form id="shippingForm" novalidate>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div class="j-form-group">
                                <label class="j-label">Full Name <span class="required">*</span></label>
                                <div class="j-input-icon-wrap">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" id="full_name" name="full_name" class="j-input" required
                                           value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="Your full name">
                                </div>
                            </div>
                            <div class="j-form-group">
                                <label class="j-label">Phone Number <span class="required">*</span></label>
                                <div class="j-input-icon-wrap">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input type="tel" id="phone" name="phone" class="j-input" required
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="07XXXXXXXX">
                                </div>
                            </div>
                        </div>
                        <div class="j-form-group">
                            <label class="j-label">Email Address <span class="required">*</span></label>
                            <div class="j-input-icon-wrap">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" id="email" name="email" class="j-input" required
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="your@email.com">
                            </div>
                        </div>
                        <div class="j-form-group">
                            <label class="j-label">Delivery Address <span class="required">*</span></label>
                            <div class="j-input-icon-wrap">
                                <i class="fas fa-map-marker-alt input-icon"></i>
                                <textarea id="delivery_address" name="delivery_address" class="j-input j-textarea" required
                                          placeholder="Enter full delivery address including landmarks…" rows="3"></textarea>
                            </div>
                            <div class="j-form-hint"><i class="fas fa-info-circle"></i> Include landmarks for easy delivery</div>
                        </div>
                        <div class="j-form-group">
                            <label class="j-label">Delivery Instructions <span style="color:var(--j-text-muted);font-weight:400;">(Optional)</span></label>
                            <textarea id="delivery_instructions" name="delivery_instructions" class="j-input j-textarea"
                                      placeholder="Any special instructions for delivery…" rows="2"></textarea>
                        </div>
                    </form>
                    <div style="display:flex;gap:12px;margin-top:8px;">
                        <button class="j-btn j-btn-ghost" onclick="goToStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
                        <button class="j-btn j-btn-primary" style="flex:1;" onclick="validateAndProceed()">
                            Continue to Payment <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Payment -->
            <div class="checkout-panel" id="step3" style="display:none;">
                <div class="checkout-panel-header">
                    <span class="step-num">3</span>
                    <h3><i class="fas fa-lock" style="color:var(--j-primary);margin-right:8px;"></i>Payment Method</h3>
                </div>
                <div class="checkout-panel-body">

                    <!-- M-Pesa option -->
                    <div class="payment-option selected" id="payMpesa" onclick="selectPayment('mpesa')">
                        <div class="pay-radio" id="radioMpesa"></div>
                        <div class="mpesa-logo-badge">M-PESA</div>
                        <div style="flex:1;">
                            <div style="font-weight:700;font-size:0.92rem;">M-Pesa Mobile Money</div>
                            <div style="font-size:0.8rem;color:var(--j-text-muted);">
                                <i class="fas fa-shield-alt" style="color:var(--j-success);"></i>
                                Secure escrow-protected payment
                                <?php if ($isSandbox): ?><span style="color:var(--j-warning);"> · Simulated locally</span><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Cash option -->
                    <div class="payment-option" id="payCash" onclick="selectPayment('cash')">
                        <div class="pay-radio" id="radioCash"></div>
                        <div style="width:52px;text-align:center;font-size:1.6rem;">💵</div>
                        <div style="flex:1;">
                            <div style="font-weight:700;font-size:0.92rem;">Cash on Delivery</div>
                            <div style="font-size:0.8rem;color:var(--j-text-muted);">Pay when your order arrives</div>
                        </div>
                    </div>

                    <!-- M-Pesa phone field -->
                    <div id="mpesaPhoneField" style="margin-top:20px;padding:18px;background:var(--j-bg);border-radius:var(--j-radius);">
                        <label class="j-label"><i class="fas fa-mobile-alt" style="color:#00a651;"></i> M-Pesa Phone Number</label>
                        <div class="j-input-icon-wrap">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="tel" id="mpesa_phone" class="j-input" required
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   placeholder="07XXXXXXXX">
                        </div>
                        <div class="j-form-hint" style="margin-top:8px;">
                            <?php if ($isSandbox): ?>
                            <span style="color:var(--j-warning);"><i class="fas fa-flask"></i> A simulated STK prompt will appear instead of a real Safaricom push</span>
                            <?php else: ?>
                            <i class="fas fa-info-circle"></i> You'll receive an STK push on this number
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="paymentError" class="j-alert j-alert-danger" style="display:none;margin-top:16px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <span id="paymentErrorMsg"></span>
                    </div>

                    <div style="display:flex;gap:12px;margin-top:20px;">
                        <button class="j-btn j-btn-ghost" onclick="goToStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
                        <button class="j-btn j-btn-primary" style="flex:1;" id="confirmBtn" onclick="confirmOrder()">
                            <i class="fas fa-lock"></i> Place Order & Pay
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 4: Confirmed -->
            <div class="checkout-panel" id="step4" style="display:none;">
                <div class="confirm-success">
                    <div class="confirm-icon-wrap"><i class="fas fa-check"></i></div>
                    <h2 style="color:var(--j-success);margin-bottom:8px;">Order Confirmed!</h2>
                    <p style="color:var(--j-text-muted);max-width:360px;margin:0 auto 24px;">Your order has been placed and payment received. We'll notify you when it ships.</p>
                    <div id="orderConfirmDetails" style="max-width:400px;margin:0 auto 28px;text-align:left;background:var(--j-bg);border-radius:var(--j-radius);padding:20px;"></div>
                    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                        <a href="<?php echo SITE_URL; ?>orders.php" class="j-btn j-btn-primary">
                            <i class="fas fa-box"></i> View My Orders
                        </a>
                        <a href="<?php echo SITE_URL; ?>index.php" class="j-btn j-btn-ghost">
                            <i class="fas fa-home"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Order Summary -->
        <div class="j-order-summary">
            <div class="j-summary-header">Order Summary</div>
            <div class="j-summary-body">
                <?php foreach ($cartItems as $item): ?>
                <div class="j-summary-row">
                    <span style="max-width:160px;"><?php echo htmlspecialchars($item['name']); ?> ×<?php echo $item['quantity']; ?></span>
                    <span class="amount">KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
                <?php endforeach; ?>

                <div class="j-summary-row">
                    <span>Subtotal</span>
                    <span class="amount">KSh <?php echo number_format($total, 2); ?></span>
                </div>
                <div class="j-summary-row">
                    <span>Delivery</span>
                    <span class="amount" style="color:var(--j-success);">Negotiated</span>
                </div>
                <div class="j-summary-row total">
                    <span>Total</span>
                    <span class="amount">KSh <?php echo number_format($total, 2); ?></span>
                </div>
            </div>
            <div style="padding:0 20px 20px;">
                <div style="display:flex;align-items:center;gap:8px;font-size:0.78rem;color:var(--j-text-muted);margin-bottom:10px;">
                    <i class="fas fa-shield-alt" style="color:var(--j-success);"></i>
                    Escrow protected — money held until delivery confirmed
                </div>
                <div style="display:flex;align-items:center;gap:8px;font-size:0.78rem;color:var(--j-text-muted);">
                    <i class="fas fa-lock" style="color:#00a651;"></i>
                    Secured with M-Pesa & SSL encryption
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ M-PESA SIMULATION MODAL ════════════════════════════ -->
<div class="j-modal-backdrop" id="mpesaModal">
    <div class="j-modal j-mpesa-modal" style="max-width:420px;">

        <!-- Waiting state -->
        <div id="mpesaWaiting">
            <div class="j-mpesa-phone-anim">📱</div>
            <div class="j-mpesa-title">STK Push Sent!</div>
            <div class="j-mpesa-subtitle" id="mpesaSubtitle">
                Check your phone for the M-Pesa prompt.<br>
                <strong id="mpesaPhoneDisplay"></strong>
            </div>

            <?php if ($isSandbox): ?>
            <div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:var(--j-radius);padding:14px 18px;margin-bottom:20px;font-size:0.82rem;color:#92400e;">
                <i class="fas fa-flask"></i> <strong>Sandbox Mode</strong> — Auto-confirming in <span id="simCountdownDisplay">8</span>s (simulating PIN entry)
            </div>
            <?php endif; ?>

            <div class="j-mpesa-countdown">
                <div class="j-countdown-circle" id="countdownNum">90</div>
                <div style="text-align:left;">
                    <div style="font-weight:600;font-size:0.9rem;">Waiting for payment…</div>
                    <div style="font-size:0.8rem;color:var(--j-text-muted);">Enter your M-Pesa PIN when prompted</div>
                </div>
            </div>

            <div style="font-size:0.82rem;color:var(--j-text-muted);line-height:1.7;text-align:left;background:var(--j-bg);border-radius:var(--j-radius);padding:14px 18px;">
                <strong>Steps:</strong><br>
                1. Open your phone's M-Pesa menu<br>
                2. You'll see a payment request for <strong>KSh <?php echo number_format($total, 2); ?></strong><br>
                3. Enter your M-Pesa PIN to confirm<br>
                4. This page will update automatically
            </div>

            <button class="j-btn j-btn-ghost j-btn-sm j-btn-full" onclick="closeMpesaModal()" style="margin-top:16px;">
                Cancel Payment
            </button>
        </div>

        <!-- Success state -->
        <div id="mpesaSuccess" style="display:none;text-align:center;">
            <div class="j-check-circle" style="margin:0 auto 20px;"><i class="fas fa-check"></i></div>
            <h3 style="color:var(--j-success);margin-bottom:8px;">Payment Confirmed!</h3>
            <p style="color:var(--j-text-muted);margin-bottom:20px;">Your M-Pesa payment of <strong>KSh <?php echo number_format($total, 2); ?></strong> was received.</p>
            <div class="j-spinner" style="margin:0 auto;"></div>
            <p style="font-size:0.82rem;color:var(--j-text-muted);margin-top:12px;">Confirming your order…</p>
        </div>
    </div>
</div>

<script>
const SITE_URL   = <?php echo json_encode(SITE_URL); ?>;
const IS_SANDBOX = <?php echo $isSandbox ? 'true' : 'false'; ?>;
const ORDER_TOTAL = <?php echo (float)$total; ?>;

let currentStep = 1;
let selectedPayment = 'mpesa';
let createdOrders = [];
let mpesaCheckoutId = null;
let mpesaOrderId = null;
let countdownTimer = null;
let statusPollTimer = null;

// ── Step navigation ────────────────────────────────────────
function goToStep(n) {
    document.querySelectorAll('[id^="step"]').forEach(el => {
        if (/^step\d$/.test(el.id)) el.style.display = 'none';
    });
    document.getElementById('step' + n).style.display = 'block';

    // Update step indicator
    ['sn1','sn2','sn3','sn4'].forEach((id, i) => {
        const el = document.getElementById(id);
        el.classList.remove('active','done');
        if (i + 1 < n) el.classList.add('done');
        else if (i + 1 === n) el.classList.add('active');
    });
    currentStep = n;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Payment selection ──────────────────────────────────────
function selectPayment(method) {
    selectedPayment = method;
    document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('selected'));
    document.querySelectorAll('.pay-radio').forEach(el => {
        el.style.borderColor = '';
        el.style.background = '';
    });
    document.getElementById('pay' + (method === 'mpesa' ? 'Mpesa' : 'Cash')).classList.add('selected');

    const mpesaField = document.getElementById('mpesaPhoneField');
    mpesaField.style.display = method === 'mpesa' ? 'block' : 'none';
}

// ── Shipping validation ────────────────────────────────────
function validateAndProceed() {
    const name    = document.getElementById('full_name').value.trim();
    const phone   = document.getElementById('phone').value.trim();
    const email   = document.getElementById('email').value.trim();
    const address = document.getElementById('delivery_address').value.trim();

    if (!name || !phone || !email || !address) {
        jToast('Please fill in all required fields.', 'error');
        if (!name) document.getElementById('full_name').focus();
        else if (!phone) document.getElementById('phone').focus();
        else if (!email) document.getElementById('email').focus();
        else document.getElementById('delivery_address').focus();
        return;
    }

    const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailReg.test(email)) {
        jToast('Please enter a valid email address.', 'error');
        return;
    }

    goToStep(3);
}

// ── Place order ────────────────────────────────────────────
async function confirmOrder() {
    const btn = document.getElementById('confirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="border-top-color:white;width:16px;height:16px;margin-right:8px;display:inline-block;border:2px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50;animation:spin 0.7s linear infinite;"></span> Placing Order…';

    hidePaymentError();

    try {
        const orderData = {
            cart_items: <?php echo json_encode($cartItems); ?>,
            shipping_info: {
                full_name: document.getElementById('full_name').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value,
                delivery_address: document.getElementById('delivery_address').value,
                delivery_instructions: document.getElementById('delivery_instructions').value
            },
            payment_method: selectedPayment
        };

        const res = await fetch(SITE_URL + 'api/orders/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        const result = await res.json();

        if (!result.success) throw new Error(result.message || 'Failed to create order');

        createdOrders = result.orders || [];

        if (selectedPayment === 'cash') {
            // Cash on delivery — go straight to confirmation
            showOrderConfirmation(createdOrders, 'Cash on Delivery');
            goToStep(4);
        } else {
            // M-Pesa flow
            const primaryOrder = createdOrders[0];
            await initiateMpesaPayment(primaryOrder.order_id);
        }

    } catch (err) {
        showPaymentError(err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-lock"></i> Place Order & Pay';
    }
}

// ── M-Pesa initiation ──────────────────────────────────────
async function initiateMpesaPayment(orderId) {
    const phone = document.getElementById('mpesa_phone').value.trim();
    if (!phone) {
        showPaymentError('Please enter your M-Pesa phone number.');
        return;
    }

    try {
        const res = await fetch(SITE_URL + 'api/mpesa/initiate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone, amount: ORDER_TOTAL, order_id: orderId })
        });
        const result = await res.json();

        if (!result.success) throw new Error(result.message || 'Failed to initiate payment');

        mpesaCheckoutId = result.CheckoutRequestID;
        mpesaOrderId    = orderId;

        openMpesaModal(phone, result.simulated);

    } catch (err) {
        showPaymentError(err.message);
    }
}

// ── M-Pesa modal ───────────────────────────────────────────
function openMpesaModal(phone, isSimulated) {
    document.getElementById('mpesaPhoneDisplay').textContent = phone;
    document.getElementById('mpesaModal').classList.add('open');

    if (IS_SANDBOX && isSimulated) {
        // Simulation: auto-confirm after 8s countdown
        startSimulationCountdown(8, mpesaCheckoutId, mpesaOrderId);
    } else {
        // Real: start polling for status with 90s countdown
        startRealCountdown(90, mpesaOrderId);
    }
}

function closeMpesaModal() {
    clearTimers();
    document.getElementById('mpesaModal').classList.remove('open');
}

function clearTimers() {
    if (countdownTimer) clearInterval(countdownTimer);
    if (statusPollTimer) clearTimeout(statusPollTimer);
}

// ── Simulation countdown & auto-confirm ───────────────────
function startSimulationCountdown(seconds, checkoutId, orderId) {
    let remaining = seconds;
    document.getElementById('simCountdownDisplay').textContent = remaining;
    document.getElementById('countdownNum').textContent = remaining;

    countdownTimer = setInterval(() => {
        remaining--;
        document.getElementById('simCountdownDisplay').textContent = remaining;
        document.getElementById('countdownNum').textContent = remaining;

        if (remaining <= 0) {
            clearTimers();
            confirmSimulatedPayment(checkoutId, orderId);
        }
    }, 1000);
}

async function confirmSimulatedPayment(checkoutId, orderId) {
    try {
        const res = await fetch(SITE_URL + 'api/mpesa/simulate_confirm.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ checkout_request_id: checkoutId, order_id: orderId })
        });
        const result = await res.json();

        if (result.success) {
            // Show success in modal
            document.getElementById('mpesaWaiting').style.display = 'none';
            document.getElementById('mpesaSuccess').style.display = 'block';

            setTimeout(() => {
                document.getElementById('mpesaModal').classList.remove('open');
                showOrderConfirmation(createdOrders, 'M-Pesa');
                goToStep(4);
            }, 2000);
        } else {
            closeMpesaModal();
            showPaymentError(result.message || 'Payment confirmation failed. Please try again.');
        }
    } catch (err) {
        closeMpesaModal();
        showPaymentError('Network error confirming payment. Please try again.');
    }
}

// ── Real M-Pesa polling ────────────────────────────────────
function startRealCountdown(seconds, orderId) {
    let remaining = seconds;
    document.getElementById('countdownNum').textContent = remaining;

    countdownTimer = setInterval(() => {
        remaining--;
        document.getElementById('countdownNum').textContent = remaining;
        if (remaining <= 0) {
            clearTimers();
            closeMpesaModal();
            showPaymentError('Payment timed out. Please try again or use Cash on Delivery.');
        }
    }, 1000);

    // Poll payment status every 5s
    pollRealPaymentStatus(orderId, 0);
}

async function pollRealPaymentStatus(orderId, attempt) {
    if (attempt > 17) return; // ~85s max
    try {
        const res  = await fetch(SITE_URL + 'api/mpesa/status.php?payment_id=' + mpesaCheckoutId);
        const data = await res.json();

        if (data.status === 'Completed') {
            clearTimers();
            document.getElementById('mpesaWaiting').style.display = 'none';
            document.getElementById('mpesaSuccess').style.display = 'block';
            setTimeout(() => {
                document.getElementById('mpesaModal').classList.remove('open');
                showOrderConfirmation(createdOrders, 'M-Pesa');
                goToStep(4);
            }, 2000);
            return;
        }

        statusPollTimer = setTimeout(() => pollRealPaymentStatus(orderId, attempt + 1), 5000);
    } catch (e) {
        statusPollTimer = setTimeout(() => pollRealPaymentStatus(orderId, attempt + 1), 5000);
    }
}

// ── Order confirmation display ─────────────────────────────
function showOrderConfirmation(orders, paymentMethod) {
    let html = '';
    orders.forEach(o => {
        html += `<div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:0.88rem;">
            <span style="color:var(--j-text-muted);">Order #${o.order_id}</span>
            <span style="font-weight:600;">${o.vendor_name || 'Vendor'}</span>
        </div>`;
    });
    html += `<hr style="border-color:var(--j-border-light);">
    <div style="display:flex;justify-content:space-between;font-size:0.88rem;margin-bottom:6px;">
        <span style="color:var(--j-text-muted);">Payment</span>
        <span style="font-weight:600;color:${paymentMethod==='M-Pesa'?'#00a651':'var(--j-text)'};">${paymentMethod}</span>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:1rem;">
        <span style="font-weight:700;">Total Paid</span>
        <span style="font-weight:800;color:var(--j-primary);">KSh ${ORDER_TOTAL.toFixed(2)}</span>
    </div>`;
    document.getElementById('orderConfirmDetails').innerHTML = html;
}

// ── Error handling ─────────────────────────────────────────
function showPaymentError(msg) {
    const el = document.getElementById('paymentError');
    document.getElementById('paymentErrorMsg').textContent = msg;
    el.style.display = 'flex';
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function hidePaymentError() {
    document.getElementById('paymentError').style.display = 'none';
}

// ── Init ──────────────────────────────────────────────────
selectPayment('mpesa');

// Close modal on backdrop click
document.getElementById('mpesaModal').addEventListener('click', function(e) {
    if (e.target === this) closeMpesaModal();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>