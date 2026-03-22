<?php
// Start output buffering at the VERY TOP to catch any stray output
ob_start();

session_start();

// Turn off display errors (they should go to log, not output)
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/db.php';

// Clear any output from included files
ob_clean();

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get and decode JSON data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log received data for debugging
error_log('Received PO update data: ' . print_r($data, true));

// Check required fields - expecting po_id and status from your JS
$po_id = $data['po_id'] ?? null;
$status = $data['status'] ?? null;

if (!$po_id || !$status) {
    // Clear buffer and send error
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Missing required fields',
        'received' => $data
    ]);
    exit();
}

// Validate status - matches your ENUM in purchase_orders table
$valid_statuses = ['draft', 'pending', 'approved', 'rejected', 'completed', 'cancelled'];

if (!in_array($status, $valid_statuses)) {
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid status. Must be one of: ' . implode(', ', $valid_statuses)
    ]);
    exit();
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // First, get the current PO details for logging and validation
    $stmt = $pdo->prepare("
        SELECT po_number, status, created_by 
        FROM purchase_orders 
        WHERE id = ?
    ");
    $stmt->execute([$po_id]);
    $current_po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_po) {
        $pdo->rollBack();
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Purchase order not found']);
        exit();
    }
    
    // Don't allow status change if already in a final state
    if (in_array($current_po['status'], ['completed', 'cancelled'])) {
        $pdo->rollBack();
        ob_clean();
        echo json_encode([
            'success' => false, 
            'error' => 'Cannot change status of a ' . $current_po['status'] . ' purchase order'
        ]);
        exit();
    }
    
    // Update the PO status
    $stmt = $pdo->prepare("
        UPDATE purchase_orders 
        SET status = ?,
            updated_at = NOW(),
            approved_by = CASE WHEN ? = 'approved' THEN ? ELSE approved_by END,
            approved_at = CASE WHEN ? = 'approved' THEN NOW() ELSE approved_at END,
            completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END
        WHERE id = ?
    ");
    
    $stmt->execute([
        $status, 
        $status, 
        $_SESSION['user_id'],
        $status,
        $status,
        $po_id
    ]);
    
    // *** NEW CODE: If status is changing to 'completed', update inventory ***
    $inventory_updated = false;
    $updated_items = [];
    
    if ($status === 'completed' && $current_po['status'] !== 'completed') {
        // Get all items from this PO
        $items_stmt = $pdo->prepare("
            SELECT poi.item_id, poi.quantity, poi.unit_price, i.item_name, i.quantity as current_stock
            FROM purchase_order_items poi
            JOIN inventory_items i ON poi.item_id = i.id
            WHERE poi.po_id = ?
        ");
        $items_stmt->execute([$po_id]);
        $items = $items_stmt->fetchAll();
        
        if (empty($items)) {
            error_log('No items found for PO ID: ' . $po_id);
        }
        
        foreach ($items as $item) {
            // Update inventory quantity (add the ordered quantity to current stock)
            $update_inv = $pdo->prepare("
                UPDATE inventory_items 
                SET quantity = quantity + :quantity,
                    last_updated = NOW()
                WHERE id = :item_id
            ");
            
            $update_inv->execute([
                ':quantity' => $item['quantity'],
                ':item_id' => $item['item_id']
            ]);
            
            $updated_items[] = [
                'name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'old_stock' => $item['current_stock'],
                'new_stock' => $item['current_stock'] + $item['quantity']
            ];
            
            // Optional: Log stock movement if you have a stock_movements table
            // Check if table exists first
            $table_check = $pdo->query("SHOW TABLES LIKE 'stock_movements'");
            if ($table_check->rowCount() > 0) {
                $log_stmt = $pdo->prepare("
                    INSERT INTO stock_movements (
                        item_id, quantity, type, reference_id, reference_type, 
                        created_by, created_at, notes
                    ) VALUES (
                        :item_id, :quantity, 'in', :po_id, 'purchase_order',
                        :user_id, NOW(), :notes
                    )
                ");
                
                $log_stmt->execute([
                    ':item_id' => $item['item_id'],
                    ':quantity' => $item['quantity'],
                    ':po_id' => $po_id,
                    ':user_id' => $_SESSION['user_id'],
                    ':notes' => "Stock added from PO #{$current_po['po_number']} completion"
                ]);
            }
            
            $inventory_updated = true;
        }
        
        error_log('Inventory updated for PO ' . $po_id . ': ' . count($updated_items) . ' items');
    }
    
    // Log the status change in notes
    $user_name = $_SESSION['full_name'] ?? 'System';
    $notes_update = "Status changed from '{$current_po['status']}' to '{$status}' by {$user_name} on " . date('Y-m-d H:i:s');
    
    if ($status === 'completed' && $inventory_updated) {
        $notes_update .= " | Inventory updated for " . count($updated_items) . " item(s)";
    }
    
    $stmt = $pdo->prepare("
        UPDATE purchase_orders 
        SET notes = CONCAT(IFNULL(notes, ''), '\n[', NOW(), '] ', ?)
        WHERE id = ?
    ");
    $stmt->execute([$notes_update, $po_id]);
    
    // Insert into status history (check if table exists)
    $history_table_check = $pdo->query("SHOW TABLES LIKE 'po_status_history'");
    if ($history_table_check->rowCount() > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO po_status_history (po_id, old_status, new_status, changed_by, changed_at, notes) 
            VALUES (?, ?, ?, ?, NOW(), ?)
        ");
        $notes = $status === 'completed' && $inventory_updated ? 'Inventory updated' : '';
        $stmt->execute([$po_id, $current_po['status'], $status, $_SESSION['user_id'], $notes]);
    }
    
    $pdo->commit();
    
    // Prepare success message
    $message = "PO {$current_po['po_number']} status updated to {$status}";
    if ($status === 'completed' && $inventory_updated) {
        $message .= ". Inventory updated for " . count($updated_items) . " item(s)";
    }
    
    // Clear any output buffer and send success response
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => $message,
        'po_number' => $current_po['po_number'],
        'new_status' => $status,
        'inventory_updated' => $inventory_updated,
        'updated_items' => $updated_items
    ]);
    exit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    
    error_log('Error updating PO status: ' . $e->getMessage());
    
    // Clear buffer and send error
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    
    error_log('General error updating PO status: ' . $e->getMessage());
    
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Error: ' . $e->getMessage()
    ]);
    exit();
}
?>