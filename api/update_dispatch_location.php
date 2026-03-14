<?php
// api/update_dispatch_location.php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['schedule_id']) || !isset($input['current_location'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$schedule_id = $input['schedule_id'];
$location_name = $input['current_location']; // Now just the location name, e.g., "Manila"

try {
    // Get current Manila time
    $manila_time = date('Y-m-d H:i:s');
    
    // Format the complete location string with timestamp
    $location_with_timestamp = "[{$manila_time}] Location update: {$location_name}";
    
    // Verify this schedule belongs to the logged-in driver
    $check = $pdo->prepare("
        SELECT ds.*, vr.vehicle_id, vr.customer_name, vr.delivery_address
        FROM dispatch_schedule ds
        LEFT JOIN vehicle_reservations vr ON ds.reservation_id = vr.id
        WHERE ds.id = ? AND ds.driver_id = ?
    ");
    $check->execute([$schedule_id, $_SESSION['user_id']]);
    $schedule = $check->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized or assignment not found']);
        exit();
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update location in dispatch schedule notes
    $update_dispatch = $pdo->prepare("
        UPDATE dispatch_schedule 
        SET notes = CONCAT(COALESCE(notes, ''), ?)
        WHERE id = ?
    ");
    
    // Add a newline before appending
    $update_dispatch->execute(["\n" . $location_with_timestamp, $schedule_id]);
    
    // Also update the corresponding shipment if it exists
    if ($schedule['vehicle_id']) {
        $update_shipment = $pdo->prepare("
            UPDATE shipments 
            SET current_location = ?
            WHERE vehicle_id = ? 
            AND shipment_status IN ('pending', 'in_transit', 'delivered')
            ORDER BY created_at DESC
            LIMIT 1
        ");
        
        $update_shipment->execute([$location_with_timestamp, $schedule['vehicle_id']]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Location updated successfully'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Update dispatch location error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>