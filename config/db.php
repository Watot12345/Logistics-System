<?php
// Start session for user management

// Database configuration
$host = 'localhost';
$dbname = 'logistics';
$username = 'root';  // Change this to your MySQL username
$password = '';      // Change this to your MySQL password

// Create connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // IMPORTANT: Set MySQL timezone to match PHP
    $pdo->exec("SET time_zone = '" . date('P') . "'");
    
    // Optional: Log the timezone for debugging
    error_log("PHP Timezone: " . date_default_timezone_get() . " (" . date('P') . ")");
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>