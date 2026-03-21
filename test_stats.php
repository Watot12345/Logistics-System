<?php
// Enable error reporting to see what's causing 500
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Try to include db.php with error handling
if (!file_exists('config/db.php')) {
    die('<h1>Error</h1><p>config/db.php not found. Current directory: ' . getcwd() . '</p>');
}

try {
    require_once 'config/db.php';
} catch (Exception $e) {
    die('<h1>Error loading database</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
}

// Force output
header('Content-Type: text/html; charset=utf-8');
ob_start();

echo "<h1>Fleet Stats Diagnostic</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

try {
    // Test 1: Check database connection
    echo "<h2>1. Database Connection</h2>";
    if (isset($pdo)) {
        echo "✅ PDO connection exists<br>";
        try {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "✅ Exception mode set<br>";
        } catch (Exception $e) {
            echo "⚠️ Could not set exception mode: " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    } else {
        echo "❌ No PDO connection<br>";
        die('<p>Database connection failed. Check config/db.php</p>');
    }
    
    // Test 2: Count total vehicles
    echo "<h2>2. Total Vehicles</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE asset_type = 'vehicle'");
    $total = $stmt->fetchColumn();
    echo "Total vehicles in database: <strong>$total</strong><br>";
    
    // Test 3: Get all vehicles with status
    echo "<h2>3. Vehicle Details</h2>";
    $stmt = $pdo->query("
        SELECT a.*, 
               s.shipment_status,
               ds.status as dispatch_status,
               CASE 
                   WHEN s.shipment_id IS NOT NULL AND s.shipment_status IN ('in_transit', 'pending') THEN 1
                   WHEN ds.id IS NOT NULL AND ds.status IN ('in-progress', 'scheduled', 'delivered', 'awaiting_verification') THEN 1
                   ELSE 0
               END as is_in_use
        FROM assets a
        LEFT JOIN shipments s ON a.id = s.vehicle_id AND s.shipment_status IN ('in_transit', 'pending')
        LEFT JOIN dispatch_schedule ds ON a.id = ds.vehicle_id 
            AND ds.status IN ('in-progress', 'scheduled', 'delivered', 'awaiting_verification')
        WHERE a.asset_type = 'vehicle'
        GROUP BY a.id
    ");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Vehicles fetched: <strong>" . count($vehicles) . "</strong><br>";
    
    // Test 4: Get maintenance alerts
    echo "<h2>4. Maintenance Alerts</h2>";
    $stmt = $pdo->query("
        SELECT * FROM maintenance_alerts 
        WHERE status IN ('pending', 'in_progress')
    ");
    $maintenance_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Active maintenance alerts: <strong>" . count($maintenance_alerts) . "</strong><br>";
    
    // Test 5: Calculate stats
    echo "<h2>5. Stats Calculation</h2>";
    $available = 0;
    $maintenance = 0;
    $in_use = 0;
    
    foreach ($vehicles as $vehicle) {
        $has_maintenance = false;
        foreach ($maintenance_alerts as $alert) {
            if ($alert['asset_name'] == $vehicle['asset_name']) {
                $has_maintenance = true;
                break;
            }
        }
        
        if ($has_maintenance) {
            $maintenance++;
            echo "- {$vehicle['asset_name']}: <span style='color: red;'>MAINTENANCE</span><br>";
        } else if ($vehicle['is_in_use']) {
            $in_use++;
            echo "- {$vehicle['asset_name']}: <span style='color: orange;'>IN USE</span><br>";
        } else {
            $available++;
            echo "- {$vehicle['asset_name']}: <span style='color: green;'>AVAILABLE</span><br>";
        }
    }
    
    echo "<h2>6. Final Stats</h2>";
    echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 8px; border: 2px solid #3b82f6;'>";
    echo "<p style='font-size: 18px; margin: 5px 0;'><strong>Total Vehicles:</strong> $total</p>";
    echo "<p style='font-size: 18px; margin: 5px 0; color: green;'><strong>Available:</strong> $available</p>";
    echo "<p style='font-size: 18px; margin: 5px 0; color: red;'><strong>Maintenance:</strong> $maintenance</p>";
    echo "<p style='font-size: 18px; margin: 5px 0; color: orange;'><strong>In Use:</strong> $in_use</p>";
    echo "</div>";
    
    echo "<h2>7. Verification</h2>";
    $sum = $available + $maintenance + $in_use;
    if ($sum == $total) {
        echo "<p style='color: green; font-size: 20px;'>✅ Stats add up correctly!</p>";
    } else {
        echo "<p style='color: red; font-size: 20px;'>❌ Stats don't match! Sum: $sum, Total: $total</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERROR</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

ob_end_flush();
?>
