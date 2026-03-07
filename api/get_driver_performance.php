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
    // Get driver performance from actual shipments
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.full_name,
            u.employee_id,
            COUNT(DISTINCT s.shipment_id) as trips,
            SUM(CASE WHEN s.shipment_status = 'delivered' THEN 1 ELSE 0 END) as completed_trips,
            SUM(CASE WHEN s.actual_arrival <= s.estimated_arrival AND s.actual_arrival IS NOT NULL THEN 1 ELSE 0 END) as on_time_trips,
            AVG(CASE 
                WHEN s.actual_arrival IS NOT NULL AND s.estimated_arrival IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, s.estimated_arrival, s.actual_arrival)
                ELSE 0 
            END) as avg_delay
        FROM users u
        LEFT JOIN shipments s ON u.id = s.driver_id
        WHERE u.role = 'driver' AND u.status = 'active'
        GROUP BY u.id
        ORDER BY trips DESC
        LIMIT 10
    ");
    
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate performance metrics
    foreach ($drivers as &$driver) {
        $trips = $driver['trips'] ?: 1;
        $driver['trips'] = intval($driver['trips'] ?? 0);
        $driver['efficiency'] = $trips > 0 ? round(($driver['completed_trips'] / $trips) * 100) : 95;
        $driver['safety'] = 98; // You might need a separate safety table
        $driver['rating'] = round(($driver['on_time_trips'] / $trips) * 5, 1);
        
        // Clean up
        unset($driver['completed_trips']);
        unset($driver['on_time_trips']);
        unset($driver['avg_delay']);
    }
    
    echo json_encode([
        'success' => true,
        'drivers' => $drivers
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>