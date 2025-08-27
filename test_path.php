<?php

echo "From test_path.php: \n";
echo "Current directory (using __DIR__): " . __DIR__ . "\n";
echo "Parent directory (dirname(__DIR__)): " . dirname(__DIR__) . "\n";

// Simulate an include from config.php
echo "\nSimulating config.php include:\n";
$config_root = dirname(__FILE__); // This would be /var/www/html/jirani_platform
echo "\$config_root: " . $config_root . "\n";

$include_path = $config_root . "/includes/User.php";
echo "Attempting to include: " . $include_path . "\n";

if (file_exists($include_path)) {
    echo "File exists at: " . $include_path . "\n";
    require_once $include_path;
    if (class_exists('User')) {
        echo "User class successfully loaded.\n";
        try {
            $conn = new mysqli('localhost', 'mikrometer', 'mikrometer123', 'jirani');
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $user = new User($conn);
            echo "User object instantiated successfully.\n";
        } catch (Exception $e) {
            echo "Error instantiating User object: " . $e->getMessage() . "\n";
        }
    } else {
        echo "User class not found after require_once.\n";
    }
} else {
    echo "File NOT found at: " . $include_path . "\n";
}

?>