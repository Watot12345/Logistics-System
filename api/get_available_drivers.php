<?php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

try {
    // Get all active drivers
    $stmt = $pdo->query("
        SELECT id, full_name, employee_id 
        FROM users 
        WHERE role = 'driver' AND status = 'active'
        ORDER BY full_name
    ");
    
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'drivers' => $drivers
    ]);
    
} catch (PDOException $e) {
    error_log("Get drivers error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
?>