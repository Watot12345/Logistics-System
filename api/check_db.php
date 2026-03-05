<?php
// api/check_db.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

echo "<h2>Database Structure Check</h2>";

// Check purchase_orders table
echo "<h3>1. purchase_orders table:</h3>";
$result = $pdo->query("SHOW TABLES LIKE 'purchase_orders'");
if ($result->rowCount() > 0) {
    echo "✅ Table exists<br>";
    $columns = $pdo->query("DESCRIBE purchase_orders")->fetchAll();
    echo "Columns: " . implode(', ', array_column($columns, 'Field')) . "<br>";
} else {
    echo "❌ Table does NOT exist!<br>";
}

// Check purchase_order_items table
echo "<h3>2. purchase_order_items table:</h3>";
$result = $pdo->query("SHOW TABLES LIKE 'purchase_order_items'");
if ($result->rowCount() > 0) {
    echo "✅ Table exists<br>";
    $columns = $pdo->query("DESCRIBE purchase_order_items")->fetchAll();
    echo "Columns: " . implode(', ', array_column($columns, 'Field')) . "<br>";
} else {
    echo "❌ Table does NOT exist!<br>";
}

// Check suppliers table
echo "<h3>3. suppliers table:</h3>";
$result = $pdo->query("SHOW TABLES LIKE 'suppliers'");
if ($result->rowCount() > 0) {
    echo "✅ Table exists<br>";
} else {
    echo "❌ Table does NOT exist!<br>";
}

// Check inventory_items table
echo "<h3>4. inventory_items table:</h3>";
$result = $pdo->query("SHOW TABLES LIKE 'inventory_items'");
if ($result->rowCount() > 0) {
    echo "✅ Table exists<br>";
} else {
    echo "❌ Table does NOT exist!<br>";
}
?>