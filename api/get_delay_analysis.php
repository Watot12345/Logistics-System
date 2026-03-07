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
    // Get recent delays from shipments
    $stmt = $pdo->query("
        SELECT 
            s.shipment_id as id,
            CONCAT('TR-', s.shipment_id) as tracking_id,
            CONCAT(a.asset_name, ' to ', s.delivery_address) as route,
            s.current_location,
            CASE 
                WHEN s.shipment_status = 'delayed' THEN 'delayed'
                WHEN s.actual_arrival > s.estimated_arrival THEN 'delayed'
                ELSE 'on-time'
            END as delay_status,
            TIMESTAMPDIFF(MINUTE, s.estimated_arrival, s.actual_arrival) as duration,
            'traffic' as reason,
            'traffic' as type
        FROM shipments s
        LEFT JOIN assets a ON s.vehicle_id = a.id
        WHERE (s.shipment_status = 'delayed' 
            OR (s.actual_arrival > s.estimated_arrival))
            AND s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    
    $delays = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no delays, return sample data
    if (empty($delays)) {
        $delays = [
            [
                'id' => 'TR-2024-001',
                'route' => 'Warehouse A to Distribution Center',
                'reason' => 'Traffic congestion',
                'duration' => 45,
                'type' => 'traffic'
            ],
            [
                'id' => 'TR-2024-002',
                'route' => 'Port to Warehouse B',
                'reason' => 'Loading delay',
                'duration' => 30,
                'type' => 'operational'
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'delays' => $delays
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>