<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

$db = getDbConnection();

// Ensure contacts table exists
function ensureContactsTableExists($db) {
    $tableExists = false;
    $result = $db->query("SHOW TABLES LIKE 'contacts'");
    if ($result && $result->num_rows > 0) {
        $tableExists = true;
    }
    
    if (!$tableExists) {
        $createTableSQL = "
        CREATE TABLE `contacts` (
          `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(100) NOT NULL,
          `email` VARCHAR(150) NOT NULL,
          `phone` VARCHAR(20) DEFAULT NULL,
          `subject` VARCHAR(255) NOT NULL,
          `message` TEXT NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        if (!$db->query($createTableSQL)) {
            error_log("Failed to create contacts table: " . $db->error);
            return false;
        }
    }
    return true;
}

// Ensure table exists before processing
if (!ensureContactsTableExists($db)) {
    header("Location: contact_us.php?status=error&messages=" . urlencode("Database setup error. Please try again later."));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone_raw = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

    // Validate inputs
    $errors = [];
    if (empty($name))
        $errors[] = "Name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email format";
    
    // Phone validation: expect 9 digits from frontend
    if (empty($phone_raw)) {
        $errors[] = "Phone number is required";
    } elseif (strlen($phone_raw) !== 9) {
        $errors[] = "Phone number must be 9 digits (without leading 0)";
    } elseif (!preg_match('/^[7][0-9]{8}$/', $phone_raw)) {
        $errors[] = "Phone number must start with 7 and be 9 digits long";
    }
    
    // Format phone with +254 prefix if valid
    $phone = !empty($phone_raw) && strlen($phone_raw) === 9 ? '+254' . $phone_raw : '';
    
    if (empty($subject))
        $errors[] = "Subject is required";
    if (empty($message))
        $errors[] = "Message is required";

    if (empty($errors)) {
        // Database connection (XAMPP default)
        // REMOVED: $conn = new mysqli("localhost", "root", "", "jiranl_db");

        // REMOVED: if ($conn->connect_error) {
        // REMOVED:     die("Connection failed: " . $conn->connect_error);
        // REMOVED: }

        // Prepare and bind
        $stmt = $db->prepare("INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);

        if ($stmt->execute()) {
            // Send email notification
            // REMOVED: $to = "support@jirani.co.ke";
            // REMOVED: $headers = "From: $email";
            // REMOVED: mail($to, $subject, $message, $headers);

            header("Location: contact_us.php?status=success");
        } else {
            header("Location: contact_us.php?status=error&messages=Database+error:+" . urlencode($stmt->error));
        }

        $stmt->close();
        // REMOVED: $conn->close();
    } else {
        header("Location: contact_us.php?status=error&messages=" . urlencode(implode(', ', $errors)));
    }
    exit();
}

// Display status messages
if (isset($_GET['status'])): ?>
    <div class="alert alert-<?= $_GET['status'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show"
        role="alert">
        <?php if ($_GET['status'] === 'success'): ?>
            Your message has been sent successfully!
        <?php else: ?>
            Error: <?= htmlspecialchars($_GET['messages']) ?>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>