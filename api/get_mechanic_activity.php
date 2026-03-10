<?php
// api/get_mechanic_activity.php
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
    // Get recent activity (completed tasks in last 7 days)
    $activity = $pdo->prepare("
        SELECT 
            m.id,
            m.asset_name,
            m.issue,
            m.completed_date,
            m.completed_notes,
            DATE_FORMAT(m.completed_date, '%b %d') as formatted_date
        FROM maintenance_alerts m
        WHERE m.assigned_mechanic = ? 
        AND m.status = 'completed'
        AND m.completed_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY m.completed_date DESC
        LIMIT 10
    ");
    $activity->execute([$mechanic_id]);
    $recent = $activity->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'activity' => $recent,
        'count' => count($recent)
    ]);
    
} catch (PDOException $e) {
    error_log("Get mechanic activity error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>