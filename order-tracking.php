<?php
require_once 'config/config.php';
$pageTitle = 'Order Tracking';

$order = null;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = trim($_POST['order_id'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($order_id) && empty($phone)) {
        $error = 'Please enter either Order ID or Phone Number';
    } else {
        $query = "SELECT o.*, u.name as customer_name, u.phone, v.business_name as vendor_name,
                         (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as total_amount
                  FROM orders o 
                  JOIN users u ON o.customer_id = u.id 
                  JOIN vendors v ON o.vendor_id = v.user_id 
                  WHERE ";

        if (!empty($order_id)) {
            $query .= "o.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $order_id);
        } else {
            $query .= "u.phone = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $phone);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            $success = 'Order found successfully!';
        } else {
            $error = 'No order found with the provided information';
        }
    }
}

// Get order items if order exists
$order_items = [];
if ($order) {
    $stmt = $conn->prepare("
        SELECT oi.*, p.name as product_name, p.price as product_price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Jirani</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            /* Primary Color Palette */
            --jirani-primary: #2A5C3D;
            /* Forest Green */
            --jirani-secondary: #FFA726;
            /* Warm Orange */
            --jirani-white: #FFFFFF;
            /* White */
            --jirani-gray: #333333;
            /* Dark Gray */
            --jirani-dark: #06402B;
            --jirani-light: #f8f9fa;
            --jirani-text: #444;
        }

        /* Base Elements */
        body {
            background-color: var(--jirani-white);
            color: var(--jirani-gray);
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: var(--jirani-white) !important;
        }

        /* Text Elements */
        .navbar-brand,
        .nav-link {
            color: var(--jirani-primary) !important;
        }

        h1,
        h2,
        h3 {
            color: var(--jirani-primary);
        }

        /* Buttons */
        .btn-jirani {
            background-color: var(--jirani-primary);
            border: none;
            color: var(--jirani-white);
        }

        .btn-jirani:hover {
            background-color: var(--jirani-secondary);
            color: var(--jirani-gray);
        }

        /* Dropdowns */
        .dropdown-menu {
            background-color: var(--jirani-primary);
            border: 1px solid var(--jirani-secondary);
        }

        .dropdown-item {
            color: var(--jirani-white) !important;
        }

        .dropdown-item:hover {
            background-color: var(--jirani-secondary) !important;
        }

        .tracking-timeline {
            position: relative;
            padding: 20px 0;
        }

        .tracking-timeline::before {
            content: '';
            position: absolute;
            left: 50px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding-left: 80px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 41px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #007bff;
        }

        .timeline-item.completed::before {
            background: #28a745;
            border-color: #28a745;
        }

        .timeline-item.current::before {
            background: #ffc107;
            border-color: #ffc107;
        }

        .order-status-badge {
            font-size: 0.9rem;
            padding: 8px 16px;
        }
    </style>
</head>

<body>
    <?php echo $navigation->renderCustomerNav(); ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-truck"></i> Order Tracking
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!$order): ?>
                            <!-- Search Form -->
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="order_id">Order ID</label>
                                            <input type="text" class="form-control" id="order_id" name="order_id"
                                                placeholder="Enter Order ID"
                                                value="<?php echo htmlspecialchars($_POST['order_id'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone">Phone Number</label>
                                            <input type="text" class="form-control" id="phone" name="phone"
                                                placeholder="Enter Phone Number"
                                                value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Track Order
                                </button>
                            </form>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <!-- Order Details -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>Order Information</h5>
                                    <p><strong>Order ID:</strong>
                                        #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></p>
                                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                                    </p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                    <p><strong>Vendor:</strong> <?php echo htmlspecialchars($order['vendor_name']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Order Summary</h5>
                                    <p><strong>Total Amount:</strong> KSh
                                        <?php echo number_format($order['total_amount'], 2); ?></p>
                                    <p><strong>Order Date:</strong>
                                        <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                                    <p><strong>Delivery Address:</strong>
                                        <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                    <p>
                                        <strong>Status:</strong>
                                        <span class="badge badge-<?php
                                        echo match ($order['status']) {
                                            'pending' => 'warning',
                                            'accepted' => 'info',
                                            'shipped' => 'primary',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger',
                                            'disputed' => 'secondary',
                                            default => 'secondary'
                                        };
                                        ?> order-status-badge">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Order Items</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Quantity</th>
                                                    <th>Price</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($order_items as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                        <td><?php echo $item['quantity']; ?></td>
                                                        <td>KSh <?php echo number_format($item['product_price'], 2); ?></td>
                                                        <td>KSh
                                                            <?php echo number_format($item['quantity'] * $item['product_price'], 2); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Tracking Timeline -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Order Progress</h6>
                                </div>
                                <div class="card-body">
                                    <div class="tracking-timeline">
                                        <?php
                                        $statuses = [
                                            'pending' => ['Order Placed', 'Your order has been placed and is awaiting confirmation'],
                                            'accepted' => ['Order Confirmed', 'Your order has been confirmed by the vendor'],
                                            'shipped' => ['Out for Delivery', 'Your order is on its way to you'],
                                            'delivered' => ['Delivered', 'Your order has been successfully delivered']
                                        ];

                                        $current_status = $order['status'];
                                        $status_keys = array_keys($statuses);
                                        $current_index = array_search($current_status, $status_keys);

                                        foreach ($statuses as $status => $info):
                                            $status_index = array_search($status, $status_keys);
                                            $is_completed = $status_index < $current_index;
                                            $is_current = $status_index == $current_index;
                                            $is_future = $status_index > $current_index;

                                            $class = 'timeline-item';
                                            if ($is_completed)
                                                $class .= ' completed';
                                            elseif ($is_current)
                                                $class .= ' current';
                                            ?>
                                            <div class="<?php echo $class; ?>">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6 class="card-title">
                                                            <?php if ($is_completed): ?>
                                                                <i class="fas fa-check-circle text-success"></i>
                                                            <?php elseif ($is_current): ?>
                                                                <i class="fas fa-clock text-warning"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-circle text-muted"></i>
                                                            <?php endif; ?>
                                                            <?php echo $info[0]; ?>
                                                        </h6>
                                                        <p class="card-text text-muted"><?php echo $info[1]; ?></p>
                                                        <?php if ($is_current): ?>
                                                            <small class="text-info">
                                                                <i class="fas fa-info-circle"></i>
                                                                Current status - <?php echo ucfirst($current_status); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="<?php echo SITE_URL; ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Home
                                </a>
                                <a href="<?php echo SITE_URL; ?>order-tracking.php" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Track Another Order
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>