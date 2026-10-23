<?php
// Simple diagnostic - no dependencies
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Simple Diagnostic</title></head><body>";
echo "<h1>Simple Diagnostic</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Directory: " . getcwd() . "</p>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Check if config file exists
echo "<h2>File Check</h2>";
$config_path = __DIR__ . '/config/db.php';
echo "<p>Looking for: $config_path</p>";
if (file_exists($config_path)) {
    echo "<p style='color: green;'>✅ config/db.php exists</p>";
} else {
    echo "<p style='color: red;'>❌ config/db.php NOT found</p>";
    echo "<p>Files in current directory:</p><ul>";
    foreach (scandir(__DIR__) as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}

// Try to connect to database
echo "<h2>Database Connection Test</h2>";
try {
    if (file_exists($config_path)) {
        require_once $config_path;
        
        if (isset($pdo)) {
            echo "<p style='color: green;'>✅ PDO connection established</p>";
            
            // Test query
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE asset_type = 'vehicle'");
            $total = $stmt->fetchColumn();
            echo "<p style='color: green;'>✅ Query successful: <strong>$total vehicles</strong> found</p>";
            
            // Get vehicle details
            $stmt = $pdo->query("SELECT asset_name, asset_status FROM assets WHERE asset_type = 'vehicle' LIMIT 5");
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample Vehicles:</h3><ul>";
            foreach ($vehicles as $v) {
                echo "<li>{$v['asset_name']} - Status: {$v['asset_status']}</li>";
            }
            echo "</ul>";
            
        } else {
            echo "<p style='color: red;'>❌ PDO variable not set after including db.php</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><p><a href='modules/fleet.php'>Go to Fleet Page</a></p>";
echo "</body></html>";
?>
