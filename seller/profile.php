<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/Vendor.php';
require_once dirname(__DIR__) . '/includes/Auth.php';

// Vendor session/role check
if (!isset($_SESSION['user_id']) || !$auth->hasRole('vendor')) {
    header('Location: ../auth.php?action=login');
    exit();
}

$db = getDbConnection();
$vendorId = $_SESSION['user_id'];
$vendorObj = new Vendor($db);
$vendorDetails = $vendorObj->get_vendor_by_user_id($vendorId);
$user = $auth->getUser();

$pageTitle = 'Profile';
$currentPage = 'profile';

// Handle form submissions for profile and password update
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Password change logic
    if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Fetch current password hash
        $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $userRow = $result->fetch_assoc();
        $current_hash = $userRow['password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required.';
        } elseif (!password_verify($current_password, $current_hash)) {
            $error = 'Current password is incorrect.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $updateStmt->bind_param('si', $hashed_password, $vendorId);
            if ($updateStmt->execute()) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password.';
            }
        }
    } else {
        $success = 'Profile updated (demo only, not persisted).';
    }
}

ob_start();
?>
<div class="container-fluid mt-4">
    <h2 class="mb-4">My Profile</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"> <?php echo $success; ?> </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"> <?php echo htmlspecialchars($error); ?> </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="mb-5">
        <div class="card mb-4">
            <div class="card-header"><strong>Profile Picture</strong></div>
            <div class="card-body d-flex align-items-center">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name'] ?? 'Vendor'); ?>&background=28a745&color=fff"
                    class="rounded-circle mr-3" style="width: 80px; height: 80px; object-fit: cover;"
                    alt="Profile Picture">
                <div>
                    <input type="file" class="form-control-file" name="profile_picture" accept="image/*" disabled>
                    <small class="form-text text-muted">Profile picture upload is demo only.</small>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header"><strong>Personal Info</strong></div>
            <div class="card-body row">
                <div class="form-group col-md-4">
                    <label for="name">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name"
                        value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone"
                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header"><strong>Business Info</strong></div>
            <div class="card-body row">
                <div class="form-group col-md-6">
                    <label for="business_name">Business Name</label>
                    <input type="text" class="form-control" id="business_name" name="business_name"
                        value="<?php echo htmlspecialchars($vendorDetails['business_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Status</label>
                    <input type="text" class="form-control"
                        value="<?php echo ucfirst($vendorDetails['status'] ?? 'pending'); ?>" readonly>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header"><strong>Change Password</strong></div>
            <div class="card-body row">
                <div class="form-group col-md-4">
                    <label for="current_password">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password">
                </div>
                <div class="form-group col-md-4">
                    <label for="new_password">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password">
                </div>
                <div class="form-group col-md-4">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-success btn-lg">Save Changes</button>
    </form>
</div>
<?php
$content = ob_get_clean();
require_once 'layout.php';