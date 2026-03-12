<?php
// process_forgot.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';
require_once 'auth_functions.php';  // ADD THIS LINE!

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    header('Location: forgot_password.php');  // Fixed: removed 'includes/'
    exit;
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Please enter a valid email address.";
    header('Location: forgot_password.php');  // Fixed: removed 'includes/'
    exit;
}

// Handle the forgot password request
$message = handleForgotPassword($email, $pdo);

$_SESSION['message'] = $message;
header('Location: forgot_password.php');  // Fixed: removed 'includes/'
exit;
?>