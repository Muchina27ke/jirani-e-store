<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/VendorVerification.php';

echo '<pre>';

echo "--- VENDOR VERIFICATION CLASS DEBUGGER ---\\n\\n";

try {
    $conn = getDbConnection();
    echo "Database connection successful.\\n";

    $verificationObj = new VendorVerification($conn);
    echo "VendorVerification class instantiated.\\n\\n";

    echo "Listing all available methods in the VendorVerification class...\\n";
    $class_methods = get_class_methods($verificationObj);
    
    if ($class_methods) {
        echo "Methods found:\\n";
        print_r($class_methods);
    } else {
        echo "Could not retrieve methods from VendorVerification class.\\n";
    }

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
}

echo '</pre>';
