<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Product.php';

// Simple verification script for products functionality
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>

<head>
    <title>Products Functionality Verification</title>
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
    </style>
</head>

<body>
    <h1>Products Functionality Verification</h1>

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

    // Test 3: Get All Products
    echo "<div class='test info'>";
    echo "<h3>3. Get All Products</h3>";
    try {
        $products = $productObj->get_all_products();
        if (is_array($products)) {
            echo "<div class='pass'>✓ Found " . count($products) . " products</div>";
            $results['get_products'] = 'PASS';
        } else {
            echo "<div class='fail'>✗ Invalid products result</div>";
            $results['get_products'] = 'FAIL';
        }
    } catch (Exception $e) {
        echo "<div class='fail'>✗ Get products error: " . $e->getMessage() . "</div>";
        $results['get_products'] = 'FAIL';
    }
    echo "</div>";

    // Test 4: Product Statistics
    echo "<div class='test info'>";
    echo "<h3>4. Product Statistics</h3>";
    try {
        $stats = $productObj->get_product_statistics();
        if (is_array($stats) && isset($stats['total_products'])) {
            echo "<div class='pass'>✓ Statistics loaded successfully</div>";
            echo "<div class='info'>";
            echo "- Total Products: " . $stats['total_products'] . "<br>";
            echo "- Active Products: " . $stats['active_products'] . "<br>";
            echo "- Out of Stock: " . $stats['out_of_stock'] . "<br>";
            echo "- Inventory Value: KSh " . number_format($stats['inventory_value'], 2);
            echo "</div>";
            $results['statistics'] = 'PASS';
        } else {
            echo "<div class='fail'>✗ Invalid statistics format</div>";
            $results['statistics'] = 'FAIL';
        }
    } catch (Exception $e) {
        echo "<div class='fail'>✗ Statistics error: " . $e->getMessage() . "</div>";
        $results['statistics'] = 'FAIL';
    }
    echo "</div>";

    // Test 5: Database Tables
    echo "<div class='test info'>";
    echo "<h3>5. Required Database Tables</h3>";
    $tables = ['products', 'product_logs', 'inventory_logs', 'reviews', 'system_logs', 'login_history'];
    $tableResults = [];

    foreach ($tables as $table) {
        try {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "<div class='pass'>✓ Table '$table' exists</div>";
                $tableResults[$table] = 'PASS';
            } else {
                echo "<div class='fail'>✗ Table '$table' missing</div>";
                $tableResults[$table] = 'FAIL';
            }
        } catch (Exception $e) {
            echo "<div class='fail'>✗ Error checking table '$table': " . $e->getMessage() . "</div>";
            $tableResults[$table] = 'FAIL';
        }
    }

    $results['tables'] = !in_array('FAIL', $tableResults) ? 'PASS' : 'FAIL';
    echo "</div>";

    // Test 6: API Endpoints
    echo "<div class='test info'>";
    echo "<h3>6. API Endpoints</h3>";

    // Test statistics API
    $apiUrl = "http://" . $_SERVER['HTTP_HOST'] . "/jirani/admin/api/product_actions.php?action=statistics";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);

    try {
        $response = @file_get_contents($apiUrl, false, $context);
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['success'])) {
                echo "<div class='pass'>✓ Statistics API responding</div>";
                $results['api_statistics'] = 'PASS';
            } else {
                echo "<div class='fail'>✗ Statistics API invalid response</div>";
                $results['api_statistics'] = 'FAIL';
            }
        } else {
            echo "<div class='fail'>✗ Statistics API not accessible</div>";
            $results['api_statistics'] = 'FAIL';
        }
    } catch (Exception $e) {
        echo "<div class='fail'>✗ Statistics API error: " . $e->getMessage() . "</div>";
        $results['api_statistics'] = 'FAIL';
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
        echo "<div class='pass'><strong>✓ All tests passed! The product system is ready for production.</strong></div>";
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

    // Recommendations
    if ($failedTests > 0) {
        echo "<div class='test info'>";
        echo "<h3>Recommendations</h3>";
        echo "<ul>";
        if ($results['database'] === 'FAIL') {
            echo "<li>Check database configuration in config.php</li>";
        }
        if ($results['tables'] === 'FAIL') {
            echo "<li>Run the missing_tables.sql script to create required tables</li>";
        }
        if ($results['api_statistics'] === 'FAIL') {
            echo "<li>Check if the API endpoint file exists and is accessible</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    ?>

    <div class='test info'>
        <h3>Next Steps</h3>
        <ul>
            <li>If all tests pass, the products system is ready for use</li>
            <li>Access the main products page at: <a href="products.php">admin/products.php</a></li>
            <li>Test the API endpoints for AJAX functionality</li>
            <li>Verify that all modals and interactive features work correctly</li>
        </ul>
    </div>
</body>

</html>