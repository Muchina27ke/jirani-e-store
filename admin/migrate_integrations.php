<?php
require_once __DIR__ . '/../config/config.php';

// Admin authentication check
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}

$pageTitle = 'Integration Migration Tool';
$currentPage = 'migrate_integrations';
ob_start();

$migrationResults = [];
$codebaseIssues = [];

// Check database settings
function checkDatabaseSettings($conn) {
    $requiredSettings = [
        // Email settings
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
        'mail_from_address', 'mail_from_name',
        // Daraja settings
        'daraja_consumer_key', 'daraja_consumer_secret', 'daraja_environment',
        'daraja_shortcode', 'daraja_passkey', 'daraja_initiator_name',
        'daraja_security_credential', 'daraja_stk_callback_url', 'daraja_b2c_callback_url',
        'daraja_c2b_callback_url', 'daraja_validation_url', 'daraja_confirmation_url',
        // System settings
        'site_name', 'site_description', 'contact_email', 'contact_phone', 'address',
        'commission_rate', 'escrow_duration', 'email_notifications', 'sms_notifications'
    ];
    
    $existingSettings = [];
    $result = $conn->query("SELECT name FROM settings");
    while ($row = $result->fetch_assoc()) {
        $existingSettings[] = $row['name'];
    }
    
    $missingSettings = array_diff($requiredSettings, $existingSettings);
    return ['existing' => $existingSettings, 'missing' => $missingSettings];
}

// Check codebase for old M-Pesa usage
function checkCodebaseIssues() {
    $issues = [];
    $filesToCheck = [
        'seller/orders.php',
        'includes/Order.php',
        'api/mpesa/initiate.php',
        'api/mpesa/callback.php',
        'config/config.php'
    ];
    
    foreach ($filesToCheck as $file) {
        $fullPath = __DIR__ . '/../' . $file;
        if (file_exists($fullPath)) {
            $content = file_get_contents($fullPath);
            
            // Check for old IntaSend references
            if (strpos($content, 'MPESA_API_KEY') !== false) {
                $issues[] = "File $file still contains MPESA_API_KEY references";
            }
            if (strpos($content, 'MPESA_PUBLISHABLE_KEY') !== false) {
                $issues[] = "File $file still contains MPESA_PUBLISHABLE_KEY references";
            }
            if (strpos($content, 'new Mpesa(') !== false) {
                $issues[] = "File $file still uses old Mpesa class instantiation";
            }
            
            // Check for email configuration issues
            if (strpos($content, 'mail(') !== false) {
                $issues[] = "File $file uses PHP mail() function instead of configured SMTP";
            }
        } else {
            $issues[] = "File $file does not exist";
        }
    }
    
    return $issues;
}

// Handle migration actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'check_database':
            $migrationResults['database'] = checkDatabaseSettings($conn);
            break;
            
        case 'check_codebase':
            $migrationResults['codebase'] = checkCodebaseIssues();
            break;
            
        case 'migrate_settings':
            // Run the database migration
            $sqlFile = __DIR__ . '/../database/update_settings_schema.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $statements = explode(';', $sql);
                $success = true;
                $errors = [];
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement) && !preg_match('/^(SELECT|--)/i', $statement)) {
                        if (!$conn->query($statement)) {
                            $success = false;
                            $errors[] = $conn->error;
                        }
                    }
                }
                
                $migrationResults['migration'] = [
                    'success' => $success,
                    'errors' => $errors,
                    'message' => $success ? 'Database settings migrated successfully' : 'Migration failed'
                ];
            } else {
                $migrationResults['migration'] = [
                    'success' => false,
                    'message' => 'Migration SQL file not found'
                ];
            }
            break;
    }
}

// Get current status
$dbStatus = checkDatabaseSettings($conn);
$codebaseIssues = checkCodebaseIssues();
?>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Integration Migration Tool
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Migration Required</h5>
                            <p>This tool helps migrate from old IntaSend M-Pesa integration to pure Daraja APIs and ensures email configuration is properly set up from admin settings.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Database Status -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-database mr-2"></i>
                            Database Settings Status
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="action" value="check_database">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i>Check Database Settings
                            </button>
                        </form>
                        
                        <?php if (isset($migrationResults['database'])): ?>
                            <div class="mt-3">
                                <h6>Existing Settings: <?php echo count($migrationResults['database']['existing']); ?></h6>
                                <h6>Missing Settings: <?php echo count($migrationResults['database']['missing']); ?></h6>
                                
                                <?php if (!empty($migrationResults['database']['missing'])): ?>
                                    <div class="alert alert-warning">
                                        <strong>Missing Settings:</strong>
                                        <ul class="mb-0">
                                            <?php foreach ($migrationResults['database']['missing'] as $setting): ?>
                                                <li><?php echo htmlspecialchars($setting); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="action" value="migrate_settings">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-database mr-2"></i>Migrate Database Settings
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check"></i> All required settings are present in the database.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($migrationResults['migration'])): ?>
                            <div class="alert alert-<?php echo $migrationResults['migration']['success'] ? 'success' : 'danger'; ?>">
                                <strong>Migration Result:</strong> <?php echo htmlspecialchars($migrationResults['migration']['message']); ?>
                                <?php if (!empty($migrationResults['migration']['errors'])): ?>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($migrationResults['migration']['errors'] as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Current Database Status -->
                        <div class="mt-4">
                            <h6>Current Status:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Existing Settings:</strong> <?php echo count($dbStatus['existing']); ?></li>
                                <li><strong>Missing Settings:</strong> <?php echo count($dbStatus['missing']); ?></li>
                                <li><strong>Database Ready:</strong> 
                                    <?php if (empty($dbStatus['missing'])): ?>
                                        <span class="badge badge-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">No</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Codebase Status -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-code mr-2"></i>
                            Codebase Migration Status
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="action" value="check_codebase">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i>Check Codebase Issues
                            </button>
                        </form>
                        
                        <?php if (isset($migrationResults['codebase'])): ?>
                            <div class="mt-3">
                                <?php if (empty($migrationResults['codebase'])): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check"></i> No codebase issues detected.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <strong>Codebase Issues Found:</strong>
                                        <ul class="mb-0">
                                            <?php foreach ($migrationResults['codebase'] as $issue): ?>
                                                <li><?php echo htmlspecialchars($issue); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Current Codebase Status -->
                        <div class="mt-4">
                            <h6>Current Issues:</h6>
                            <?php if (empty($codebaseIssues)): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check"></i> No issues detected.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <ul class="mb-0">
                                        <?php foreach ($codebaseIssues as $issue): ?>
                                            <li><?php echo htmlspecialchars($issue); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Migration Steps -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-ol mr-2"></i>
                            Migration Steps
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Database Migration:</h6>
                                <ol>
                                    <li>Check current database settings</li>
                                    <li>Run database migration script</li>
                                    <li>Verify all settings are present</li>
                                    <li>Configure email and M-Pesa settings in <a href="settings.php">Admin Settings</a></li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h6>Code Migration:</h6>
                                <ol>
                                    <li>Update all M-Pesa API calls to use Daraja</li>
                                    <li>Replace email functions with SMTP configuration</li>
                                    <li>Remove old IntaSend references</li>
                                    <li>Test integrations with <a href="test_integrations.php">Test Page</a></li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-info-circle"></i> Important Notes</h6>
                            <ul class="mb-0">
                                <li>Backup your database before running migrations</li>
                                <li>Test email and M-Pesa functionality after migration</li>
                                <li>Update callback URLs in Safaricom Developer Portal</li>
                                <li>Configure SMTP settings for email notifications</li>
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
