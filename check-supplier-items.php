<?php
require_once 'inventory_functions.php';

$inventory = new InventoryManager();
$count = $inventory->getSupplierProductCount($_GET['supplier_id']);

echo json_encode([
    'has_items' => $count > 0,
    'item_count' => $count
]);