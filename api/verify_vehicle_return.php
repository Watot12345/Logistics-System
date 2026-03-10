<?php
// api/verify_vehicle_return.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'dispatcher', 'fleet_manager'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['schedule_id']) || !isset($input['approved'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$schedule_id = $input['schedule_id'];
$approved = $input['approved'];
$reason = $input['reason'] ?? '';

try {
    $pdo->beginTransaction();
    
    // Get the dispatch schedule details with reservation info
    $get_schedule = $pdo->prepare("
        SELECT ds.*, 
               vr.id as reservation_id,
               vr.customer_name,
               vr.delivery_address,
               vr.vehicle_id
        FROM dispatch_schedule ds
        LEFT JOIN vehicle_reservations vr ON ds.reservation_id = vr.id
        WHERE ds.id = ?
    ");
    $get_schedule->execute([$schedule_id]);
    $schedule = $get_schedule->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Schedule not found']);
        exit();
    }
    
    $new_status = $approved ? 'completed' : 'delivered';
    $action = $approved ? 'Return verified' : 'Return rejected';
    
    // 1. Update dispatch schedule
    $update = $pdo->prepare("
        UPDATE dispatch_schedule 
        SET status = ?,
            notes = CONCAT(COALESCE(notes, ''), '\n', NOW(), ': ', ?)
        WHERE id = ?
    ");
    $update->execute([$new_status, $action . ($reason ? " - $reason" : ""), $schedule_id]);
    
    // 2. If approved (completed), update the related shipment
    if ($approved) {
        // Check if there's a shipment for this vehicle/reservation
        $check_shipment = $pdo->prepare("
            SELECT shipment_id FROM shipments 
            WHERE vehicle_id = ? OR order_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $check_shipment->execute([$schedule['vehicle_id'], $schedule['reservation_id']]);
        $shipment = $check_shipment->fetch(PDO::FETCH_ASSOC);
        
        if ($shipment) {
            // Update existing shipment
            $update_shipment = $pdo->prepare("
                UPDATE shipments 
                SET shipment_status = 'delivered',
                    actual_arrival = NOW()
                WHERE shipment_id = ?
            ");
            $update_shipment->execute([$shipment['shipment_id']]);
        }
        
        // 3. Also update the reservation status
        if (!empty($schedule['reservation_id'])) {
            $update_reservation = $pdo->prepare("
                UPDATE vehicle_reservations 
                SET status = 'completed'
                WHERE id = ?
            ");
            $update_reservation->execute([$schedule['reservation_id']]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $approved ? 'Return verified and shipment completed' : 'Return rejected'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Verify return error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>