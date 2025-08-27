<?php
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header('Location: login.php');
    exit();
}

// Include header (navbar with cart count)
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Navigation.php';
$auth = new Auth($conn);
$currentUser = null;
if ($auth->isLoggedIn()) {
    $currentUser = $auth->getUser();
}
$navigation = new Navigation($conn, $currentUser, 'orders');
echo $navigation->renderCustomerNav();

$userId = $_SESSION['user_id'];

// Fetch orders for this customer
$stmt = $conn->prepare('SELECT o.id, o.status, o.created_at, v.business_name FROM orders o JOIN vendors v ON o.vendor_id = v.user_id WHERE o.customer_id = ? ORDER BY o.created_at DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Include footer at the end
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Jirani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/About.css">
    <link rel="stylesheet" href="css/footer.css">
    <style>
        :root {
            --primary-color: #2A5C3D;
            --secondary-color: #f8f9fa;
        }

        .orders-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .orders-container h2 {
            color: #2A5C3D;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #f8f9fa;
            color: #2A5C3D;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status {
            font-weight: bold;
            text-transform: capitalize;
        }

        .status.pending {
            color: #FFA726;
        }

        .status.accepted {
            color: #007bff;
        }

        .status.shipped {
            color: #17a2b8;
        }

        .status.delivered {
            color: #28a745;
        }

        .status.cancelled,
        .status.disputed {
            color: #dc3545;
        }

        .view-link {
            color: #2A5C3D;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="orders-container">
        <h2>My Orders</h2>
        <?php if (empty($orders)): ?>
            <p>You have not placed any orders yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Vendor</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($order['business_name']); ?></td>
                            <td class="status <?php echo htmlspecialchars($order['status']); ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                            <td><a class="view-link" href="order_details.php?order_id=<?php echo $order['id']; ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>