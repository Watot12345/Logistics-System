<?php
// api/assign_mechanic_to_breakdown.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user has permission (admin or fleet_manager only)
if (!in_array($_SESSION['role'], ['admin', 'fleet_manager'])) {
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
$required = ['breakdown_id', 'assigned_mechanic'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'error' => "$field is required"]);
        exit();
    }
}

$breakdown_id = $input['breakdown_id'];
$assigned_mechanic = $input['assigned_mechanic'];
$estimated_arrival = $input['estimated_arrival'] ?? null;
$notes = $input['notes'] ?? '';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if breakdown exists and is still in 'reported' status
    $check = $pdo->prepare("
        SELECT eb.*, u.full_name as driver_name, u.phone as driver_phone 
        FROM emergency_breakdowns eb
        LEFT JOIN users u ON eb.driver_id = u.id
        WHERE eb.id = ?
    ");
    $check->execute([$breakdown_id]);
    $breakdown = $check->fetch(PDO::FETCH_ASSOC);
    
    if (!$breakdown) {
        throw new Exception('Breakdown record not found');
    }
    
    if ($breakdown['status'] !== 'reported') {
        throw new Exception('This breakdown has already been assigned or is already being handled');
    }
    
    // Update breakdown with mechanic assignment
    $stmt = $pdo->prepare("
        UPDATE emergency_breakdowns 
        SET assigned_mechanic = ?,
            status = 'assigned',
            assigned_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$assigned_mechanic, $breakdown_id]);
    
    // Get mechanic info for notification
    $mechanic_stmt = $pdo->prepare("SELECT full_name, phone FROM users WHERE id = ?");
    $mechanic_stmt->execute([$assigned_mechanic]);
    $mechanic = $mechanic_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create notification for the mechanic
    try {
        // Check if notifications table exists
        $checkTable = $pdo->query("SHOW TABLES LIKE 'notifications'");
        if ($checkTable->rowCount() > 0) {
            // Notify the mechanic
            $notify_mechanic = $pdo->prepare("
                INSERT INTO notifications (user_id, message, type, related_id)
                VALUES (?, ?, 'breakdown_assigned', ?)
            ");
            $mechanic_message = "EMERGENCY BREAKDOWN assigned: {$breakdown['vehicle_name']} at {$breakdown['location']}. Driver: {$breakdown['driver_name']} ({$breakdown['driver_phone']})";
            $notify_mechanic->execute([$assigned_mechanic, $mechanic_message, $breakdown_id]);
            
            // Notify the driver that mechanic is on the way
            $notify_driver = $pdo->prepare("
                INSERT INTO notifications (user_id, message, type, related_id)
                VALUES (?, ?, 'mechanic_dispatched', ?)
            ");
            $driver_message = "Mechanic {$mechanic['full_name']} has been dispatched to your location. ETA: " . ($estimated_arrival ?? 'ASAP');
            $notify_driver->execute([$breakdown['driver_id'], $driver_message, $breakdown_id]);
        }
    } catch (PDOException $e) {
        // Notifications table might not exist, just log
        error_log("Could not create notifications: " . $e->getMessage());
    }
    
    // If there's a dispatch schedule, update its notes
    if ($breakdown['dispatch_schedule_id']) {
        $update_dispatch = $pdo->prepare("
            UPDATE dispatch_schedule 
            SET notes = CONCAT(COALESCE(notes, ''), ?)
            WHERE id = ?
        ");
        $dispatch_note = "\n[" . date('Y-m-d H:i:s') . "] MECHANIC ASSIGNED: {$mechanic['full_name']} dispatched to emergency at {$breakdown['location']}";
        $update_dispatch->execute([$dispatch_note, $breakdown['dispatch_schedule_id']]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Mechanic assigned to breakdown successfully',
        'mechanic_name' => $mechanic['full_name'] ?? 'Unknown',
        'breakdown_id' => $breakdown_id
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Assign mechanic to breakdown error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>