<?php

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'name' => 'Temporary Test Product',
    'vendor_id' => 1,
    'category_id' => 1,
    'price' => 123.45,
    'stock' => 50,
    'description' => 'This is a temporary product for testing.'
];

// Simulate file upload (create a dummy file)
$dummyImagePath = __DIR__ . '/dummy_product_image.jpg';
file_put_contents($dummyImagePath, ''); // Create an empty file

$_FILES = [
    'image' => [
        'name' => 'dummy_product_image.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => $dummyImagePath,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($dummyImagePath)
    ]
];

// Include the target script
require_once __DIR__ . '/admin/add_product.php';

// Clean up dummy file
unlink($dummyImagePath);

echo "Script execution finished.";

?>