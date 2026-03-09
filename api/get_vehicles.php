<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

try {
    // Get all vehicles with their current status
    $stmt = $pdo->prepare("
        SELECT 
            a.id, 
            a.asset_name, 
            a.asset_type,
            a.status as asset_status,
            a.asset_condition,
            -- Check if vehicle has active shipment
            CASE 
                WHEN s.shipment_id IS NOT NULL THEN 1
                ELSE 0
            END as is_in_use,
            s.shipment_status,
            -- Get driver from dispatch_schedule (where drivers are actually assigned)
            u.full_name as current_driver,
            -- Check if vehicle has pending maintenance
            CASE 
                WHEN m.id IS NOT NULL THEN 1
                ELSE 0
            END as has_pending_maintenance,
            m.issue as maintenance_issue,
            m.priority as maintenance_priority,
            m.due_date,
            -- Default values
            '85' as fuel_level,
            '15000' as mileage
        FROM assets a
        LEFT JOIN shipments s ON a.id = s.vehicle_id 
            AND s.shipment_status IN ('in_transit', 'pending')
        LEFT JOIN dispatch_schedule ds ON a.id = ds.vehicle_id 
            AND ds.status IN ('in-progress', 'scheduled')
        LEFT JOIN users u ON COALESCE(s.driver_id, ds.driver_id) = u.id
        LEFT JOIN maintenance_alerts m ON a.asset_name = m.asset_name 
            AND m.status = 'pending'
        WHERE a.asset_type = 'vehicle'
        ORDER BY 
            CASE 
                WHEN m.id IS NOT NULL THEN 1
                WHEN s.shipment_id IS NOT NULL THEN 2
                ELSE 3
            END,
            a.asset_name
    ");
    
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'vehicles' => $vehicles
    ]);
    
} catch (PDOException $e) {
    error_log("Get vehicles error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>