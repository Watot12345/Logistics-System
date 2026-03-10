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
    // Get stats for this mechanic
    $stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
            ROUND(AVG(CASE WHEN status = 'completed' THEN DATEDIFF(completed_date, due_date) ELSE NULL END), 1) as avg_completion_time
        FROM maintenance_alerts 
        WHERE assigned_mechanic = ?
    ");
    $stats->execute([$mechanic_id]);
    $data = $stats->fetch(PDO::FETCH_ASSOC);
    
    // Calculate efficiency (completed vs total attempted)
    $total_attempted = ($data['completed_tasks'] ?? 0) + ($data['in_progress_tasks'] ?? 0);
    $efficiency = $total_attempted > 0 ? round(($data['completed_tasks'] / $total_attempted) * 100) : 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'completed' => (int)($data['completed_tasks'] ?? 0),
            'in_progress' => (int)($data['in_progress_tasks'] ?? 0),
            'pending' => (int)($data['pending_tasks'] ?? 0),
            'efficiency' => $efficiency,
            'avg_time' => $data['avg_completion_time'] ?? 'N/A'
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Get mechanic stats error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>