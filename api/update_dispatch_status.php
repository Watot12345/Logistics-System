<?php
// api/update_dispatch_status.php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['schedule_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$schedule_id = $input['schedule_id'];
$status = $input['status'];
$current_location = $input['current_location'] ?? '';

try {
    // Verify this schedule belongs to the logged-in driver
    $check = $pdo->prepare("
        SELECT ds.*, vr.vehicle_id, vr.id as reservation_id,
               vr.customer_name, vr.delivery_address
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
    
    // FIXED: Update dispatch schedule status - removed the extra parameter
    $update = $pdo->prepare("
        UPDATE dispatch_schedule 
        SET status = ?, 
            notes = CONCAT(COALESCE(notes, ''), '\n[', NOW(), '] ', ?)
        WHERE id = ?
    ");
    
    // Create the complete note text
    $note_text = "Status changed to $status";
    if ($current_location) {
        $note_text .= " - Location: $current_location";
    }
    
    // FIXED: Only pass 3 parameters for 3 placeholders
    $update->execute([$status, $note_text, $schedule_id]);
    
    // Update the corresponding shipment based on status
    if ($schedule['vehicle_id']) {
        // Find the shipment linked to this reservation
        $find_shipment = $pdo->prepare("
            SELECT shipment_id FROM shipments 
            WHERE order_id = ? OR vehicle_id = ?
            ORDER BY created_at DESC LIMIT 1
        ");
        $find_shipment->execute([$schedule['reservation_id'], $schedule['vehicle_id']]);
        $shipment = $find_shipment->fetch(PDO::FETCH_ASSOC);
        
        if ($shipment) {
            // Map dispatch status to shipment status
            if ($status === 'in-progress') {
                $shipment_status = 'in_transit';
                $update_shipment = $pdo->prepare("
                    UPDATE shipments 
                    SET shipment_status = ?,
                        departure_time = COALESCE(departure_time, NOW()),
                        current_location = ?
                    WHERE shipment_id = ?
                ");
                $update_shipment->execute([$shipment_status, $current_location, $shipment['shipment_id']]);
                
            } else if ($status === 'delivered') {
                $shipment_status = 'delivered';
                $update_shipment = $pdo->prepare("
                    UPDATE shipments 
                    SET shipment_status = ?,
                        current_location = ?
                    WHERE shipment_id = ?
                ");
                $update_shipment->execute([$shipment_status, $current_location, $shipment['shipment_id']]);
                
            } else if ($status === 'awaiting_verification') {
                $shipment_status = 'delivered';
                $update_shipment = $pdo->prepare("
                    UPDATE shipments 
                    SET shipment_status = ?,
                        current_location = ?
                    WHERE shipment_id = ?
                ");
                $update_shipment->execute([$shipment_status, $current_location, $shipment['shipment_id']]);
                
            } else if ($status === 'completed') {
                $shipment_status = 'delivered';
                $update_shipment = $pdo->prepare("
                    UPDATE shipments 
                    SET shipment_status = ?,
                        actual_arrival = NOW()
                    WHERE shipment_id = ?
                ");
                $update_shipment->execute([$shipment_status, $shipment['shipment_id']]);
            }
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Update dispatch status error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>