<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'employee'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
    $vehicle_id = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
    $shipment_status = $_POST['shipment_status'] ?? 'pending';
    $departure_time = !empty($_POST['departure_time']) ? $_POST['departure_time'] : null;
    $estimated_arrival = !empty($_POST['estimated_arrival']) ? $_POST['estimated_arrival'] : null;
    $current_location = trim($_POST['current_location'] ?? '');
    
    if (empty($_POST['customer_name']) || empty($_POST['delivery_address'])) {
        echo json_encode(['success' => false, 'message' => 'Customer name and delivery address are required']);
        exit();
    }
    
    // Note: You might need to adjust this query based on your shipments table structure
    $query = "INSERT INTO shipments (
        driver_id, vehicle_id, shipment_status, departure_time, 
        estimated_arrival, current_location, created_at
    ) VALUES (
        :driver_id, :vehicle_id, :shipment_status, :departure_time,
        :estimated_arrival, :current_location, NOW()
    )";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->bindParam(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
    $stmt->bindParam(':shipment_status', $shipment_status);
    $stmt->bindParam(':departure_time', $departure_time);
    $stmt->bindParam(':estimated_arrival', $estimated_arrival);
    $stmt->bindParam(':current_location', $current_location);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Shipment added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add shipment']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>