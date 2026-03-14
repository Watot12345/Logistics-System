<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user is a mechanic
if ($_SESSION['role'] !== 'mechanic') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$mechanic_id = $_SESSION['user_id'];

try {
    // Get breakdowns assigned to this mechanic
    $stmt = $pdo->prepare("
        SELECT 
            eb.*,
            d.full_name as driver_name,
            d.phone as driver_phone,
            a.asset_condition,
            ds.status as dispatch_status,
            ds.scheduled_date,
            ds.shift
        FROM emergency_breakdowns eb
        LEFT JOIN users d ON eb.driver_id = d.id
        LEFT JOIN assets a ON eb.vehicle_id = a.id
        LEFT JOIN dispatch_schedule ds ON eb.dispatch_schedule_id = ds.id
        WHERE eb.assigned_mechanic = ? 
        AND eb.status IN ('assigned', 'in_progress')
        ORDER BY 
            CASE eb.priority
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END,
            eb.assigned_at DESC
    ");
    
    $stmt->execute([$mechanic_id]);
    $breakdowns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM emergency_breakdowns 
        WHERE assigned_mechanic = ? AND status IN ('assigned', 'in_progress')
    ");
    $countStmt->execute([$mechanic_id]);
    $activeCount = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'breakdowns' => $breakdowns,
        'active_count' => $activeCount
    ]);
    
} catch (PDOException $e) {
    error_log("Error in get_my_breakdowns: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>