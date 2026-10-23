<?php
// api/assign_driver_to_schedule.php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'dispatcher'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['schedule_id']) || !isset($input['driver_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$schedule_id = $input['schedule_id'];
$driver_id = $input['driver_id'];

try {
    $pdo->beginTransaction();
    
    // Update dispatch schedule with driver
    $update = $pdo->prepare("
        UPDATE dispatch_schedule 
        SET driver_id = ?, status = 'scheduled' 
        WHERE id = ?
    ");
    $update->execute([$driver_id, $schedule_id]);
    
    if ($update->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Schedule not found']);
        exit();
    }
    
    // Get driver name for response
    $driver = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $driver->execute([$driver_id]);
    $driver_name = $driver->fetchColumn();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Driver assigned successfully',
        'driver_name' => $driver_name
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Assign driver error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>