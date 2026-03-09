<?php
// api/get_mechanic_tasks.php - DEBUG VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

// Log who is accessing
error_log("get_mechanic_tasks.php accessed by user_id: " . ($_SESSION['user_id'] ?? 'none'));

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Check role
if ($_SESSION['role'] !== 'mechanic') {
    error_log("Unauthorized access - role: " . $_SESSION['role']);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - not a mechanic']);
    exit();
}

require_once '../config/db.php';

$mechanic_id = $_SESSION['user_id'];
error_log("Mechanic ID: " . $mechanic_id);

try {
    // Simple query first to test
    $test = $pdo->prepare("SELECT COUNT(*) as count FROM maintenance_alerts WHERE assigned_mechanic = ?");
    $test->execute([$mechanic_id]);
    $count = $test->fetchColumn();
    error_log("Found $count tasks for mechanic $mechanic_id");
    
    // Get tasks assigned to this mechanic
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            a.asset_name,
            a.asset_condition,
            DATEDIFF(m.due_date, CURDATE()) as days_until_due,
            CASE 
                WHEN m.due_date < CURDATE() AND m.status != 'completed' THEN 'overdue'
                WHEN m.due_date = CURDATE() THEN 'due-today'
                WHEN m.due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'due-soon'
                ELSE 'upcoming'
            END as due_status
        FROM maintenance_alerts m
        LEFT JOIN assets a ON m.asset_name = a.asset_name
        WHERE m.assigned_mechanic = ? 
        AND m.status IN ('pending', 'in_progress')
        ORDER BY 
            CASE m.priority
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END,
            m.due_date ASC
    ");
    
    $stmt->execute([$mechanic_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Returning " . count($tasks) . " tasks");
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks,
        'count' => count($tasks)
    ]);
    
} catch (PDOException $e) {
    error_log("Get mechanic tasks error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>