<?php
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

if (!$orderId) {
    header('Location: orders.php');
    exit();
}

// Fetch order details
$stmt = $conn->prepare('
    SELECT o.*, v.business_name as vendor_name, 
           vu.phone as vendor_phone, vu.email as vendor_email,
           u.name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM orders o 
    JOIN vendors v ON o.vendor_id = v.user_id
    JOIN users vu ON v.user_id = vu.id
    JOIN users u ON o.customer_id = u.id
    WHERE o.id = ? AND o.customer_id = ?
');
$stmt->bind_param('ii', $orderId, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Fetch order items
$stmt = $conn->prepare('
    SELECT oi.*, p.name as product_name, p.image as product_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
');
$stmt->bind_param('i', $orderId);
$stmt->execute();
$orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch payment information
$stmt = $conn->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1');
$stmt->bind_param('i', $orderId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

// Calculate total
$total = 0;
foreach ($orderItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

$pageTitle = "Order Details #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . " - Jirani";
$currentPage = 'orders';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Navigation.php';
$auth = new Auth($conn);
$currentUser = null;
if ($auth->isLoggedIn()) {
    $currentUser = $auth->getUser();
}
$navigation = new Navigation($conn, $currentUser, 'orders');
echo $navigation->renderCustomerNav();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="css/About.css">
<link rel="stylesheet" href="css/footer.css">
<style>
    :root {
        --jirani-primary: #2A5C3D;
        --jirani-secondary: #FFA726;
        --jirani-white: #FFFFFF;
        --jirani-gray: #333333;
    }

    html,
    body {
        height: 100%;
        min-height: 100%;
        width: 100%;
        overflow-x: hidden;
    }

    .order-details-container {
        min-height: 100vh;
        padding-bottom: 120px;
        /* Prevent footer overlap */
        box-sizing: border-box;
        overflow-x: hidden;
    }

    .order-header {
        background: linear-gradient(135deg, var(--jirani-primary), #1e4a2f);
        color: var(--jirani-white);
        padding: 2rem 0;
        margin-bottom: 2rem;
    }

    .order-header h1 {
        font-weight: 700;
        margin: 0;
    }

    .order-card {
        background: var(--jirani-white);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(42, 92, 61, 0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .order-card-header {
        background: linear-gradient(135deg, rgba(42, 92, 61, 0.1), rgba(42, 92, 61, 0.05));
        padding: 1.5rem;
        border-bottom: 1px solid rgba(42, 92, 61, 0.1);
    }

    .order-card-body {
        padding: 1.5rem;
    }

    .order-number {
        color: var(--jirani-primary);
        font-weight: 700;
        font-size: 1.3rem;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .status-pending {
        background: linear-gradient(135deg, #ffc107, #ffb74d);
        color: #000;
    }

    .status-accepted {
        background: linear-gradient(135deg, #007bff, #2196f3);
        color: white;
    }

    .status-shipped {
        background: linear-gradient(135deg, #17a2b8, #00bcd4);
        color: white;
    }

    .status-delivered {
        background: linear-gradient(135deg, #28a745, #4caf50);
        color: white;
    }

    .status-cancelled {
        background: linear-gradient(135deg, #dc3545, #f44336);
        color: white;
    }

    .status-disputed {
        background: linear-gradient(135deg, #fd7e14, #ff9800);
        color: white;
    }

    .order-item {
        background: rgba(42, 92, 61, 0.05);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(42, 92, 61, 0.1);
        transition: all 0.3s ease;
    }

    .order-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .product-image {
        border-radius: 8px;
        object-fit: cover;
        width: 100%;
        height: 80px;
    }

    .product-title {
        color: var(--jirani-primary);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .price-highlight {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
    }

    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 3px;
        background: linear-gradient(135deg, var(--jirani-primary), var(--jirani-secondary));
        border-radius: 2px;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -22px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--jirani-primary);
        border: 3px solid white;
        box-shadow: 0 0 0 2px var(--jirani-primary);
    }

    .timeline-item.completed::before {
        background: var(--jirani-secondary);
        box-shadow: 0 0 0 2px var(--jirani-secondary);
    }

    .timeline-item.current::before {
        background: var(--jirani-secondary);
        box-shadow: 0 0 0 2px var(--jirani-secondary);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 167, 38, 0.7);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(255, 167, 38, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(255, 167, 38, 0);
        }
    }

    .timeline-content h6 {
        margin: 0 0 5px 0;
        color: var(--jirani-primary);
        font-weight: 600;
    }

    .timeline-content p {
        margin: 0;
        color: var(--jirani-gray);
        font-size: 0.9rem;
    }

    .info-card {
        background: var(--jirani-white);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(42, 92, 61, 0.1);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .info-card-header {
        background: linear-gradient(135deg, rgba(42, 92, 61, 0.1), rgba(42, 92, 61, 0.05));
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(42, 92, 61, 0.1);
    }

    .info-card-title {
        color: var(--jirani-primary);
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .info-card-body {
        padding: 1.5rem;
    }

    .contact-info {
        background: rgba(42, 92, 61, 0.05);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(42, 92, 61, 0.1);
    }

    .contact-name {
        color: var(--jirani-primary);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .contact-detail {
        color: var(--jirani-gray);
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    .btn-jirani {
        background: linear-gradient(135deg, var(--jirani-primary), #1e4a2f);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-jirani:hover {
        background: linear-gradient(135deg, var(--jirani-secondary), #ff9800);
        color: var(--jirani-gray);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 167, 38, 0.3);
        text-decoration: none;
    }

    .btn-danger-jirani {
        background: linear-gradient(135deg, #dc3545, #c82333);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-danger-jirani:hover {
        background: linear-gradient(135deg, #c82333, #bd2130);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    .btn-secondary-jirani {
        background: rgba(42, 92, 61, 0.1);
        border: 2px solid rgba(42, 92, 61, 0.3);
        color: var(--jirani-primary);
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary-jirani:hover {
        background: rgba(42, 92, 61, 0.2);
        border-color: var(--jirani-primary);
        color: var(--jirani-primary);
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .order-details-container {
            padding: 1rem 0;
        }

        .order-header {
            padding: 1.5rem 0;
        }

        .order-header h1 {
            font-size: 1.8rem;
        }

        .timeline {
            padding-left: 20px;
        }

        .timeline-item::before {
            left: -17px;
            width: 10px;
            height: 10px;
        }
    }
</style>

<div class="order-details-container">
    <div class="order-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>
                        <i class="fas fa-shopping-bag me-3"></i>
                        Order #<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?>
                    </h1>
                    <p class="mb-0">Track your order status and details</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <!-- Order Header -->
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="order-number mb-2">
                                    <i class="fas fa-receipt me-2"></i>
                                    Order #<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?>
                                </h4>
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Placed on <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-end">
                                <p class="mb-1">
                                    <strong>Vendor:</strong> <?php echo htmlspecialchars($order['vendor_name']); ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Total:</strong>
                                    <span class="price-highlight">KSh <?php echo number_format($total, 2); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="order-card">
                    <div class="order-card-header">
                        <h5 class="info-card-title">
                            <i class="fas fa-list me-2"></i>Order Items
                        </h5>
                    </div>
                    <div class="order-card-body">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="order-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2 col-4">
                                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>"
                                            alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                            class="product-image">
                                    </div>
                                    <div class="col-md-6 col-8">
                                        <h6 class="product-title"><?php echo htmlspecialchars($item['product_name']); ?>
                                        </h6>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-hashtag me-1"></i>
                                            Quantity: <?php echo $item['quantity']; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="price-highlight">
                                            KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                        </div>
                                        <small class="text-muted">
                                            KSh <?php echo number_format($item['price'], 2); ?> each
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-end mt-3">
                            <h5 class="price-highlight">
                                Total: KSh <?php echo number_format($total, 2); ?>
                            </h5>
                        </div>
                    </div>
                </div>

                <!-- Order Timeline -->
                <div class="order-card">
                    <div class="order-card-header">
                        <h5 class="info-card-title">
                            <i class="fas fa-clock me-2"></i>Order Timeline
                        </h5>
                    </div>
                    <div class="order-card-body">
                        <div class="timeline">
                            <?php echo generateOrderTimeline($order); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Delivery Information -->
                <div class="info-card">
                    <div class="info-card-header">
                        <h5 class="info-card-title">
                            <i class="fas fa-truck me-2"></i>Delivery Information
                        </h5>
                    </div>
                    <div class="info-card-body">
                        <div class="contact-info">
                            <h6 class="contact-name">
                                <i class="fas fa-map-marker-alt me-2"></i>Delivery Address
                            </h6>
                            <p class="contact-detail">
                                <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                            </p>
                        </div>

                        <?php if (!empty($order['delivery_instructions'])): ?>
                            <div class="contact-info">
                                <h6 class="contact-name">
                                    <i class="fas fa-info-circle me-2"></i>Delivery Instructions
                                </h6>
                                <p class="contact-detail">
                                    <?php echo nl2br(htmlspecialchars($order['delivery_instructions'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="contact-info">
                            <h6 class="contact-name">
                                <i class="fas fa-calendar-alt me-2"></i>Estimated Delivery
                            </h6>
                            <p class="contact-detail">
                                <?php echo date('M d, Y', strtotime($order['created_at'] . ' +2 days')); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="info-card">
                    <div class="info-card-header">
                        <h5 class="info-card-title">
                            <i class="fas fa-phone me-2"></i>Contact Information
                        </h5>
                    </div>
                    <div class="info-card-body">
                        <div class="contact-info">
                            <h6 class="contact-name">
                                <i class="fas fa-store me-2"></i>Vendor Contact
                            </h6>
                            <p class="contact-detail">
                                <strong><?php echo htmlspecialchars($order['vendor_name']); ?></strong>
                            </p>
                            <p class="contact-detail">
                                <i class="fas fa-phone me-1"></i>
                                <?php echo htmlspecialchars($order['vendor_phone']); ?>
                            </p>
                            <p class="contact-detail">
                                <i class="fas fa-envelope me-1"></i>
                                <?php echo htmlspecialchars($order['vendor_email']); ?>
                            </p>
                        </div>

                        <hr>

                        <div class="contact-info">
                            <h6 class="contact-name">
                                <i class="fas fa-user me-2"></i>Your Contact
                            </h6>
                            <p class="contact-detail">
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                            </p>
                            <p class="contact-detail">
                                <i class="fas fa-phone me-1"></i>
                                <?php echo htmlspecialchars($order['customer_phone']); ?>
                            </p>
                            <p class="contact-detail">
                                <i class="fas fa-envelope me-1"></i>
                                <?php echo htmlspecialchars($order['customer_email']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <?php if ($payment): ?>
                    <div class="info-card">
                        <div class="info-card-header">
                            <h5 class="info-card-title">
                                <i class="fas fa-credit-card me-2"></i>Payment Information
                            </h5>
                        </div>
                        <div class="info-card-body">
                            <div class="contact-info">
                                <p class="contact-detail">
                                    <strong>Method:</strong> <?php echo ucfirst($payment['method']); ?>
                                </p>
                                <p class="contact-detail">
                                    <strong>Status:</strong>
                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </p>
                                <p class="contact-detail">
                                    <strong>Amount:</strong>
                                    <span class="price-highlight">KSh
                                        <?php echo number_format($payment['amount'], 2); ?></span>
                                </p>
                                <?php if ($payment['mpesa_transaction_id']): ?>
                                    <p class="contact-detail">
                                        <strong>Transaction ID:</strong>
                                        <?php echo htmlspecialchars($payment['mpesa_transaction_id']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="info-card">
                    <div class="info-card-body">
                        <a href="orders.php" class="btn btn-secondary-jirani w-100 mb-2">
                            <i class="fas fa-arrow-left me-2"></i>Back to Orders
                        </a>
                        <?php if ($order['status'] === 'pending'): ?>
                            <button class="btn btn-danger-jirani w-100 mb-2" onclick="cancelOrder(<?php echo $orderId; ?>)">
                                <i class="fas fa-times me-2"></i>Cancel Order
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-jirani w-100" onclick="contactVendor()">
                            <i class="fas fa-comments me-2"></i>Contact Vendor
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function cancelOrder(orderId) {
        if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
            fetch('api/orders/cancel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order cancelled successfully');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to cancel order');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the order');
                });
        }
    }

    function contactVendor() {
        const vendorPhone = '<?php echo htmlspecialchars($order['vendor_phone']); ?>';
        const vendorName = '<?php echo htmlspecialchars($order['vendor_name']); ?>';

        if (confirm(`Contact ${vendorName}?\n\nPhone: ${vendorPhone}\nEmail: <?php echo htmlspecialchars($order['vendor_email']); ?>`)) {
            window.open(`tel:${vendorPhone}`, '_blank');
        }
    }
</script>

<?php
function getStatusBadgeClass($status)
{
    return match ($status) {
        'pending' => 'pending',
        'accepted' => 'accepted',
        'shipped' => 'shipped',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
        'disputed' => 'disputed',
        default => 'secondary'
    };
}

function getPaymentStatusBadgeClass($status)
{
    return match ($status) {
        'pending' => 'payment-pending',
        'completed' => 'payment-completed',
        'failed' => 'payment-failed',
        default => 'secondary'
    };
}

function generateOrderTimeline($order)
{
    $timeline = [];

    // Order placed
    $timeline[] = [
        'status' => 'completed',
        'title' => 'Order Placed',
        'description' => 'Your order has been successfully placed',
        'time' => date('M d, Y H:i', strtotime($order['created_at']))
    ];

    // Processing
    if (in_array($order['status'], ['accepted', 'shipped', 'delivered'])) {
        $timeline[] = [
            'status' => 'completed',
            'title' => 'Processing',
            'description' => 'Vendor is preparing your order',
            'time' => date('M d, Y H:i', strtotime($order['created_at'] . ' +1 hour'))
        ];
    } elseif ($order['status'] === 'pending') {
        $timeline[] = [
            'status' => 'current',
            'title' => 'Processing',
            'description' => 'Vendor is preparing your order',
            'time' => 'In progress'
        ];
    } else {
        $timeline[] = [
            'status' => 'pending',
            'title' => 'Processing',
            'description' => 'Vendor is preparing your order',
            'time' => 'Pending'
        ];
    }

    // Shipped
    if (in_array($order['status'], ['shipped', 'delivered'])) {
        $timeline[] = [
            'status' => 'completed',
            'title' => 'Shipped',
            'description' => 'Order is on its way to you',
            'time' => date('M d, Y H:i', strtotime($order['created_at'] . ' +1 day'))
        ];
    } elseif ($order['status'] === 'accepted') {
        $timeline[] = [
            'status' => 'current',
            'title' => 'Shipped',
            'description' => 'Order is on its way to you',
            'time' => 'In progress'
        ];
    } else {
        $timeline[] = [
            'status' => 'pending',
            'title' => 'Shipped',
            'description' => 'Order is on its way to you',
            'time' => 'Pending'
        ];
    }

    // Delivered
    if ($order['status'] === 'delivered') {
        $timeline[] = [
            'status' => 'completed',
            'title' => 'Delivered',
            'description' => 'Order delivered to your address',
            'time' => date('M d, Y H:i', strtotime($order['created_at'] . ' +2 days'))
        ];
    } else {
        $timeline[] = [
            'status' => 'pending',
            'title' => 'Delivered',
            'description' => 'Order delivered to your address',
            'time' => 'Pending'
        ];
    }

    $html = '';
    foreach ($timeline as $item) {
        $html .= '<div class="timeline-item ' . $item['status'] . '">';
        $html .= '<div class="timeline-content">';
        $html .= '<h6>' . htmlspecialchars($item['title']) . '</h6>';
        $html .= '<p>' . htmlspecialchars($item['description']) . '</p>';
        $html .= '<small class="text-muted">' . htmlspecialchars($item['time']) . '</small>';
        $html .= '</div>';
        $html .= '</div>';
    }

    return $html;
}
?>

<?php include 'footer.php'; ?>