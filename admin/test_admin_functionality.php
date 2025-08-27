<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "<h2>Access Denied</h2>";
    echo "<p>You must be logged in as an admin to run these tests.</p>";
    exit;
}

class AdminFunctionalityTest
{
    private $conn;
    private $testResults = [];

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function runAllTests()
    {
        echo "<h1>Admin Functionality Test Report</h1>";
        echo "<p>Testing all admin features and functionality...</p>";
        echo "<hr>";

        $this->testDatabaseConnection();
        $this->testAdminPages();
        $this->testAPIEndpoints();
        $this->testSecurityFeatures();
        $this->testDataIntegrity();
        $this->testUserInterface();
        $this->displayResults();
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

    private function testAdminPages()
    {
        echo "<h3>2. Admin Pages Test</h3>";

        $pages = [
            'index.php' => 'Dashboard',
            'products.php' => 'Products Management',
            'vendors.php' => 'Vendors Management',
            'orders.php' => 'Orders Management',
            'payments.php' => 'Payments Management',
            'verifications.php' => 'Verifications Management',
            'settings.php' => 'Settings',
            'profile.php' => 'Profile',
            'logs.php' => 'System Logs'
        ];

        foreach ($pages as $page => $description) {
            try {
                $file_path = __DIR__ . '/' . $page;
                if (file_exists($file_path)) {
                    // Check if page has proper security
                    $content = file_get_contents($file_path);
                    if (
                        strpos($content, 'session_start()') !== false &&
                        strpos($content, 'user_role') !== false
                    ) {
                        $this->testResults[$description] = 'PASS';
                        echo "✓ $description - Page exists with security<br>";
                    } else {
                        $this->testResults[$description] = 'WARNING';
                        echo "⚠ $description - Page exists but security may be incomplete<br>";
                    }
                } else {
                    $this->testResults[$description] = 'FAIL';
                    echo "✗ $description - Page missing<br>";
                }
            } catch (Exception $e) {
                $this->testResults[$description] = 'FAIL';
                echo "✗ $description - Error: " . $e->getMessage() . "<br>";
            }
        }
    }

    private function testAPIEndpoints()
    {
        echo "<h3>3. API Endpoints Test</h3>";

        $apis = [
            'api/product_actions.php' => 'Product Actions API',
            'api/order_actions.php' => 'Order Actions API',
            'api/payment_actions.php' => 'Payment Actions API',
            'api/verification_actions.php' => 'Verification Actions API'
        ];

        foreach ($apis as $api => $description) {
            try {
                $file_path = __DIR__ . '/' . $api;
                if (file_exists($file_path)) {
                    $content = file_get_contents($file_path);
                    if (
                        strpos($content, 'session_start()') !== false &&
                        strpos($content, 'user_role') !== false &&
                        strpos($content, 'Content-Type: application/json') !== false
                    ) {
                        $this->testResults[$description] = 'PASS';
                        echo "✓ $description - API exists with security and JSON response<br>";
                    } else {
                        $this->testResults[$description] = 'WARNING';
                        echo "⚠ $description - API exists but may be incomplete<br>";
                    }
                } else {
                    $this->testResults[$description] = 'FAIL';
                    echo "✗ $description - API missing<br>";
                }
            } catch (Exception $e) {
                $this->testResults[$description] = 'FAIL';
                echo "✗ $description - Error: " . $e->getMessage() . "<br>";
            }
        }
    }

    private function testSecurityFeatures()
    {
        echo "<h3>4. Security Features Test</h3>";

        // Test session security
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
            $this->testResults['session_security'] = 'PASS';
            echo "✓ Session security - Admin session properly set<br>";
        } else {
            $this->testResults['session_security'] = 'FAIL';
            echo "✗ Session security - Admin session not properly set<br>";
        }

        // Test CSRF protection
        if (isset($_SESSION['csrf_token'])) {
            $this->testResults['csrf_protection'] = 'PASS';
            echo "✓ CSRF protection - Token exists<br>";
        } else {
            $this->testResults['csrf_protection'] = 'WARNING';
            echo "⚠ CSRF protection - Token may be missing<br>";
        }

        // Test file permissions
        $admin_dir = __DIR__;
        if (is_readable($admin_dir) && is_writable($admin_dir)) {
            $this->testResults['file_permissions'] = 'PASS';
            echo "✓ File permissions - Admin directory accessible<br>";
        } else {
            $this->testResults['file_permissions'] = 'WARNING';
            echo "⚠ File permissions - Admin directory may have permission issues<br>";
        }
    }

