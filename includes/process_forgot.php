<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';
require_once 'auth_functions.php'; // This already has maskEmail() function

session_start();

// Handle resend request
if (isset($_GET['resend']) && isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
    
    $reset_code = sprintf("%06d", random_int(0, 999999));
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->execute([$email]);
    
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, password_hash($reset_code, PASSWORD_DEFAULT), $expires]);
    
    sendPasswordResetCode($email, $reset_code);
    
    $_SESSION['success'] = 'New code sent to ' . maskEmail($email); // maskEmail from auth_functions.php
    header('Location: forgot_password.php?code_sent=1');
    exit();
}

// Handle initial request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $_SESSION['error'] = 'Please enter your email address';
        header('Location: forgot_password.php');
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = 'Email not found in our records';
        header('Location: forgot_password.php');
        exit();
    }
    
    $reset_code = sprintf("%06d", random_int(0, 999999));
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->execute([$email]);
    
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, password_hash($reset_code, PASSWORD_DEFAULT), $expires]);
    
    $sent = sendPasswordResetCode($email, $reset_code);
    
    if ($sent) {
        $_SESSION['reset_email'] = $email;
        $_SESSION['success'] = 'Reset code sent to ' . maskEmail($email);
        header('Location: forgot_password.php?code_sent=1');
    } else {
        $_SESSION['error'] = 'Failed to send reset code. Please try again.';
        header('Location: forgot_password.php');
    }
    exit();
}

header('Location: forgot_password.php');
exit();
?>