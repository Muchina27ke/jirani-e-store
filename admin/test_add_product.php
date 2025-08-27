<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Product.php';

// Simple test script for add_product functionality
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>

<head>
    <title>Add Product Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .test {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }

        .pass {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .fail {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .summary {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <h1>Add Product Functionality Test</h1>

    <?php
    $results = [];

    // Test 1: Database Connection
    echo "<div class='test info'>";
    echo "<h3>1. Database Connection</h3>";
    try {
        $test = $conn->query("SELECT 1");
        if ($test) {
            echo "<div class='pass'>✓ Database connection successful</div>";
            $results['database'] = 'PASS';
        } else {
            echo "<div class='fail'>✗ Database connection failed</div>";
            $results['database'] = 'FAIL';
        }
    } catch (Exception $e) {
        echo "<div class='fail'>✗ Database connection error: " . $e->getMessage() . "</div>";
        $results['database'] = 'FAIL';
    }
    echo "</div>";

    // Test 2: Product Class
    echo "<div class='test info'>";
    echo "<h3>2. Product Class</h3>";
    try {
        $productObj = new Product($conn);
        echo "<div class='pass'>✓ Product class instantiated successfully</div>";
        $results['product_class'] = 'PASS';
    } catch (Exception $e) {
        echo "<div class='fail'>✗ Product class error: " . $e->getMessage() . "</div>";
        $results['product_class'] = 'FAIL';
    }
    echo "</div>";

    // Test 3: Vendors Data
    echo "<div class='test info'>";
    echo "<h3>3. Vendors Data</h3>";
    try {
        $vendors_stmt = $conn->prepare("SELECT user_id, business_name FROM vendors WHERE status = 'active' LIMIT 5");
        $vendors_stmt->execute();
        $vendors = $vendors_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (count($vendors) > 0) {
            echo "<div class='pass'>✓ Found " . count($vendors) . " active vendors</div>";
            $results['vendors'] = 'PASS';
        } else {
            echo "<div class='fail'>✗ No active vendors found</div>";
            $results['vendors'] = 'FAIL';
        }
    } catch (Exception $e) {
        echo "<div class='fail'>✗ Vendors query error: " . $e->getMessage() . "</div>";
        $results['vendors'] = 'FAIL';
    }
    echo "</div>";

    // Test 4: Categories Data
    echo "<div class='test info'>";
    echo "<h3>4. Categories Data</h3>";
    try {
        $categories_stmt = $conn->prepare("SELECT id, name FROM categories WHERE status = 'active' LIMIT 5");
        $categories_stmt->execute();
        $categories = $categories_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (count($categories) > 0) {
            echo "<div class='pass'>✓ Found " . count($categories) . " active categories</div>";
            $results['categories'] = 'PASS';
        } else {
            echo "<div class='fail'>✗ No active categories found</div>";
            $results['categories'] = 'FAIL';
        }
    } catch (Exception $e) {
        echo "<div class='fail'>✗ Categories query error: " . $e->getMessage() . "</div>";
        $results['categories'] = 'FAIL';
    }
    echo "</div>";

    // Test 5: File Upload Directory
    echo "<div class='test info'>";
    echo "<h3>5. File Upload Directory</h3>";
    $uploadDir = '../uploads/products/';
    if (is_dir($uploadDir) || mkdir($uploadDir, 0777, true)) {
        echo "<div class='pass'>✓ Upload directory accessible</div>";
        $results['upload_dir'] = 'PASS';
    } else {
        echo "<div class='fail'>✗ Upload directory not accessible</div>";
        $results['upload_dir'] = 'FAIL';
    }
    echo "</div>";

    // Test 6: Add Product Method
    echo "<div class='test info'>";
    echo "<h3>6. Add Product Method</h3>";
    try {
        // Test with sample data
        $testVendorId = 1; // Assuming vendor ID 1 exists
        $testName = "Test Product " . time();
        $testDescription = "Test product description";
        $testPrice = 100.00;
        $testStock = 10;

        $result = $productObj->add_product($testVendorId, $testName, $testDescription, $testPrice, $testStock);

        if ($result) {
            echo "<div class='pass'>✓ Add product method working</div>";
            $results['add_product'] = 'PASS';

            // Clean up test product
            $conn->query("DELETE FROM products WHERE name = '$testName'");
        } else {
            echo "<div class='fail'>✗ Add product method failed</div>";
            $results['add_product'] = 'FAIL';
        }
    } catch (Exception $e) {
        echo "<div class='fail'>✗ Add product error: " . $e->getMessage() . "</div>";
        $results['add_product'] = 'FAIL';
    }
    echo "</div>";

    // Summary
    echo "<div class='summary'>";
    echo "<h3>Test Summary</h3>";

    $totalTests = count($results);
    $passedTests = count(array_filter($results, function ($result) {
        return $result === 'PASS';
    }));
    $failedTests = $totalTests - $passedTests;

    echo "<strong>Overall Result:</strong> $passedTests/$totalTests tests passed<br>";

    if ($failedTests === 0) {
        echo "<div class='pass'><strong>✓ All tests passed! The add product system is ready.</strong></div>";
    } else {
        echo "<div class='fail'><strong>✗ $failedTests tests failed. Please review the issues above.</strong></div>";
    }

    echo "<br><strong>Detailed Results:</strong><br>";
    foreach ($results as $test => $result) {
        $class = $result === 'PASS' ? 'pass' : 'fail';
        $icon = $result === 'PASS' ? '✓' : '✗';
        echo "<div class='$class'>$icon $test: $result</div>";
    }
    echo "</div>";

    // Navigation
    echo "<div class='test info'>";
    echo "<h3>Navigation</h3>";
    echo "<a href='add_product.php' class='btn btn-primary'>Go to Add Product Page</a>";
    echo "<a href='products.php' class='btn btn-success'>Go to Products List</a>";
    echo "</div>";
    ?>
</body>

</html>