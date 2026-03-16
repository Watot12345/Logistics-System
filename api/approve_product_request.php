<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit();
}

require_once '../config/db.php';

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid input data');
    }
    
    $request_id = $data['id'] ?? 0;
    $action = $data['action'] ?? '';
    $reason = $data['reason'] ?? '';
    
    if (!$request_id || !$action) {
        throw new Exception('Missing required fields');
    }
    
    // Start transaction
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
                :price, 0, :reorder_level, :description
            )
        ");
        
        $stmt->execute([
            ':item_name' => $request['product_name'],
            ':sku' => $request['sku'],
            ':category_id' => $request['category_id'],
            ':supplier_id' => $request['supplier_id'],
            ':price' => $request['estimated_price'],
            ':reorder_level' => $request['reorder_level'] ?? 10,
            ':description' => $request['description'] ?? ''
        ]);
        
        $new_item_id = $pdo->lastInsertId();
        
        // Update request status
        $stmt = $pdo->prepare("
            UPDATE product_requests 
            SET status = 'approved', 
                reviewed_by = ?, 
                reviewed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $request_id]);
        
        $message = 'Product approved and added to inventory';
        
    } elseif ($action === 'reject') {
        // Update request status
        $stmt = $pdo->prepare("
            UPDATE product_requests 
            SET status = 'rejected', 
                reviewed_by = ?, 
                reviewed_at = NOW(),
                review_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $reason, $request_id]);
        
        $message = 'Product request rejected';
        
    } else {
        throw new Exception('Invalid action');
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    // Rollback on error
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