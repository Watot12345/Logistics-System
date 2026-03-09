<?php
// api/delete_maintenance.php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Only admin and fleet_manager can delete
if (!in_array($_SESSION['role'], ['admin', 'fleet_manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM maintenance_alerts WHERE id = ?");
    $stmt->execute([$input['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Maintenance deleted']);
    
} catch (PDOException $e) {
    error_log("Delete maintenance error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>