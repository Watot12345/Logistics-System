<?php
// api/receive_po.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $po_id = $data['po_id'];
    $received_items = $data['items']; // [{item_id, quantity}]
    
    $pdo->beginTransaction();
    
    foreach ($received_items as $item) {
        // 1. Add to receiving_history
        $stmt = $pdo->prepare("
            INSERT INTO receiving_history (po_id, item_id, quantity_received, received_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$po_id, $item['item_id'], $item['quantity'], $_SESSION['user_id']]);
        
        // 2. Update inventory stock
        $stmt = $pdo->prepare("
            UPDATE inventory_items 
            SET quantity = quantity + ? 
            WHERE id = ?
        ");
        $stmt->execute([$item['quantity'], $item['item_id']]);
        
        // 3. Record stock movement
        $stmt = $pdo->prepare("
            INSERT INTO stock_movements (item_id, movement_type, quantity_change, notes)
            VALUES (?, 'in', ?, ?)
        ");
        $notes = "Received from PO #{$po_id}";
        $stmt->execute([$item['item_id'], $item['quantity'], $notes]);
        
        // 4. Update PO item received quantity
        $stmt = $pdo->prepare("
            UPDATE purchase_order_items 
            SET received_quantity = received_quantity + ?,
                status = CASE 
                    WHEN received_quantity + ? >= quantity THEN 'received'
                    ELSE 'partial'
                END
            WHERE po_id = ? AND item_id = ?
        ");
        $stmt->execute([$item['quantity'], $item['quantity'], $po_id, $item['item_id']]);
    }
    
    // 5. Check if all items are received
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pending 
        FROM purchase_order_items 
        WHERE po_id = ? AND status != 'received'
    ");
    $stmt->execute([$po_id]);
    $result = $stmt->fetch();
    
    if ($result['pending'] == 0) {
        // All items received - complete the PO
        $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'completed' WHERE id = ?");
        $stmt->execute([$po_id]);
        
        // Log status change
        $stmt = $pdo->prepare("
            INSERT INTO po_status_history (po_id, old_status, new_status, changed_by, changed_at)
            VALUES (?, 'approved', 'completed', ?, NOW())
        ");
        $stmt->execute([$po_id, $_SESSION['user_id']]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>