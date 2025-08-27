<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Product.php';

// Test script for products functionality
class ProductFunctionalityTest
{
    private $conn;
    private $productObj;
    private $testResults = [];

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->productObj = new Product($conn);
    }

    public function runAllTests()
    {
        echo "<h2>Product Functionality Test Results</h2>";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

        $this->testDatabaseConnection();
        $this->testProductClassMethods();
        $this->testAPIEndpoints();
        $this->testDatabaseTables();
        $this->testStatistics();
        $this->testFilters();

        $this->displayResults();
        echo "</div>";
    }

    private function testDatabaseConnection()
    {
        echo "<h3>1. Database Connection Test</h3>";

        try {
            $result = $this->conn->query("SELECT 1");
            if ($result) {
                $this->testResults['database_connection'] = 'PASS';
                echo "✓ Database connection successful<br>";
            } else {
                $this->testResults['database_connection'] = 'FAIL';
                echo "✗ Database connection failed<br>";
            }
        } catch (Exception $e) {
            $this->testResults['database_connection'] = 'FAIL';
            echo "✗ Database connection error: " . $e->getMessage() . "<br>";
        }
    }

    private function testProductClassMethods()
    {
        echo "<h3>2. Product Class Methods Test</h3>";

        // Test get_all_products
        try {
            $products = $this->productObj->get_all_products();
            if (is_array($products)) {
                $this->testResults['get_all_products'] = 'PASS';
                echo "✓ get_all_products() - Found " . count($products) . " products<br>";
            } else {
                $this->testResults['get_all_products'] = 'FAIL';
                echo "✗ get_all_products() - Invalid return type<br>";
            }
        } catch (Exception $e) {
            $this->testResults['get_all_products'] = 'FAIL';
            echo "✗ get_all_products() - Error: " . $e->getMessage() . "<br>";
        }

        // Test get_product_statistics
        try {
            $stats = $this->productObj->get_product_statistics();
            if (is_array($stats) && isset($stats['total_products'])) {
                $this->testResults['get_product_statistics'] = 'PASS';
                echo "✓ get_product_statistics() - Statistics loaded<br>";
            } else {
                $this->testResults['get_product_statistics'] = 'FAIL';
                echo "✗ get_product_statistics() - Invalid statistics format<br>";
            }
        } catch (Exception $e) {
            $this->testResults['get_product_statistics'] = 'FAIL';
            echo "✗ get_product_statistics() - Error: " . $e->getMessage() . "<br>";
        }

        // Test get_products_with_filters
        try {
            $filters = ['status' => 'active'];
            $products = $this->productObj->get_products_with_filters($filters);
            if (is_array($products)) {
                $this->testResults['get_products_with_filters'] = 'PASS';
                echo "✓ get_products_with_filters() - Filtered " . count($products) . " active products<br>";
            } else {
                $this->testResults['get_products_with_filters'] = 'FAIL';
                echo "✗ get_products_with_filters() - Invalid return type<br>";
            }
        } catch (Exception $e) {
            $this->testResults['get_products_with_filters'] = 'FAIL';
            echo "✗ get_products_with_filters() - Error: " . $e->getMessage() . "<br>";
        }
    }

    private function testAPIEndpoints()
    {
        echo "<h3>3. API Endpoints Test</h3>";

        $endpoints = [
            'api/product_actions.php?action=statistics' => 'Statistics API',
            'api/product_actions.php?action=export' => 'Export API',
        ];

        foreach ($endpoints as $endpoint => $description) {
            try {
                $url = "http://" . $_SERVER['HTTP_HOST'] . "/jirani/admin/" . $endpoint;
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => 'Content-Type: application/json'
                    ]
                ]);

                $response = file_get_contents($url, false, $context);
                if ($response !== false) {
                    $data = json_decode($response, true);
                    if ($data && isset($data['success'])) {
                        $this->testResults[$description] = 'PASS';
                        echo "✓ $description - API responding correctly<br>";
                    } else {
                        $this->testResults[$description] = 'FAIL';
                        echo "✗ $description - Invalid response format<br>";
                    }
                } else {
                    $this->testResults[$description] = 'FAIL';
                    echo "✗ $description - API not accessible<br>";
                }
            } catch (Exception $e) {
                $this->testResults[$description] = 'FAIL';
                echo "✗ $description - Error: " . $e->getMessage() . "<br>";
            }
        }
    }

    private function testDatabaseTables()
    {
        echo "<h3>4. Database Tables Test</h3>";

        $tables = [
            'products' => 'Products table',
            'product_logs' => 'Product logs table',
            'inventory_logs' => 'Inventory logs table',
            'reviews' => 'Reviews table',
            'system_logs' => 'System logs table',
            'login_history' => 'Login history table'
        ];

        foreach ($tables as $table => $description) {
            try {
                $result = $this->conn->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->num_rows > 0) {
                    $this->testResults[$description] = 'PASS';
                    echo "✓ $description - Table exists<br>";

                    // Check table structure
                    $columns = $this->conn->query("DESCRIBE $table");
                    if ($columns) {
                        echo "  - Columns: " . $columns->num_rows . "<br>";
                    }
                } else {
                    $this->testResults[$description] = 'FAIL';
                    echo "✗ $description - Table missing<br>";
                }
            } catch (Exception $e) {
                $this->testResults[$description] = 'FAIL';
                echo "✗ $description - Error: " . $e->getMessage() . "<br>";
            }
        }
    }

    private function testStatistics()
    {
        echo "<h3>5. Statistics Test</h3>";

        try {
            $stats = $this->productObj->get_product_statistics();

            $requiredFields = ['total_products', 'active_products', 'inactive_products', 'out_of_stock', 'inventory_value'];
            $allFieldsPresent = true;

            foreach ($requiredFields as $field) {
                if (!isset($stats[$field])) {
                    $allFieldsPresent = false;
                    break;
                }
            }

            if ($allFieldsPresent) {
                $this->testResults['statistics_fields'] = 'PASS';
                echo "✓ Statistics fields - All required fields present<br>";
                echo "  - Total Products: " . $stats['total_products'] . "<br>";
                echo "  - Active Products: " . $stats['active_products'] . "<br>";
                echo "  - Out of Stock: " . $stats['out_of_stock'] . "<br>";
                echo "  - Inventory Value: KSh " . number_format($stats['inventory_value'], 2) . "<br>";
            } else {
                $this->testResults['statistics_fields'] = 'FAIL';
                echo "✗ Statistics fields - Missing required fields<br>";
            }
        } catch (Exception $e) {
            $this->testResults['statistics_fields'] = 'FAIL';
            echo "✗ Statistics fields - Error: " . $e->getMessage() . "<br>";
        }
    }

    private function testFilters()
    {
        echo "<h3>6. Filter Functionality Test</h3>";

        $filterTests = [
            ['status' => 'active'] => 'Active products filter',
            ['vendor_id' => 1] => 'Vendor filter',
            ['price_min' => 100] => 'Price minimum filter',
            ['price_max' => 1000] => 'Price maximum filter',
            ['status' => 'active', 'price_min' => 100] => 'Combined filters'
        ];

        foreach ($filterTests as $filters => $description) {
            try {
                $products = $this->productObj->get_products_with_filters($filters);
                if (is_array($products)) {
                    $this->testResults[$description] = 'PASS';
                    echo "✓ $description - " . count($products) . " products found<br>";
                } else {
                    $this->testResults[$description] = 'FAIL';
                    echo "✗ $description - Invalid result<br>";
                }
            } catch (Exception $e) {
                $this->testResults[$description] = 'FAIL';
                echo "✗ $description - Error: " . $e->getMessage() . "<br>";
            }
        }
    }

    private function displayResults()
    {
        echo "<h3>Test Summary</h3>";

        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, function ($result) {
            return $result === 'PASS';
        }));
        $failedTests = $totalTests - $passedTests;

        echo "<div style='background: " . ($failedTests === 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Overall Result:</strong> $passedTests/$totalTests tests passed<br>";

        if ($failedTests === 0) {
            echo "<span style='color: #155724;'>✓ All tests passed! The product system is ready for production.</span>";
        } else {
            echo "<span style='color: #721c24;'>✗ $failedTests tests failed. Please review the issues above.</span>";
        }
        echo "</div>";

        // Detailed results
        echo "<h4>Detailed Results:</h4>";
        foreach ($this->testResults as $test => $result) {
            $color = $result === 'PASS' ? '#155724' : '#721c24';
            $icon = $result === 'PASS' ? '✓' : '✗';
            echo "<div style='color: $color; margin: 2px 0;'>$icon $test: $result</div>";
        }
    }
}

// Run tests if accessed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    // Check if user is admin
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        echo "<h2>Access Denied</h2>";
        echo "<p>You must be logged in as an admin to run these tests.</p>";
        exit;
    }

    $test = new ProductFunctionalityTest($conn);
    $test->runAllTests();
}
?>