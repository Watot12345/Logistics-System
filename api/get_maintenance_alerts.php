<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT 
            m.*,
            a.asset_name,
            CASE 
                WHEN m.due_date < CURDATE() THEN 'overdue'
                WHEN m.due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'due-soon'
                ELSE 'upcoming'
            END as status,
            DATEDIFF(m.due_date, CURDATE()) as days_remaining
        FROM maintenance_alerts m
        LEFT JOIN assets a ON m.asset_name = a.asset_name
        WHERE m.status = 'pending'
        ORDER BY 
            CASE m.priority
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END,
            m.due_date ASC
    ");
    
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'alerts' => $alerts
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>