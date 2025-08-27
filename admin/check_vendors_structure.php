<?php
require_once __DIR__ . '/../config/config.php';

echo "<h2>Vendors Table Structure Check</h2>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";

try {
    // Check if vendors table exists
    echo "<h3>1. Checking if vendors table exists...</h3>";
    $tableExists = $conn->query("SHOW TABLES LIKE 'vendors'");
    if ($tableExists->num_rows > 0) {
        echo "✓ Vendors table exists<br>";

        // Get table structure
        echo "<h3>2. Current vendors table structure:</h3>";
        $structure = $conn->query("DESCRIBE vendors");
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Check current data
        echo "<h3>3. Current vendors data:</h3>";
        $vendors = $conn->query("SELECT * FROM vendors LIMIT 5");
        if ($vendors->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            $first = true;
            while ($row = $vendors->fetch_assoc()) {
                if ($first) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        echo "<th>$key</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No vendors found in table<br>";
        }

    } else {
        echo "✗ Vendors table does not exist<br>";
    }

    // Check users table for reference
    echo "<h3>4. Users table structure (for reference):</h3>";
    $usersStructure = $conn->query("DESCRIBE users");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $usersStructure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check if we have users to reference
    echo "<h3>5. Available users for vendors:</h3>";
    $users = $conn->query("SELECT id, name, email FROM users LIMIT 5");
    if ($users->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        while ($row = $users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No users found<br>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>✗ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</div>";

// Navigation
echo "<div style='margin: 20px 0;'>";
echo "<a href='fix_database.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Run Database Fix</a>";
echo "<a href='test_add_product.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Test Add Product</a>";
echo "</div>";
?>