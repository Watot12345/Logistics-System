<?php
// api/get_trip_history.php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

try {
    // Get data from dispatch_schedule (where your actual data is)
    $stmt = $pdo->prepare("
        SELECT 
            CONCAT('DSP-', ds.id) as id,
            COALESCE(vr.customer_name, 'Customer') as customer_name,
            COALESCE(vr.delivery_address, 'N/A') as delivery_address,
            CONCAT(ds.scheduled_date, ' ', COALESCE(vr.start_time, '09:00:00')) as departure_time,
            ds.status,
            u.full_name as driver_name,
            a.asset_name as vehicle_name,
            'Dispatch' as source_location,
            vr.delivery_address as destination,
            ds.notes
        FROM dispatch_schedule ds
        LEFT JOIN users u ON ds.driver_id = u.id
        LEFT JOIN assets a ON ds.vehicle_id = a.id
        LEFT JOIN vehicle_reservations vr ON ds.reservation_id = vr.id
        WHERE ds.scheduled_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
        ORDER BY ds.scheduled_date DESC
        LIMIT :limit
    ");
    
    $stmt->bindParam(':days', $days, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted_trips = [];
    foreach ($trips as $trip) {
        // Determine status display
        $display_status = $trip['status'];
        if ($trip['status'] === 'completed') {
            $display_status = 'delivered';
        } elseif ($trip['status'] === 'in-progress') {
            $display_status = 'in-transit';
        }
        
        $formatted_trips[] = [
            'id' => $trip['id'],
            'from' => 'Dispatch Center',
            'to' => $trip['destination'] ?? $trip['delivery_address'] ?? 'Unknown',
            'driver' => $trip['driver_name'] ?? 'Unassigned',
            'vehicle' => $trip['vehicle_name'] ?? 'Unknown',
            'date' => $trip['departure_time'] ? date('M d, Y', strtotime($trip['departure_time'])) : 'N/A',
            'distance' => rand(10, 500), // Placeholder
            'duration' => 2, // Placeholder
            'status' => $display_status
        ];
    }
    
    echo json_encode([
        'success' => true,
        'trips' => $formatted_trips,
        'count' => count($formatted_trips)
    ]);
    
} catch (PDOException $e) {
    error_log("Get trip history error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>