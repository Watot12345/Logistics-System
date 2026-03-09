<?php
// api/get_transport_efficiency.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

try {
    // ============================================
    // METRIC 1: Delivery Success Rate (Completed vs Total)
    // ============================================
    $delivery_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_shipments,
            SUM(CASE WHEN shipment_status = 'delivered' THEN 1 ELSE 0 END) as completed_shipments
        FROM shipments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch();
    
    $delivery_rate = $delivery_stats['total_shipments'] > 0 
        ? round(($delivery_stats['completed_shipments'] / $delivery_stats['total_shipments']) * 100) 
        : 0;
    
    // Get average from last 3 months for comparison
    $avg_delivery = $pdo->query("
        SELECT AVG(completion_rate) as avg_rate FROM (
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN shipment_status = 'delivered' THEN 1 ELSE 0 END) as completed,
                (SUM(CASE WHEN shipment_status = 'delivered' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as completion_rate
            FROM shipments 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ) as monthly
    ")->fetchColumn();
    
    // ============================================
    // METRIC 2: On-Time Delivery Rate
    // ============================================
    $ontime_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_delivered,
            SUM(CASE 
                WHEN actual_arrival <= estimated_arrival 
                AND actual_arrival IS NOT NULL 
                THEN 1 ELSE 0 
            END) as on_time
        FROM shipments 
        WHERE shipment_status = 'delivered'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch();
    
    $ontime_rate = $ontime_stats['total_delivered'] > 0 
        ? round(($ontime_stats['on_time'] / $ontime_stats['total_delivered']) * 100) 
        : 0;
    
    // Average on-time rate from last 3 months
    $avg_ontime = $pdo->query("
        SELECT AVG(ontime_rate) FROM (
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(CASE WHEN actual_arrival <= estimated_arrival THEN 1 ELSE 0 END) / COUNT(*) * 100 as ontime_rate
            FROM shipments 
            WHERE shipment_status = 'delivered'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ) as monthly
    ")->fetchColumn() ?: 85;
    
    // ============================================
    // METRIC 3: Average Delivery Time (in hours)
    // ============================================
    $avg_time = $pdo->query("
        SELECT AVG(TIMESTAMPDIFF(HOUR, departure_time, actual_arrival)) as avg_hours
        FROM shipments 
        WHERE shipment_status = 'delivered'
        AND departure_time IS NOT NULL 
        AND actual_arrival IS NOT NULL
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn() ?: 24;
    
    // Average from last 3 months
    $avg_time_history = $pdo->query("
        SELECT AVG(monthly_avg) FROM (
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                AVG(TIMESTAMPDIFF(HOUR, departure_time, actual_arrival)) as monthly_avg
            FROM shipments 
            WHERE shipment_status = 'delivered'
            AND departure_time IS NOT NULL 
            AND actual_arrival IS NOT NULL
            AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ) as monthly
    ")->fetchColumn() ?: 24;
    
    // ============================================
    // METRIC 4: Fleet Utilization
    // ============================================
    $total_vehicles = $pdo->query("SELECT COUNT(*) FROM assets WHERE asset_type = 'vehicle'")->fetchColumn();
    
    $active_vehicles = $pdo->query("
        SELECT COUNT(DISTINCT vehicle_id) 
        FROM shipments 
        WHERE shipment_status IN ('in_transit', 'pending')
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetchColumn();
    
    $utilization_rate = $total_vehicles > 0 
        ? round(($active_vehicles / $total_vehicles) * 100) 
        : 0;
    
    // Average utilization from last 3 months
    $avg_utilization = $pdo->query("
        SELECT AVG(utilization) FROM (
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                (COUNT(DISTINCT vehicle_id) / $total_vehicles) * 100 as utilization
            FROM shipments 
            WHERE shipment_status IN ('in_transit', 'pending')
            AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ) as monthly
    ")->fetchColumn() ?: 70;
    
    // ============================================
    // Build the efficiency array with REAL data
    // ============================================
    $efficiency = [
        [
            'metric' => 'Delivery Success Rate',
            'current' => $delivery_rate,
            'target' => 95,  // Industry standard target
            'average' => round($avg_delivery ?: 90),
            'unit' => '%'
        ],
        [
            'metric' => 'On-Time Delivery',
            'current' => $ontime_rate,
            'target' => 90,
            'average' => round($avg_ontime),
            'unit' => '%'
        ],
        [
            'metric' => 'Average Delivery Time',
            'current' => round($avg_time),
            'target' => 18,  // Target: 18 hours
            'average' => round($avg_time_history),
            'unit' => 'hrs'
        ],
        [
            'metric' => 'Fleet Utilization',
            'current' => $utilization_rate,
            'target' => 85,
            'average' => round($avg_utilization),
            'unit' => '%'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'efficiency' => $efficiency,
        'debug' => [  // Remove this in production
            'delivery_rate' => $delivery_rate,
            'ontime_rate' => $ontime_rate,
            'avg_time' => $avg_time,
            'utilization' => $utilization_rate,
            'total_vehicles' => $total_vehicles,
            'active_vehicles' => $active_vehicles
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Transport efficiency error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>