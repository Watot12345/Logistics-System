z   <?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get and decode JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Log received data for debugging
error_log('Received PO update data: ' . print_r($data, true));

// Check required fields - expecting po_id and status from your JS
$po_id = $data['po_id'] ?? null;
$status = $data['status'] ?? null;

if (!$po_id || !$status) {
    echo json_encode([
        'success' => false, 
        'error' => 'Missing required fields',
        'received' => $data // This helps debug what was actually received
    ]);
    exit();
}

// Validate status - matches your ENUM in purchase_orders table
$valid_statuses = ['draft', 'pending', 'approved', 'rejected', 'completed', 'cancelled'];

if (!in_array($status, $valid_statuses)) {
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
        echo json_encode(['success' => false, 'error' => 'Purchase order not found']);
        $pdo->rollBack();
        exit();
    }
    
    // Don't allow status change if already in a final state
    if (in_array($current_po['status'], ['completed', 'cancelled'])) {
        echo json_encode([
            'success' => false, 
            'error' => 'Cannot change status of a ' . $current_po['status'] . ' purchase order'
        ]);
        $pdo->rollBack();
        exit();
    }
    
    // Update the PO status
    $stmt = $pdo->prepare("
        UPDATE purchase_orders 
        SET status = ?,
            updated_at = NOW(),
            approved_by = CASE WHEN ? = 'approved' THEN ? ELSE approved_by END,
            approved_at = CASE WHEN ? = 'approved' THEN NOW() ELSE approved_at END
        WHERE id = ?
    ");
    
    $stmt->execute([
        $status, 
        $status, 
        $_SESSION['user_id'],
        $status,
        $po_id
    ]);
    
    // Log the status change in notes
    $user_name = $_SESSION['full_name'] ?? 'System';
    $notes_update = "Status changed from '{$current_po['status']}' to '{$status}' by {$user_name} on " . date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("
        UPDATE purchase_orders 
        SET notes = CONCAT(IFNULL(notes, ''), '\n[', NOW(), '] ', ?)
        WHERE id = ?
    ");
    $stmt->execute([$notes_update, $po_id]);
    
    $stmt = $pdo->prepare("
        INSERT INTO po_status_history (po_id, old_status, new_status, changed_by, changed_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$po_id, $current_po['status'], $status, $_SESSION['user_id']]);
    
    
    
    if ($status === 'approved') {
        
    }
    
   
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "PO {$current_po['po_number']} status updated to {$status}",
        'po_number' => $current_po['po_number'],
        'new_status' => $status
    ]);
    
} catch (PDOException $e) {
   
    $pdo->rollBack();
    
    error_log('Error updating PO status: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>