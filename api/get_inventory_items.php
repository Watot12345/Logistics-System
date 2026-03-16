<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // REMOVED the comment that was causing the SQL error
    $stmt = $pdo->query("
        SELECT 
            i.id, 
            i.item_name, 
            i.sku, 
            i.price, 
            i.quantity,
            i.supplier_id,
            c.category_name, 
            s.supplier_name
        FROM inventory_items i
        LEFT JOIN categories c ON i.category_id = c.id
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        WHERE i.quantity > 0 AND i.deleted_at IS NULL
        ORDER BY i.item_name
    ");
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Check if supplier_id is present
    error_log("Items loaded: " . count($items));
    
    header('Content-Type: application/json');
    echo json_encode($items);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>