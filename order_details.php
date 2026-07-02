<?php
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

if (!$orderId) {
    header('Location: ' . SITE_URL . 'orders.php');
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
    header('Location: ' . SITE_URL . 'orders.php');
    exit();
}

// Fetch order items
$stmt = $conn->prepare('
    SELECT oi.*, p.name as product_name, p.image_url as product_image
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
$additionalCSS = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css'
];
require_once __DIR__ . '/navbar.php';
?>
<style>
.order-details-page { padding: 40px 0 80px; }
.order-timeline-wrap { position: relative; padding-left: 20px; }
.order-timeline-wrap::before {
    content: '';
    position: absolute;
    left: 7px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--j-border-light);
}
.timeline-item { position: relative; margin-bottom: 24px; }
.timeline-item:last-child { margin-bottom: 0; }
.timeline-item::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 5px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--j-bg);
    border: 2px solid var(--j-border-light);
    z-index: 1;
}
.timeline-item.completed::before {
    background: var(--j-primary);
    border-color: var(--j-primary);
}
.timeline-item.current::before {
    background: var(--j-accent);
    border-color: var(--j-accent);
    box-shadow: 0 0 0 4px rgba(255, 167, 38, 0.2);
}
.timeline-content h6 { margin: 0 0 4px; font-weight: 700; color: var(--j-text); }
.timeline-content p { margin: 0 0 4px; font-size: 0.9rem; color: var(--j-text-muted); }
.timeline-content small { font-size: 0.8rem; color: var(--j-text-muted); opacity: 0.8; }

