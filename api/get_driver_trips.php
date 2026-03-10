<?php
// api/get_driver_trips.php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$driver_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

try {
    // Use named parameters to avoid quote issues
    $sql = "
        SELECT 
            ds.id,
            ds.vehicle_id,
            ds.driver_id,
            ds.scheduled_date,
            ds.status,
            ds.shift,
            ds.notes,
            a.asset_name as vehicle_name,
            vr.customer_name,
            vr.delivery_address,
            DATE_FORMAT(ds.scheduled_date, '%b %d, %Y') as formatted_date
        FROM dispatch_schedule ds
        LEFT JOIN assets a ON ds.vehicle_id = a.id
        LEFT JOIN vehicle_reservations vr ON ds.reservation_id = vr.id
        WHERE ds.driver_id = :driver_id
        ORDER BY ds.scheduled_date DESC
        LIMIT :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters with explicit types
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    
    $stmt->execute();
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted_trips = [];
    foreach ($trips as $trip) {
        // Map status for display
        $display_status = $trip['status'];
        if ($trip['status'] === 'in-progress') {
            $display_status = 'in_transit';
        }
        
        $formatted_trips[] = [
            'customer_name' => $trip['customer_name'] ?? 'Trip #' . $trip['id'],
            'vehicle_name' => $trip['vehicle_name'] ?? 'Unknown Vehicle',
            'departure_time' => $trip['formatted_date'] ?? 'N/A',
            'shipment_status' => $display_status
        ];
    }
    
    echo json_encode([
        'success' => true,
        'has_assignments' => !empty($formatted_trips),
        'trips' => $formatted_trips,
        'count' => count($formatted_trips)
    ]);
    
} catch (PDOException $e) {
    error_log("Get driver trips error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>