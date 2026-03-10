<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$schedule_id = $data['schedule_id'] ?? null;
$status = $data['status'] ?? null;
$location = $data['current_location'] ?? null;

if (!$schedule_id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

try {
    // Map the status values correctly
    $valid_statuses = ['in-progress', 'delivered', 'awaiting_verification', 'completed', 'cancelled'];
    
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit();
    }
    
    $stmt = $pdo->prepare("
        UPDATE dispatch_schedule 
        SET status = ?, 
            notes = CONCAT(IFNULL(notes, ''), '\n[', NOW(), '] Status changed to ', ?, ' - Location: ', ?),
            updated_at = NOW() 
        WHERE id = ?
    ");
    
    $stmt->execute([$status, $status, $location, $schedule_id]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log("Error updating dispatch status: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>