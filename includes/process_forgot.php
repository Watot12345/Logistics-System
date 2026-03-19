<?php
// process_forgot.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';
require_once 'auth_functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any old messages
unset($_SESSION['error']);
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    $_SESSION['error'] = "Invalid request method.";
    header('Location: forgot_password.php');
    exit;
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Please enter a valid email address.";
    header('Location: forgot_password.php');
    exit;
}

// Handle the forgot password request
try {
    error_log("=== Processing forgot password for: $email ===");
    $result = handleForgotPassword($email, $pdo);
    
    // Check what actually happened
    if ($result === true) {
        $_SESSION['success'] = "Reset link sent! Check your email.";
    } else {
        // If we got a message back
        $_SESSION['success'] = $result;
    }
    
} catch (Exception $e) {
    error_log("❌ Exception in process_forgot: " . $e->getMessage());
    $_SESSION['error'] = "System error. Please try again later.";
}

header('Location: forgot_password.php');
exit;
?>