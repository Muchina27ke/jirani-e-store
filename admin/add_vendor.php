<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/User.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}

$pageTitle = 'Add Vendor';
$currentPage = 'add_vendor';

ob_start();
?>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Existing add vendor form or confirmation message here -->
        <?php
        require_once '../includes/Auth.php';

        // Ensure only admins can access this page
        $auth = new Auth($conn);
        $auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: vendors.php');
            exit;
        }

        // Get and validate input
        $businessName = trim($_POST['business_name'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validate required fields
        if (empty($businessName) || empty($name) || empty($email) || empty($phone) || empty($location)) {
            $_SESSION['error'] = "All required fields must be filled out.";
            header('Location: vendors.php');
            exit;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            header('Location: vendors.php');
            exit;
        }

        // Validate phone format (basic validation for Kenyan numbers)
        if (!preg_match('/^(\+254|0)[17]\d{8}$/', $phone)) {
            $_SESSION['error'] = "Invalid phone number format. Please use a valid Kenyan number.";
            header('Location: vendors.php');
            exit;
        }

        try {
            $conn->begin_transaction();

            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE name = ? OR email = ?");
            $stmt->bind_param("ss", $name, $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Name or email already exists.");
            }

            // Generate a temporary password
            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Create user account
            $stmt = $conn->prepare("
            INSERT INTO users (name, email, password, phone, role, created_at)
            VALUES (?, ?, ?, ?, 'vendor', NOW())
        ");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $phone);
            $stmt->execute();
            $userId = $conn->insert_id;

            // Create vendor profile
            $stmt = $conn->prepare("
            INSERT INTO vendors (user_id, business_name, description, location, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
            $stmt->bind_param("isss", $userId, $businessName, $description, $location);
            $stmt->execute();
            $vendorId = $conn->insert_id;

            // Create verification record
            $stmt = $conn->prepare("
            INSERT INTO verifications (vendor_id, status, created_at)
            VALUES (?, 'pending', NOW())
        ");
            $stmt->bind_param("i", $vendorId);
            $stmt->execute();

            // Log the action
            $stmt = $conn->prepare("
            INSERT INTO vendor_logs (vendor_id, action, performed_by, created_at)
            VALUES (?, 'created', ?, NOW())
        ");
            $stmt->bind_param("ii", $vendorId, $user['id']);
            $stmt->execute();

            $conn->commit();

            // Send welcome email with temporary password
            $to = $email;
            $subject = "Welcome to Jirani - Your Vendor Account";
            $message = "
            <html>
            <head>
                <title>Welcome to Jirani</title>
            </head>
            <body>
                <h2>Welcome to Jirani!</h2>
                <p>Your vendor account has been created successfully.</p>
                <p>Here are your login credentials:</p>
                <ul>
                    <li>Name: {$name}</li>
                    <li>Temporary Password: {$tempPassword}</li>
                </ul>
                <p>Please log in and change your password immediately.</p>
                <p>Your account is currently pending verification. You will be notified once it's approved.</p>
                <p>Best regards,<br>Jirani Team</p>
            </body>
            </html>
        ";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: Jirani <noreply@jirani.com>' . "\r\n";

            // Use EmailConfig for sending emails
            global $emailConfig;
            if ($emailConfig) {
                $emailConfig->sendEmail($to, $subject, $message, false);
            }

            $_SESSION['success'] = "Vendor added successfully. A welcome email has been sent.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Failed to add vendor: " . $e->getMessage();
        }

        header('Location: vendors.php');
        exit;
        ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>