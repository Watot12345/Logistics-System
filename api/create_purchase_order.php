<?php
// api/create_purchase_order.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once '../config/db.php';
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Check user role - is this user allowed to create POs?
    $user_role = $_SESSION['role'] ?? 'employee';
    $is_admin = in_array($user_role, ['admin', 'procurement_manager']);
    
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
    
    $pdo->beginTransaction();
    
    // Generate PO number
    $year = date('Y');
    $month = date('m');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchase_orders WHERE po_number LIKE ?");
    $stmt->execute(["PO-$year-$month%"]);
    $result = $stmt->fetch();
    $count = ($result['count'] ?? 0) + 1;
    $po_number = 'PO-' . $year . '-' . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    
    // Check if this PO contains NEW items
    $has_new_items = false;
    foreach ($data['items'] as $item) {
        if (empty($item['item_id']) || $item['item_id'] === 'new') {
            $has_new_items = true;
            break;
        }
    }
    
    // Determine initial status
    // If user is admin AND no new items, can be auto-approved
    // Otherwise, always pending
    $initial_status = 'pending'; // Default to pending
    
    if ($is_admin && !$has_new_items) {
        // Admin ordering existing items - can be auto-approved
        $initial_status = 'approved';
    }
    
    // Insert purchase order
    $sql = "INSERT INTO purchase_orders (
        po_number, supplier_id, order_date, expected_delivery,
        priority, subtotal, tax_amount, shipping_cost, total_amount,
        notes, status, created_by, approved_by, approved_at
    ) VALUES (
        :po_number, :supplier_id, :order_date, :expected_delivery,
        :priority, :subtotal, :tax_amount, :shipping_cost, :total_amount,
        :notes, :status, :created_by, 
        CASE WHEN :status = 'approved' THEN :created_by ELSE NULL END,
        CASE WHEN :status = 'approved' THEN NOW() ELSE NULL END
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
        'status' => $initial_status,
        'created_by' => (int)$_SESSION['user_id']
    ];
    
    if (!$stmt->execute($params)) {
        $error = $stmt->errorInfo();
        throw new Exception('Failed to create purchase order: ' . $error[2]);
    }
    
    $po_id = $pdo->lastInsertId();
    
    // Process items
    $new_items_created = [];
    
    foreach ($data['items'] as $index => $item) {
        $item_id = null;
        $is_new_item = false;
        
        if (empty($item['item_id']) || $item['item_id'] === 'new') {
            $is_new_item = true;
            
            // For pending POs, we don't create the item yet
            // We just store the item details in a temporary field or notes
            if ($initial_status === 'pending') {
                // Store new item details in PO notes or a separate table
                // For now, append to notes
                $item_details = "NEW ITEM: {$item['new_item_name']} (SKU: {$item['new_sku']}) - Qty: {$item['quantity']}";
                $update_notes = $pdo->prepare("UPDATE purchase_orders SET notes = CONCAT(notes, '\n', ?) WHERE id = ?");
                $update_notes->execute([$item_details, $po_id]);
                
                // Use a placeholder item_id (0 or NULL) - but need to handle FK constraint
                // Better: Create the item but mark as pending approval?
                $item_id = 0; // This will fail FK constraint!
                throw new Exception('New items require approval. Please create the item first or have an admin approve.');
            } else {
                // Admin is creating - create the item now
                // Get category_id
                $category_id = null;
                if (!empty($item['new_category'])) {
                    $cat_stmt = $pdo->prepare("SELECT id FROM categories WHERE category_name = ?");
                    $cat_stmt->execute([$item['new_category']]);
                    $category = $cat_stmt->fetch();
                    if ($category) {
                        $category_id = $category['id'];
                    } else {
                        // Create new category
                        $cat_insert = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
                        $cat_insert->execute([$item['new_category']]);
                        $category_id = $pdo->lastInsertId();
                    }
                }
                
                // Create the new item
                $new_item_sql = "INSERT INTO inventory_items (
                    item_name, sku, category_id, supplier_id, 
                    quantity, price, reorder_level, description, created_at
                ) VALUES (
                    :item_name, :sku, :category_id, :supplier_id,
                    0, :price, :reorder_level, :description, NOW()
                )";
                
                $new_item_stmt = $pdo->prepare($new_item_sql);
                $new_item_stmt->execute([
                    ':item_name' => $item['new_item_name'],
                    ':sku' => $item['new_sku'],
                    ':category_id' => $category_id,
                    ':supplier_id' => $data['supplier_id'],
                    ':price' => (float)($item['unit_price'] ?? 0),
                    ':reorder_level' => (int)($item['new_reorder_level'] ?? 10),
                    ':description' => $item['new_description'] ?? ''
                ]);
                
                $item_id = $pdo->lastInsertId();
                $new_items_created[] = $item['new_item_name'];
                
                // Record stock movement
                $movement_stmt = $pdo->prepare("
                    INSERT INTO stock_movements (
                        item_id, movement_type, quantity_change, 
                        previous_quantity, new_quantity, notes
                    ) VALUES (?, 'in', 0, 0, 0, ?)
                ");
                $movement_stmt->execute([$item_id, 'New product created via PO ' . $po_number]);
            }
        } else {
            // Existing item
            $item_id = $item['item_id'];
            
            // Verify item exists
            $check = $pdo->prepare("SELECT id FROM inventory_items WHERE id = ?");
            $check->execute([$item_id]);
            if (!$check->fetch()) {
                throw new Exception('Item ID ' . $item_id . ' does not exist');
            }
        }
        
        // Only insert PO item if we have a valid item_id
        if ($item_id && $item_id > 0) {
            $quantity = (int)($item['quantity'] ?? 0);
            $unit_price = (float)($item['unit_price'] ?? 0);
            $total_price = $quantity * $unit_price;
            
            $item_sql = "INSERT INTO purchase_order_items (
                po_id, item_id, quantity, unit_price, total_price, status
            ) VALUES (
                :po_id, :item_id, :quantity, :unit_price, :total_price, 'pending'
            )";
            
            $item_stmt = $pdo->prepare($item_sql);
            $item_stmt->execute([
                ':po_id' => $po_id,
                ':item_id' => $item_id,
                ':quantity' => $quantity,
                ':unit_price' => $unit_price,
                ':total_price' => $total_price
            ]);
        }
    }
    
    // Log status history
    $history_stmt = $pdo->prepare("
        INSERT INTO po_status_history (
            po_id, old_status, new_status, changed_by, changed_at, notes
        ) VALUES (
            :po_id, 'draft', :new_status, :changed_by, NOW(), :notes
        )
    ");
    
    $status_notes = '';
    if ($has_new_items && $initial_status === 'pending') {
        $status_notes = 'Contains new items awaiting approval';
    } else if ($initial_status === 'approved') {
        $status_notes = 'Auto-approved by admin';
    }
    
    $history_stmt->execute([
        ':po_id' => $po_id,
        ':new_status' => $initial_status,
        ':changed_by' => $_SESSION['user_id'],
        ':notes' => $status_notes
    ]);
    
    $pdo->commit();
    
    $message = 'Purchase order created successfully';
    if ($initial_status === 'pending' && $has_new_items) {
        $message = 'PO created with new items. Waiting for admin approval.';
    } else if ($initial_status === 'pending') {
        $message = 'PO created and waiting for approval.';
    } else if ($initial_status === 'approved') {
        $message = 'PO created and auto-approved.';
    }
    
    echo json_encode([
        'success' => true,
        'po_id' => $po_id,
        'po_number' => $po_number,
        'status' => $initial_status,
        'message' => $message,
        'new_items_created' => $new_items_created
    ]);
    
} catch (Exception $e) {
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