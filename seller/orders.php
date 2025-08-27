<?php
require_once dirname(__DIR__) . '/config/config.php';
// Vendor session/role check
if (!isset($_SESSION['user_id']) || !$auth->hasRole('vendor')) {
    header('Location: ../auth.php?action=login');
    exit();
}
$db = getDbConnection();
$vendorId = $_SESSION['user_id'];

$pageTitle = 'Orders';
$currentPage = 'orders';

require_once __DIR__ . '/../includes/Order.php';

// Initialize database connection and Order class
$orderObj = new Order($db);

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = $_POST['order_id'] ?? null;
    $action = $_POST['action'];

    if ($orderId) {
        try {
            // Verify order belongs to vendor
            $orderData = $orderObj->get_order_by_id($orderId);

            if ($orderData && $orderData['vendor_id'] == $vendorId) {
                switch ($action) {
                    case 'processing':
                        $orderObj->update_order_status($orderId, 'processing');
                        $_SESSION['success'] = 'Order status updated to processing.';
                        break;
                    case 'shipped':
                        $orderObj->update_order_status($orderId, 'shipped');
                        $_SESSION['success'] = 'Order status updated to shipped.';
                        break;
                    case 'delivered':
    if ($orderObj->update_order_status($orderId, 'delivered')) {
        // Check if payment method is cash and update payment status accordingly
        $payment = $orderObj->get_order_payment($orderId);
        if ($payment && (strtolower($payment['method']) === 'cash' || strtolower($payment['method']) === 'cash_on_delivery')) {
            // Mark payment as completed for cash on delivery
            $orderObj->update_payment_status($payment['id'], 'completed');

            // --- COD PAYOUT TRIGGER ---
            // Payout to vendor for COD (direct, not via escrow)
            require_once dirname(__DIR__) . '/includes/Mpesa.php';
            $vendor = $orderObj->get_order_vendor($orderId);
            if ($vendor && !empty($vendor['mpesa_number'])) {
                $commissionRate = 5.0; // Default commission rate
                if (isset($globalSettings['commission_rate'])) {
                    $commissionRate = floatval($globalSettings['commission_rate']);
                }
                $amount = floatval($payment['amount']);
                $commission = round($amount * ($commissionRate / 100), 2);
                $vendorPayout = $amount - $commission;
                // Use Daraja M-Pesa API for payment release
                global $darajaMpesa;
                if (!isset($darajaMpesa) || !$darajaMpesa) {
                    error_log("Daraja M-Pesa API not initialized");
                } else {
                    $response = $darajaMpesa->b2cPayment(
                        $vendor['mpesa_number'],
                        $vendorPayout,
                        'Payment release for order #' . $orderId,
                        'Order completion payment'
                    );
                    if ($response && isset($response['success']) && $response['success']) {
                        $payoutResponse = $response;
                        // Log payout and commission
                        $stmt = $db->prepare("INSERT INTO commissions (order_id, vendor_id, commission_amount, rate, payout_amount, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("iiddd", $orderId, $vendorId, $commission, $commissionRate, $vendorPayout);
                        $stmt->execute();
                        $orderObj->log_order_action($orderId, 'cod_payout_released', [
                            'amount' => $amount,
                            'commission' => $commission,
                            'commission_rate' => $commissionRate,
                            'vendor_payout' => $vendorPayout,
                            'vendor_id' => $vendorId,
                            'released_by' => 'vendor',
                            'payout_response' => $payoutResponse
                        ]);
                    } else {
                        $orderObj->log_order_action($orderId, 'cod_payout_failed', [
                            'amount' => $amount,
                            'commission' => $commission,
                            'commission_rate' => $commissionRate,
                            'vendor_payout' => $vendorPayout,
                            'vendor_id' => $vendorId,
                            'released_by' => 'vendor',
                            'payout_response' => $response
                        ]);
                    }
                }
            }

            // --- COD AUDIT TRAIL, ADMIN NOTIFICATION, SECURITY LOG ---
            // 1. Log order action (audit trail)
            $orderObj->log_order_action($orderId, 'cod_payment_completed', [
                'vendor_id' => $vendorId,
                'payment_id' => $payment['id'],
                'method' => $payment['method'],
                'amount' => $payment['amount'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            // 2. Notify all admin/super_admin users
            require_once dirname(__DIR__) . '/includes/Notification.php';
            $notification = new Notification($db);
            $adminsStmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'super_admin')");
            $adminsStmt->execute();
            $adminsResult = $adminsStmt->get_result();
            $adminIds = [];
            while ($adminRow = $adminsResult->fetch_assoc()) {
                $adminIds[] = $adminRow['id'];
            }
            foreach ($adminIds as $adminId) {
                $notification->send($adminId, 'cod_payment_completed',
                    'COD payment for Order #' . $orderId . ' has been marked as completed.',
                    [
                        'order_id' => $orderId,
                        'vendor_id' => $vendorId,
                        'payment_id' => $payment['id'],
                        'amount' => $payment['amount']
                    ]
                );
            }

            // 3. System security log
            require_once dirname(__DIR__) . '/includes/Security.php';
            $security = new Security($db);
            $security->logSecurityEvent(
                'COD Payment Completed',
                [
                    'order_id' => $orderId,
                    'vendor_id' => $vendorId,
                    'payment_id' => $payment['id'],
                    'amount' => $payment['amount'],
                    'method' => $payment['method'],
                    'performed_by' => 'vendor',
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            );

            $_SESSION['success'] = 'Order delivered and payment marked as completed (Cash on Delivery). Admin notified.';
        } else {
            // Release escrow funds upon delivery confirmation for M-Pesa orders
            if ($orderObj->release_escrow($orderId, 'vendor_id_' . $vendorId)) {
                $_SESSION['success'] = 'Order delivered and escrow released successfully.';
            } else {
                $_SESSION['error'] = 'Order delivered, but failed to release escrow. Please contact support.';
            }
        }
    } else {
        $_SESSION['error'] = 'Error updating order status to delivered.';
    }
    break;
case 'cancelled':
                        $reason = $_POST['reason'] ?? '';
                        if ($orderObj->update_order_status($orderId, 'cancelled', $reason)) {
                            // Reverse escrow if payment was held
                            if ($orderObj->reverse_escrow($orderId, 'vendor_id_' . $vendorId, $reason)) {
                                $_SESSION['success'] = 'Order cancelled and escrow reversed successfully.';
                            } else {
                                $_SESSION['error'] = 'Order cancelled, but failed to reverse escrow. Please contact support.';
                            }
                        } else {
                            $_SESSION['error'] = 'Error cancelling order.';
                        }
                        break;
                }
            } else {
                $_SESSION['error'] = 'Order not found or you do not have permission to update this order.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error updating order: ' . $e->getMessage();
        }
    }

    header('Location: /seller/orders.php');
    exit;
}

// Get orders with customer details using the Order class
$orders = $orderObj->get_orders_by_vendor($vendorId);

ob_start();
?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($order['customer_email']); ?>
                                </small>
                            </td>
                            <td><?php echo number_format($order['total_items']); ?></td>
                            <td>KSh <?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php
                                // Retrieve payment status from the database for the current order
                                $paymentStatus = 'N/A';
                                $paymentQuery = $db->prepare("SELECT status FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
                                $paymentQuery->bind_param("i", $order['id']);
                                $paymentQuery->execute();
                                $paymentResult = $paymentQuery->get_result();
                                if ($paymentRow = $paymentResult->fetch_assoc()) {
                                    $paymentStatus = $paymentRow['status'];
                                }

                                echo match ($paymentStatus) {
                                    'Paid' => 'success',
                                    'Pending' => 'warning',
                                    'Failed' => 'danger',
                                    'Reversed' => 'danger',
                                    'Completed' => 'success',
                                    default => 'secondary'
                                };
                                ?>">
                                    <?php echo ucfirst($paymentStatus); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php
                                echo match ($order['status']) {
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'shipped' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal"
                                        data-target="#viewOrderModal<?php echo $order['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
                                            data-target="#processOrderModal<?php echo $order['id']; ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                            data-target="#cancelOrderModal<?php echo $order['id']; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php elseif ($order['status'] === 'processing'): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                            data-target="#shipOrderModal<?php echo $order['id']; ?>">
                                            <i class="fas fa-truck"></i>
                                        </button>
                                    <?php elseif ($order['status'] === 'shipped'): ?>
                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
                                            data-target="#deliverOrderModal<?php echo $order['id']; ?>">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- View Order Modal -->
                                <div class="modal fade" id="viewOrderModal<?php echo $order['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    Order Details
                                                    #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?>
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6>Customer Information</h6>
                                                        <p>
                                                            Name:
                                                            <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                                            Email:
                                                            <?php echo htmlspecialchars($order['customer_email']); ?><br>
                                                            Phone: <?php echo isset($order['customer_phone']) ? htmlspecialchars($order['customer_phone']) : ''; ?>
                                                        </p>
                                                        <h6>Delivery Address</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Order Information</h6>
                                                        <p>
                                                            Date:
                                                            <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?><br>
                                                            Status: <?php echo ucfirst($order['status']); ?><br>
                                                            Payment: <?php echo ucfirst($paymentStatus); ?>
                                                        </p>
                                                        <?php if ($order['status'] === 'cancelled' && isset($order['cancellation_reason']) && $order['cancellation_reason']): ?>
                                                            <h6>Cancellation Reason</h6>
                                                            <p><?php echo htmlspecialchars($order['cancellation_reason']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <hr>
                                                <h6>Order Items</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Product</th>
                                                                <th>Price</th>
                                                                <th>Quantity</th>
                                                                <th>Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $stmt = $db->prepare("
                                                                SELECT oi.*, p.name as product_name
                                                                FROM order_items oi
                                                                JOIN products p ON oi.product_id = p.id
                                                                WHERE oi.order_id = ?
                                                            ");
                                                            $stmt->bind_param("i", $order['id']);
                                                            $stmt->execute();
                                                            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                                            foreach ($items as $item):
                                                                ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($item['product_name']); ?>
                                                                    </td>
                                                                    <td>KSh <?php echo number_format($item['price'], 2); ?></td>
                                                                    <td><?php echo number_format($item['quantity']); ?></td>
                                                                    <td>KSh
                                                                        <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <th colspan="3" class="text-right">Total:</th>
                                                                <th>KSh
                                                                    <?php echo number_format($order['total_amount'], 2); ?>
                                                                </th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Process Order Modal -->
                                <div class="modal fade" id="processOrderModal<?php echo $order['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="action" value="processing">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Process Order</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to process this order? This will notify the
                                                        customer that you are preparing their items.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Process Order</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ship Order Modal -->
                                <div class="modal fade" id="shipOrderModal<?php echo $order['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="action" value="shipped">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Ship Order</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to mark this order as shipped? This will notify
                                                        the customer.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Ship Order</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Deliver Order Modal -->
                                <div class="modal fade" id="deliverOrderModal<?php echo $order['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="action" value="delivered">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Mark as Delivered</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to mark this order as delivered? This will
                                                        complete the order process.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Mark as Delivered</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cancel Order Modal -->
                                <div class="modal fade" id="cancelOrderModal<?php echo $order['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="action" value="cancelled">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cancel Order</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to cancel this order?</p>
                                                    <div class="form-group">
                                                        <label for="reason">Reason for Cancellation</label>
                                                        <textarea class="form-control" name="reason" rows="3"
                                                            required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-danger">Cancel Order</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>