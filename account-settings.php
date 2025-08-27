<?php
require_once 'config/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=account-settings.php');
    exit;
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        if (empty($name) || empty($email) || empty($phone)) {
            $error = 'All fields are required';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $phone, $userId);

            if ($stmt->execute()) {
                $success = 'Profile updated successfully';
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Failed to update profile';
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $userId);

            if ($stmt->execute()) {
                $success = 'Password changed successfully';
            } else {
                $error = 'Failed to change password';
            }
        }
    } elseif ($action === 'contact_support') {
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);

        if (empty($subject) || empty($message)) {
            $error = 'Subject and message are required';
        } else {
            // Here you would typically send an email or save to database
            $success = 'Support message sent successfully. We will get back to you soon.';
        }
    }
}

$pageTitle = "Account Settings";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <!-- Account Navigation -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Menu</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#profile" class="list-group-item list-group-item-action active" data-toggle="tab">
                        <i class="fas fa-user"></i> Profile Settings
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action" data-toggle="tab">
                        <i class="fas fa-lock"></i> Security
                    </a>
                    <a href="#orders" class="list-group-item list-group-item-action" data-toggle="tab">
                        <i class="fas fa-shopping-bag"></i> My Orders
                    </a>
                    <a href="#support" class="list-group-item list-group-item-action" data-toggle="tab">
                        <i class="fas fa-headset"></i> Support
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- Alert Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Profile Settings Tab -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user"></i> Profile Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Account Type</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo ucfirst($user['role']); ?>" readonly>
                                    <small class="form-text text-muted">Account type cannot be changed</small>
                                </div>

                                <div class="form-group">
                                    <label>Member Since</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo date('M d, Y', strtotime($user['created_at'])); ?>" readonly>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Security Tab -->
                <div class="tab-pane fade" id="security">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-lock"></i> Security Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">

                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" class="form-control" id="current_password"
                                        name="current_password" required>
                                </div>

                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password"
                                        required>
                                    <small class="form-text text-muted">Password must be at least 6 characters
                                        long</small>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" required>
                                </div>

                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </form>

                            <hr>

                            <div class="alert alert-info">
                                <h6><i class="fas fa-shield-alt"></i> Security Tips</h6>
                                <ul class="mb-0">
                                    <li>Use a strong password with letters, numbers, and symbols</li>
                                    <li>Never share your password with anyone</li>
                                    <li>Log out when using shared computers</li>
                                    <li>Keep your contact information up to date</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Tab -->
                <div class="tab-pane fade" id="orders">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-shopping-bag"></i> My Orders</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get user's orders
                            $stmt = $conn->prepare("
                                SELECT o.*, v.business_name as vendor_name,
                                       (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as total_amount
                                FROM orders o
                                JOIN vendors v ON o.vendor_id = v.user_id
                                WHERE o.customer_id = ?
                                ORDER BY o.created_at DESC
                                LIMIT 10
                            ");
                            $stmt->bind_param("i", $userId);
                            $stmt->execute();
                            $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>

                            <?php if (empty($orders)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                    <h5>No Orders Yet</h5>
                                    <p class="text-muted">You haven't placed any orders yet.</p>
                                    <a href="index.php" class="btn btn-primary">Start Shopping</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Vendor</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></td>
                                                    <td><?php echo htmlspecialchars($order['vendor_name']); ?></td>
                                                    <td>KSh <?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                                    <td>
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
                                                        ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                    <td>
                                                        <a href="order-tracking.php?order_id=<?php echo $order['id']; ?>"
                                                            class="btn btn-sm btn-info">
                                                            <i class="fas fa-truck"></i> Track
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center">
                                    <a href="orders.php" class="btn btn-primary">View All Orders</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Support Tab -->
                <div class="tab-pane fade" id="support">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-headset"></i> Contact Support</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="contact_support">

                                        <div class="form-group">
                                            <label for="subject">Subject</label>
                                            <input type="text" class="form-control" id="subject" name="subject"
                                                required>
                                        </div>

                                        <div class="form-group">
                                            <label for="message">Message</label>
                                            <textarea class="form-control" id="message" name="message" rows="5"
                                                placeholder="Describe your issue or question..." required></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Send Message
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Quick Support</h6>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-question-circle text-info"></i>
                                            How to place an order?
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-truck text-primary"></i>
                                            Delivery information
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-credit-card text-success"></i>
                                            Payment methods
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-undo text-warning"></i>
                                            Return policy
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Contact Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><i class="fas fa-phone text-primary"></i> +254 700 000 000</p>
                                    <p><i class="fas fa-envelope text-primary"></i> support@jirani.com</p>
                                    <p><i class="fas fa-clock text-primary"></i> Mon-Fri: 8AM-6PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab navigation
    $(document).ready(function () {
        // Handle tab clicks
        $('.list-group-item').on('click', function (e) {
            e.preventDefault();

            // Remove active class from all items
            $('.list-group-item').removeClass('active');

            // Add active class to clicked item
            $(this).addClass('active');

            // Show corresponding tab
            const target = $(this).attr('href');
            $('.tab-pane').removeClass('show active');
            $(target).addClass('show active');
        });

        // Handle URL hash for direct navigation
        if (window.location.hash) {
            const hash = window.location.hash;
            $('.list-group-item[href="' + hash + '"]').click();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>