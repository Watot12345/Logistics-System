<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get driver performance from dispatch_schedule (where your data actually is)
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.full_name,
            u.employee_id,
            COUNT(ds.id) as trips,
            SUM(CASE WHEN ds.status = 'completed' THEN 1 ELSE 0 END) as completed_trips,
            SUM(CASE WHEN ds.status = 'in-progress' THEN 1 ELSE 0 END) as ongoing_trips,
            SUM(CASE WHEN ds.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_trips
        FROM users u
        LEFT JOIN dispatch_schedule ds ON u.id = ds.driver_id
        WHERE u.role = 'driver' AND u.status = 'active'
        GROUP BY u.id, u.full_name, u.employee_id
        ORDER BY trips DESC
    ");
    
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate performance metrics
    $result = [];
    foreach ($drivers as $driver) {
        $total_trips = $driver['trips'] ?: 0;
        $completed = $driver['completed_trips'] ?: 0;
        
        // Calculate efficiency (completed vs total attempted)
        $efficiency = $total_trips > 0 ? round(($completed / $total_trips) * 100) : 0;
        
        $result[] = [
            'id' => $driver['id'],
            'full_name' => $driver['full_name'],
            'employee_id' => $driver['employee_id'],
            'trips' => $total_trips,
            'completed_trips' => $completed,
            'ongoing_trips' => $driver['ongoing_trips'] ?: 0,
            'scheduled_trips' => $driver['scheduled_trips'] ?: 0,
            'efficiency' => $efficiency,
            'safety' => 98, // You can calculate this from another table if needed
            'rating' => $total_trips > 0 ? round(($completed / $total_trips) * 5, 1) : 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'drivers' => $result
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>