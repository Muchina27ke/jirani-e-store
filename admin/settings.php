<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}

$pageTitle = 'Settings';
$currentPage = 'settings';
ob_start();

// Load settings from DB
$current_settings = [];
$result = $conn->query("SELECT name, value FROM settings");
while ($row = $result->fetch_assoc()) {
    $current_settings[$row['name']] = $row['value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'site_name',
        'site_description',
        'contact_email',
        'contact_phone',
        'address',
        // Email Configuration
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'mail_from_address',
        'mail_from_name',
        // Daraja M-Pesa Configuration
        'daraja_consumer_key',
        'daraja_consumer_secret',
        'daraja_environment',
        'daraja_shortcode',
        'daraja_passkey',
        'daraja_initiator_name',
        'daraja_security_credential',
        'daraja_stk_callback_url',
        'daraja_b2c_callback_url',
        'daraja_c2b_callback_url',
        'daraja_validation_url',
        'daraja_confirmation_url',
        // System Settings
        'commission_rate',
        'escrow_duration',
        'email_notifications',
        'sms_notifications'
    ];
    foreach ($fields as $field) {
        $value = $_POST[$field] ?? '';
        if (in_array($field, ['email_notifications', 'sms_notifications'])) {
            $value = isset($_POST[$field]) ? '1' : '0';
        }
        // Encrypt sensitive fields
        if (in_array($field, ['smtp_password', 'daraja_consumer_secret', 'daraja_security_credential'])) {
            $value = base64_encode($value); // Simple encoding for sensitive data
        }
        $stmt = $conn->prepare("REPLACE INTO settings (name, value) VALUES (?, ?)");
        $stmt->bind_param('ss', $field, $value);
        $stmt->execute();
    }
    $_SESSION['success'] = 'Settings updated successfully!';
    header('Location: settings.php');
    exit;
}
?>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <?php echo $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <!-- Site Settings -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cog mr-2"></i>
                                Site Settings
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="site_name">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name"
                                    value="<?php echo htmlspecialchars($current_settings['site_name'] ?? 'Jirani'); ?>"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="site_description">Site Description</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="3"
                                    placeholder="Enter site description..."><?php echo htmlspecialchars($current_settings['site_description'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="contact_email">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email"
                                    value="<?php echo htmlspecialchars($current_settings['contact_email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="contact_phone">Contact Phone</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone"
                                    value="<?php echo htmlspecialchars($current_settings['contact_phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"
                                    placeholder="Enter business address..."><?php echo htmlspecialchars($current_settings['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Configuration -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-envelope mr-2"></i>
                                Email Configuration
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="smtp_host">SMTP Host</label>
                                <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                    value="<?php echo htmlspecialchars($current_settings['smtp_host'] ?? 'smtp.gmail.com'); ?>"
                                    placeholder="e.g., smtp.gmail.com">
                            </div>
                            <div class="form-group">
                                <label for="smtp_port">SMTP Port</label>
                                <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                    value="<?php echo htmlspecialchars($current_settings['smtp_port'] ?? '587'); ?>"
                                    placeholder="587">
                            </div>
                            <div class="form-group">
                                <label for="smtp_username">SMTP Username</label>
                                <input type="email" class="form-control" id="smtp_username" name="smtp_username"
                                    value="<?php echo htmlspecialchars($current_settings['smtp_username'] ?? ''); ?>"
                                    placeholder="your-email@gmail.com">
                            </div>
                            <div class="form-group">
                                <label for="smtp_password">SMTP Password</label>
                                <input type="password" class="form-control" id="smtp_password" name="smtp_password"
                                    value="<?php echo htmlspecialchars(base64_decode($current_settings['smtp_password'] ?? '')); ?>"
                                    placeholder="App password or email password">
                                <small class="form-text text-muted">Use app password for Gmail</small>
                            </div>
                            <div class="form-group">
                                <label for="smtp_encryption">SMTP Encryption</label>
                                <select class="form-control" id="smtp_encryption" name="smtp_encryption">
                                    <option value="tls" <?php echo ($current_settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($current_settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="mail_from_address">From Email Address</label>
                                <input type="email" class="form-control" id="mail_from_address" name="mail_from_address"
                                    value="<?php echo htmlspecialchars($current_settings['mail_from_address'] ?? ''); ?>"
                                    placeholder="noreply@jirani.com">
                            </div>
                            <div class="form-group">
                                <label for="mail_from_name">From Name</label>
                                <input type="text" class="form-control" id="mail_from_name" name="mail_from_name"
                                    value="<?php echo htmlspecialchars($current_settings['mail_from_name'] ?? 'Jirani'); ?>"
                                    placeholder="Jirani">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Daraja M-Pesa Settings -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-credit-card mr-2"></i>
                                Daraja M-Pesa API Configuration
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="daraja_consumer_key">Consumer Key</label>
                                        <input type="text" class="form-control" id="daraja_consumer_key" name="daraja_consumer_key"
                                            value="<?php echo htmlspecialchars($current_settings['daraja_consumer_key'] ?? ''); ?>"
                                            placeholder="Daraja API Consumer Key">
                                        <small class="form-text text-muted">From Safaricom Developer Portal</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="daraja_consumer_secret">Consumer Secret</label>
                                        <input type="password" class="form-control" id="daraja_consumer_secret" name="daraja_consumer_secret"
                                            value="<?php echo htmlspecialchars(base64_decode($current_settings['daraja_consumer_secret'] ?? '')); ?>"
                                            placeholder="Daraja API Consumer Secret">
                                        <small class="form-text text-muted">Keep this secure</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="daraja_environment">Environment</label>
                                        <select class="form-control" id="daraja_environment" name="daraja_environment">
                                            <option value="sandbox" <?php echo ($current_settings['daraja_environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>Sandbox</option>
                                            <option value="production" <?php echo ($current_settings['daraja_environment'] ?? '') === 'production' ? 'selected' : ''; ?>>Production</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="daraja_shortcode">Business Shortcode</label>
                                        <input type="text" class="form-control" id="daraja_shortcode" name="daraja_shortcode"
                                            value="<?php echo htmlspecialchars($current_settings['daraja_shortcode'] ?? '174379'); ?>"
                                            placeholder="174379 (sandbox) or your shortcode">
                                    </div>
                                    <div class="form-group">
                                        <label for="daraja_passkey">Lipa Na M-Pesa Passkey</label>
                                        <input type="password" class="form-control" id="daraja_passkey" name="daraja_passkey"
                                            value="<?php echo htmlspecialchars($current_settings['daraja_passkey'] ?? ''); ?>"
                                            placeholder="Lipa Na M-Pesa Online Passkey">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="daraja_initiator_name">Initiator Name</label>
                                        <input type="text" class="form-control" id="daraja_initiator_name" name="daraja_initiator_name"
                                            value="<?php echo htmlspecialchars($current_settings['daraja_initiator_name'] ?? 'testapi'); ?>"
                                            placeholder="testapi (sandbox) or your initiator">
                                    </div>
                                    <div class="form-group">
                                        <label for="daraja_security_credential">Security Credential</label>
                                        <input type="password" class="form-control" id="daraja_security_credential" name="daraja_security_credential"
                                            value="<?php echo htmlspecialchars(base64_decode($current_settings['daraja_security_credential'] ?? '')); ?>"
                                            placeholder="Encrypted initiator password">
                                        <small class="form-text text-muted">Base64 encoded credential</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="daraja_stk_callback_url">STK Push Callback URL</label>
                                        <input type="url" class="form-control" id="daraja_stk_callback_url" name="daraja_stk_callback_url"
                                            value="<?php echo htmlspecialchars($current_settings['daraja_stk_callback_url'] ?? 'https://yourdomain.com/api/mpesa/stk_callback.php'); ?>"
                                            placeholder="https://yourdomain.com/api/mpesa/stk_callback.php">
                                    </div>
                                    <div class="form-group">
                                        <label for="daraja_b2c_callback_url">B2C Callback URL</label>
                                        <input type="url" class="form-control" id="daraja_b2c_callback_url" name="daraja_b2c_callback_url"
                                            value="<?php echo htmlspecialchars($current_settings['daraja_b2c_callback_url'] ?? 'https://yourdomain.com/api/mpesa/b2c_callback.php'); ?>"
                                            placeholder="https://yourdomain.com/api/mpesa/b2c_callback.php">
                                    </div>
                                    <div class="form-group">
                                        <label for="daraja_c2b_callback_url">C2B Callback URL</label>
                                        <input type="url" class="form-control" id="daraja_c2b_callback_url" name="daraja_c2b_callback_url"
                                            value="<?php echo htmlspecialchars($current_settings['daraja_c2b_callback_url'] ?? 'https://yourdomain.com/api/mpesa/c2b_callback.php'); ?>"
                                            placeholder="https://yourdomain.com/api/mpesa/c2b_callback.php">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- System Settings -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cogs mr-2"></i>
                                System Settings
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="commission_rate">Commission Rate (%)</label>
                                <input type="number" class="form-control" id="commission_rate" name="commission_rate"
                                    value="<?php echo htmlspecialchars($current_settings['commission_rate'] ?? '5'); ?>"
                                    min="0" max="50" step="0.1">
                                <small class="form-text text-muted">Platform commission rate on sales</small>
                            </div>
                            <div class="form-group">
                                <label for="escrow_duration">Escrow Duration (Days)</label>
                                <input type="number" class="form-control" id="escrow_duration" name="escrow_duration"
                                    value="<?php echo htmlspecialchars($current_settings['escrow_duration'] ?? '7'); ?>"
                                    min="1" max="30">
                                <small class="form-text text-muted">How long to hold payments in escrow</small>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="email_notifications" name="email_notifications"
                                        <?php echo ($current_settings['email_notifications'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="email_notifications">Enable Email Notifications</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="sms_notifications" name="sms_notifications"
                                        <?php echo ($current_settings['sms_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="sms_notifications">Enable SMS Notifications</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification URLs -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bell mr-2"></i>
                                Callback URLs
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="daraja_validation_url">Validation URL</label>
                                <input type="url" class="form-control" id="daraja_validation_url" name="daraja_validation_url"
                                    value="<?php echo htmlspecialchars($current_settings['daraja_validation_url'] ?? 'https://yourdomain.com/api/mpesa/validation.php'); ?>"
                                    placeholder="https://yourdomain.com/api/mpesa/validation.php">
                            </div>
                            <div class="form-group">
                                <label for="daraja_confirmation_url">Confirmation URL</label>
                                <input type="url" class="form-control" id="daraja_confirmation_url" name="daraja_confirmation_url"
                                    value="<?php echo htmlspecialchars($current_settings['daraja_confirmation_url'] ?? 'https://yourdomain.com/api/mpesa/confirmation.php'); ?>"
                                    placeholder="https://yourdomain.com/api/mpesa/confirmation.php">
                            </div>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Callback URL Setup</h6>
                                <p class="mb-0">Register these URLs in your Safaricom Developer Portal for C2B transactions.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save mr-2"></i>
                                Save All Settings
                            </button>
                            <p class="mt-2 text-muted">All settings will be saved and applied immediately.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-2"></i>
                                System Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info"><i class="fas fa-server"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">PHP Version</span>
                                            <span class="info-box-number"><?php echo PHP_VERSION; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success"><i class="fas fa-database"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">MySQL Version</span>
                                            <span class="info-box-number"><?php echo $conn->server_info; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Server Time</span>
                                            <span class="info-box-number"><?php echo date('H:i:s'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-danger"><i class="fas fa-calendar"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Server Date</span>
                                            <span class="info-box-number"><?php echo date('Y-m-d'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-right">
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo mr-2"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>

<script>
    function resetForm() {
        if (confirm('Are you sure you want to reset all settings to their default values?')) {
            document.querySelector('form').reset();
        }
    }

    // Show/hide password fields
    document.addEventListener('DOMContentLoaded', function () {
        const apiKeyField = document.getElementById('mpesa_api_key');
        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'btn btn-sm btn-outline-secondary';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
        toggleButton.style.position = 'absolute';
        toggleButton.style.right = '10px';
        toggleButton.style.top = '50%';
        toggleButton.style.transform = 'translateY(-50%)';

        apiKeyField.parentNode.style.position = 'relative';
        apiKeyField.parentNode.appendChild(toggleButton);

        toggleButton.addEventListener('click', function () {
            if (apiKeyField.type === 'password') {
                apiKeyField.type = 'text';
                toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                apiKeyField.type = 'password';
                toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
</script>