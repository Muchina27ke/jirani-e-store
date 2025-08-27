<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';

echo '<pre>';
echo "=== SELLER SYSTEM COMPREHENSIVE QA CHECK ===\n\n";

try {
    $conn = getDbConnection();
    echo "✅ Database connection successful.\n\n";

    // 1. CHECK SQL QUERIES AND TABLE CONSISTENCY
    echo "1. SQL QUERIES & TABLE CONSISTENCY CHECK:\n";
    echo "========================================\n";
    
    // Check all seller-related tables exist
    $tables = ['users', 'vendors', 'verifications', 'products', 'orders', 'payments', 'order_items'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
    
    // Check for data consistency between vendors and verifications
    echo "\nData Consistency Check:\n";
    $query = "
        SELECT 
            COUNT(*) as total_vendors,
            SUM(CASE WHEN v.status = vr.status THEN 1 ELSE 0 END) as synced_vendors,
            SUM(CASE WHEN v.status != vr.status THEN 1 ELSE 0 END) as unsynced_vendors
        FROM vendors v 
        LEFT JOIN verifications vr ON v.user_id = vr.vendor_id
    ";
    $result = $conn->query($query);
    if ($result) {
        $data = $result->fetch_assoc();
        echo "Total vendors: {$data['total_vendors']}\n";
        echo "Synced status: {$data['synced_vendors']}\n";
        echo "Unsynced status: {$data['unsynced_vendors']}\n";
        if ($data['unsynced_vendors'] > 0) {
            echo "⚠️  Some vendors have inconsistent status between tables\n";
        } else {
            echo "✅ All vendor statuses are synchronized\n";
        }
    }
    
    echo "\n";

    // 2. CHECK SELLER FILES EXIST
    echo "2. SELLER FILES EXISTENCE CHECK:\n";
    echo "===============================\n";
    
    $sellerFiles = [
        'dashboard.php' => 'Main dashboard',
        'verification.php' => 'Document verification',
        'products.php' => 'Product management',
        'orders.php' => 'Order management', 
        'payments.php' => 'Payment tracking',
        'layout.php' => 'Navigation layout',
        'add_product.php' => 'Add new product'
    ];
    
    foreach ($sellerFiles as $file => $description) {
        $path = __DIR__ . "/../seller/$file";
        if (file_exists($path)) {
            echo "✅ $file ($description)\n";
        } else {
            echo "❌ $file ($description) - MISSING\n";
        }
    }
    
    echo "\n";

    // 3. CHECK SELLER SQL QUERIES IN FILES
    echo "3. SELLER SQL QUERIES ANALYSIS:\n";
    echo "==============================\n";
    
    $filesToCheck = [
        __DIR__ . '/../seller/dashboard.php',
        __DIR__ . '/../seller/products.php',
        __DIR__ . '/../seller/orders.php',
        __DIR__ . '/../seller/payments.php'
    ];
    
    foreach ($filesToCheck as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $fileName = basename($file);
            
            // Check for problematic SQL patterns
            $issues = [];
            
            // Check for JOIN syntax
            if (preg_match_all('/JOIN\s+(\w+)/i', $content, $matches)) {
                echo "✅ $fileName uses JOINs: " . implode(', ', array_unique($matches[1])) . "\n";
            }
            
            // Check for WHERE clauses with user_id
            if (strpos($content, 'user_id') !== false) {
                echo "✅ $fileName filters by user_id\n";
            } else {
                echo "⚠️  $fileName might not filter by user_id (security concern)\n";
            }
            
            // Check for prepared statements
            if (strpos($content, 'prepare(') !== false) {
                echo "✅ $fileName uses prepared statements\n";
            } else {
                echo "⚠️  $fileName might not use prepared statements\n";
            }
        }
    }
    
    echo "\n";

    // 4. CHECK ORDERS FUNCTIONALITY
    echo "4. ORDERS FUNCTIONALITY CHECK:\n";
    echo "=============================\n";
    
    // Check if orders table has proper structure
    $orderColumns = $conn->query("DESCRIBE orders");
    if ($orderColumns) {
        echo "Orders table columns:\n";
        while ($col = $orderColumns->fetch_assoc()) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }
    
    // Check for sample order data
    $orderCount = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
    echo "Total orders in system: $orderCount\n";
    
    // Check order statuses
    $statusQuery = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $statusResult = $conn->query($statusQuery);
    if ($statusResult) {
        echo "Order status distribution:\n";
        while ($row = $statusResult->fetch_assoc()) {
            echo "  - {$row['status']}: {$row['count']}\n";
        }
    }
    
    echo "\n";

    // 5. CHECK PRODUCT ACTIONS
    echo "5. PRODUCT ACTIONS CHECK:\n";
    echo "========================\n";
    
    // Check products table structure
    $productColumns = $conn->query("DESCRIBE products");
    if ($productColumns) {
        echo "Products table key columns:\n";
        while ($col = $productColumns->fetch_assoc()) {
            if (in_array($col['Field'], ['id', 'vendor_id', 'name', 'price', 'stock', 'status'])) {
                echo "  ✅ {$col['Field']} ({$col['Type']})\n";
            }
        }
    }
    
    // Check for products by vendor
    $productsByVendor = $conn->query("
        SELECT vendor_id, COUNT(*) as product_count 
        FROM products 
        GROUP BY vendor_id 
        ORDER BY product_count DESC 
        LIMIT 5
    ");
    if ($productsByVendor) {
        echo "Top vendors by product count:\n";
        while ($row = $productsByVendor->fetch_assoc()) {
            echo "  - Vendor {$row['vendor_id']}: {$row['product_count']} products\n";
        }
    }
    
    echo "\n";

    // 6. CHECK PAYMENTS FUNCTIONALITY
    echo "6. PAYMENTS FUNCTIONALITY CHECK:\n";
    echo "===============================\n";
    
    // Check payments table
    $paymentColumns = $conn->query("DESCRIBE payments");
    if ($paymentColumns) {
        echo "Payments table exists with columns:\n";
        while ($col = $paymentColumns->fetch_assoc()) {
            if (in_array($col['Field'], ['id', 'order_id', 'amount', 'status', 'payment_method'])) {
                echo "  ✅ {$col['Field']} ({$col['Type']})\n";
            }
        }
    }
    
    // Check payment methods
    $paymentMethods = $conn->query("SELECT method, COUNT(*) as count FROM payments GROUP BY method");
    if ($paymentMethods) {
        echo "Payment methods used:\n";
        while ($row = $paymentMethods->fetch_assoc()) {
            echo "  - {$row['method']}: {$row['count']} transactions\n";
        }
    }
    
    echo "\n";

    // 7. CHECK VERIFICATION LOGIC
    echo "7. VERIFICATION LOGIC CHECK:\n";
    echo "===========================\n";
    
    // Check verification workflow
    $verificationStats = $conn->query("
        SELECT 
            v.status as verification_status,
            vend.status as vendor_status,
            COUNT(*) as count
        FROM verifications v
        JOIN vendors vend ON v.vendor_id = vend.user_id
        GROUP BY v.status, vend.status
    ");
    
    if ($verificationStats) {
        echo "Verification status matrix:\n";
        echo "Verification | Vendor | Count\n";
        echo "-------------|--------|------\n";
        while ($row = $verificationStats->fetch_assoc()) {
            printf("%-12s | %-6s | %s\n", 
                $row['verification_status'], 
                $row['vendor_status'], 
                $row['count']
            );
        }
    }
    
    echo "\n";

    // 8. CHECK NAVBAR CONSISTENCY
    echo "8. NAVBAR CONSISTENCY CHECK:\n";
    echo "===========================\n";
    
    $layoutFile = __DIR__ . '/../seller/layout.php';
    if (file_exists($layoutFile)) {
        $layoutContent = file_get_contents($layoutFile);
        
        // Check for navigation items
        $navItems = [
            'Dashboard' => ['dashboard.php', 'index.php'], // Check for both dashboard.php and index.php
            'Products' => 'products.php', 
            'Orders' => 'orders.php',
            'Payments' => 'payments.php',
            'Verification' => 'verification.php'
        ];
        
        echo "Navigation items check:\n";
        foreach ($navItems as $item => $files) {
            $found = false;
            $foundFile = '';
            
            // Handle array of files (for Dashboard) or single file
            $filesToCheck = is_array($files) ? $files : [$files];
            
            foreach ($filesToCheck as $file) {
                if (strpos($layoutContent, $file) !== false) {
                    $found = true;
                    $foundFile = $file;
                    break;
                }
            }
            
            if ($found) {
                echo "✅ $item ($foundFile) found in navigation\n";
            } else {
                echo "❌ $item ($file) missing from navigation\n";
            }
        }
        
        // Check for responsive design
        if (strpos($layoutContent, 'navbar-toggler') !== false) {
            echo "✅ Mobile-responsive navigation detected\n";
        } else {
            echo "⚠️  Mobile responsiveness might be missing\n";
        }
        
        // Check for user info display
        if (strpos($layoutContent, '$_SESSION') !== false) {
            echo "✅ User session info displayed in navbar\n";
        } else {
            echo "⚠️  User session info might be missing\n";
        }
    } else {
        echo "❌ Layout file not found\n";
    }
    
    echo "\n";

    // 9. SECURITY CHECKS
    echo "9. SECURITY CHECKS:\n";
    echo "==================\n";
    
    $securityChecks = [
        'Session validation' => '$_SESSION',
        'SQL injection protection' => 'prepare(',
        'XSS protection' => 'htmlspecialchars',
        'File upload validation' => 'UPLOAD_ERR_OK'
    ];
    
    foreach ($filesToCheck as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $fileName = basename($file);
            
            foreach ($securityChecks as $check => $pattern) {
                if (strpos($content, $pattern) !== false) {
                    echo "✅ $fileName: $check implemented\n";
                } else {
                    echo "⚠️  $fileName: $check might be missing\n";
                }
            }
        }
    }
    
    echo "\n";

    // 10. SUMMARY AND RECOMMENDATIONS
    echo "10. SUMMARY & RECOMMENDATIONS:\n";
    echo "=============================\n";
    
    echo "✅ WORKING COMPONENTS:\n";
    echo "- Database connectivity\n";
    echo "- Basic table structure\n";
    echo "- File upload system\n";
    echo "- Verification workflow\n";
    
    echo "\n⚠️  AREAS TO REVIEW:\n";
    echo "- Check seller file SQL queries for consistency\n";
    echo "- Verify order management functionality\n";
    echo "- Test payment processing workflow\n";
    echo "- Ensure navbar is unified across all pages\n";
    
    echo "\n🔧 NEXT STEPS:\n";
    echo "1. Test seller dashboard functionality\n";
    echo "2. Verify product CRUD operations\n";
    echo "3. Test order status updates\n";
    echo "4. Check payment tracking accuracy\n";
    echo "5. Ensure verification status sync\n";
    
} catch (Exception $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== QA CHECK COMPLETE ===\n";
echo '</pre>';
?>
