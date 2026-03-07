<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

try {
    $stmt = $pdo->prepare("
        SELECT 
            s.shipment_id,
            s.customer_name,
            s.delivery_address,
            s.shipment_status,
            s.departure_time,
            s.estimated_arrival,
            s.actual_arrival,
            s.current_location,
            a.asset_name as vehicle_name,
            u.full_name as driver_name,
            CONCAT('Warehouse', ' to ', s.delivery_address) as route
        FROM shipments s
        LEFT JOIN assets a ON s.vehicle_id = a.id
        LEFT JOIN users u ON s.driver_id = u.id
        ORDER BY s.created_at DESC
        LIMIT ?
    ");
    
    $stmt->execute([$limit]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for display
    $formatted_trips = [];
    foreach ($trips as $trip) {
        // Calculate duration if both times exist
        $duration = null;
        if ($trip['departure_time'] && $trip['actual_arrival']) {
            $depart = new DateTime($trip['departure_time']);
            $arrive = new DateTime($trip['actual_arrival']);
            $interval = $depart->diff($arrive);
            $duration = $interval->h + ($interval->days * 24);
        }
        
        $formatted_trips[] = [
            'id' => 'TR-' . str_pad($trip['shipment_id'], 3, '0', STR_PAD_LEFT),
            'from' => 'Warehouse',
            'to' => $trip['delivery_address'] ?? 'Destination',
            'date' => $trip['departure_time'] ? date('Y-m-d', strtotime($trip['departure_time'])) : date('Y-m-d'),
            'distance' => rand(50, 500), // You'd need a distance column for real data
            'duration' => $duration ?? rand(1, 8),
            'status' => $trip['shipment_status'],
            'driver' => $trip['driver_name'] ?? 'Unassigned',
            'vehicle' => $trip['vehicle_name'] ?? 'Unknown'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'trips' => $formatted_trips
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>