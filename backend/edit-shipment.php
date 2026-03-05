<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'employee'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $shipment_id = intval($_POST['shipment_id']);
    $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
    $vehicle_id = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
    $shipment_status = $_POST['shipment_status'] ?? 'pending';
    $departure_time = !empty($_POST['departure_time']) ? $_POST['departure_time'] : null;
    $estimated_arrival = !empty($_POST['estimated_arrival']) ? $_POST['estimated_arrival'] : null;
    $actual_arrival = !empty($_POST['actual_arrival']) ? $_POST['actual_arrival'] : null;
    $current_location = trim($_POST['current_location'] ?? '');
    
    $query = "UPDATE shipments SET 
                driver_id = :driver_id,
                vehicle_id = :vehicle_id,
                shipment_status = :shipment_status,
                departure_time = :departure_time,
                estimated_arrival = :estimated_arrival,
                actual_arrival = :actual_arrival,
                current_location = :current_location
              WHERE shipment_id = :shipment_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
    $stmt->bindParam(':shipment_status', $shipment_status);
    $stmt->bindParam(':departure_time', $departure_time);
    $stmt->bindParam(':estimated_arrival', $estimated_arrival);
    $stmt->bindParam(':actual_arrival', $actual_arrival);
    $stmt->bindParam(':current_location', $current_location);
    $stmt->bindParam(':shipment_id', $shipment_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        // Add tracking entry for status change
        if (isset($_POST['old_status']) && $_POST['old_status'] != $shipment_status) {
            $tracking_query = "INSERT INTO shipment_tracking (shipment_id, location, status_update, updated_at) 
                              VALUES (:shipment_id, :location, :status_update, NOW())";
            $tracking_stmt = $pdo->prepare($tracking_query);
            $tracking_stmt->bindParam(':shipment_id', $shipment_id, PDO::PARAM_INT);
            $tracking_stmt->bindParam(':location', $current_location);
            $status_message = "Status changed from {$_POST['old_status']} to {$shipment_status}";
            $tracking_stmt->bindParam(':status_update', $status_message);
            $tracking_stmt->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Shipment updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update shipment']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>