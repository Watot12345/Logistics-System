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
    // Get fleet condition summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN asset_condition >= 70 THEN 1 ELSE 0 END) as excellent,
            SUM(CASE WHEN asset_condition >= 50 AND asset_condition < 70 THEN 1 ELSE 0 END) as good,
            SUM(CASE WHEN asset_condition >= 30 AND asset_condition < 50 THEN 1 ELSE 0 END) as fair,
            SUM(CASE WHEN asset_condition < 30 THEN 1 ELSE 0 END) as poor
        FROM assets 
        WHERE asset_type = 'vehicle'
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $stats['total'] ?? 1;
    
    $conditions = [
        [
            'category' => 'Excellent',
            'count' => intval($stats['excellent'] ?? 0),
            'percentage' => round(($stats['excellent'] ?? 0) / $total * 100),
            'color' => 'good',
            'icon' => 'star'
        ],
        [
            'category' => 'Good',
            'count' => intval($stats['good'] ?? 0),
            'percentage' => round(($stats['good'] ?? 0) / $total * 100),
            'color' => 'good',
            'icon' => 'thumbs-up'
        ],
        [
            'category' => 'Fair',
            'count' => intval($stats['fair'] ?? 0),
            'percentage' => round(($stats['fair'] ?? 0) / $total * 100),
            'color' => 'warning',
            'icon' => 'exclamation'
        ],
        [
            'category' => 'Poor',
            'count' => intval($stats['poor'] ?? 0),
            'percentage' => round(($stats['poor'] ?? 0) / $total * 100),
            'color' => 'critical',
            'icon' => 'exclamation-triangle'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'conditions' => $conditions
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>