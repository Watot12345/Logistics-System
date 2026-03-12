<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// includes/auth_functions.php - All forgot password functions
require_once '../config/db.php';

// Site configuration
define('SITE_URL', 'http://localhost/Logistics%20System/includes');
define('SMTP_FROM_NAME', 'Logistics System');

// Gmail SMTP configuration - YOUR ACTUAL CREDENTIALS
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'asierra389@gmail.com');  // YOUR Gmail
define('SMTP_PASS', 'rvlk umip yixd zycm');   // YOUR App Password

// Include PHPMailer
require_once __DIR__ . '/../src/PHPMailer.php';
require_once __DIR__ . '/../src/SMTP.php';
require_once __DIR__ . '/../src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
// Create password_resets table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (email),
            INDEX (token)
        )
    ");
} catch (PDOException $e) {
    // Table might already exist - ignore error
}

/**
 * Generate a reset token and store it
 */
function generateResetToken($email, $pdo) {
    $raw_token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $raw_token);
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Delete old tokens
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->execute([$email]);
    
    // Save new token
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token_hash, $expires]);
    
    return $raw_token;
}

/**
 * Validate a reset token
 */
function validateResetToken($token, $pdo) {
    $token_hash = hash('sha256', $token);
    
    // Debug - log what we're looking for
    error_log("Looking for token hash: " . $token_hash);
    
    // First, check if token exists at all (even if used or expired)
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->execute([$token_hash]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        error_log("Token not found in database");
        return false;
    }
    
    // Check if used
    if ($result['used'] == 1) {
        error_log("Token has been used already");
        return false;
    }
    
    // Check if expired
    $now = date('Y-m-d H:i:s');
    if ($result['expires_at'] < $now) {
        error_log("Token expired. Expires: " . $result['expires_at'] . ", Now: " . $now);
        return false;
    }
    
    // Token is valid
    error_log("Token is valid for email: " . $result['email']);
    return [
        'email' => $result['email'],
        'expires_at' => $result['expires_at']
    ];
}


/**
 * Mark token as used
 */
function markTokenAsUsed($token, $pdo) {
    $token_hash = hash('sha256', $token);
    $stmt = $pdo->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
    $stmt->execute([$token_hash]);
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($to_email, $raw_token) {
    $reset_link = SITE_URL . "/reset_password.php?token=" . urlencode($raw_token);
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = str_replace(' ', '', SMTP_PASS);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($to_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password - ' . SMTP_FROM_NAME;
        
        // HTML email body
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 12px 30px; background-color: #4CAF50; 
                         color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Password Reset Request</h2>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>Click the button to reset your password:</p>
                    <div style='text-align: center;'>
                        <a href='{$reset_link}' class='button'>Reset Password</a>
                    </div>
                    <p><strong>Link expires in 1 hour.</strong></p>
                    <p>If you didn't request this, ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SMTP_FROM_NAME . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Reset your password here: $reset_link\n\nThis link expires in 1 hour.";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Password reset email failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Handle forgot password request
 */
function handleForgotPassword($email, $pdo) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $raw_token = generateResetToken($email, $pdo);
        sendPasswordResetEmail($email, $raw_token);
    }
    
    return "If an account exists with this email, you will receive a reset link.";
}

/**
 * Reset password
 */
function resetPassword($token, $new_password, $pdo) {
    $reset_data = validateResetToken($token, $pdo);
    
    if (!$reset_data) {
        return ['success' => false, 'message' => 'Invalid or expired reset link.'];
    }
    
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$password_hash, $reset_data['email']]);
    
    markTokenAsUsed($token, $pdo);
    
    return ['success' => true, 'message' => 'Password reset successful!'];
}
?>