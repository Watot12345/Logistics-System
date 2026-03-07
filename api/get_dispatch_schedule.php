<?php
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

try {
    // Check if dispatch_schedule table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'dispatch_schedule'");
    
    if ($tableCheck->rowCount() == 0) {
        // Create the table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS dispatch_schedule (
                id INT PRIMARY KEY AUTO_INCREMENT,
                reservation_id INT,
                vehicle_id INT NOT NULL,
                driver_id INT NULL,
                scheduled_date DATE NOT NULL,
                status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reservation_id) REFERENCES vehicle_reservations(id) ON DELETE CASCADE,
                FOREIGN KEY (vehicle_id) REFERENCES assets(id),
                FOREIGN KEY (driver_id) REFERENCES users(id)
            )
        ");
    }
    
    // Get today's dispatch schedule
    $stmt = $pdo->prepare("
        SELECT 
            ds.id,
            ds.reservation_id,
            ds.vehicle_id,
            ds.driver_id,
            ds.scheduled_date,
            ds.status,
            a.asset_name as vehicle_name,
            u.full_name as driver_name,
            vr.purpose,
            vr.department,
            vr.start_time,
            vr.end_time
        FROM dispatch_schedule ds
        LEFT JOIN assets a ON ds.vehicle_id = a.id
        LEFT JOIN users u ON ds.driver_id = u.id
        LEFT JOIN vehicle_reservations vr ON ds.reservation_id = vr.id
        WHERE ds.scheduled_date = CURDATE()
        ORDER BY vr.start_time ASC
    ");
    
    $stmt->execute();
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted = [];
    foreach ($schedule as $s) {
        // Format time with AM/PM
        $time_display = 'TBD';
        if (!empty($s['start_time'])) {
            $time_display = date('g:i A', strtotime($s['start_time']));
        }
        
        $formatted[] = [
            'id' => $s['id'], // Include ID for the button
            'time' => $time_display,
            'route' => $s['purpose'] ?? 'Reservation #' . $s['reservation_id'],
            'vehicle' => $s['vehicle_name'] ?? 'Vehicle #' . $s['vehicle_id'],
            'type' => $s['status'],
            'driver' => $s['driver_name'] ?? 'Unassigned',
            'driver_initials' => $s['driver_name'] ? substr($s['driver_name'], 0, 1) : '?'
        ];
    }
    
    // If no schedule for today, check if there are any approved reservations that need dispatch entries
    if (empty($formatted)) {
        // Get approved reservations that don't have dispatch entries yet
        $stmt = $pdo->prepare("
            SELECT 
                vr.*,
                a.asset_name as vehicle_name
            FROM vehicle_reservations vr
            LEFT JOIN assets a ON vr.vehicle_id = a.id
            WHERE vr.status = 'approved'
            AND vr.id NOT IN (
                SELECT reservation_id FROM dispatch_schedule 
                WHERE reservation_id IS NOT NULL
            )
            ORDER BY vr.created_at DESC
        ");
        $stmt->execute();
        $approved = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($approved as $a) {
            // Create dispatch entries for approved reservations
            $insert = $pdo->prepare("
                INSERT INTO dispatch_schedule (reservation_id, vehicle_id, scheduled_date, status)
                VALUES (?, ?, ?, 'scheduled')
            ");
            $insert->execute([$a['id'], $a['vehicle_id'], $a['start_date']]);
            
            // Get the ID of the newly inserted record
            $new_id = $pdo->lastInsertId();
            
            // Format time with AM/PM
            $time_display = '09:00 AM'; // Default
            if (!empty($a['start_time'])) {
                $time_display = date('g:i A', strtotime($a['start_time']));
            }
            
            $formatted[] = [
                'id' => $new_id, // Use the new ID
                'time' => $time_display,
                'route' => $a['purpose'] ?? 'New Reservation',
                'vehicle' => $a['vehicle_name'] ?? 'Unknown',
                'type' => 'scheduled',
                'driver' => 'Unassigned',
                'driver_initials' => '?'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'schedule' => $formatted
    ]);
    
} catch (PDOException $e) {
    error_log("Dispatch schedule error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'schedule' => []
    ]);
}
?>