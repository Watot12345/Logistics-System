<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Only admin can approve/reject
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id']) || !isset($data['action'])) {
        throw new Exception('Invalid request data');
    }
    
    $request_id = $data['id'];
    $action = $data['action'];
    $reason = $data['reason'] ?? null;
    
    $pdo->beginTransaction();
    
    if ($action === 'approve') {
        // Get the request details
        $stmt = $pdo->prepare("SELECT * FROM product_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            throw new Exception('Request not found');
        }
        
        // Insert into inventory_items
        $stmt = $pdo->prepare("
            INSERT INTO inventory_items (
                item_name, sku, category_id, supplier_id, 
                price, quantity, reorder_level, description
            ) VALUES (
                :item_name, :sku, :category_id, :supplier_id,
                :price, :quantity, :reorder_level, :description
            )
        ");
        
        $stmt->execute([
            ':item_name' => $request['product_name'],
            ':sku' => $request['sku'],
            ':category_id' => $request['category_id'],
            ':supplier_id' => $request['supplier_id'],
            ':price' => $request['estimated_price'],
            ':quantity' => 0, // Start with 0, will be added when PO is created/received
            ':reorder_level' => $request['reorder_level'],
            ':description' => $request['description']
        ]);
        
        // Update request status
        $stmt = $pdo->prepare("
            UPDATE product_requests 
            SET status = 'approved', 
                reviewed_by = ?, 
                reviewed_at = NOW(),
                review_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $reason, $request_id]);
        
    } elseif ($action === 'reject') {
        // Just update the request status
        $stmt = $pdo->prepare("
            UPDATE product_requests 
            SET status = 'rejected', 
                reviewed_by = ?, 
                reviewed_at = NOW(),
                review_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $reason, $request_id]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Request ' . $action . 'ed successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>