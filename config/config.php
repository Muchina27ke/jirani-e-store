<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'jirani');
define('DB_USER', 'root');
define('DB_PASS', '');

// Database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

function getDbConnection()
{
    global $conn;
    return $conn;
}

// Include all core classes first to ensure they are available before any instantiations
require_once dirname(__DIR__) . "/includes/Setting.php";
require_once dirname(__DIR__) . "/includes/Security.php";
require_once dirname(__DIR__) . "/includes/ErrorHandler.php";
require_once dirname(__DIR__) . "/includes/EmailTemplate.php";
require_once dirname(__DIR__) . "/includes/User.php";
require_once dirname(__DIR__) . "/includes/Vendor.php";
require_once dirname(__DIR__) . "/includes/Customer.php";
require_once dirname(__DIR__) . "/includes/Product.php";
require_once dirname(__DIR__) . "/includes/Order.php";
require_once dirname(__DIR__) . "/includes/Cart.php";
require_once dirname(__DIR__) . "/includes/Wishlist.php";
require_once dirname(__DIR__) . "/includes/Notification.php";
require_once dirname(__DIR__) . "/includes/Geolocation.php";
require_once dirname(__DIR__) . "/includes/Mpesa.php";
require_once dirname(__DIR__) . "/includes/Escrow.php";
require_once dirname(__DIR__) . "/includes/VendorVerification.php";
require_once dirname(__DIR__) . "/includes/Auth.php";

// Initialize Setting class and load dynamic settings
$settingObj = new Setting($conn);
$globalSettings = $settingObj->get_all();

// Define constants from settings (or fallback to defaults)
// Email Configuration
define('SMTP_HOST', $globalSettings['smtp_host'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $globalSettings['smtp_port'] ?? 587);
define('SMTP_USERNAME', $globalSettings['smtp_username'] ?? '');
define('SMTP_PASSWORD', base64_decode($globalSettings['smtp_password'] ?? ''));
define('SMTP_ENCRYPTION', $globalSettings['smtp_encryption'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $globalSettings['mail_from_address'] ?? 'noreply@jirani.com');
define('MAIL_FROM_NAME', $globalSettings['mail_from_name'] ?? 'Jirani');

// Daraja M-Pesa Configuration
define('DARAJA_CONSUMER_KEY', $globalSettings['daraja_consumer_key'] ?? '');
define('DARAJA_CONSUMER_SECRET', base64_decode($globalSettings['daraja_consumer_secret'] ?? ''));
define('DARAJA_ENVIRONMENT', $globalSettings['daraja_environment'] ?? 'sandbox');
define('DARAJA_SHORTCODE', $globalSettings['daraja_shortcode'] ?? '174379');
define('DARAJA_PASSKEY', $globalSettings['daraja_passkey'] ?? '');
define('DARAJA_INITIATOR_NAME', $globalSettings['daraja_initiator_name'] ?? 'testapi');
define('DARAJA_SECURITY_CREDENTIAL', base64_decode($globalSettings['daraja_security_credential'] ?? ''));

// System Settings
define('MAX_DELIVERY_RADIUS', $globalSettings['max_delivery_radius'] ?? 10);
define('SITE_URL', $globalSettings['site_url'] ?? 'http://localhost/jirani/');
define('SITE_NAME', $globalSettings['site_name'] ?? 'Jirani');
define('COMMISSION_RATE', $globalSettings['commission_rate'] ?? 5);
define('ESCROW_DURATION', $globalSettings['escrow_duration'] ?? 7);
define('EMAIL_NOTIFICATIONS_ENABLED', ($globalSettings['email_notifications'] ?? '1') === '1');
define('SMS_NOTIFICATIONS_ENABLED', ($globalSettings['sms_notifications'] ?? '0') === '1');
define('HASH_COST', 10); // Default bcrypt cost for password_hash

// Initialize other classes
$security = new Security($conn);
$errorHandler = new ErrorHandler($conn);

// Initialize Email Configuration
require_once dirname(__DIR__) . "/includes/EmailConfig.php";
$emailConfig = new EmailConfig($conn);

// Initialize Daraja M-Pesa API
require_once dirname(__DIR__) . "/includes/DarajaMpesa.php";
$darajaMpesa = new DarajaMpesa($conn);

$emailNotifications = new EmailNotifications($conn);
$user = new User($conn);
$vendor = new Vendor($conn);
$customer = new Customer($conn);
$product = new Product($conn);
$order = new Order($conn);
$cart = new Cart($conn, $_SESSION['user_id'] ?? null);
$wishlist = new Wishlist($conn, $_SESSION['user_id'] ?? null);
$notification = new Notification($conn);
$geolocation = new Geolocation($conn);
// Old M-Pesa initialization removed - now using Daraja API via DarajaMpesa class
$escrow = new Escrow($conn);
$vendorVerification = new VendorVerification($conn);
$auth = new Auth($conn);



?>