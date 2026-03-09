<?php
// api/assign_mechanic.php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user has permission (admin or fleet_manager only)
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['admin', 'fleet_manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden - Insufficient permissions']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Validate required fields
$required = ['id', 'issue_type', 'priority', 'assigned_mechanic', 'due_date'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'error' => "$field is required"]);
        exit();
    }
}

$maintenance_id = $input['id'];
$issue_type = $input['issue_type'];
$priority = $input['priority'];
$assigned_mechanic = $input['assigned_mechanic'];
$due_date = $input['due_date'];
$estimated_hours = !empty($input['estimated_hours']) ? $input['estimated_hours'] : null;
$notes = $input['notes'] ?? '';

try {
    // Check if maintenance record exists
    $check = $pdo->prepare("SELECT id FROM maintenance_alerts WHERE id = ?");
    $check->execute([$maintenance_id]);
    
    if ($check->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Maintenance record not found']);
        exit();
    }
    
    // Update maintenance record with assignment
    $stmt = $pdo->prepare("
        UPDATE maintenance_alerts 
        SET issue_type = ?,
            priority = ?,
            assigned_mechanic = ?,
            due_date = ?,
            estimated_hours = ?,
            notes = CONCAT(COALESCE(notes, ''), '\n[', NOW(), '] Assigned: ', ?)
        WHERE id = ?
    ");
    
    $stmt->execute([
        $issue_type,
        $priority,
        $assigned_mechanic,
        $due_date,
        $estimated_hours,
        $notes,
        $maintenance_id
    ]);
    
    // Log activity
    $log_stmt = $pdo->prepare("
        INSERT INTO user_activity_logs (user_id, action_type, timestamp) 
        VALUES (?, 'assign_maintenance', NOW())
    ");
    $log_stmt->execute([$_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Maintenance task assigned successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Assign mechanic error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>