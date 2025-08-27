<?php
require_once __DIR__ . '/config/config.php';
$db = getDbConnection();
require_once __DIR__ . '/includes/Cart.php';
require_once __DIR__ . '/includes/Mpesa.php';
// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}
// Get cart items
$cart = new Cart($db, $_SESSION['user_id']);
$cartResult = $cart->getCartItems();
if (!$cartResult['success'] || empty($cartResult['items'])) {
    header('Location: cart.php');
    exit;
}
$cartItems = $cartResult['items'];
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
// Get user details
$userId = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Navigation.php';
$auth = new Auth($db);
$currentUser = $auth->isLoggedIn() ? $auth->getUser() : null;
$navigation = new Navigation($db, $currentUser, 'checkout');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Jirani</title>
    <link rel="icon" type="image/jpeg" href="title_logo.jpg">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/About.css">
    <!-- Footer CSS -->
    <link rel="stylesheet" href="css/footer.css">
</head>
<body>
<?php echo $navigation->renderCustomerNav(); ?>
<!-- (rest of original checkout HTML and logic follows) -->
<style>
    /* Only keep checkout-specific styles here, remove theme variables */
    .checkout-container {
        min-height: 100vh;
        padding-bottom: 120px;
        /* Prevent footer overlap */
        box-sizing: border-box;
        overflow-x: hidden;
    }

    .checkout-header {
        background: linear-gradient(135deg, var(--jirani-primary), #1e4a2f);
        color: var(--jirani-white);
        padding: 2rem 0;
        margin-bottom: 2rem;
    }

    .checkout-header h1 {
        font-weight: 700;
        margin: 0;
    }

    .progress-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 3rem;
        position: relative;
        background: var(--jirani-white);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .progress-steps::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 3px;
        background: rgba(42, 92, 61, 0.2);
        z-index: 1;
        transform: translateY(-50%);
    }

    .step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }

    .step-number {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(42, 92, 61, 0.2);
        color: var(--jirani-gray);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-weight: bold;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .step.active .step-number {
        background: var(--jirani-primary);
        color: white;
        transform: scale(1.1);
    }

    .step.completed .step-number {
        background: var(--jirani-secondary);
        color: var(--jirani-gray);
    }

    .step-label {
        font-size: 0.9rem;
        color: var(--jirani-gray);
        font-weight: 500;
    }

    .step.active .step-label {
        color: var(--jirani-primary);
        font-weight: bold;
    }

    .checkout-step {
        background: var(--jirani-white);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(42, 92, 61, 0.1);
        margin-bottom: 2rem;
    }

    .step-card {
        padding: 2rem;
    }

    .step-title {
        color: var(--jirani-primary);
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
    }

    .step-title i {
        margin-right: 0.75rem;
        font-size: 1.2rem;
    }

    .order-item {
        background: rgba(42, 92, 61, 0.05);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(42, 92, 61, 0.1);
    }

    .order-item-title {
        color: var(--jirani-primary);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .order-item-details {
        color: var(--jirani-gray);
        font-size: 0.9rem;
    }

    .price-highlight {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
    }

    .form-control {
        border: 2px solid rgba(42, 92, 61, 0.2);
        border-radius: 8px;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--jirani-primary);
        box-shadow: 0 0 0 0.2rem rgba(42, 92, 61, 0.25);
    }

    .form-label {
        color: var(--jirani-primary);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .payment-method {
        border: 2px solid rgba(42, 92, 61, 0.2);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        background: var(--jirani-white);
    }

    .payment-method:hover {
        border-color: var(--jirani-primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .payment-method.active {
        border-color: var(--jirani-primary);
        background: rgba(42, 92, 61, 0.05);
    }

    .payment-method input[type="radio"] {
        margin-right: 10px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--jirani-primary), #1e4a2f);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--jirani-secondary), #ff9800);
        color: var(--jirani-gray);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 167, 38, 0.3);
    }

    .btn-secondary {
        background: rgba(42, 92, 61, 0.1);
        border: 2px solid rgba(42, 92, 61, 0.3);
        color: var(--jirani-primary);
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: rgba(42, 92, 61, 0.2);
        border-color: var(--jirani-primary);
        color: var(--jirani-primary);
    }

    .btn-success {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #20c997, #17a2b8);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .order-summary-sidebar {
        background: var(--jirani-white);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(42, 92, 61, 0.1);
        position: sticky;
        top: 20px;
        padding: 1.5rem;
    }

    .summary-title {
        color: var(--jirani-primary);
        font-weight: 700;
        border-bottom: 2px solid rgba(42, 92, 61, 0.1);
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(42, 92, 61, 0.05);
    }

    .summary-total {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--jirani-primary);
        border-top: 2px solid rgba(42, 92, 61, 0.1);
        padding-top: 1rem;
        margin-top: 1rem;
    }

    .loading-spinner {
        color: var(--jirani-primary);
    }

    .alert {
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border-left: 4px solid #28a745;
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

    .alert-info {
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        color: #0c5460;
        border-left: 4px solid #17a2b8;
    }

    @media (max-width: 768px) {
        .checkout-container {
            padding: 1rem 0;
        }

        .checkout-header {
            padding: 1.5rem 0;
        }

        .checkout-header h1 {
            font-size: 1.8rem;
        }

        .progress-steps {
            flex-direction: column;
            gap: 1rem;
        }

        .progress-steps::before {
            display: none;
        }

        .order-summary-sidebar {
            position: static;
            margin-top: 2rem;
        }
    }

    @media (max-width: 576px) {

        .step-card,
        .order-summary-sidebar {
            padding: 1rem;
        }

        .btn {
            width: 100%;
            margin-bottom: 0.75rem;
        }

        .progress-steps {
            flex-direction: row;
            overflow-x: auto;
            gap: 0.5rem;
            padding: 1rem 0.5rem;
        }

        .step {
            min-width: 90px;
        }

        .checkout-header h1 {
            font-size: 1.2rem;
        }

        .order-summary-sidebar {
            margin-bottom: 1.5rem;
        }
    }

      
</style>

<div class="checkout-container">
    <div class="checkout-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-credit-card me-3"></i>Checkout</h1>
                    <p class="mb-0">Complete your purchase securely</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="fas fa-shopping-cart me-2"></i><?php echo count($cartItems); ?> Items
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Checkout Progress Steps -->
        <div class="progress-steps">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-label">Review Order</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-label">Shipping & Contact</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-label">Payment</div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-label">Confirmation</div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-8 mb-4 mb-lg-0">
                <!-- Step 1: Order Summary -->
                <div class="checkout-step" id="step1">
                    <div class="step-card">
                        <h4 class="step-title">
                            <i class="fas fa-shopping-cart"></i>Order Summary
                        </h4>
                        <?php foreach ($cartItems as $item): ?>
                            <div class="order-item">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="order-item-title"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <div class="order-item-details">
                                            <i class="fas fa-store me-1"></i>
                                            <?php echo htmlspecialchars($item['vendor_name']); ?> |
                                            <i class="fas fa-hashtag me-1"></i>
                                            Quantity: <?php echo $item['quantity']; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="price-highlight">
                                            KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center mt-4">
                            <button class="btn btn-primary w-100" onclick="nextStep(2)">
                                <i class="fas fa-arrow-right me-2"></i>Proceed to Checkout
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Shipping & Contact Information -->
                <div class="checkout-step" id="step2" style="display: none;">
                    <div class="step-card">
                        <h4 class="step-title">
                            <i class="fas fa-shipping-fast"></i>Shipping & Contact Information
                        </h4>
                        <form id="shipping-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name"
                                            value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                            value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="delivery_address" class="form-label">Delivery Address</label>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3"
                                    placeholder="Enter your complete delivery address" required></textarea>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Please provide a detailed address including landmarks for easy delivery
                                </small>
                            </div>
                            <div class="mb-3">
                                <label for="delivery_instructions" class="form-label">Delivery Instructions
                                    (Optional)</label>
                                <textarea class="form-control" id="delivery_instructions" name="delivery_instructions"
                                    rows="2" placeholder="Any special instructions for delivery"></textarea>
                            </div>
                        </form>

                        <div class="d-flex justify-content-between mt-4">
                            <button class="btn btn-secondary" onclick="prevStep(1)">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <button class="btn btn-primary w-100" onclick="nextStep(3)">
                                <i class="fas fa-arrow-right me-2"></i>Continue to Payment
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Payment Method -->
                <div class="checkout-step" id="step3" style="display: none;">
                    <div class="step-card">
                        <h4 class="step-title">
                            <i class="fas fa-credit-card"></i>Payment Method
                        </h4>

                        <div class="payment-methods">
                            <div class="payment-method active" data-method="mpesa">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="mpesa" checked>
                                    <img src="Images/mpesa-logo.png" alt="M-Pesa"
                                        style="height: 30px; margin-left: 10px;">
                                    <span class="ms-2 fw-bold">M-Pesa Mobile Money</span>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>Pay securely using M-Pesa
                                </small>
                            </div>

                            <div class="payment-method" data-method="cash">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="cash">
                                    <i class="fas fa-money-bill-wave ms-2 text-success"></i>
                                    <span class="ms-2 fw-bold">Cash on Delivery</span>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-truck me-1"></i>Pay when you receive your order
                                </small>
                            </div>
                        </div>

                        <div id="mpesa-payment-form" class="mt-4">
                            <div class="mb-3">
                                <label for="mpesa_phone" class="form-label">M-Pesa Phone Number</label>
                                <input type="tel" class="form-control" id="mpesa_phone" name="mpesa_phone"
                                    value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Enter the phone number registered with M-Pesa
                                </small>
                            </div>
                        </div>

                        <div id="payment-loading" style="display: none;">
                            <div class="text-center">
                                <div class="spinner-border loading-spinner" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Processing payment...</p>
                            </div>
                        </div>

                        <div id="payment-instructions" style="display: none;"></div>
                        <div id="payment-alert" class="alert" style="display: none;"></div>

                        <div class="d-flex justify-content-between mt-4">
                            <button class="btn btn-secondary" onclick="prevStep(2)">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <button class="btn btn-success w-100" onclick="confirmOrder()">
                                <i class="fas fa-check me-2"></i>Confirm Order
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Order Confirmation -->
                <div class="checkout-step" id="step4" style="display: none;">
                    <div class="step-card text-center">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="text-success mb-3">Order Confirmed!</h3>
                        <p class="text-muted mb-4">Your order has been successfully placed.</p>
                        <div id="order-details"></div>
                        <div class="mt-4">
                            <a href="orders.php" class="btn btn-primary me-2">
                                <i class="fas fa-list me-2"></i>View My Orders
                            </a>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-home me-2"></i>Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <!-- Order Summary Sidebar -->
                <div class="order-summary-sidebar">
                    <h5 class="summary-title">
                        <i class="fas fa-receipt me-2"></i>Order Summary
                    </h5>

                    <div id="order-summary-content">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="summary-item">
                                <span><?php echo htmlspecialchars($item['name']); ?> x
                                    <?php echo $item['quantity']; ?></span>
                                <span>KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>

                        <hr>
                        <div class="summary-item summary-total">
                            <span>Total</span>
                            <span>KSh <?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentStep = 1;

    function nextStep(step) {
        if (step === 2 && !validateStep1()) return;
        if (step === 3 && !validateStep2()) return;

        document.getElementById(`step${currentStep}`).style.display = 'none';
        document.getElementById(`step${step}`).style.display = 'block';

        // Update progress steps
        updateProgressSteps(step);
        currentStep = step;
    }

    function prevStep(step) {
        document.getElementById(`step${currentStep}`).style.display = 'none';
        document.getElementById(`step${step}`).style.display = 'block';

        updateProgressSteps(step);
        currentStep = step;
    }

    function updateProgressSteps(activeStep) {
        const steps = document.querySelectorAll('.step');
        steps.forEach((step, index) => {
            step.classList.remove('active', 'completed');
            if (index + 1 < activeStep) {
                step.classList.add('completed');
            } else if (index + 1 === activeStep) {
                step.classList.add('active');
            }
        });
    }

    function validateStep1() {
        return true; // Order summary is always valid
    }

    function validateStep2() {
        const form = document.getElementById('shipping-form');
        const requiredFields = ['full_name', 'phone', 'email', 'delivery_address'];

        for (let field of requiredFields) {
            const input = form.querySelector(`[name="${field}"]`);
            if (!input.value.trim()) {
                alert(`Please fill in ${field.replace('_', ' ')}`);
                input.focus();
                return false;
            }
        }
        return true;
    }

    // Payment method selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function () {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
            this.classList.add('active');
            this.querySelector('input[type="radio"]').checked = true;
        });
    });

    async function confirmOrder() {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        const shippingForm = document.getElementById('shipping-form');

        // Validate shipping form
        if (!validateStep2()) {
            return;
        }

        // Show loading
        document.getElementById('payment-loading').style.display = 'block';

        try {
            // Prepare order data
            const orderData = {
                cart_items: <?php echo json_encode($cartItems); ?>,
                shipping_info: {
                    full_name: document.getElementById('full_name').value,
                    phone: document.getElementById('phone').value,
                    email: document.getElementById('email').value,
                    delivery_address: document.getElementById('delivery_address').value,
                    delivery_instructions: document.getElementById('delivery_instructions').value
                },
                payment_method: paymentMethod
            };

            // Create orders
            const response = await fetch('api/orders/create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (result.success) {
                // Handle payment based on method
                if (paymentMethod === 'mpesa') {
                    await processMpesaPayment(result.orders[0].order_id);
                } else {
                    // Cash payment - show success
                    document.getElementById('payment-loading').style.display = 'none';
                    nextStep(4);

                    // Show order details
                    let orderDetailsHtml = '<div class="alert alert-success"><h6>Order Details</h6>';
                    result.orders.forEach(order => {
                        orderDetailsHtml += `<p><strong>Order #${order.order_id}</strong> - ${order.vendor_name}</p>`;
                    });
                    orderDetailsHtml += `<p><strong>Payment Method:</strong> Cash on Delivery</p>`;
                    orderDetailsHtml += `<p><strong>Total Amount:</strong> KSh ${result.total_amount.toFixed(2)}</p>`;
                    orderDetailsHtml += '<p>You will pay when you receive your order.</p></div>';

                    document.getElementById('order-details').innerHTML = orderDetailsHtml;
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            document.getElementById('payment-alert').className = 'alert alert-danger';
            document.getElementById('payment-alert').textContent = error.message;
            document.getElementById('payment-alert').style.display = 'block';
            document.getElementById('payment-loading').style.display = 'none';
        }
    }

    async function processMpesaPayment(orderId) {
        const formData = new FormData();
        formData.append('phone', document.getElementById('mpesa_phone').value);
        formData.append('amount', <?php echo $total; ?>);
        formData.append('order_id', orderId);

        try {
            document.getElementById('payment-loading').style.display = 'block';

            const response = await fetch('api/mpesa/initiate.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                document.getElementById('payment-instructions').innerHTML = `
                <div class="alert alert-info">
                        <h5><i class="fas fa-mobile-alt me-2"></i>Complete Your Payment</h5>
                    <p>1. You will receive an M-Pesa prompt on your phone</p>
                    <p>2. Enter your M-Pesa PIN to complete the payment</p>
                    <p>3. Wait for confirmation</p>
                </div>
            `;
                document.getElementById('payment-instructions').style.display = 'block';

                // Poll for payment status
                pollPaymentStatus(orderId);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            document.getElementById('payment-alert').className = 'alert alert-danger';
            document.getElementById('payment-alert').textContent = error.message;
            document.getElementById('payment-alert').style.display = 'block';
            document.getElementById('payment-loading').style.display = 'none';
        }
    }

    async function pollPaymentStatus(paymentId) {
        const maxAttempts = 30;
        let attempts = 0;

        const checkStatus = async () => {
            try {
                const response = await fetch(`api/mpesa/status.php?payment_id=${paymentId}`);
                const data = await response.json();

                if (data.status === 'COMPLETED') {
                    document.getElementById('payment-alert').className = 'alert alert-success';
                    document.getElementById('payment-alert').textContent = 'Payment successful!';
                    document.getElementById('payment-alert').style.display = 'block';

                    setTimeout(() => {
                        nextStep(4);
                        document.getElementById('order-details').innerHTML = `
                            <div class="alert alert-success">
                                <h6>Order Details</h6>
                                <p><strong>Order ID:</strong> ${paymentId}</p>
                                <p><strong>Payment Method:</strong> M-Pesa</p>
                                <p><strong>Total Amount:</strong> KSh <?php echo number_format($total, 2); ?></p>
                                <p>Payment completed successfully!</p>
                                <p>You will receive an email confirmation shortly.</p>
                            </div>
                        `;
                    }, 2000);
                    return;
                }

                attempts++;
                if (attempts < maxAttempts) {
                    setTimeout(checkStatus, 10000);
                } else {
                    document.getElementById('payment-alert').className = 'alert alert-warning';
                    document.getElementById('payment-alert').textContent = 'Payment status check timed out. Please contact support.';
                    document.getElementById('payment-alert').style.display = 'block';
                }
            } catch (error) {
                console.error('Error checking payment status:', error);
            }
        };

        checkStatus();
    }
</script>

<?php include 'footer.php'; ?>