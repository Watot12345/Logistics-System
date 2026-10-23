<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // Use created_at instead of updated_at since that's what your table has
    $stmt = $pdo->query("
        SELECT 
            ds.id, 
            ds.created_at, 
            a.asset_name, 
            u.full_name as driver_name,
            DATE_FORMAT(ds.created_at, '%b %d, %H:%i') as requested_time
        FROM dispatch_schedule ds
        LEFT JOIN assets a ON ds.vehicle_id = a.id
        LEFT JOIN users u ON ds.driver_id = u.id
        WHERE ds.status = 'awaiting_verification'
        ORDER BY ds.created_at DESC
    ");
    
    $verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'verifications' => $verifications
    ]);
    
} catch (PDOException $e) {
    error_log("Error in get_pending_verifications: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>