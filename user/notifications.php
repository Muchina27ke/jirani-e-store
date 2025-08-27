<?php
$pageTitle = 'Notification Settings';
$currentPage = 'notifications';

// Get user ID
$userId = $_SESSION['user']['id'];

// Initialize Notification service
$notification = new Notification($db);

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'email_notifications' => filter_input(INPUT_POST, 'email_notifications', FILTER_VALIDATE_BOOLEAN),
        'sms_notifications' => filter_input(INPUT_POST, 'sms_notifications', FILTER_VALIDATE_BOOLEAN),
        'push_notifications' => filter_input(INPUT_POST, 'push_notifications', FILTER_VALIDATE_BOOLEAN),
        'notification_types' => [
            'orders' => filter_input(INPUT_POST, 'notify_orders', FILTER_VALIDATE_BOOLEAN),
            'payments' => filter_input(INPUT_POST, 'notify_payments', FILTER_VALIDATE_BOOLEAN),
            'verification' => filter_input(INPUT_POST, 'notify_verification', FILTER_VALIDATE_BOOLEAN),
            'system' => filter_input(INPUT_POST, 'notify_system', FILTER_VALIDATE_BOOLEAN)
        ]
    ];

    try {
        $notification->updateSettings($userId, $settings);
        $_SESSION['success'] = 'Notification settings updated successfully';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to update notification settings';
    }

    header('Location: /user/notifications.php');
    exit;
}

// Get current settings
$settings = $notification->getUserSettings($userId);
if (!$settings) {
    $settings = $notification->createDefaultSettings($userId);
}

// Get recent notifications
$notifications = $notification->getUnreadNotifications($userId);

ob_start();
?>

<!-- Notification Settings -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Notification Settings</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="/user/notifications.php">
                    <div class="form-group">
                        <label>Notification Channels</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="emailNotifications"
                                name="email_notifications" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="emailNotifications">Email Notifications</label>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="smsNotifications"
                                name="sms_notifications" <?php echo $settings['sms_notifications'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="smsNotifications">SMS Notifications</label>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="pushNotifications"
                                name="push_notifications" <?php echo $settings['push_notifications'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="pushNotifications">Push Notifications</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notification Types</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notifyOrders" name="notify_orders"
                                <?php echo $settings['notification_types']['orders'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="notifyOrders">Order Updates</label>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notifyPayments"
                                name="notify_payments" <?php echo $settings['notification_types']['payments'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="notifyPayments">Payment Updates</label>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notifyVerification"
                                name="notify_verification" <?php echo $settings['notification_types']['verification'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="notifyVerification">Verification Updates</label>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notifySystem" name="notify_system"
                                <?php echo $settings['notification_types']['system'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="notifySystem">System Updates</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Notifications</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                    <p class="text-muted p-3">No unread notifications</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                    <small><?php echo date('M d, H:i', strtotime($notification['created_at'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <small class="text-muted">
                                    <?php echo ucfirst($notification['type']); ?> notification
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
?>