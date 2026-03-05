<?php
// api/get_purchase_orders.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$status = isset($_GET['status']) ? $_GET['status'] : 'all';

try {
    $sql = "SELECT po.*, 
                   s.supplier_name,
                   s.contact_person,
                   s.email as supplier_email,
                   (SELECT COUNT(*) FROM purchase_order_items WHERE po_id = po.id) as item_count
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id";
    
    if ($status !== 'all') {
        $sql .= " WHERE po.status = :status";
    }
    
    $sql .= " ORDER BY po.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($orders);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>