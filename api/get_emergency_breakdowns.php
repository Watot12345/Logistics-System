<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user is admin or fleet manager
if (!in_array($_SESSION['role'], ['admin', 'fleet_manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

try {
    // Get all unreported/resolved emergency breakdowns
    // REMOVED ds.route since it doesn't exist
    $stmt = $pdo->prepare("
        SELECT 
            eb.*,
            d.full_name as driver_name,
            d.phone as driver_phone,
            ds.status as dispatch_status,
            ds.scheduled_date,
            ds.shift,
            a.asset_condition,
            vr.customer_name,
            vr.delivery_address,
            vr.purpose as route_description
        FROM emergency_breakdowns eb
        LEFT JOIN users d ON eb.driver_id = d.id
        LEFT JOIN dispatch_schedule ds ON eb.dispatch_schedule_id = ds.id
        LEFT JOIN assets a ON eb.vehicle_id = a.id
        LEFT JOIN vehicle_reservations vr ON ds.reservation_id = vr.id
        WHERE eb.status IN ('reported', 'assigned', 'in_progress')
        ORDER BY 
            CASE eb.priority
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END,
            eb.reported_at DESC
    ");
    
    $stmt->execute();
    $breakdowns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get count for badge
    $countStmt = $pdo->query("SELECT COUNT(*) FROM emergency_breakdowns WHERE status IN ('reported', 'assigned', 'in_progress')");
    $activeCount = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'breakdowns' => $breakdowns,
        'active_count' => $activeCount
    ]);
    
} catch (PDOException $e) {
    error_log("Error in get_emergency_breakdowns: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>