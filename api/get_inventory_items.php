<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Remove the quantity > 0 filter to show ALL items
    $stmt = $pdo->query("
        SELECT 
            i.id, 
            i.item_name, 
            i.sku, 
            i.price, 
            i.quantity,
            i.supplier_id,
            i.reorder_level,
            c.category_name, 
            s.supplier_name,
            CASE 
                WHEN i.quantity <= 0 THEN 'out_of_stock'
                WHEN i.quantity <= i.reorder_level THEN 'low_stock'
                ELSE 'in_stock'
            END as stock_status
        FROM inventory_items i
        LEFT JOIN categories c ON i.category_id = c.id
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        WHERE i.deleted_at IS NULL
        ORDER BY 
            CASE 
                WHEN i.quantity <= 0 THEN 0  -- Show out of stock first (need to reorder)
                WHEN i.quantity <= i.reorder_level THEN 1  -- Then low stock
                ELSE 2  -- Then in stock
            END,
            i.item_name
    ");
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Items loaded: " . count($items));
    error_log("Out of stock items: " . count(array_filter($items, function($item) {
        return $item['quantity'] <= 0;
    })));
    
    header('Content-Type: application/json');
    echo json_encode($items);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>