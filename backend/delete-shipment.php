<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'employee'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Shipment ID required']);
    exit();
}

try {
    $shipment_id = intval($_POST['id']);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete tracking records first
    $delete_tracking = "DELETE FROM shipment_tracking WHERE shipment_id = :shipment_id";
    $stmt_tracking = $pdo->prepare($delete_tracking);
    $stmt_tracking->bindParam(':shipment_id', $shipment_id, PDO::PARAM_INT);
    $stmt_tracking->execute();
    
    // Delete shipment
    $delete_shipment = "DELETE FROM shipments WHERE shipment_id = :shipment_id";
    $stmt_shipment = $pdo->prepare($delete_shipment);
    $stmt_shipment->bindParam(':shipment_id', $shipment_id, PDO::PARAM_INT);
    $stmt_shipment->execute();
    
    if ($stmt_shipment->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Shipment deleted successfully']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Shipment not found']);
    }
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>