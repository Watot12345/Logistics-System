<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

try {
    // DIAGNOSTIC: Check SQL mode and raw asset counts
    $diag = [];
    
    // Check current SQL mode (ONLY_FULL_GROUP_BY can break GROUP BY queries)
    $sql_mode_row = $pdo->query("SELECT @@sql_mode as sql_mode")->fetch(PDO::FETCH_ASSOC);
    $diag['sql_mode'] = $sql_mode_row['sql_mode'] ?? 'unknown';
    
    // Check raw count of ALL assets
    $total_assets = $pdo->query("SELECT COUNT(*) as cnt FROM assets")->fetch(PDO::FETCH_ASSOC);
    $diag['total_assets'] = $total_assets['cnt'] ?? 0;
    
    // Check count of vehicle-type assets specifically
    $vehicle_assets = $pdo->query("SELECT COUNT(*) as cnt FROM assets WHERE asset_type = 'vehicle'")->fetch(PDO::FETCH_ASSOC);
    $diag['vehicle_type_count'] = $vehicle_assets['cnt'] ?? 0;
    
    // Check distinct asset_type values to detect casing/value mismatch
    $asset_types = $pdo->query("SELECT DISTINCT asset_type, COUNT(*) as cnt FROM assets GROUP BY asset_type")->fetchAll(PDO::FETCH_ASSOC);
    $diag['asset_types'] = $asset_types;
    
    error_log("DIAG get_vehicles - SQL mode: " . $diag['sql_mode']);
    error_log("DIAG get_vehicles - Total assets: " . $diag['total_assets'] . ", Vehicle-type: " . $diag['vehicle_type_count']);
    error_log("DIAG get_vehicles - Asset types: " . json_encode($asset_types));

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
            -- Check if vehicle has active maintenance (pending OR in_progress)
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
            AND m.status IN ('pending', 'in_progress')
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
    
    $diag['vehicles_returned'] = count($vehicles);
    error_log("DIAG get_vehicles - Vehicles returned by query: " . count($vehicles));
    
    echo json_encode([
        'success' => true,
        'vehicles' => $vehicles,
        'debug' => $diag  // DIAGNOSTIC: remove after confirming fix
    ]);
    
} catch (PDOException $e) {
    error_log("Get vehicles error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => $diag ?? []
    ]);
}
?>