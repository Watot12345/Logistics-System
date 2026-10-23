<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

function formatDatetime($value) {
    if (empty($value)) return null;
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    if ($dt) {
        return $dt->format('Y-m-d H:i:s');
    }
    $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', $value);
    if ($dt) {
        return $dt->format('Y-m-d H:i:s');
    }
    return null;
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'employee'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
    $vehicle_id = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
    $shipment_status = $_POST['shipment_status'] ?? 'pending';
    $departure_time = !empty($_POST['departure_time']) ? formatDatetime($_POST['departure_time']) : null;
    $estimated_arrival = !empty($_POST['estimated_arrival']) ? formatDatetime($_POST['estimated_arrival']) : null;
    $current_location = trim($_POST['current_location'] ?? '');

    if (empty($customer_name) || empty($delivery_address)) {
        echo json_encode(['success' => false, 'message' => 'Customer name and delivery address are required']);
        exit();
    }

    $query = "INSERT INTO shipments (
        customer_name, delivery_address, driver_id, vehicle_id, 
        shipment_status, departure_time, estimated_arrival, current_location
    ) VALUES (
        :customer_name, :delivery_address, :driver_id, :vehicle_id,
        :shipment_status, :departure_time, :estimated_arrival, :current_location
    )";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':customer_name', $customer_name);
    $stmt->bindParam(':delivery_address', $delivery_address);
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