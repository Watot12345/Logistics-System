<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

$driver_id = $_SESSION['user_id'];

try {
    // First check dispatch_schedule for active assignments
    $stmt = $pdo->prepare("
        SELECT 
            ds.id,
            ds.vehicle_id,
            ds.status as dispatch_status,
            ds.scheduled_date,
            ds.shift,
            ds.notes as current_location,
            a.asset_name as vehicle_name,
            a.asset_condition as vehicle_condition,
            vr.customer_name,
            vr.delivery_address,
            vr.start_date,
            vr.end_date,
            vr.start_time,
            vr.end_time
        FROM dispatch_schedule ds
        LEFT JOIN assets a ON ds.vehicle_id = a.id
        LEFT JOIN vehicle_reservations vr ON ds.reservation_id = vr.id
        WHERE ds.driver_id = ? 
        AND ds.status IN ('scheduled', 'in-progress', 'delivered', 'awaiting_verification')
        ORDER BY ds.created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$driver_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($assignment) {
        // Format estimated arrival from end_date and end_time
        $estimated_arrival = null;
        if (!empty($assignment['end_date'])) {
            $estimated_arrival = $assignment['end_date'];
            if (!empty($assignment['end_time'])) {
                $estimated_arrival .= ' ' . $assignment['end_time'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'has_assignment' => true,
            'assignment' => [
                'id' => $assignment['id'],
                'vehicle_name' => $assignment['vehicle_name'],
                'vehicle_condition' => $assignment['vehicle_condition'],
                'customer_name' => $assignment['customer_name'] ?? 'No customer',
                'delivery_address' => $assignment['delivery_address'] ?? 'No address',
                'shipment_status' => $assignment['dispatch_status'],
                'current_location' => $assignment['current_location'] ?? 'Not started',
                'estimated_arrival' => $estimated_arrival
            ]
        ]);
    } else {
        // Check if there are any assignments at all for debugging
        $debug_stmt = $pdo->prepare("
            SELECT id, status, driver_id, created_at 
            FROM dispatch_schedule 
            WHERE driver_id = ? 
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $debug_stmt->execute([$driver_id]);
        $recent = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'has_assignment' => false,
            'message' => 'You have no active assignments',
            'debug' => [
                'driver_id' => $driver_id,
                'recent_assignments' => $recent
            ]
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error in get_driver_assignment: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
?>