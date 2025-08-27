<?php
// Simple test to verify upload directory exists and is writable
$uploadDir = __DIR__ . '/uploads/vendor_documents/';

echo '<h2>File Upload System Test</h2>';

if (!is_dir($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        echo '<p style="color: green;">✅ Upload directory created successfully</p>';
    } else {
        echo '<p style="color: red;">❌ Failed to create upload directory</p>';
    }
} else {
    echo '<p style="color: green;">✅ Upload directory exists</p>';
}

if (is_writable($uploadDir)) {
    echo '<p style="color: green;">✅ Upload directory is writable</p>';
} else {
    echo '<p style="color: red;">❌ Upload directory is not writable</p>';
}

echo '<p><strong>Upload directory path:</strong> ' . $uploadDir . '</p>';
echo '<p><strong>File upload system is ready!</strong></p>';
?>
