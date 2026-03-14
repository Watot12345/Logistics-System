<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Get all active mechanics
    $stmt = $pdo->prepare("
        SELECT id, full_name, phone, email
        FROM users 
        WHERE role = 'mechanic' AND status = 'active'
        ORDER BY full_name
    ");
    $stmt->execute();
    $mechanics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'mechanics' => $mechanics
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>