<?php
// api/get_maintenance_details.php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u.full_name as mechanic_name,
               creator.full_name as created_by_name
        FROM maintenance_alerts m
        LEFT JOIN users u ON m.assigned_mechanic = u.id
        LEFT JOIN users creator ON m.created_by = creator.id
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($details) {
        echo json_encode(['success' => true, 'data' => $details]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Not found']);
    }
    
} catch (PDOException $e) {
    error_log("Get maintenance details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>