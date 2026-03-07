<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

try {
    // Get all vehicles that are in good condition
    $vehicles = [];
    
    // First, get all vehicles
    $stmt = $pdo->query("
        SELECT id, asset_name, asset_condition 
        FROM assets 
        WHERE asset_type = 'vehicle' AND status = 'good'
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $vehicle_id = $row['id'];
        $vehicle_name = $row['asset_name'];
        
        // Check if vehicle has pending maintenance
        $maintenance_check = $pdo->prepare("
            SELECT id FROM maintenance_alerts 
            WHERE asset_name = ? AND status = 'pending'
        ");
        $maintenance_check->execute([$vehicle_name]);
        
        if ($maintenance_check->rowCount() > 0) {
            continue; // Skip vehicles with pending maintenance
        }
        
        // Check if vehicle is in use
        $shipment_check = $pdo->prepare("
            SELECT shipment_id FROM shipments 
            WHERE vehicle_id = ? AND shipment_status IN ('in_transit', 'pending')
        ");
        $shipment_check->execute([$vehicle_id]);
        
        if ($shipment_check->rowCount() > 0) {
            continue; // Skip vehicles in use
        }
        
        // Vehicle is available
        $vehicles[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'vehicles' => $vehicles,
        'count' => count($vehicles)
    ]);
    
} catch (PDOException $e) {
    error_log("Get vehicles error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>