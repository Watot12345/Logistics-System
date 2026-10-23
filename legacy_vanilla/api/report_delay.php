<?php
// api/report_delay.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Validate required fields
$required = ['shipment_id', 'reason', 'duration'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'error' => "$field is required"]);
        exit();
    }
}

$shipment_id = $input['shipment_id'];
$reason = $input['reason'];
$duration = $input['duration']; // Now stores text like "2 hours"
$route = $input['route'] ?? 'Unknown route';
$driver_id = $_SESSION['user_id'];

// Determine delay type based on reason keywords
$delay_type = 'other';
$reason_lower = strtolower($reason);
if (strpos($reason_lower, 'traffic') !== false) $delay_type = 'traffic';
else if (strpos($reason_lower, 'weather') !== false) $delay_type = 'weather';
else if (strpos($reason_lower, 'mechanic') !== false) $delay_type = 'mechanical';
else if (strpos($reason_lower, 'load') !== false) $delay_type = 'loading';
else if (strpos($reason_lower, 'accident') !== false) $delay_type = 'accident';

try {
    // Insert delay record
    $stmt = $pdo->prepare("
        INSERT INTO shipment_delays 
        (shipment_id, driver_id, route_name, delay_reason, delay_duration, delay_type, reported_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $shipment_id,
        $driver_id,
        $route,
        $reason,
        $duration, // Now storing as text
        $delay_type
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Delay reported successfully',
        'delay_id' => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    error_log("Report delay error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>