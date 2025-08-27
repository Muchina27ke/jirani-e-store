<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Geolocation.php';

// Simple verification script for delivery zones functionality
echo "<h2>Delivery Zones System Verification</h2>";

try {
    $db = getDbConnection();
    
    // Check if geofences table exists
    $result = $db->query("SHOW TABLES LIKE 'geofences'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Geofences table exists</p>";
        
        // Check table structure
        $columns = $db->query("DESCRIBE geofences");
        $columnNames = [];
        while ($row = $columns->fetch_assoc()) {
            $columnNames[] = $row['Field'];
        }
        
        $requiredColumns = ['id', 'vendor_id', 'zone_name', 'polygon_json', 'radius_km', 'delivery_fee', 'min_order_amount', 'is_active', 'created_at', 'updated_at'];
        $missingColumns = array_diff($requiredColumns, $columnNames);
        
        if (empty($missingColumns)) {
            echo "<p style='color: green;'>✓ All required columns exist</p>";
        } else {
            echo "<p style='color: red;'>✗ Missing columns: " . implode(', ', $missingColumns) . "</p>";
            echo "<p><strong>To fix:</strong> Run the SQL commands from database/update_geofences.sql</p>";
        }
        
        // Test Geolocation class
        $geolocation = new Geolocation($db);
        echo "<p style='color: green;'>✓ Geolocation class instantiated successfully</p>";
        
        // Test getting delivery zones for a vendor (this should not fail even if no zones exist)
        $testVendorId = 1; // Use a test vendor ID
        $zones = $geolocation->getVendorDeliveryZones($testVendorId);
        echo "<p style='color: green;'>✓ getVendorDeliveryZones method works (returned " . count($zones) . " zones)</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Geofences table does not exist</p>";
        echo "<p><strong>To fix:</strong> Import database/jirani.sql and database/additional.sql</p>";
    }
    
    // Check if delivery zones page exists
    $deliveryZonesPath = __DIR__ . '/../seller/delivery_zones.php';
    if (file_exists($deliveryZonesPath)) {
        echo "<p style='color: green;'>✓ Delivery zones page exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Delivery zones page missing</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li>If missing columns are detected, run: <code>database/update_geofences.sql</code></li>";
    echo "<li>Access delivery zones at: <a href='../seller/delivery_zones.php'>seller/delivery_zones.php</a></li>";
    echo "<li>Test creating, editing, and deleting delivery zones</li>";
    echo "<li>Verify map functionality works with Leaflet and drawing tools</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>
