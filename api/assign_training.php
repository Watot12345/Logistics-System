<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user is admin
$isAdmin = false;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
}

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden - Admin access only']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, full_name, role, status FROM users WHERE id = :id");
    $stmt->execute([':id' => $data['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Check if user is already a driver or in training
    if ($user['role'] === 'driver') {
        throw new Exception('User is already a driver');
    }

    if ($user['status'] === 'pending') {
        throw new Exception('User is already in training');
    }

    // Check if already in driver_training table
    $stmt = $pdo->prepare("SELECT id FROM driver_training WHERE user_id = :user_id AND training_status IN ('pending', 'in_progress')");
    $stmt->execute([':user_id' => $data['user_id']]);
    if ($stmt->fetch()) {
        throw new Exception('User is already assigned to training');
    }

    // Update user status to pending
    $stmt = $pdo->prepare("UPDATE users SET status = 'pending' WHERE id = :id");
    $stmt->execute([':id' => $data['user_id']]);

    // Insert into driver_training
    $stmt = $pdo->prepare("
        INSERT INTO driver_training (user_id, assigned_by, assigned_date, training_status, notes)
        VALUES (:user_id, :assigned_by, :assigned_date, 'pending', :notes)
    ");
    $stmt->execute([
        ':user_id' => $data['user_id'],
        ':assigned_by' => $_SESSION['user_id'],
        ':assigned_date' => date('Y-m-d'),
        ':notes' => $data['notes'] ?? null
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Employee assigned to driver training successfully'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>