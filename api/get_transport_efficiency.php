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
    // Get shipment statistics from last 30 days
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_shipments,
            SUM(CASE WHEN shipment_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN shipment_status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
            SUM(CASE WHEN shipment_status = 'delayed' THEN 1 ELSE 0 END) as delayed,
            SUM(CASE WHEN actual_arrival <= estimated_arrival AND actual_arrival IS NOT NULL THEN 1 ELSE 0 END) as on_time
        FROM shipments
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate percentages
    $total = $stats['total_shipments'] ?: 1;
    $on_time_rate = $stats['total_shipments'] > 0 
        ? round(($stats['on_time'] / $stats['total_shipments']) * 100) 
        : 94;
    
    $efficiency = [
        [
            'metric' => 'On-time Delivery',
            'current' => $on_time_rate,
            'average' => 92,
            'target' => 98,
            'unit' => '%'
        ],
        [
            'metric' => 'Delivery Success Rate',
            'current' => round(($stats['delivered'] / $total) * 100),
            'average' => 95,
            'target' => 98,
            'unit' => '%'
        ],
        [
            'metric' => 'Active Shipments',
            'current' => $stats['in_transit'],
            'average' => 8,
            'target' => 12,
            'unit' => 'count'
        ],
        [
            'metric' => 'Delay Rate',
            'current' => round(($stats['delayed'] / $total) * 100),
            'average' => 8,
            'target' => 5,
            'unit' => '%'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'efficiency' => $efficiency
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>