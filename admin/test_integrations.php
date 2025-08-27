<?php
require_once __DIR__ . '/../config/config.php';

// Admin authentication check
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}

$pageTitle = 'Test Email & M-Pesa Integration';
$currentPage = 'test_integrations';
ob_start();

$testResults = [];

// Handle test requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testType = $_POST['test_type'] ?? '';
    
    switch ($testType) {
        case 'email_connection':
            $testResults['email_connection'] = $emailConfig->testConnection();
            break;
            
        case 'email_send':
            $testEmail = $_POST['test_email'] ?? '';
            if ($testEmail) {
                $testResults['email_send'] = $emailConfig->sendEmail(
                    $testEmail,
                    'Test Email from Jirani',
                    '<h2>Email Configuration Test</h2><p>This is a test email to verify your email configuration is working correctly.</p><p>If you received this email, your SMTP settings are configured properly!</p>',
                    true
                );
            } else {
                $testResults['email_send'] = ['success' => false, 'message' => 'Please provide a test email address'];
            }
            break;
            
        case 'daraja_connection':
            $testResults['daraja_connection'] = $darajaMpesa->testConnection();
            break;
            
        case 'daraja_stk':
            $testPhone = $_POST['test_phone'] ?? '';
            $testAmount = $_POST['test_amount'] ?? '';
            if ($testPhone && $testAmount) {
                $testResults['daraja_stk'] = $darajaMpesa->stkPush(
                    $testPhone,
                    $testAmount,
                    'TEST_REF_' . time(),
                    'Test STK Push from Jirani'
                );
            } else {
                $testResults['daraja_stk'] = ['success' => false, 'message' => 'Please provide phone number and amount'];
            }
            break;
            
        case 'register_c2b':
            $testResults['register_c2b'] = $darajaMpesa->registerC2BUrls();
            break;
    }
}
?>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-vial mr-2"></i>
                            Email & M-Pesa Integration Tests
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Integration Testing</h5>
                            <p>Use this page to test your email configuration and Daraja M-Pesa API integration. Make sure you have configured the settings in the admin settings page first.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Email Configuration Tests -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-envelope mr-2"></i>
                            Email Configuration Tests
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Email Connection Test -->
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="test_type" value="email_connection">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plug mr-2"></i>Test SMTP Connection
                            </button>
                        </form>
                        
                        <?php if (isset($testResults['email_connection'])): ?>
                            <div class="alert alert-<?php echo $testResults['email_connection']['success'] ? 'success' : 'danger'; ?>">
                                <strong>SMTP Connection Test:</strong> <?php echo htmlspecialchars($testResults['email_connection']['message']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Send Test Email -->
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="test_type" value="email_send">
                            <div class="form-group">
                                <label for="test_email">Test Email Address</label>
                                <input type="email" class="form-control" id="test_email" name="test_email" 
                                       placeholder="your-email@example.com" required>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-paper-plane mr-2"></i>Send Test Email
                            </button>
                        </form>
                        
                        <?php if (isset($testResults['email_send'])): ?>
                            <div class="alert alert-<?php echo $testResults['email_send']['success'] ? 'success' : 'danger'; ?>">
                                <strong>Send Email Test:</strong> <?php echo htmlspecialchars($testResults['email_send']['message']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Current Email Settings Display -->
                        <div class="mt-4">
                            <h6>Current Email Settings:</h6>
                            <ul class="list-unstyled">
                                <li><strong>SMTP Host:</strong> <?php echo htmlspecialchars(SMTP_HOST); ?></li>
                                <li><strong>SMTP Port:</strong> <?php echo htmlspecialchars(SMTP_PORT); ?></li>
                                <li><strong>From Address:</strong> <?php echo htmlspecialchars(MAIL_FROM_ADDRESS); ?></li>
                                <li><strong>From Name:</strong> <?php echo htmlspecialchars(MAIL_FROM_NAME); ?></li>
                                <li><strong>Notifications Enabled:</strong> <?php echo EMAIL_NOTIFICATIONS_ENABLED ? 'Yes' : 'No'; ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daraja M-Pesa Tests -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-credit-card mr-2"></i>
                            Daraja M-Pesa API Tests
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Daraja Connection Test -->
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="test_type" value="daraja_connection">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-link mr-2"></i>Test Daraja Connection
                            </button>
                        </form>
                        
                        <?php if (isset($testResults['daraja_connection'])): ?>
                            <div class="alert alert-<?php echo $testResults['daraja_connection']['success'] ? 'success' : 'danger'; ?>">
                                <strong>Daraja Connection Test:</strong> <?php echo htmlspecialchars($testResults['daraja_connection']['message']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- STK Push Test -->
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="test_type" value="daraja_stk">
                            <div class="form-group">
                                <label for="test_phone">Test Phone Number</label>
                                <input type="text" class="form-control" id="test_phone" name="test_phone" 
                                       placeholder="254712345678" required>
                                <small class="form-text text-muted">Use format: 254XXXXXXXXX</small>
                            </div>
                            <div class="form-group">
                                <label for="test_amount">Test Amount (KSh)</label>
                                <input type="number" class="form-control" id="test_amount" name="test_amount" 
                                       min="1" max="70000" value="10" required>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-mobile-alt mr-2"></i>Test STK Push
                            </button>
                        </form>
                        
                        <?php if (isset($testResults['daraja_stk'])): ?>
                            <div class="alert alert-<?php echo $testResults['daraja_stk']['success'] ? 'success' : 'danger'; ?>">
                                <strong>STK Push Test:</strong> <?php echo htmlspecialchars($testResults['daraja_stk']['message']); ?>
                                <?php if ($testResults['daraja_stk']['success']): ?>
                                    <br><small>Checkout Request ID: <?php echo htmlspecialchars($testResults['daraja_stk']['checkout_request_id'] ?? 'N/A'); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Register C2B URLs -->
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="test_type" value="register_c2b">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-cog mr-2"></i>Register C2B URLs
                            </button>
                        </form>
                        
                        <?php if (isset($testResults['register_c2b'])): ?>
                            <div class="alert alert-<?php echo $testResults['register_c2b']['success'] ? 'success' : 'danger'; ?>">
                                <strong>C2B URL Registration:</strong> <?php echo htmlspecialchars($testResults['register_c2b']['message']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Current Daraja Settings Display -->
                        <div class="mt-4">
                            <h6>Current Daraja Settings:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Environment:</strong> <?php echo htmlspecialchars(DARAJA_ENVIRONMENT); ?></li>
                                <li><strong>Shortcode:</strong> <?php echo htmlspecialchars(DARAJA_SHORTCODE); ?></li>
                                <li><strong>Initiator:</strong> <?php echo htmlspecialchars(DARAJA_INITIATOR_NAME); ?></li>
                                <li><strong>Consumer Key:</strong> <?php echo DARAJA_CONSUMER_KEY ? 'Configured' : 'Not Set'; ?></li>
                                <li><strong>Consumer Secret:</strong> <?php echo DARAJA_CONSUMER_SECRET ? 'Configured' : 'Not Set'; ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integration Status Overview -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-2"></i>
                            Integration Status Overview
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-<?php echo EMAIL_NOTIFICATIONS_ENABLED ? 'success' : 'warning'; ?>">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Email Notifications</span>
                                        <span class="info-box-number"><?php echo EMAIL_NOTIFICATIONS_ENABLED ? 'Enabled' : 'Disabled'; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-<?php echo DARAJA_CONSUMER_KEY ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Daraja API Keys</span>
                                        <span class="info-box-number"><?php echo DARAJA_CONSUMER_KEY ? 'Configured' : 'Missing'; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-server"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">M-Pesa Environment</span>
                                        <span class="info-box-number"><?php echo ucfirst(DARAJA_ENVIRONMENT); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-<?php echo SMS_NOTIFICATIONS_ENABLED ? 'success' : 'secondary'; ?>">
                                        <i class="fas fa-sms"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">SMS Notifications</span>
                                        <span class="info-box-number"><?php echo SMS_NOTIFICATIONS_ENABLED ? 'Enabled' : 'Disabled'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-lightbulb"></i> Next Steps</h6>
                            <ul class="mb-0">
                                <li>Configure email settings in <a href="settings.php">Admin Settings</a></li>
                                <li>Set up Daraja API credentials from Safaricom Developer Portal</li>
                                <li>Test email functionality before going live</li>
                                <li>Register callback URLs with Safaricom for C2B transactions</li>
                                <li>Test STK Push with small amounts in sandbox environment</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>
