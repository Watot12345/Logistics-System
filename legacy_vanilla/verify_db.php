<?php
// Database verification script for deployment
require_once 'config/db.php';

echo "<h2>Database Verification</h2>";

try {
    // Check if tables exist
    $tables = ['assets', 'shipments', 'dispatch_schedule', 'maintenance_alerts', 'users'];
    
    echo "<h3>Table Check:</h3>";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "<p>$table: " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "</p>";
        
        if ($exists) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<p>&nbsp;&nbsp;→ Rows: $count</p>";
        }
    }
    
    // Check vehicles specifically
    echo "<h3>Vehicle Data:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) FROM assets WHERE asset_type = 'vehicle'");
    $vehicle_count = $stmt->fetchColumn();
    echo "<p>Total vehicles: $vehicle_count</p>";
    
    if ($vehicle_count > 0) {
        $stmt = $pdo->query("SELECT asset_name, status FROM assets WHERE asset_type = 'vehicle' LIMIT 5");
        echo "<p>Sample vehicles:</p><ul>";
        while ($row = $stmt->fetch()) {
            echo "<li>{$row['asset_name']} - {$row['status']}</li>";
        }
        echo "</ul>";
    }
    
    echo "<p style='color: green;'><strong>Database connection successful!</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Error: " . $e->getMessage() . "</strong></p>";
}
?>
