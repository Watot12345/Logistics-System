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
    // Get recent driver activities
    $stmt = $pdo->query("
        SELECT 
            u.full_name as driver,
            CASE 
                WHEN s.shipment_status = 'in_transit' THEN 'Started trip'
                WHEN s.shipment_status = 'delivered' THEN 'Completed trip'
                WHEN s.shipment_status = 'pending' THEN 'Assigned to trip'
                ELSE 'Vehicle check'
            END as action,
            CONCAT(a.asset_name, ' → ', s.delivery_address) as detail,
            CONCAT(
                CASE 
                    WHEN TIMESTAMPDIFF(MINUTE, COALESCE(s.updated_at, s.created_at), NOW()) < 60 
                    THEN CONCAT(TIMESTAMPDIFF(MINUTE, COALESCE(s.updated_at, s.created_at), NOW()), ' minutes ago')
                    ELSE CONCAT(TIMESTAMPDIFF(HOUR, COALESCE(s.updated_at, s.created_at), NOW()), ' hours ago')
                END
            ) as time,
            CASE 
                WHEN s.shipment_status = 'in_transit' THEN 'green'
                WHEN s.shipment_status = 'delivered' THEN 'blue'
                ELSE 'amber'
            END as type
        FROM shipments s
        JOIN users u ON s.driver_id = u.id
        JOIN assets a ON s.vehicle_id = a.id
        WHERE s.updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) OR s.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY COALESCE(s.updated_at, s.created_at) DESC
        LIMIT 10
    ");
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Always return an array, even if empty
    if (!$activities) {
        $activities = [];
    }
    
    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);
    
} catch (PDOException $e) {
    // Return empty array on error instead of failing
    echo json_encode([
        'success' => true,
        'activities' => [],
        'debug' => $e->getMessage()
    ]);
}
?>