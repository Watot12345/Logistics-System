<?php
// api/get_driver_stats.php
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
    // Get stats from dispatch_schedule (where your data actually is)
    $stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_trips,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_trips,
            SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as ongoing_trips,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_trips
        FROM dispatch_schedule 
        WHERE driver_id = ?
    ");
    $stats->execute([$driver_id]);
    $result = $stats->fetch(PDO::FETCH_ASSOC);
    
    $total_trips = $result['total_trips'] ?? 0;
    $completed_trips = $result['completed_trips'] ?? 0;
    
    // Calculate performance score (you can adjust this formula)
    $performance_score = $total_trips > 0 ? round(($completed_trips / $total_trips) * 100) : 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_trips' => $total_trips,
            'completed_trips' => $completed_trips,
            'performance_score' => $performance_score
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Get driver stats error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>