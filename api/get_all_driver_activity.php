<?php
// api/get_all_driver_activity.php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Only admin and dispatcher can view all driver activity
if (!in_array($_SESSION['role'], ['admin', 'dispatcher', 'fleet_manager', 'employee'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config/db.php';

try {
    // Get all active drivers with their stats
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.full_name,
            u.employee_id,
            u.status as user_status,
            COALESCE(ds.total_trips, 0) as total_trips,
            COALESCE(ds.completed_trips, 0) as completed_trips,
            COALESCE(ds.in_progress_trips, 0) as in_progress_trips,
            COALESCE(ds.scheduled_trips, 0) as scheduled_trips,
            COALESCE(ls.current_location, 'N/A') as current_location,
            ls.last_update as last_location_update,
            COALESCE(v.asset_name, 'No vehicle') as current_vehicle,
            COALESCE(v.id, 0) as vehicle_id
        FROM users u
        LEFT JOIN (
            SELECT 
                driver_id,
                COUNT(*) as total_trips,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_trips,
                SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress_trips,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_trips
            FROM dispatch_schedule
            GROUP BY driver_id
        ) ds ON u.id = ds.driver_id
        LEFT JOIN (
            SELECT 
                ds.driver_id,
                ds.vehicle_id,
                a.asset_name,
                SUBSTRING_INDEX(SUBSTRING_INDEX(ds.notes, 'Location: ', -1), '\n', 1) as current_location,
                MAX(ds.created_at) as last_update
            FROM dispatch_schedule ds
            LEFT JOIN assets a ON ds.vehicle_id = a.id
            WHERE ds.status IN ('in-progress', 'scheduled')
            GROUP BY ds.driver_id
        ) ls ON u.id = ls.driver_id
        LEFT JOIN assets v ON ls.vehicle_id = v.id
        WHERE u.role = 'driver' AND u.status = 'active'
        ORDER BY u.full_name
    ");
    
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity for each driver
    $activity = [];
    foreach ($drivers as $driver) {
        // Get last 3 trips for this driver
        $trip_stmt = $pdo->prepare("
            SELECT 
                CONCAT('DSP-', ds.id) as id,
                COALESCE(vr.customer_name, 'Customer') as customer,
                ds.status,
                ds.scheduled_date,
                a.asset_name as vehicle,
                TIME_FORMAT(vr.start_time, '%h:%i %p') as start_time
            FROM dispatch_schedule ds
            LEFT JOIN vehicle_reservations vr ON ds.reservation_id = vr.id
            LEFT JOIN assets a ON ds.vehicle_id = a.id
            WHERE ds.driver_id = ?
            ORDER BY ds.scheduled_date DESC
            LIMIT 3
        ");
        $trip_stmt->execute([$driver['id']]);
        $recent_trips = $trip_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate performance score
        $performance = $driver['total_trips'] > 0 
            ? round(($driver['completed_trips'] / $driver['total_trips']) * 100) 
            : 0;
        
        $activity[] = [
            'driver_id' => $driver['id'],
            'driver_name' => $driver['full_name'],
            'employee_id' => $driver['employee_id'],
            'total_trips' => (int)$driver['total_trips'],
            'completed_trips' => (int)$driver['completed_trips'],
            'in_progress_trips' => (int)$driver['in_progress_trips'],
            'scheduled_trips' => (int)$driver['scheduled_trips'],
            'performance' => $performance,
            'current_status' => $driver['in_progress_trips'] > 0 ? 'On Trip' : ($driver['scheduled_trips'] > 0 ? 'Assigned' : 'Available'),
            'current_location' => $driver['current_location'],
            'current_vehicle' => $driver['current_vehicle'],
            'recent_trips' => $recent_trips
        ];
    }
    
    echo json_encode([
        'success' => true,
        'drivers' => $activity,
        'total_drivers' => count($activity)
    ]);
    
} catch (PDOException $e) {
    error_log("Get driver activity error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>