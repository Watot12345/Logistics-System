<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

require_once '../config/db.php';

// Only admin and dispatcher can approve/reject
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'dispatcher'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['reservation_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$valid_statuses = ['approved', 'rejected'];
if (!in_array($data['status'], $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Update reservation status
    $stmt = $pdo->prepare("
        UPDATE vehicle_reservations 
        SET status = ?, 
            updated_at = NOW() 
        WHERE id = ?
    ");
    
    $stmt->execute([$data['status'], $data['reservation_id']]);
    
    // If approved, create dispatch schedule AND shipment
    if ($data['status'] === 'approved') {
        // Get reservation details with ALL fields
        $stmt = $pdo->prepare("
            SELECT r.*, a.asset_name, a.asset_condition
            FROM vehicle_reservations r
            JOIN assets a ON r.vehicle_id = a.id
            WHERE r.id = ?
        ");
        $stmt->execute([$data['reservation_id']]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 1. Create dispatch schedule
        $dispatch_stmt = $pdo->prepare("
            INSERT INTO dispatch_schedule (
                reservation_id, 
                vehicle_id, 
                driver_id, 
                scheduled_date, 
                shift,
                status,
                created_at
            ) VALUES (?, ?, NULL, ?, ?, 'scheduled', NOW())
        ");
        
        // Determine shift based on start time
        $hour = $reservation['start_time'] ? date('H', strtotime($reservation['start_time'])) : 9;
        $shift = 'morning';
        if ($hour >= 12 && $hour < 17) {
            $shift = 'afternoon';
        } elseif ($hour >= 17) {
            $shift = 'night';
        }
        
        $dispatch_stmt->execute([
            $data['reservation_id'],
            $reservation['vehicle_id'],
            $reservation['start_date'],
            $shift
        ]);
        
        // 2. Create shipment with customer data
        $shipment_stmt = $pdo->prepare("
            INSERT INTO shipments (
                customer_name,
                delivery_address,
                vehicle_id,
                shipment_status,
                departure_time,
                estimated_arrival,
                created_at
            ) VALUES (?, ?, ?, 'pending', ?, ?, NOW())
        ");
        
        // Combine date and time
        $departure = $reservation['start_date'] . ' ' . ($reservation['start_time'] ?? '09:00:00');
        $arrival = $reservation['end_date'] . ' ' . ($reservation['end_time'] ?? '17:00:00');
        
        $shipment_stmt->execute([
            $reservation['customer_name'] ?? 'Customer',
            $reservation['delivery_address'] ?? 'To be assigned',
            $reservation['vehicle_id'],
            $departure,
            $arrival
        ]);
        
        $shipment_id = $pdo->lastInsertId();
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reservation ' . $data['status'] . ' successfully',
        'shipment_id' => $shipment_id ?? null
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Update reservation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>