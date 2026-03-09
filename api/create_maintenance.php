<?php
// api/create_maintenance.php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Only admin and fleet_manager can create
if (!in_array($_SESSION['role'], ['admin', 'fleet_manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$required = ['asset_name', 'issue', 'due_date'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'error' => "$field is required"]);
        exit();
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO maintenance_alerts (
            asset_name, issue, issue_type, priority, 
            assigned_mechanic, estimated_hours, due_date, 
            status, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");
    
    $stmt->execute([
        $input['asset_name'],
        $input['issue'],
        $input['issue_type'] ?? 'minor',
        $input['priority'] ?? 'medium',
        $input['assigned_mechanic'] ?? null,
        $input['estimated_hours'] ?? null,
        $input['due_date'],
        $_SESSION['user_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'message' => 'Maintenance task created'
    ]);
    
} catch (PDOException $e) {
    error_log("Create maintenance error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>