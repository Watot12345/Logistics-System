<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

try {
    $request_id = $_GET['id'] ?? 0;
    
    if (!$request_id) {
        throw new Exception('Request ID required');
    }
    
    $stmt = $pdo->prepare("
        SELECT pr.*, 
               c.category_name,
               s.supplier_name,
               u.full_name as requester_name,
               ru.full_name as reviewer_name
        FROM product_requests pr
        LEFT JOIN categories c ON pr.category_id = c.id
        LEFT JOIN suppliers s ON pr.supplier_id = s.id
        LEFT JOIN users u ON pr.requested_by = u.id
        LEFT JOIN users ru ON pr.reviewed_by = ru.id
        WHERE pr.id = ?
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('Request not found');
    }
    
    echo json_encode([
        'success' => true,
        'request' => $request
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>