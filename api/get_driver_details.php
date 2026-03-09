<?php
// api/get_driver_details.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$driver_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$driver_id) {
    echo json_encode(['success' => false, 'error' => 'Driver ID required']);
    exit();
}

try {
    // Get driver details from users table
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.full_name,
            u.employee_id,
            u.email,
            u.phone,
            u.department,
            u.status,
            u.join_date,
            u.last_login,
            u.created_at,
            -- Get trip statistics
            COUNT(ds.id) as total_trips,
            SUM(CASE WHEN ds.status = 'completed' THEN 1 ELSE 0 END) as completed_trips,
            SUM(CASE WHEN ds.status = 'in-progress' THEN 1 ELSE 0 END) as active_trips
        FROM users u
        LEFT JOIN dispatch_schedule ds ON u.id = ds.driver_id
        WHERE u.id = ? AND u.role = 'driver'
        GROUP BY u.id
    ");
    
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($driver) {
        echo json_encode([
            'success' => true,
            'driver' => $driver
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Driver not found']);
    }
    
} catch (PDOException $e) {
    error_log("Get driver details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>