<?php
// api/get_inventory_items.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT i.id, i.item_name, i.sku, i.price, i.quantity, 
               c.category_name, s.supplier_name
        FROM inventory_items i
        LEFT JOIN categories c ON i.category_id = c.id
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        WHERE i.quantity > 0
        ORDER BY i.item_name
    ");
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($items);
    
} catch(PDOException $e) {
    error_log("Error in get_inventory_items: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>