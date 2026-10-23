<?php
// health.php - Lightweight health check endpoint for Render
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'status' => 'healthy',
    'timestamp' => time(),
    'php_version' => PHP_VERSION,
    'app' => 'Logistics-System'
]);
