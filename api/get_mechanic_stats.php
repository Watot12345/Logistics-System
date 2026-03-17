<?php
// api/get_mechanic_stats.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Only mechanics can access this
if ($_SESSION['role'] !== 'mechanic') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../config/db.php';

$mechanic_id = $_SESSION['user_id'];

try {
    // Get stats from maintenance_alerts
    $maintenance_query = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
        FROM maintenance_alerts 
        WHERE assigned_mechanic = ?
    ");
    $maintenance_query->execute([$mechanic_id]);
    $maintenance = $maintenance_query->fetch(PDO::FETCH_ASSOC);
    
    // Get stats from emergency_breakdowns
    $emergency_query = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress
        FROM emergency_breakdowns 
        WHERE assigned_mechanic = ?
    ");
    $emergency_query->execute([$mechanic_id]);
    $emergency = $emergency_query->fetch(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $completed = (int)($maintenance['completed'] ?? 0) + (int)($emergency['completed'] ?? 0);
    $in_progress = (int)($maintenance['in_progress'] ?? 0) + (int)($emergency['in_progress'] ?? 0);
    $pending = (int)($maintenance['pending'] ?? 0);
    
    // Calculate efficiency (completed vs total attempted)
    $total_attempted = $completed + $in_progress;
    $efficiency = $total_attempted > 0 ? round(($completed / $total_attempted) * 100) : 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'completed' => $completed,
            'in_progress' => $in_progress,
            'pending' => $pending,
            'efficiency' => $efficiency
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Get mechanic stats error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>