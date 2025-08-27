<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Vendor.php';

echo '<pre>';

$vendorId = 120;
echo "--- DEBUGGING VENDOR ID: {$vendorId} ---\\n\\n";

try {
    $conn = getDbConnection();
    echo "Database connection successful.\\n";

    $vendorObj = new Vendor($conn);
    echo "Vendor class instantiated.\\n\\n";

    echo "Attempting to fetch vendor details using get_vendor_by_user_id()...\\n";
    $vendor_data = $vendorObj->get_vendor_by_user_id($vendorId);

    if ($vendor_data) {
        echo "Vendor data found:\\n";
        print_r($vendor_data);
    } else {
        echo "Vendor with ID {$vendorId} not found by get_vendor_by_user_id().\\n";
    }

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
}

echo '</pre>';
