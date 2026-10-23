<?php
// api/receive_po_item.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo->beginTransaction();
    
    // Update PO item
    $stmt = $pdo->prepare("
        UPDATE purchase_order_items 
        SET received_quantity = received_quantity + ?,
            status = CASE 
                WHEN received_quantity + ? >= quantity THEN 'received'
                ELSE 'partial'
            END
        WHERE po_id = ? AND item_id = ?
    ");
    $stmt->execute([$data['quantity'], $data['quantity'], $data['po_id'], $data['item_id']]);
    
    // Get current inventory
    $stmt = $pdo->prepare("SELECT quantity FROM inventory_items WHERE id = ?");
    $stmt->execute([$data['item_id']]);
    $current = $stmt->fetch();
    
    // Update inventory
    $newQuantity = $current['quantity'] + $data['quantity'];
    $stmt = $pdo->prepare("UPDATE inventory_items SET quantity = ? WHERE id = ?");
    $stmt->execute([$newQuantity, $data['item_id']]);
    
    // Record stock movement
    $stmt = $pdo->prepare("
        INSERT INTO stock_movements 
        (item_id, movement_type, quantity_change, previous_quantity, new_quantity, notes, user_id)
        VALUES (?, 'in', ?, ?, ?, 'Received from PO', ?)
    ");
    $stmt->execute([
        $data['item_id'],
        $data['quantity'],
        $current['quantity'],
        $newQuantity,
        $_SESSION['user_id']
    ]);
    
    $movementId = $pdo->lastInsertId();
    
    // Record receiving history
    $stmt = $pdo->prepare("
        INSERT INTO receiving_history 
        (po_id, item_id, quantity_received, received_by, stock_movement_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['po_id'],
        $data['item_id'],
        $data['quantity'],
        $_SESSION['user_id'],
        $movementId
    ]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>