.status-badge-custom {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 99px;
    font-size: 0.8rem; font-weight: 700;
    text-transform: capitalize;
}
.badge-pending { background: rgba(245,158,11,0.1); color: #d97706; }
.badge-accepted { background: rgba(59,130,246,0.1); color: #2563eb; }
.badge-shipped { background: rgba(139,92,246,0.1); color: #7c3aed; }
.badge-delivered { background: rgba(22,163,74,0.1); color: #16a34a; }
.badge-cancelled { background: rgba(239,68,68,0.1); color: #dc2626; }

.product-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: var(--j-radius-md); }
</style>

<div class="j-page-header">
    <div class="j-container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <div>
                <h1><i class="fas fa-shopping-bag" style="margin-right:12px;opacity:.8;"></i>Order #<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></h1>
                <p style="opacity:.7;margin:8px 0 0;font-size:0.95rem;">Track your order status and details</p>
            </div>
            <div>
                <span class="status-badge-custom badge-<?php echo strtolower($order['status']); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="j-container order-details-page">
    <div class="row">
        <div class="col-lg-8">
            <!-- Order Info -->
            <div class="j-card" style="margin-bottom: 24px;">
                <div class="j-card-header" style="padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--j-border-light);">
                    <div>
                        <h4 style="margin: 0; font-weight: 700; color: var(--j-text); font-size: 1.1rem;">
                            <i class="fas fa-receipt me-2" style="color:var(--j-primary)"></i> Order Details
                        </h4>
                        <p style="margin: 4px 0 0; font-size: 0.85rem; color: var(--j-text-muted);">
                            Placed on <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                        </p>
                    </div>
                </div>
                <div class="j-card-body" style="padding: 24px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0;">
                        <div>
                            <span style="font-size: 0.8rem; color: var(--j-text-muted); text-transform: uppercase; font-weight: 600;">Vendor</span>
                            <div style="font-weight: 600; color: var(--j-text);"><?php echo htmlspecialchars($order['vendor_name']); ?></div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 0.8rem; color: var(--j-text-muted); text-transform: uppercase; font-weight: 600;">Total Amount</span>
                            <div style="font-weight: 800; color: var(--j-primary); font-size: 1.2rem;">KSh <?php echo number_format($total, 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="j-card" style="margin-bottom: 24px;">
                <div class="j-card-header" style="padding: 20px 24px; border-bottom: 1px solid var(--j-border-light);">
                    <h5 style="margin: 0; font-weight: 700; font-size: 1.05rem;">
                        <i class="fas fa-list me-2" style="color:var(--j-primary)"></i> Order Items
                    </h5>
                </div>
                <div class="j-card-body" style="padding: 0;">
                    <?php foreach ($orderItems as $item): ?>
                        <div style="padding: 20px 24px; border-bottom: 1px solid var(--j-border-light); display: flex; align-items: center; gap: 16px;">
                            <img src="<?php echo htmlspecialchars(strpos($item['product_image'], 'http') === 0 ? $item['product_image'] : (strpos($item['product_image'], 'uploads/') !== false ? SITE_URL . $item['product_image'] : SITE_URL . 'uploads/' . $item['product_image'])); ?>" alt="Product" class="product-thumb">
                            <div style="flex: 1;">
                                <h6 style="margin: 0 0 4px; font-weight: 600; color: var(--j-text);"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                <p style="margin: 0; font-size: 0.85rem; color: var(--j-text-muted);">Qty: <?php echo $item['quantity']; ?> × KSh <?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            <div style="font-weight: 700; color: var(--j-text);">
                                KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Timeline -->
            <div class="j-card" style="margin-bottom: 24px;">
                <div class="j-card-header" style="padding: 20px 24px; border-bottom: 1px solid var(--j-border-light);">
                    <h5 style="margin: 0; font-weight: 700; font-size: 1.05rem;">
                        <i class="fas fa-clock me-2" style="color:var(--j-primary)"></i> Order Status
                    </h5>
                </div>
                <div class="j-card-body" style="padding: 24px;">
                    <div class="order-timeline-wrap">
                        <?php echo generateOrderTimeline($order); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Delivery Info -->
            <div class="j-card" style="margin-bottom: 24px;">
                <div class="j-card-header" style="padding: 20px 24px; border-bottom: 1px solid var(--j-border-light);">
                    <h5 style="margin: 0; font-weight: 700; font-size: 1.05rem;">
                        <i class="fas fa-truck me-2" style="color:var(--j-primary)"></i> Delivery Info
                    </h5>
                </div>
                <div class="j-card-body" style="padding: 24px;">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 0.8rem; color: var(--j-text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Delivery Address</div>
                        <div style="font-size: 0.95rem; line-height: 1.5; color: var(--j-text);"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></div>
                    </div>
                    <?php if (!empty($order['delivery_instructions'])): ?>
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 0.8rem; color: var(--j-text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Instructions</div>
                        <div style="font-size: 0.95rem; line-height: 1.5; color: var(--j-text);"><?php echo nl2br(htmlspecialchars($order['delivery_instructions'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--j-text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Est. Delivery</div>
                        <div style="font-size: 0.95rem; color: var(--j-text);"><?php echo date('M d, Y', strtotime($order['created_at'] . ' +2 days')); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Info -->
            <?php if ($payment): ?>
            <div class="j-card" style="margin-bottom: 24px;">
                <div class="j-card-header" style="padding: 20px 24px; border-bottom: 1px solid var(--j-border-light);">
                    <h5 style="margin: 0; font-weight: 700; font-size: 1.05rem;">
                        <i class="fas fa-credit-card me-2" style="color:var(--j-primary)"></i> Payment Details
                    </h5>
                </div>
                <div class="j-card-body" style="padding: 24px;">
                    <div style="margin-bottom: 12px;">
                        <span style="font-size: 0.9rem; color: var(--j-text-muted); font-weight: 600;">Method:</span>
                        <span style="font-weight: 600; color: var(--j-text); float: right;"><?php echo ucfirst($payment['method']); ?></span>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <span style="font-size: 0.9rem; color: var(--j-text-muted); font-weight: 600;">Status:</span>
                        <span class="status-badge-custom badge-<?php echo strtolower($payment['status']); ?>" style="float: right; padding: 2px 10px; font-size: 0.7rem;"><?php echo ucfirst($payment['status']); ?></span>
                    </div>
                    <?php if ($payment['mpesa_transaction_id']): ?>
                    <div style="margin-bottom: 12px;">
                        <span style="font-size: 0.9rem; color: var(--j-text-muted); font-weight: 600;">Transaction ID:</span>
                        <span style="font-weight: 600; color: var(--j-text); float: right;"><?php echo htmlspecialchars($payment['mpesa_transaction_id']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div style="border-top: 1px solid var(--j-border-light); padding-top: 12px; margin-top: 12px;">
                        <span style="font-size: 0.9rem; color: var(--j-text-muted); font-weight: 600;">Amount:</span>
                        <span style="font-weight: 800; color: var(--j-primary); float: right; font-size: 1.1rem;">KSh <?php echo number_format($payment['amount'], 2); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Contact Info -->
            <div class="j-card" style="margin-bottom: 24px;">
                <div class="j-card-header" style="padding: 20px 24px; border-bottom: 1px solid var(--j-border-light);">
                    <h5 style="margin: 0; font-weight: 700; font-size: 1.05rem;">
                        <i class="fas fa-address-book me-2" style="color:var(--j-primary)"></i> Contact Details
                    </h5>
                </div>
                <div class="j-card-body" style="padding: 24px;">
                    <div style="margin-bottom: 20px;">
                        <div style="font-weight: 600; color: var(--j-text); margin-bottom: 4px;">Vendor: <?php echo htmlspecialchars($order['vendor_name']); ?></div>
                        <div style="font-size: 0.9rem; color: var(--j-text-muted);"><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($order['vendor_phone']); ?></div>
                        <div style="font-size: 0.9rem; color: var(--j-text-muted);"><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($order['vendor_email']); ?></div>
                    </div>
                    <div style="border-top: 1px solid var(--j-border-light); padding-top: 20px;">
                        <div style="font-weight: 600; color: var(--j-text); margin-bottom: 4px;">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></div>
                        <div style="font-size: 0.9rem; color: var(--j-text-muted);"><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                        <div style="font-size: 0.9rem; color: var(--j-text-muted);"><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($order['customer_email']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="orders.php" class="j-btn j-btn-outline" style="text-align: center; display: block; width: 100%;">
                    <i class="fas fa-arrow-left me-2"></i> Back to Orders
                </a>
                <button class="j-btn j-btn-primary" style="width: 100%;" onclick="contactVendor()">
                    <i class="fas fa-comments me-2"></i> Contact Vendor
                </button>
                <?php if ($order['status'] === 'pending'): ?>
                <button class="j-btn" style="background: #dc2626; color: white; border: none; width: 100%;" onclick="cancelOrder(<?php echo $orderId; ?>)">
                    <i class="fas fa-times me-2"></i> Cancel Order
                </button>
                <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/footer.php'; ?>