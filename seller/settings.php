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

$pageTitle = 'Settings';
$currentPage = 'settings';

// Handle form submissions (UI only, no DB update logic for now)
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = 'Settings saved (demo only, not persisted).';
}

ob_start();
?>
<div class="container-fluid mt-4">
    <h2 class="mb-4">Account & Business Settings</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"> <?php echo $success; ?> </div>
    <?php endif; ?>
    <form method="POST" class="mb-5">
        <div class="card mb-4">
            <div class="card-header"><strong>Profile Info</strong></div>
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
            <div class="card-header"><strong>Settlement Details</strong></div>
            <div class="card-body row">
                <div class="form-group col-md-6">
                    <label for="settlement_type">Settlement Method</label>
                    <select class="form-control" id="settlement_type" name="settlement_type"
                        onchange="toggleSettlementFields()">
                        <option value="mpesa">M-Pesa Number</option>
                        <option value="till">Till Number</option>
                        <option value="paybill">Paybill</option>
                        <option value="bank">Bank Account</option>
                    </select>
                </div>
                <div class="form-group col-md-6" id="mpesa_field">
                    <label for="mpesa_number">M-Pesa Number</label>
                    <input type="text" class="form-control" id="mpesa_number" name="mpesa_number"
                        value="<?php echo htmlspecialchars($vendorDetails['mpesa_number'] ?? ''); ?>">
                </div>
                <div class="form-group col-md-6 d-none" id="till_field">
                    <label for="till_number">Till Number</label>
                    <input type="text" class="form-control" id="till_number" name="till_number">
                </div>
                <div class="form-group col-md-6 d-none" id="paybill_field">
                    <label for="paybill_number">Paybill Number</label>
                    <input type="text" class="form-control" id="paybill_number" name="paybill_number">
                    <label for="paybill_account" class="mt-2">Account Number</label>
                    <input type="text" class="form-control" id="paybill_account" name="paybill_account">
                </div>
                <div class="form-group col-md-6 d-none" id="bank_field">
                    <label for="bank_name">Bank Name</label>
                    <input type="text" class="form-control" id="bank_name" name="bank_name">
                    <label for="bank_account" class="mt-2">Account Number</label>
                    <input type="text" class="form-control" id="bank_account" name="bank_account">
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
<script>
    function toggleSettlementFields() {
        const type = document.getElementById('settlement_type').value;
        document.getElementById('mpesa_field').classList.toggle('d-none', type !== 'mpesa');
        document.getElementById('till_field').classList.toggle('d-none', type !== 'till');
        document.getElementById('paybill_field').classList.toggle('d-none', type !== 'paybill');
        document.getElementById('bank_field').classList.toggle('d-none', type !== 'bank');
    }
    document.addEventListener('DOMContentLoaded', toggleSettlementFields);
</script>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>