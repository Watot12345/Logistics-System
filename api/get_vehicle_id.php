<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$vehicle_name = $_GET['name'] ?? '';

if (empty($vehicle_name)) {
    echo json_encode(['success' => false, 'error' => 'Vehicle name required']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id FROM assets WHERE asset_name = ? AND asset_type = 'vehicle'");
    $stmt->execute([$vehicle_name]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vehicle) {
        echo json_encode([
            'success' => true,
            'vehicle_id' => $vehicle['id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Vehicle not found'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>