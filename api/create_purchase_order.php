<?php
// api/create_purchase_order.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once '../config/db.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (empty($data['supplier_id'])) {
        throw new Exception('Supplier ID is required');
    }
    if (empty($data['order_date'])) {
        throw new Exception('Order date is required');
    }
    if (empty($data['items']) || !is_array($data['items'])) {
        throw new Exception('Items are required');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Generate PO number
    $year = date('Y');
    $month = date('m');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchase_orders WHERE po_number LIKE ?");
    $stmt->execute(["PO-$year-$month%"]);
    $result = $stmt->fetch();
    $count = ($result['count'] ?? 0) + 1;
    $po_number = 'PO-' . $year . '-' . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    
    // Insert purchase order - MATCHING YOUR EXACT COLUMN NAMES
    $sql = "INSERT INTO purchase_orders (
        po_number, 
        supplier_id, 
        order_date, 
        expected_delivery,
        priority, 
        subtotal, 
        tax_amount, 
        shipping_cost, 
        total_amount,
        notes, 
        status, 
        created_by
    ) VALUES (
        :po_number, 
        :supplier_id, 
        :order_date, 
        :expected_delivery,
        :priority, 
        :subtotal, 
        :tax_amount, 
        :shipping_cost, 
        :total_amount,
        :notes, 
        'pending', 
        :created_by
    )";
    
    $stmt = $pdo->prepare($sql);
    
    $params = [
        'po_number' => $po_number,
        'supplier_id' => (int)$data['supplier_id'],
        'order_date' => $data['order_date'],
        'expected_delivery' => !empty($data['expected_delivery']) ? $data['expected_delivery'] : null,
        'priority' => $data['priority'] ?? 'normal',
        'subtotal' => (float)($data['subtotal'] ?? 0),
        'tax_amount' => (float)($data['tax_amount'] ?? 0),
        'shipping_cost' => (float)($data['shipping_cost'] ?? 0),
        'total_amount' => (float)($data['total_amount'] ?? 0),
        'notes' => $data['notes'] ?? '',
        'created_by' => (int)$_SESSION['user_id']
    ];
    
    if (!$stmt->execute($params)) {
        $error = $stmt->errorInfo();
        throw new Exception('Failed to create purchase order: ' . $error[2]);
    }
    
    $po_id = $pdo->lastInsertId();
    
    // Insert items - MATCHING YOUR EXACT COLUMN NAMES
    $item_sql = "INSERT INTO purchase_order_items (
        po_id, item_id, quantity, unit_price, total_price, status
    ) VALUES (
        :po_id, :item_id, :quantity, :unit_price, :total_price, 'pending'
    )";
    
    $item_stmt = $pdo->prepare($item_sql);
    
    foreach ($data['items'] as $item) {
        // Verify item exists
        $check = $pdo->prepare("SELECT id FROM inventory_items WHERE id = ?");
        $check->execute([$item['item_id']]);
        if (!$check->fetch()) {
            throw new Exception('Item ID ' . $item['item_id'] . ' does not exist');
        }
        
        $item_params = [
            'po_id' => $po_id,
            'item_id' => (int)$item['item_id'],
            'quantity' => (int)($item['quantity'] ?? 0),
            'unit_price' => (float)($item['unit_price'] ?? 0),
            'total_price' => (float)($item['total_price'] ?? 0)
        ];
        
        if (!$item_stmt->execute($item_params)) {
            $error = $item_stmt->errorInfo();
            throw new Exception('Failed to insert item: ' . $error[2]);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'po_id' => $po_id,
        'po_number' => $po_number,
        'message' => 'Purchase order created successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if started
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>