<?php
session_start();
header('Content-Type: ');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data provided']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Check if PO is in editable state
    $stmt = $pdo->prepare("SELECT status FROM purchase_orders WHERE id = ?");
    $stmt->execute([$data['po_id']]);
    $current = $stmt->fetch();
    
    if (!$current) {
        throw new Exception('PO not found');
    }
    
    if ($current['status'] !== 'pending') {
        throw new Exception('Only pending POs can be edited');
    }
    
    // Update PO header
    $stmt = $pdo->prepare("
        UPDATE purchase_orders 
        SET supplier_id = ?,
            order_date = ?,
            expected_delivery = ?,
            priority = ?,
            subtotal = ?,
            tax_amount = ?,
            total_amount = ?,
            notes = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['supplier_id'],
        $data['order_date'],
        $data['expected_delivery'] ?? null,
        $data['priority'] ?? 'normal',
        $data['subtotal'],
        $data['tax_amount'],
        $data['total_amount'],
        $data['notes'] ?? '',
        $data['po_id']
    ]);
    
    // Delete existing items
    $stmt = $pdo->prepare("DELETE FROM purchase_order_items WHERE po_id = ?");
    $stmt->execute([$data['po_id']]);
    
    // Insert updated items
    $stmt = $pdo->prepare("
        INSERT INTO purchase_order_items (po_id, item_id, quantity, unit_price, total_price, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    
    foreach ($data['items'] as $item) {
        $total_price = $item['quantity'] * $item['unit_price'];
        $stmt->execute([
            $data['po_id'],
            $item['item_id'],
            $item['quantity'],
            $item['unit_price'],
            $total_price
        ]);
    }
    
    // Log the update
    $stmt = $pdo->prepare("
        INSERT INTO po_status_history (po_id, old_status, new_status, changed_by, notes, changed_at)
        VALUES (?, ?, ?, ?, 'PO updated', NOW())
    ");
    $stmt->execute([$data['po_id'], $current['status'], $current['status'], $_SESSION['user_id']]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'PO updated successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>