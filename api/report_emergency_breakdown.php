<?php
// api/report_emergency_breakdown.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Enable error logging
error_log("========== EMERGENCY BREAKDOWN REPORT ==========");
error_log("User ID: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("User Role: " . ($_SESSION['role'] ?? 'not set'));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - You must be logged in as a driver']);
    exit();
}

// Get POST data
$input = file_get_contents('php://input');
error_log("Raw input: " . $input);

$data = json_decode($input, true);
error_log("Decoded data: " . print_r($data, true));

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
$required = ['vehicle_id', 'vehicle_name', 'issue_type', 'priority', 'description', 'location'];
$missing = [];
foreach ($required as $field) {
    if (empty($data[$field])) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Missing required fields: ' . implode(', ', $missing)
    ]);
    exit();
}

try {
    // Check if table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'emergency_breakdowns'");
    if ($checkTable->rowCount() == 0) {
        throw new Exception("emergency_breakdowns table does not exist");
    }
    
    // Get current dispatch schedule (if any)
    $stmt = $pdo->prepare("
        SELECT id FROM dispatch_schedule 
        WHERE driver_id = ? AND status IN ('in-progress', 'scheduled')
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $dispatch = $stmt->fetch();
    
    error_log("Dispatch schedule found: " . ($dispatch ? 'YES (ID: ' . $dispatch['id'] . ')' : 'NO'));
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Insert into emergency_breakdowns
    $stmt = $pdo->prepare("
        INSERT INTO emergency_breakdowns (
            vehicle_id, 
            vehicle_name, 
            driver_id, 
            dispatch_schedule_id,
            issue_type, 
            priority, 
            description, 
            location, 
            can_drive
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $data['vehicle_id'],
        $data['vehicle_name'],
        $_SESSION['user_id'],
        $dispatch['id'] ?? null,
        $data['issue_type'],
        $data['priority'],
        $data['description'],
        $data['location'],
        $data['can_drive'] ?? 'no'
    ]);
    
    if (!$result) {
        throw new Exception("Failed to insert emergency breakdown");
    }
    
    $breakdown_id = $pdo->lastInsertId();
    error_log("Emergency breakdown inserted with ID: " . $breakdown_id);
    
    // Update dispatch schedule notes to mention emergency
    if ($dispatch) {
        $updateNotes = $pdo->prepare("
            UPDATE dispatch_schedule 
            SET notes = CONCAT(COALESCE(notes, ''), ?)
            WHERE id = ?
        ");
        $note = "\n[" . date('Y-m-d H:i:s') . "] EMERGENCY REPORTED: " . $data['issue_type'] . " - " . $data['description'] . " at " . $data['location'];
        $updateNotes->execute([$note, $dispatch['id']]);
        error_log("Updated dispatch schedule notes");
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Emergency reported successfully',
        'breakdown_id' => $breakdown_id
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Emergency breakdown error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>