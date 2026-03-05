<?php
// api/update_po_status.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("
        UPDATE purchase_orders 
        SET status = :status,
            approved_by = CASE WHEN :status = 'approved' THEN :user_id ELSE approved_by END,
            approved_at = CASE WHEN :status = 'approved' THEN NOW() ELSE approved_at END
        WHERE id = :po_id
    ");
    
    $stmt->execute([
        'status' => $data['status'],
        'po_id' => $data['po_id'],
        'user_id' => $_SESSION['user_id']
    ]);
    
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>