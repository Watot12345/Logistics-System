<?php
// api/get_procurement_history.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT 
            po.id,
            po.po_number,
            po.total_amount,
            po.status,
            po.created_at,
            s.supplier_name,
            u.full_name as created_by_name,
            (SELECT COUNT(*) FROM purchase_order_items WHERE po_id = po.id) as item_count
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        JOIN users u ON po.created_by = u.id
        WHERE po.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY po.created_at DESC
        LIMIT 10
    ");
    
    $history = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($history);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>