<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is dispatcher
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user is dispatcher
$isDispatcher = false;
if (isset($_SESSION['user_data']) && is_array($_SESSION['user_data'])) {
    if (isset($_SESSION['user_data']['role']) && $_SESSION['user_data']['role'] === 'dispatcher') {
        $isDispatcher = true;
    }
}
if (!$isDispatcher && isset($_SESSION['role']) && $_SESSION['role'] === 'dispatcher') {
    $isDispatcher = true;
}

if (!$isDispatcher) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden - Dispatcher access only']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['training_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Get training record
    $stmt = $pdo->prepare("
        SELECT dt.*, u.id as user_id 
        FROM driver_training dt
        JOIN users u ON dt.user_id = u.id
        WHERE dt.id = :id
    ");
    $stmt->execute([':id' => $data['training_id']]);
    $training = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$training) {
        throw new Exception('Training record not found');
    }

    // Update training status
    $completed_date = null;
    if ($data['status'] === 'passed' || $data['status'] === 'failed') {
        $completed_date = date('Y-m-d');
    }

    $stmt = $pdo->prepare("
        UPDATE driver_training 
        SET training_status = :status, 
            notes = :notes,
            completed_date = :completed_date
        WHERE id = :id
    ");
    $stmt->execute([
        ':status' => $data['status'],
        ':notes' => $data['notes'] ?? null,
        ':completed_date' => $completed_date,
        ':id' => $data['training_id']
    ]);

    // If passed, update user role to driver
    if ($data['status'] === 'passed') {
        $stmt = $pdo->prepare("UPDATE users SET role = 'driver' WHERE id = :id");
        $stmt->execute([':id' => $training['user_id']]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Training review submitted successfully']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>