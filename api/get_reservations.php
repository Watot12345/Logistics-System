<?php
date_default_timezone_set('Asia/Manila'); // ADD THIS LINE
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

try {
    // Simple query to get all reservations with joins
    $query = "
        SELECT 
            vr.*,
            a.asset_name as vehicle_name,
            u.full_name as requester_name
        FROM vehicle_reservations vr
        LEFT JOIN assets a ON vr.vehicle_id = a.id
        LEFT JOIN users u ON vr.requester_id = u.id
        ORDER BY vr.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log for debugging
    error_log("Found " . count($reservations) . " reservations");
    
    // Format the data for frontend with AM/PM time
    $formatted = [];
    foreach ($reservations as $r) {
        // Combine date and time for display with AM/PM format
        // Using 'g:i A' for 12-hour format with AM/PM (e.g., 3:30 PM)
        $from_display = date('M d, Y', strtotime($r['start_date'])) . ' at ' . date('g:i A', strtotime($r['start_time']));
        $to_display = date('M d, Y', strtotime($r['end_date'])) . ' at ' . date('g:i A', strtotime($r['end_time']));
        
        $formatted[] = [
            'id' => $r['id'],
            'vehicle_id' => $r['vehicle_id'],
            'vehicle_name' => $r['vehicle_name'] ?? 'Unknown Vehicle',
            'requester' => $r['requester_name'] ?? 'Unknown User',
            'requester_id' => $r['requester_id'],
            'department' => $r['department'] ?? 'N/A',
            'purpose' => $r['purpose'] ?? 'No purpose specified',
            'from' => $from_display,
            'to' => $to_display,
            'status' => $r['status'] ?? 'pending',
            'notes' => $r['notes'] ?? ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $formatted
    ]);
    
} catch (PDOException $e) {
    error_log("Get reservations error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>