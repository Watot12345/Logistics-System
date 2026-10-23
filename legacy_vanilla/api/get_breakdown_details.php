<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user is admin or fleet manager
if (!in_array($_SESSION['role'], ['admin', 'fleet_manager', 'mechanic'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$breakdown_id = $_GET['id'] ?? 0;

if (!$breakdown_id) {
    echo json_encode(['success' => false, 'error' => 'Breakdown ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            eb.*,
            d.full_name as driver_name,
            d.phone as driver_phone
        FROM emergency_breakdowns eb
        LEFT JOIN users d ON eb.driver_id = d.id
        WHERE eb.id = ?
    ");
    $stmt->execute([$breakdown_id]);
    $breakdown = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($breakdown) {
        echo json_encode([
            'success' => true,
            'breakdown' => $breakdown
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Breakdown not found'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>