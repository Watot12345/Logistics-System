<?php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'dispatcher', 'fleet_manager'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config/db.php';

try {
    // Get vehicle assignments from dispatch_schedule (same as dispatch schedule)
    $stmt = $pdo->prepare("
        SELECT 
            ds.id,
            ds.reservation_id,
            ds.vehicle_id,
            ds.driver_id,
            ds.scheduled_date,
            ds.status,
            a.asset_name as vehicle,
            a.asset_condition,
            u.full_name as driver,
            vr.purpose,
            vr.department,
            vr.start_time,
            vr.end_time,
            vr.start_date
        FROM dispatch_schedule ds
        LEFT JOIN assets a ON ds.vehicle_id = a.id
        LEFT JOIN users u ON ds.driver_id = u.id
        LEFT JOIN vehicle_reservations vr ON ds.reservation_id = vr.id
        WHERE ds.scheduled_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY 
            CASE 
                WHEN ds.status = 'in_progress' THEN 1
                WHEN ds.status = 'scheduled' THEN 2
                WHEN ds.status = 'completed' THEN 3
                ELSE 4
            END,
            ds.scheduled_date DESC,
            vr.start_time ASC
    ");
    
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for the vehicle assignments display
    $formatted = [];
    foreach ($assignments as $a) {
        // Generate plate number based on vehicle_id
        $plate = 'ABC-' . str_pad($a['vehicle_id'] ?? 0, 3, '0', STR_PAD_LEFT);
        $vehicleCode = 'VH-' . str_pad($a['vehicle_id'] ?? 0, 3, '0', STR_PAD_LEFT);
        
        // Format date
        $date_display = 'TBD';
        if (!empty($a['scheduled_date'])) {
            $date_display = date('M d, Y', strtotime($a['scheduled_date']));
        } elseif (!empty($a['start_date'])) {
            $date_display = date('M d, Y', strtotime($a['start_date']));
        }
        
        // Determine shift based on time
        $shift = 'Regular';
        if (!empty($a['start_time'])) {
            $hour = date('H', strtotime($a['start_time']));
            if ($hour < 12) {
                $shift = 'Morning';
            } elseif ($hour < 17) {
                $shift = 'Afternoon';
            } else {
                $shift = 'Night';
            }
        }
        
        // Map status from dispatch_schedule to what your JS expects
        $status = $a['status'] ?? 'pending';
        if ($status === 'in_progress') {
            $status = 'in_transit';
        } elseif ($status === 'completed') {
            $status = 'delivered';
        }
        
        $formatted[] = [
            'id' => $a['id'],
            'vehicle' => $a['vehicle'] ?? 'Unknown Vehicle',
            'vehicle_id' => $a['vehicle_id'],
            'vehicle_code' => $vehicleCode,
            'plate' => $plate,
            'driver' => $a['driver'] ?? 'Unassigned',
            'driver_id' => $a['driver_id'],
            'date' => $date_display,
            'shift' => $shift,
            'status' => $status,
            'purpose' => $a['purpose'] ?? '',
            'department' => $a['department'] ?? ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'assignments' => $formatted,
        'count' => count($formatted)
    ]);
    
} catch (PDOException $e) {
    error_log("Vehicle assignments error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>