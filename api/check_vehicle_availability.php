<?php
// api/check_vehicle_availability.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['available' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['vehicle_id']) || !isset($input['from_date']) || !isset($input['to_date'])) {
    echo json_encode(['available' => false, 'error' => 'Invalid input']);
    exit();
}

$vehicle_id = $input['vehicle_id'];
$from_date = $input['from_date'];
$to_date = $input['to_date'];

try {
    $from = new DateTime($from_date);
    $to = new DateTime($to_date);
    
    $start_date = $from->format('Y-m-d');
    $start_time = $from->format('H:i:s');
    $end_date = $to->format('Y-m-d');
    $end_time = $to->format('H:i:s');
    
    // Check for overlapping reservations
    $stmt = $pdo->prepare("
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
    
    $stmt->execute([
        $vehicle_id,
        $start_date, $start_time, $end_date, $end_time,
        $start_date, $start_time, $end_time,
        $end_date, $start_time, $end_time,
        $start_date, $end_date
    ]);
    
    echo json_encode([
        'available' => $stmt->rowCount() === 0,
        'vehicle_id' => $vehicle_id
    ]);
    
} catch (Exception $e) {
    echo json_encode(['available' => false, 'error' => $e->getMessage()]);
}
?>