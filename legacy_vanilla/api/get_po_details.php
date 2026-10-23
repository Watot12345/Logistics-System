<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;

if (!$po_id) {
    echo json_encode(['success' => false, 'error' => 'PO ID required']);
    exit();
}

try {
    // Get PO details with supplier info
    $stmt = $pdo->prepare("
        SELECT 
            po.*,
            s.supplier_name,
            s.contact_person as supplier_contact,
            s.email as supplier_email,
            u.full_name as requester
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        LEFT JOIN users u ON po.created_by = u.id
        WHERE po.id = ?
    ");
    $stmt->execute([$po_id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$po) {
        echo json_encode(['success' => false, 'error' => 'PO not found']);
        exit();
    }
    
    // Get PO items
    $stmt = $pdo->prepare("
        SELECT 
            poi.*,
            i.item_name,
            i.sku
        FROM purchase_order_items poi
        JOIN inventory_items i ON poi.item_id = i.id
        WHERE poi.po_id = ?
    ");
    $stmt->execute([$po_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $po['items'] = $items;
    
    echo json_encode([
        'success' => true,
        'po' => $po
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>