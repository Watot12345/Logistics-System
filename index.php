<?php
session_start();

// Debug: Check if file exists
$auth_file = __DIR__ . '/includes/auth.php';
if (file_exists($auth_file)) {
    echo "Auth file found at: " . $auth_file . "<br>";
    require_once $auth_file;
} else {
    die("ERROR: Auth file not found at: " . $auth_file);
}

// That's it! auth.php handles everything
?>