<?php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$driver_id = $_SESSION['user_id'];

try {
    // First, check if user is a driver
    $user_check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $user_check->execute([$driver_id]);
    $user = $user_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $user['role'] !== 'driver') {
        echo json_encode([
            'success' => true,
            'has_assignment' => false,
            'message' => 'You are not registered as a driver'
        ]);
        exit();
    }
    
    // Check for active assignment in dispatch_schedule with correct joins
    $stmt = $pdo->prepare("
        SELECT 
            ds.id,
            ds.vehicle_id,
            ds.driver_id,
            ds.scheduled_date,
            ds.status as dispatch_status,
            ds.shift,
            ds.notes,
            a.asset_name as vehicle_name,
            a.asset_condition,
            -- Get customer data from vehicle_reservations
            vr.customer_name,
            vr.delivery_address,
            vr.purpose,
            vr.department,
            vr.start_time,
            vr.end_time,
            vr.start_date,
            vr.end_date,
            -- Get data from shipments (if available)
            s.customer_name as shipment_customer,
            s.delivery_address as shipment_address,
            s.current_location,
            s.estimated_arrival,
            s.shipment_status
        FROM dispatch_schedule ds
        LEFT JOIN assets a ON ds.vehicle_id = a.id
        LEFT JOIN vehicle_reservations vr ON ds.reservation_id = vr.id
        LEFT JOIN shipments s ON s.vehicle_id = ds.vehicle_id 
            AND DATE(s.departure_time) = ds.scheduled_date
        WHERE ds.driver_id = ? 
            AND ds.status IN ('scheduled', 'in-progress')
        ORDER BY ds.scheduled_date DESC, vr.start_time ASC
        LIMIT 1
    ");
    
    $stmt->execute([$driver_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($assignment) {
        // Determine which customer data to use (prioritize shipments data if available)
        $customer_name = $assignment['shipment_customer'] ?? 
                        $assignment['customer_name'] ?? 
                        'Customer';
        
        $delivery_address = $assignment['shipment_address'] ?? 
                           $assignment['delivery_address'] ?? 
                           'No address provided';
        
        $current_location = $assignment['current_location'] ?? 
                           $assignment['notes'] ?? 
                           'Not started';
        
        $estimated_arrival = $assignment['estimated_arrival'] ?? '';
        if (empty($estimated_arrival) && $assignment['end_date']) {
            $estimated_arrival = $assignment['end_date'];
            if ($assignment['end_time']) {
                $estimated_arrival .= ' ' . date('g:i A', strtotime($assignment['end_time']));
            }
        }
        
        // Format the response
        $response = [
            'success' => true,
            'has_assignment' => true,
            'assignment' => [
                'id' => $assignment['id'],
                'vehicle_name' => $assignment['vehicle_name'] ?? 'Unknown Vehicle',
                'vehicle_condition' => $assignment['asset_condition'] ?? 100,
                'customer_name' => $customer_name,
                'delivery_address' => $delivery_address,
                'current_location' => $current_location,
                'estimated_arrival' => $estimated_arrival ?: 'TBD',
                'shipment_status' => $assignment['dispatch_status'] === 'in-progress' ? 'in_transit' : 'pending'
            ]
        ];
        
        echo json_encode($response);
    } else {
        // Also check shipments table directly as fallback
        $shipment_stmt = $pdo->prepare("
            SELECT 
                s.shipment_id as id,
                s.vehicle_id,
                s.driver_id,
                s.customer_name,
                s.delivery_address,
                s.current_location,
                s.estimated_arrival,
                s.shipment_status,
                a.asset_name as vehicle_name,
                a.asset_condition
            FROM shipments s
            LEFT JOIN assets a ON s.vehicle_id = a.id
            WHERE s.driver_id = ? 
                AND s.shipment_status IN ('pending', 'in_transit')
            ORDER BY s.departure_time DESC
            LIMIT 1
        ");
        
        $shipment_stmt->execute([$driver_id]);
        $shipment = $shipment_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shipment) {
            echo json_encode([
                'success' => true,
                'has_assignment' => true,
                'assignment' => [
                    'id' => $shipment['id'],
                    'vehicle_name' => $shipment['vehicle_name'] ?? 'Unknown Vehicle',
                    'vehicle_condition' => $shipment['asset_condition'] ?? 100,
                    'customer_name' => $shipment['customer_name'] ?? 'N/A',
                    'delivery_address' => $shipment['delivery_address'] ?? 'N/A',
                    'current_location' => $shipment['current_location'] ?? 'Not started',
                    'estimated_arrival' => $shipment['estimated_arrival'] ?? 'N/A',
                    'shipment_status' => $shipment['shipment_status'] ?? 'pending'
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'has_assignment' => false,
                'message' => 'You have no active assignments at the moment'
            ]);
        }
    }
    
} catch (PDOException $e) {
    error_log("Get driver assignment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>