<?php
require_once __DIR__ . '/config/config.php';

$db = getDbConnection();

// Check if contacts table exists
$tableExists = false;
$result = $db->query("SHOW TABLES LIKE 'contacts'");
if ($result->num_rows > 0) {
    $tableExists = true;
    echo "✅ Contacts table already exists.\n";
} else {
    echo "❌ Contacts table does not exist. Creating it now...\n";
}

// Create contacts table if it doesn't exist
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
    
    if ($db->query($createTableSQL)) {
        echo "✅ Contacts table created successfully!\n";
    } else {
        echo "❌ Error creating contacts table: " . $db->error . "\n";
        exit(1);
    }
}

// Test the table by inserting a sample record
$testName = "Test User";
$testEmail = "test@example.com";
$testPhone = "+254700000000";
$testSubject = "Test Message";
$testMessage = "This is a test message to verify the contacts table is working properly.";

$stmt = $db->prepare("INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $testName, $testEmail, $testPhone, $testSubject, $testMessage);

if ($stmt->execute()) {
    echo "✅ Test message inserted successfully! Contact form should now work.\n";
    echo "📧 You can view messages in the admin panel at: admin/contacts.php\n";
    
    // Get the inserted ID
    $insertedId = $db->insert_id;
    echo "📝 Test message ID: " . $insertedId . "\n";
    
    // Clean up test record
    $deleteStmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
    $deleteStmt->bind_param("i", $insertedId);
    if ($deleteStmt->execute()) {
        echo "🧹 Test record cleaned up successfully.\n";
    }
} else {
    echo "❌ Error inserting test message: " . $stmt->error . "\n";
}

echo "\n🎉 Contact form setup complete! Users can now submit messages and admins can view them.\n";
?>
