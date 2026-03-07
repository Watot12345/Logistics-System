<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['shipment_id']) || !isset($data['current_location'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$driver_id = $_SESSION['user_id'];
$shipment_id = $data['shipment_id'];
$current_location = $data['current_location'];

try {
    // Verify this shipment belongs to the driver
    $stmt = $pdo->prepare("
        SELECT shipment_id FROM shipments 
        WHERE shipment_id = ? AND driver_id = ?
    ");
    $stmt->execute([$shipment_id, $driver_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to update this shipment']);
        exit();
    }
    
    // Update location
    $stmt = $pdo->prepare("
        UPDATE shipments 
        SET current_location = ?,
            updated_at = NOW()
        WHERE shipment_id = ?
    ");
    
    $stmt->execute([$current_location, $shipment_id]);
    
    // Add to tracking history
    $stmt = $pdo->prepare("
        INSERT INTO shipment_tracking (shipment_id, location, status_update, updated_at)
        VALUES (?, ?, 'Location updated', NOW())
    ");
    $stmt->execute([$shipment_id, $current_location]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Location updated successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>