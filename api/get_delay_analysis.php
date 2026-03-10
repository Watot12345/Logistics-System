<?php
// api/get_delay_analysis.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

try {
    // Get delays from last 24 hours
    $stmt = $pdo->query("
        SELECT 
            CONCAT('TR-', LPAD(d.id, 4, '0')) as id,
            d.route_name as route,
            d.delay_reason as reason,
            COALESCE(d.delay_duration, CONCAT(d.delay_minutes, ' minutes')) as duration,
            d.delay_type as type
        FROM shipment_delays d
        WHERE d.reported_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY d.reported_at DESC
        LIMIT 10
    ");
    
    $delays = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'delays' => $delays
    ]);
    
} catch (PDOException $e) {
    error_log("Get delay analysis error: " . $e->getMessage());
    
    echo json_encode([
        'success' => true,
        'delays' => []
    ]);
}
?>