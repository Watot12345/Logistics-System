<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
$notes = $data['notes'] ?? '';

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit();
}

try {
    // COUNT YOUR PLACEHOLDERS: There are 2 placeholders (?, ?)
    $stmt = $pdo->prepare("
        UPDATE maintenance_alerts 
        SET status = 'completed', 
            completed_date = NOW(),
            completed_notes = ?
        WHERE id = ? AND status = 'in_progress'
    ");
    
    // YOU MUST PASS EXACTLY 2 VALUES: first for completed_notes, second for id
    $stmt->execute([$notes, $id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Maintenance task not found or not in progress']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>