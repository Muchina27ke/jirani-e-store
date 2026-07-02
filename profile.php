<?php
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'login.php?redirect=profile.php');
    exit();
}

$successMsg = '';
$errorMsg = '';

$userId = $_SESSION['user_id'];
$userRow = $conn->prepare('SELECT * FROM users WHERE id = ?');
$userRow->bind_param('i', $userId);
$userRow->execute();
$user = $userRow->get_result()->fetch_assoc();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $errorMsg = 'All password fields are required.';
    } elseif (!password_verify($current_password, $user['password'])) {
        $errorMsg = 'Current password is incorrect.';
    } elseif ($new_password !== $confirm_password) {
        $errorMsg = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $errorMsg = 'Password must be at least 6 characters long.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $updateStmt->bind_param('si', $hashed_password, $userId);
        if ($updateStmt->execute()) {
            $successMsg = 'Password changed successfully!';
        } else {
            $errorMsg = 'Failed to change password.';
        }
    }
}

// $user is already fetched as an array from the database query above
$isVendor = ($user['role'] === 'vendor');

// Fetch vendor info if applicable
$vendorInfo = null;
if ($isVendor) {
    $stmt = $conn->prepare('SELECT * FROM vendors WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $vendorInfo = $stmt->get_result()->fetch_assoc();
}

// Fetch recent orders
$stmt = $conn->prepare('SELECT o.*, v.business_name FROM orders o JOIN vendors v ON o.vendor_id = v.user_id WHERE o.customer_id = ? ORDER BY o.created_at DESC LIMIT 5');
$stmt->bind_param('i', $userId);
$stmt->execute();
$recentOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch addresses (optional, if you have an address table)
$addresses = [];
if ($conn->query("SHOW TABLES LIKE 'user_addresses'")->num_rows) {
    $stmt = $conn->prepare('SELECT * FROM user_addresses WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$pageTitle = 'My Profile - Jirani';
$currentPage = 'profile';
$additionalCSS = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'];
require_once __DIR__ . '/navbar.php';
?>

<style>
    .profile-container {
        background: linear-gradient(135deg, rgba(42, 92, 61, 0.02), rgba(42, 92, 61, 0.05));
        min-height: 100vh;
        padding-bottom: 120px;
        box-sizing: border-box;
        overflow-x: hidden;
    }

    .profile-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(42, 92, 61, 0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--j-primary);
        background: #eee;
    }

    .profile-section-title {
        color: var(--j-primary);
        font-weight: 700;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }

    .profile-section-title i {
        margin-right: 0.75rem;
        font-size: 1.2rem;
    }

    .address-card {
        background: rgba(42, 92, 61, 0.05);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(42, 92, 61, 0.1);
    }

    .order-summary-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(42, 92, 61, 0.1);
        margin-bottom: 1.5rem;
        padding: 1.5rem;
    }

    @media (max-width: 768px) {
        .profile-container {
            padding: 1rem 0;
        }

        .profile-card,
        .order-summary-card {
            padding: 1rem;
        }
    }
</style>

<div class="profile-container py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="profile-card text-center p-4">
                    <form id="avatar-form" enctype="multipart/form-data" method="post" action="profile.php">
                        <img src="<?php echo isset($user['avatar']) && $user['avatar'] ? htmlspecialchars($user['avatar']) : 'Images/Admin/profile.jpg'; ?>"
                            class="profile-avatar mb-3" id="profileAvatar" alt="Profile Picture">
                        <input type="file" name="avatar" id="avatarInput" class="form-control mb-2" accept="image/*"
                            style="display:none;" onchange="document.getElementById('avatar-form').submit();">
                        <button type="button" class="j-btn j-btn-outline j-btn-sm w-100"
                            onclick="document.getElementById('avatarInput').click();"><i class="fas fa-camera"></i>
                            Change Photo</button>
                    </form>
                    <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($user['phone']); ?></p>
                    <span class="badge" style="background-color: var(--j-primary);"><?php echo ucfirst($user['role']); ?></span>
                    <div class="mt-3">
                        <a href="cart.php" class="j-btn j-btn-outline w-100 mb-2"><i
                                class="fas fa-shopping-cart me-1"></i> View Cart</a>
                        <a href="wishlist.php" class="j-btn j-btn-outline w-100"><i
                                class="fas fa-heart me-1"></i> View Wishlist</a>
                    </div>
                </div>
                <?php if ($isVendor && $vendorInfo): ?>
                    <div class="profile-card p-3 mt-3">
                        <h6 class="profile-section-title"><i class="fas fa-store"></i>Vendor Info</h6>
                        <p class="mb-1"><strong>Business:</strong>
                            <?php echo htmlspecialchars($vendorInfo['business_name']); ?></p>
                        <p class="mb-1"><strong>Status:</strong> <span
                                class="badge" style="background-color: var(--j-primary);"><?php echo ucfirst($vendorInfo['status']); ?></span></p>
                        <?php if (!empty($vendorInfo['mpesa_number'])): ?>
                            <p class="mb-1"><strong>M-Pesa:</strong>
                                <?php echo htmlspecialchars($vendorInfo['mpesa_number']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-8">
    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"> <?php echo htmlspecialchars($successMsg); ?> </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"> <?php echo htmlspecialchars($errorMsg); ?> </div>
    <?php endif; ?>
                <div class="profile-card p-4 mb-4">
                    <h5 class="profile-section-title"><i class="fas fa-user-edit"></i>Edit Profile</h5>
                    <form method="post" action="profile.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="j-btn j-btn-primary w-100">Save Changes</button>
                    </form>
                </div>
                <div class="profile-card p-4 mb-4">
                    <h5 class="profile-section-title"><i class="fas fa-key"></i>Change Password</h5>
                    <form method="post" action="profile.php">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                        </div>
                        <button type="submit" name="change_password" class="j-btn j-btn-primary w-100">Change
                            Password</button>
                    </form>
                </div>
                <div class="profile-card p-4 mb-4">
                    <h5 class="profile-section-title"><i class="fas fa-map-marker-alt"></i>Address Book</h5>
                    <?php if (!empty($addresses)): ?>
                        <?php foreach ($addresses as $address): ?>
                            <div class="address-card">
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars($address['label'] ?? 'Address'); ?>:</strong>
                                    <?php echo nl2br(htmlspecialchars($address['address'])); ?></p>
                                <p class="mb-1"><i class="fas fa-phone me-1"></i>
                                    <?php echo htmlspecialchars($address['phone']); ?></p>
                                <p class="mb-1"><i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($address['recipient']); ?></p>
                                <!-- Add edit/delete buttons as needed -->
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No addresses saved yet.</p>
                    <?php endif; ?>
                </div>
                <div class="order-summary-card">
                    <h5 class="profile-section-title"><i class="fas fa-box"></i>Recent Orders</h5>
                    <?php if (!empty($recentOrders)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recentOrders as $order): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <a href="order_details.php?order_id=<?php echo $order['id']; ?>"
                                            class="fw-bold" style="color: var(--j-primary); text-decoration: none;">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></a>
                                        <span
                                            class="text-muted ms-2">(<?php echo htmlspecialchars($order['business_name']); ?>)</span>
                                    </span>
                                    <span class="badge" style="background-color: var(--j-primary);"><?php echo ucfirst($order['status']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="text-end mt-2">
                            <a href="orders.php" class="j-btn j-btn-outline j-btn-sm">View All Orders</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No recent orders found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Optionally preview avatar before upload
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (evt) {
                    document.getElementById('profileAvatar').src = evt.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once __DIR__ . '/footer.php'; ?>