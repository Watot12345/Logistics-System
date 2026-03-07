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
$new_status = $input['new_status'] ?? '';

try {
    // Verify this schedule belongs to the logged-in driver
    $check = $pdo->prepare("
        SELECT ds.*, vr.vehicle_id, vr.start_date, vr.end_date, vr.customer_name, vr.delivery_address,
               vr.id as reservation_id
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
    
    // Update dispatch schedule status
    $update = $pdo->prepare("
        UPDATE dispatch_schedule 
        SET status = ?, 
            notes = CONCAT(COALESCE(notes, ''), '\n[', NOW(), '] ', ?, ' at ', ?)
        WHERE id = ?
    ");
    
    $action_text = "Status changed to $status";
    if ($current_location) {
        $action_text .= " - Location: $current_location";
    }
    
    $update->execute([$status, $action_text, $current_location, $schedule_id]);
    
    // ===== IMPORTANT: Update reservation to 'completed' when trip is done =====
    if ($status === 'completed' || $new_status === 'delivered') {
        // Update the corresponding reservation to 'completed'
        if (!empty($schedule['reservation_id'])) {
            $update_reservation = $pdo->prepare("
                UPDATE vehicle_reservations 
                SET status = 'completed',
                    notes = CONCAT(COALESCE(notes, ''), '\nTrip completed on ', NOW())
                WHERE id = ?
            ");
            $update_reservation->execute([$schedule['reservation_id']]);
        }
    }
    
    // Also update the corresponding shipment if it exists
    $shipment_updated = false;
    
    // Try to find and update the shipment
    if ($schedule['vehicle_id']) {
        // First, try to find shipment by vehicle_id that's in progress
        $find_shipment = $pdo->prepare("
            SELECT shipment_id FROM shipments 
            WHERE vehicle_id = ? 
            AND shipment_status IN ('pending', 'in_transit')
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $find_shipment->execute([$schedule['vehicle_id']]);
        $shipment = $find_shipment->fetch(PDO::FETCH_ASSOC);
        
        if ($shipment) {
            // Update existing shipment
            $shipment_status = 'pending';
            if ($new_status === 'in_transit' || $status === 'in-progress') {
                $shipment_status = 'in_transit';
            } else if ($new_status === 'delivered' || $status === 'completed') {
                $shipment_status = 'delivered';
            }
            
            $update_shipment = $pdo->prepare("
                UPDATE shipments 
                SET shipment_status = ?,
                    current_location = ?,
                    departure_time = CASE 
                        WHEN ? = 'in_transit' AND departure_time IS NULL THEN NOW() 
                        ELSE departure_time 
                    END,
                    actual_arrival = CASE 
                        WHEN ? = 'delivered' THEN NOW() 
                        ELSE actual_arrival 
                    END
                WHERE shipment_id = ?
            ");
            
            $update_shipment->execute([
                $shipment_status, 
                $current_location,
                $shipment_status,
                $shipment_status,
                $shipment['shipment_id']
            ]);
            
            $shipment_updated = true;
        }
    }
    
    // If no shipment found but we're marking as delivered, create a completion record
    if (!$shipment_updated && ($new_status === 'delivered' || $status === 'completed')) {
        // Insert into trip history or create a completion record
        $complete_trip = $pdo->prepare("
            INSERT INTO shipment_tracking (
                shipment_id, 
                location, 
                status_update, 
                updated_at
            ) VALUES (NULL, ?, 'Trip completed from dispatch schedule', NOW())
        ");
        $complete_trip->execute([$current_location ?: 'Delivery location']);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'new_status' => $status,
        'shipment_updated' => $shipment_updated,
        'reservation_updated' => !empty($schedule['reservation_id']) // Add this for debugging
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