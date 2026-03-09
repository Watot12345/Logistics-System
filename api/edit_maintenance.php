<?php
// api/edit_maintenance.php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Only admin and fleet_manager can edit
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
    $stmt = $pdo->prepare("
        UPDATE maintenance_alerts 
        SET asset_name = ?,
            issue = ?,
            issue_type = ?,
            priority = ?,
            assigned_mechanic = ?,
            estimated_hours = ?,
            due_date = ?,
            notes = CONCAT(COALESCE(notes, ''), '\n[', NOW(), '] Updated')
        WHERE id = ?
    ");
    
    $stmt->execute([
        $input['asset_name'],
        $input['issue'],
        $input['issue_type'],
        $input['priority'],
        $input['assigned_mechanic'] ?? null,
        $input['estimated_hours'] ?? null,
        $input['due_date'],
        $input['id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Maintenance updated']);
    
} catch (PDOException $e) {
    error_log("Edit maintenance error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>