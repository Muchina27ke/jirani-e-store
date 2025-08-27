<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/VendorVerification.php';

echo '<pre>';

echo "--- VERIFICATIONS DEBUG ---\\n\\n";

try {
    $conn = getDbConnection();
    echo "Database connection successful.\\n";

    $verificationObj = new VendorVerification($conn);
    echo "VendorVerification class instantiated.\\n\\n";

    echo "Testing getPendingVerifications()...\\n";
    $verifications = $verificationObj->getPendingVerifications();
    echo "Returned data:\\n";
    print_r($verifications);
    
    echo "\\n\\nTesting getVerificationStats()...\\n";
    $stats = $verificationObj->getVerificationStats();
    echo "Stats data:\\n";
    print_r($stats);
    
    echo "\\n\\nDirect SQL query test...\\n";
    $query = "SELECT v.*, vend.business_name, vend.status AS vendor_status, u.name as vendor_name, u.phone, u.email
              FROM verifications v
              LEFT JOIN vendors vend ON v.vendor_id = vend.user_id
              LEFT JOIN users u ON vend.user_id = u.id
              ORDER BY v.created_at DESC";
    $result = $conn->query($query);
    if ($result) {
        echo "Direct query results:\\n";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Direct query failed: " . $conn->error . "\\n";
    }

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
}

echo '</pre>';
