<?php
// api/get_supplier_performance.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT 
            s.id,
            s.supplier_name,
            s.contact_person,
            s.email,
            s.phone,
            COUNT(DISTINCT po.id) as total_orders,
            SUM(po.total_amount) as total_spent,
            COUNT(CASE WHEN po.status = 'completed' THEN 1 END) as completed_orders,
            AVG(CASE 
                WHEN po.actual_delivery <= po.expected_delivery THEN 100 
                WHEN po.actual_delivery IS NOT NULL THEN 80 
                ELSE 90 
            END) as performance_score
        FROM suppliers s
        LEFT JOIN purchase_orders po ON s.id = po.supplier_id
        GROUP BY s.id
        HAVING total_orders > 0
        ORDER BY performance_score DESC
        LIMIT 5
    ");
    
    $suppliers = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($suppliers);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>