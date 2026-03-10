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
    // Get driver performance from dispatch_schedule
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.full_name,
            u.employee_id,
            COALESCE(COUNT(ds.id), 0) as trips,
            COALESCE(SUM(CASE WHEN ds.status = 'completed' THEN 1 ELSE 0 END), 0) as completed_trips,
            ROUND(
                COALESCE(
                    (SUM(CASE WHEN ds.status = 'completed' THEN 1 ELSE 0 END) / 
                    NULLIF(COUNT(ds.id), 0)) * 100, 
                0), 1
            ) as efficiency,
            98 as safety,
            ROUND(
                COALESCE(
                    (SUM(CASE WHEN ds.status = 'completed' THEN 1 ELSE 0 END) / 
                    NULLIF(COUNT(ds.id), 0)) * 5, 
                0), 1
            ) as rating
        FROM users u
        LEFT JOIN dispatch_schedule ds ON u.id = ds.driver_id
        WHERE u.role = 'driver' AND u.status = 'active'
        GROUP BY u.id
        ORDER BY trips DESC
        LIMIT 10
    ");
    
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also loop through to ensure no nulls
    foreach ($drivers as &$driver) {
        $driver['trips'] = (int)($driver['trips'] ?? 0);
        $driver['completed_trips'] = (int)($driver['completed_trips'] ?? 0);
        $driver['efficiency'] = (float)($driver['efficiency'] ?? 0);
        $driver['rating'] = (float)($driver['rating'] ?? 0);
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