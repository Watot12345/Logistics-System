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
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>