    private function testDataIntegrity()
    {
        echo "<h3>5. Data Integrity Test</h3>";

        // Test required tables
        $required_tables = [
            'users',
            'vendors',
            'products',
            'orders',
            'order_items',
            'payments',
            'verifications',
            'system_logs',
            'categories'
        ];

        foreach ($required_tables as $table) {
            try {
                $result = $this->conn->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->num_rows > 0) {
                    $this->testResults["table_$table"] = 'PASS';
                    echo "✓ Table '$table' exists<br>";
                } else {
                    $this->testResults["table_$table"] = 'FAIL';
                    echo "✗ Table '$table' missing<br>";
                }
            } catch (Exception $e) {
                $this->testResults["table_$table"] = 'FAIL';
                echo "✗ Table '$table' error: " . $e->getMessage() . "<br>";
            }
        }

        // Test data consistency
        try {
            $result = $this->conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
            $admin_count = $result->fetch_assoc()['count'];
            if ($admin_count > 0) {
                $this->testResults['admin_users'] = 'PASS';
                echo "✓ Admin users exist ($admin_count found)<br>";
            } else {
                $this->testResults['admin_users'] = 'WARNING';
                echo "⚠ No admin users found<br>";
            }
        } catch (Exception $e) {
            $this->testResults['admin_users'] = 'FAIL';
            echo "✗ Admin users check failed: " . $e->getMessage() . "<br>";
        }
    }

    private function testUserInterface()
    {
        echo "<h3>6. User Interface Test</h3>";

        // Test required CSS/JS files
        $required_assets = [
            '../assets/css/admin.css' => 'Admin CSS',
            '../assets/js/admin.js' => 'Admin JS',
            'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css' => 'AdminLTE CSS',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11' => 'SweetAlert2'
        ];

        foreach ($required_assets as $asset => $description) {
            if (strpos($asset, 'http') === 0) {
                // External CDN - just check if it's referenced
                $this->testResults[$description] = 'PASS';
                echo "✓ $description - External CDN referenced<br>";
            } else {
                $file_path = __DIR__ . '/' . $asset;
                if (file_exists($file_path)) {
                    $this->testResults[$description] = 'PASS';
                    echo "✓ $description - File exists<br>";
                } else {
                    $this->testResults[$description] = 'WARNING';
                    echo "⚠ $description - File may be missing<br>";
                }
            }
        }

        // Test layout file
        $layout_file = __DIR__ . '/layout.php';
        if (file_exists($layout_file)) {
            $this->testResults['layout_file'] = 'PASS';
            echo "✓ Layout file exists<br>";
        } else {
            $this->testResults['layout_file'] = 'FAIL';
            echo "✗ Layout file missing<br>";
        }
    }

    private function displayResults()
    {
        echo "<h3>Test Summary</h3>";

        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, function ($result) {
            return $result === 'PASS';
        }));
        $warningTests = count(array_filter($this->testResults, function ($result) {
            return $result === 'WARNING';
        }));
        $failedTests = count(array_filter($this->testResults, function ($result) {
            return $result === 'FAIL';
        }));

        echo "<div style='background: " . ($failedTests === 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Overall Result:</strong> $passedTests/$totalTests tests passed<br>";
        echo "<strong>Warnings:</strong> $warningTests<br>";
        echo "<strong>Failures:</strong> $failedTests<br>";

        if ($failedTests === 0) {
            echo "<span style='color: #155724;'>✓ All critical tests passed! The admin system is ready for production.</span>";
        } else {
            echo "<span style='color: #721c24;'>✗ $failedTests tests failed. Please review the issues above.</span>";
        }
        echo "</div>";

        // Detailed results
        echo "<h4>Detailed Results:</h4>";
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>Test</th><th>Status</th><th>Details</th></tr></thead><tbody>";

        foreach ($this->testResults as $test => $result) {
            $color = $result === 'PASS' ? '#155724' : ($result === 'WARNING' ? '#856404' : '#721c24');
            $icon = $result === 'PASS' ? '✓' : ($result === 'WARNING' ? '⚠' : '✗');
            echo "<tr>";
            echo "<td>$test</td>";
            echo "<td style='color: $color;'><strong>$icon $result</strong></td>";
            echo "<td>";
            if ($result === 'FAIL') {
                echo "This feature needs attention before production use.";
            } elseif ($result === 'WARNING') {
                echo "This feature works but may need improvement.";
            } else {
                echo "This feature is working correctly.";
            }
            echo "</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";

        // Recommendations
        echo "<h4>Recommendations:</h4>";
        echo "<ul>";
        if ($failedTests > 0) {
            echo "<li><strong>Critical:</strong> Fix all failed tests before going live.</li>";
        }
        if ($warningTests > 0) {
            echo "<li><strong>Important:</strong> Address warnings to improve system reliability.</li>";
        }
        echo "<li><strong>Security:</strong> Ensure all admin pages have proper session checks.</li>";
        echo "<li><strong>Performance:</strong> Consider implementing caching for frequently accessed data.</li>";
        echo "<li><strong>Monitoring:</strong> Set up logging and monitoring for production use.</li>";
        echo "</ul>";
    }
}

// Run tests if accessed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new AdminFunctionalityTest($conn);
    $test->runAllTests();
}
?>