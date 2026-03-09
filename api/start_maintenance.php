<?php
// api/start_maintenance.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user has permission (admin, fleet_manager, or assigned mechanic)
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$maintenance_id = $input['id'];

require_once '../config/db.php';

try {
    // Get maintenance details to check permissions
    $check = $pdo->prepare("
        SELECT assigned_mechanic FROM maintenance_alerts WHERE id = ?
    ");
    $check->execute([$maintenance_id]);
    $maintenance = $check->fetch(PDO::FETCH_ASSOC);
    
    if (!$maintenance) {
        echo json_encode(['success' => false, 'error' => 'Maintenance record not found']);
        exit();
    }
    
    // Check if user can start this maintenance
    $can_start = in_array($user_role, ['admin', 'fleet_manager']) || 
                 ($user_role === 'mechanic' && $maintenance['assigned_mechanic'] == $user_id);
    
    if (!$can_start) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden - Cannot start this task']);
        exit();
    }
    
    // Update maintenance status to in_progress - REMOVED the notes column
    $stmt = $pdo->prepare("
        UPDATE maintenance_alerts 
        SET status = 'in_progress',
            started_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$maintenance_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Maintenance started successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Start maintenance error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>