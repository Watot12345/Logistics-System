<?php
// api/get_dashboard_stats.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM purchase_orders");
    $total_orders = $stmt->fetch()['total'];
    
    // Pending approval
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM purchase_orders WHERE status = 'pending'");
    $pending = $stmt->fetch()['total'];
    
    // Active suppliers
    $stmt = $pdo->query("SELECT COUNT(DISTINCT supplier_id) as total FROM purchase_orders");
    $active_suppliers = $stmt->fetch()['total'];
    
    // Total spend
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM purchase_orders WHERE status IN ('approved', 'completed')");
    $total_spend = $stmt->fetch()['total'] ?? 0;
    
    // Approved total this month
    $stmt = $pdo->query("
        SELECT SUM(total_amount) as total 
        FROM purchase_orders 
        WHERE status = 'approved' 
        AND MONTH(created_at) = MONTH(CURDATE())
    ");
    $approved_month = $stmt->fetch()['total'] ?? 0;
    
    // Pending total this month
    $stmt = $pdo->query("
        SELECT SUM(total_amount) as total 
        FROM purchase_orders 
        WHERE status = 'pending' 
        AND MONTH(created_at) = MONTH(CURDATE())
    ");
    $pending_month = $stmt->fetch()['total'] ?? 0;
    
    // Approval queue
    $stmt = $pdo->query("
        SELECT po.id, po.po_number, po.total_amount, po.created_at,
               s.supplier_name, u.full_name as requester
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        JOIN users u ON po.created_by = u.id
        WHERE po.status = 'pending'
        ORDER BY 
            CASE po.priority 
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'normal' THEN 3
                ELSE 4
            END,
            po.created_at ASC
        LIMIT 5
    ");
    $approval_queue = $stmt->fetchAll();
    
    $stats = [
        'total_orders' => $total_orders,
        'pending_approval' => $pending,
        'active_suppliers' => $active_suppliers,
        'total_spend' => $total_spend,
        'approved_month' => $approved_month,
        'pending_month' => $pending_month,
        'approval_queue' => $approval_queue
    ];
    
    header('Content-Type: application/json');
    echo json_encode($stats);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>