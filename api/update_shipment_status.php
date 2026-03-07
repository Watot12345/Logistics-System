<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Only admins and dispatchers can approve/reject
if (!in_array($_SESSION['role'], ['admin', 'dispatcher'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['reservation_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

try {
    // Validate status
    if (!in_array($input['status'], ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit();
    }
    
    $stmt = $pdo->prepare("
        UPDATE vehicle_reservations 
        SET status = :status, 
            approved_by = :approved_by, 
            approved_at = NOW(),
            rejected_reason = :reason
        WHERE id = :id
    ");
    
    $stmt->execute([
        'status' => $input['status'],
        'approved_by' => $_SESSION['user_id'],
        'reason' => $input['reason'] ?? null,
        'id' => $input['reservation_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Reservation ' . $input['status']
    ]);
    
} catch (PDOException $e) {
    error_log("Update reservation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
?>