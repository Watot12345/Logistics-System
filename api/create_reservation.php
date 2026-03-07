<?php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Validate required fields
$required = ['vehicle_id', 'customer_name', 'delivery_address', 'purpose', 'from_date', 'to_date'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'error' => "$field is required"]);
        exit();
    }
}

$vehicle_id = $input['vehicle_id'];
$customer_name = $input['customer_name'];
$department = $input['department'] ?? '';
$delivery_address = $input['delivery_address'];
$purpose = $input['purpose'];
$from_date = $input['from_date'];
$to_date = $input['to_date'];
$user_id = $_SESSION['user_id'];

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Check if vehicle exists and is available
    $vehicle_check = $pdo->prepare("
        SELECT a.*, 
            CASE 
                WHEN s.shipment_id IS NOT NULL THEN 1
                ELSE 0
            END as is_in_use,
            CASE 
                WHEN m.id IS NOT NULL THEN 1
                ELSE 0
            END as has_maintenance
        FROM assets a
        LEFT JOIN shipments s ON a.id = s.vehicle_id 
            AND s.shipment_status IN ('in_transit', 'pending')
        LEFT JOIN maintenance_alerts m ON a.asset_name = m.asset_name 
            AND m.status = 'pending'
        WHERE a.id = ? AND a.asset_type = 'vehicle'
    ");
    $vehicle_check->execute([$vehicle_id]);
    $vehicle = $vehicle_check->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Vehicle not found']);
        exit();
    }

    // Check if vehicle is in maintenance
    if ($vehicle['has_maintenance']) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Vehicle is currently in maintenance and cannot be reserved']);
        exit();
    }

    // Check if vehicle is in use
    if ($vehicle['is_in_use']) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Vehicle is currently in use and cannot be reserved']);
        exit();
    }

    // Check if vehicle status is good
    if ($vehicle['status'] !== 'good') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Vehicle is not available for reservation']);
        exit();
    }

    // Parse dates
    $from = new DateTime($from_date);
    $to = new DateTime($to_date);
    
    // Extract date and time
    $start_date = $from->format('Y-m-d');
    $start_time = $from->format('H:i:s');
    $end_date = $to->format('Y-m-d');
    $end_time = $to->format('H:i:s');

    // Check for overlapping reservations
    $overlap_check = $pdo->prepare("
        SELECT id FROM vehicle_reservations 
        WHERE vehicle_id = ? 
        AND status IN ('pending', 'approved')
        AND (
            (start_date = ? AND start_time < ? AND end_date = ? AND end_time > ?)
            OR
            (start_date = ? AND start_time >= ? AND start_time < ?)
            OR
            (end_date = ? AND end_time > ? AND end_time <= ?)
            OR
            (start_date < ? AND end_date > ?)
        )
    ");
    
    $overlap_check->execute([
        $vehicle_id,
        $start_date, $start_time, $end_date, $end_time,
        $start_date, $start_time, $end_time,
        $end_date, $start_time, $end_time,
        $start_date, $end_date
    ]);
    
    if ($overlap_check->rowCount() > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Vehicle is already reserved for this time period']);
        exit();
    }

    // Insert reservation with new fields
    $insert = $pdo->prepare("
        INSERT INTO vehicle_reservations (
            vehicle_id, 
            requester_id, 
            customer_name,
            department, 
            delivery_address,
            purpose, 
            start_date, 
            end_date, 
            start_time, 
            end_time, 
            status, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $insert->execute([
        $vehicle_id,
        $user_id,
        $customer_name,
        $department,
        $delivery_address,
        $purpose,
        $start_date,
        $end_date,
        $start_time,
        $end_time
    ]);
    
    $reservation_id = $pdo->lastInsertId();
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'reservation_id' => $reservation_id,
        'message' => 'Reservation created successfully'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Create reservation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>