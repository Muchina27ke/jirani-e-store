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
<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0" style="font-family: var(--j-font-heading); font-weight: 700; color: var(--j-primary);">My Profile</h1>
    </div>
    <?php if ($success): ?>
        <div class="alert alert-success"> <?php echo $success; ?> </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"> <?php echo htmlspecialchars($error); ?> </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="mb-5">
        <div class="card shadow-sm border-0 mb-4" style="border-radius: var(--j-radius-lg); overflow: hidden;">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                <strong style="font-family: var(--j-font-heading); font-size: 1.1rem; color: #4b5563;">Profile Picture</strong>
            </div>
            <div class="card-body d-flex align-items-center px-4 pb-4 pt-2">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name'] ?? 'Vendor'); ?>&background=6366f1&color=fff"
                    class="rounded-circle mr-3" style="width: 80px; height: 80px; object-fit: cover; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"
                    alt="Profile Picture">
                <div>
                    <input type="file" class="form-control-file" name="profile_picture" accept="image/*" disabled>
                    <small class="form-text text-muted mt-2">Profile picture upload is demo only.</small>
                </div>
            </div>
        </div>
        <div class="card shadow-sm border-0 mb-4" style="border-radius: var(--j-radius-lg); overflow: hidden;">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                <strong style="font-family: var(--j-font-heading); font-size: 1.1rem; color: #4b5563;">Personal Info</strong>
            </div>
            <div class="card-body row px-4 pb-4 pt-2">
                <div class="form-group col-md-4">
                    <label for="name" style="font-weight: 500; color: #374151;">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name"
                        value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" style="border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 15px;" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="email" style="font-weight: 500; color: #374151;">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" style="border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 15px;">
                </div>
                <div class="form-group col-md-4">
                    <label for="phone" style="font-weight: 500; color: #374151;">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone"
                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" style="border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 15px;" required>
                </div>
            </div>
        </div>
        <div class="card shadow-sm border-0 mb-4" style="border-radius: var(--j-radius-lg); overflow: hidden;">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                <strong style="font-family: var(--j-font-heading); font-size: 1.1rem; color: #4b5563;">Business Info</strong>
            </div>
            <div class="card-body row px-4 pb-4 pt-2">
                <div class="form-group col-md-6">
                    <label for="business_name" style="font-weight: 500; color: #374151;">Business Name</label>
                    <input type="text" class="form-control" id="business_name" name="business_name"
                        value="<?php echo htmlspecialchars($vendorDetails['business_name'] ?? ''); ?>" style="border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 15px;" required>
                </div>
                <div class="form-group col-md-6">
                    <label style="font-weight: 500; color: #374151;">Status</label>
                    <input type="text" class="form-control"
                        value="<?php echo ucfirst($vendorDetails['status'] ?? 'pending'); ?>" style="border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 15px; background-color: #f9fafb;" readonly>
                </div>
            </div>
        </div>
        <div class="card shadow-sm border-0 mb-4" style="border-radius: var(--j-radius-lg); overflow: hidden;">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                <strong style="font-family: var(--j-font-heading); font-size: 1.1rem; color: #4b5563;">Change Password</strong>
            </div>
            <div class="card-body row px-4 pb-4 pt-2">
                <div class="form-group col-md-4">
                    <label for="current_password" style="font-weight: 500; color: #374151;">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" style="border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 15px;">
                </div>
                <div class="form-group col-md-4">
                    <label for="new_password" style="font-weight: 500; color: #374151;">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" style="border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 15px;">
                </div>
                <div class="form-group col-md-4">
                    <label for="confirm_password" style="font-weight: 500; color: #374151;">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" style="border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 15px;">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="background: var(--j-primary); border-radius: 50px; font-weight: 600; padding: 10px 30px; border: none; box-shadow: 0 4px 6px rgba(99,102,241,0.2);">Save Changes</button>
    </form>
</div>
<?php
$content = ob_get_clean();
require_once 'layout.php';