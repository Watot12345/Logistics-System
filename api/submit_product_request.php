<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid data');
    }
    
    // Validate required fields
    $required = ['product_name', 'sku', 'category_id', 'supplier_id', 'estimated_price', 'description'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Insert product request
    $stmt = $pdo->prepare("
        INSERT INTO product_requests (
            product_name, sku, category_id, supplier_id, 
            estimated_price, initial_quantity, reorder_level, 
            description, urgent, requested_by, requested_at
        ) VALUES (
            :product_name, :sku, :category_id, :supplier_id,
            :estimated_price, :initial_quantity, :reorder_level,
            :description, :urgent, :requested_by, NOW()
        )
    ");
    
    $stmt->execute([
        ':product_name' => $data['product_name'],
        ':sku' => $data['sku'],
        ':category_id' => $data['category_id'],
        ':supplier_id' => $data['supplier_id'],
        ':estimated_price' => $data['estimated_price'],
        ':initial_quantity' => $data['initial_quantity'] ?? 1,
        ':reorder_level' => $data['reorder_level'] ?? 10,
        ':description' => $data['description'],
        ':urgent' => $data['urgent'] ? 1 : 0,
        ':requested_by' => $_SESSION['user_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Product request submitted successfully',
        'id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>