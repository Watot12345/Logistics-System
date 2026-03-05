<?php
// api/get_suppliers.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->query("SELECT id, supplier_name, contact_person, email, phone FROM suppliers ORDER BY supplier_name");
    $suppliers = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($suppliers);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>