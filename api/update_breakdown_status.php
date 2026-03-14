<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user is a mechanic
if ($_SESSION['role'] !== 'mechanic') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['breakdown_id']) || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$breakdown_id = $data['breakdown_id'];
$action = $data['action']; // 'start' or 'complete'
$mechanic_id = $_SESSION['user_id'];
$notes = $data['notes'] ?? '';

try {
    // Verify this breakdown is assigned to this mechanic
    $check = $pdo->prepare("
        SELECT * FROM emergency_breakdowns 
        WHERE id = ? AND assigned_mechanic = ?
    ");
    $check->execute([$breakdown_id, $mechanic_id]);
    
    if ($check->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Breakdown not found or not assigned to you']);
        exit();
    }
    
    $breakdown = $check->fetch(PDO::FETCH_ASSOC);
    
    // Start transaction
    $pdo->beginTransaction();
    
    if ($action === 'start') {
        // Start working on the breakdown
        $stmt = $pdo->prepare("
            UPDATE emergency_breakdowns 
            SET status = 'in_progress',
                started_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$breakdown_id]);
        
        $message = "You have started working on the breakdown";
        
        // Notify fleet manager
        try {
            $notify = $pdo->prepare("
                INSERT INTO notifications (user_id, message, type, related_id)
                SELECT id, ?, 'breakdown_started', ?
                FROM users WHERE role IN ('admin', 'fleet_manager')
            ");
            $notify->execute([
                "Mechanic has started working on breakdown #{$breakdown_id}",
                $breakdown_id
            ]);
        } catch (Exception $e) {
            // Notifications table might not exist
        }
        
    } elseif ($action === 'complete') {
        // Complete the breakdown
        $stmt = $pdo->prepare("
            UPDATE emergency_breakdowns 
            SET status = 'resolved',
                resolved_at = NOW(),
                resolution_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$notes, $breakdown_id]);
        
        $message = "Breakdown marked as resolved";
        
        // Notify fleet manager and driver
        try {
            $notify = $pdo->prepare("
                INSERT INTO notifications (user_id, message, type, related_id)
                SELECT id, ?, 'breakdown_resolved', ?
                FROM users WHERE role IN ('admin', 'fleet_manager', 'driver') AND id = ?
            ");
            $notify->execute([
                "Breakdown #{$breakdown_id} has been resolved",
                $breakdown_id,
                $breakdown['driver_id']
            ]);
        } catch (Exception $e) {
            // Notifications table might not exist
        }
    } else {
        throw new Exception('Invalid action');
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Update breakdown status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>