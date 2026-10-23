<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'employee'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Shipment ID required']);
    exit();
}

try {
    $shipment_id = intval($_GET['id']);
    
    $query = "SELECT * FROM shipments WHERE shipment_id = :shipment_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':shipment_id', $shipment_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shipment) {
        echo json_encode(['success' => true, 'data' => $shipment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Shipment not found']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>