<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// includes/auth_functions.php - All authentication functions
require_once __DIR__ . '/../config/db.php';

// Site configuration
define('SITE_URL', 'http://localhost/Logistics%20System/includes');
define('SMTP_FROM_NAME', 'Logistics System');

// Gmail SMTP configuration (keep for localhost)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'asierra389@gmail.com');
define('SMTP_PASS', 'rvlk umip yixd zycm');

// Include PHPMailer
require_once __DIR__ . '/../src/PHPMailer.php';
require_once __DIR__ . '/../src/SMTP.php';
require_once __DIR__ . '/../src/Exception.php';

// Include Resend (if Composer is installed)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

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
 * Send password reset email using Resend (for Railway)
 */
function sendPasswordResetEmailViaResend($to_email, $raw_token) {
    $reset_link = SITE_URL . "/reset_password.php?token=" . urlencode($raw_token);
    $api_key = getenv('RESEND_API_KEY');
    
    if (!$api_key) {
        error_log("RESEND_API_KEY not found");
        return false;
    }
    
    try {
        $resend = Resend::client($api_key);
        
        $result = $resend->emails->send([
            'from' => 'onboarding@resend.dev',
            'to' => [$to_email],
            'subject' => 'Reset Your Password - Logistics System',
            'html' => "
                <html>
                <body>
                    <h2>Password Reset Request</h2>
                    <p>Click the button to reset your password:</p>
                    <a href='{$reset_link}' style='display:inline-block; padding:12px 30px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Reset Password</a>
                    <p><strong>Link expires in 1 hour.</strong></p>
                </body>
                </html>
            ",
            'text' => "Reset your password here: $reset_link"
        ]);
        
        error_log("Password reset email sent via Resend to $to_email");
        return true;
        
    } catch (Exception $e) {
        error_log("Resend password reset failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send login verification email using Resend (for Railway)
 */
function sendVerificationEmailViaResend($to_email, $full_name, $code) {
    $api_key = getenv('RESEND_API_KEY');
    
    if (!$api_key) {
        error_log("RESEND_API_KEY not found in environment");
        return false;
    }
    
    try {
        $resend = Resend::client($api_key);
        
        $result = $resend->emails->send([
            'from' => 'onboarding@resend.dev',
            'to' => [$to_email],
            'subject' => '🔐 Your Login Verification Code',
            'html' => "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .code { font-size: 48px; font-weight: bold; letter-spacing: 10px; color: #3b82f6; text-align: center; padding: 30px; background: #f0f9ff; border-radius: 10px; }
                    </style>
                </head>
                <body>
                    <h2>Hello {$full_name},</h2>
                    <p>Your verification code is:</p>
                    <div class='code'>{$code}</div>
                    <p>This code expires in 10 minutes.</p>
                    <p style='color: #666;'>If you didn't try to log in, ignore this email.</p>
                </body>
                </html>
            ",
            'text' => "Your verification code is: {$code}\n\nThis code expires in 10 minutes."
        ]);
        
        error_log("✅ Verification email sent via Resend to {$to_email}");
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Resend failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send login notification email using Resend
 */
function sendLoginNotificationEmailViaResend($to_email, $full_name, $user_agent, $ip_address) {
    $api_key = getenv('RESEND_API_KEY');
    
    if (!$api_key) return false;
    
    try {
        $resend = Resend::client($api_key);
        
        $result = $resend->emails->send([
            'from' => 'onboarding@resend.dev',
            'to' => [$to_email],
            'subject' => '🔐 New Login Detected',
            'html' => "
                <html>
                <body>
                    <h2>Hello {$full_name},</h2>
                    <p>A new login was detected on your account:</p>
                    <p><strong>Date & Time:</strong> " . date('F j, Y, g:i a') . "</p>
                    <p><strong>Browser:</strong> {$user_agent}</p>
                    <p><strong>IP Address:</strong> {$ip_address}</p>
                    <p style='color: #d97706;'>If this wasn't you, secure your account immediately.</p>
                </body>
                </html>
            "
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Resend notification failed: " . $e->getMessage());
        return false;
    }
}

// ============ KEEP YOUR ORIGINAL PHPMailer FUNCTIONS ============

/**
 * Send password reset email (original PHPMailer version)
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
 * Handle forgot password request - SMART VERSION (tries both)
 */
function handleForgotPassword($email, $pdo) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $raw_token = generateResetToken($email, $pdo);
        
        // Try Resend first (for Railway), fallback to PHPMailer
        if (function_exists('sendPasswordResetEmailViaResend')) {
            $sent = sendPasswordResetEmailViaResend($email, $raw_token);
        } else {
            $sent = sendPasswordResetEmail($email, $raw_token);
        }
        
        if (!$sent) {
            // Fallback to original
            sendPasswordResetEmail($email, $raw_token);
        }
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

/**
 * Send login verification email - SMART VERSION (tries both)
 */
function sendVerificationEmail($to_email, $full_name, $code) {
    // Try Resend first (for Railway)
    if (function_exists('sendVerificationEmailViaResend')) {
        $result = sendVerificationEmailViaResend($to_email, $full_name, $code);
        if ($result) {
            return true;
        }
        error_log("Resend failed, falling back to PHPMailer");
    }
    
    // Fallback to original PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = str_replace(' ', '', SMTP_PASS);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 10; // Add timeout
        
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($to_email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Login - ' . SMTP_FROM_NAME;
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 20px; background-color: #f9f9f9; text-align: center; }
                .code-box { background: white; padding: 30px; border-radius: 10px; margin: 20px 0; border: 2px dashed #3b82f6; }
                .verification-code { font-size: 48px; font-weight: bold; letter-spacing: 10px; color: #3b82f6; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔐 Verify Your Login</h2>
                </div>
                <div class='content'>
                    <p>Hello <strong>{$full_name}</strong>,</p>
                    <p>Enter this code to complete your login:</p>
                    
                    <div class='code-box'>
                        <div class='verification-code'>{$code}</div>
                        <p style='color: #666; margin-top: 10px;'>This code expires in 10 minutes</p>
                    </div>
                    
                    <p style='color: #d97706;'>⚠️ If you didn't try to log in, ignore this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Your verification code is: $code\n\nThis code expires in 10 minutes.";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Verification email failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send login notification email - SMART VERSION
 */
function sendLoginNotificationEmail($to_email, $full_name, $user_agent, $ip_address) {
    // Try Resend first
    if (function_exists('sendLoginNotificationEmailViaResend')) {
        $result = sendLoginNotificationEmailViaResend($to_email, $full_name, $user_agent, $ip_address);
        if ($result) {
            return true;
        }
    }
    
    // Fallback to original
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = str_replace(' ', '', SMTP_PASS);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($to_email);
        
        $mail->isHTML(true);
        $mail->Subject = 'New Login Detected - ' . SMTP_FROM_NAME;
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .info-box { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #3b82f6; }
                .warning { color: #d97706; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔐 New Login Alert</h2>
                </div>
                <div class='content'>
                    <p>Hello <strong>{$full_name}</strong>,</p>
                    <p>A new login was detected on your account.</p>
                    
                    <div class='info-box'>
                        <p><strong>📅 Date & Time:</strong> " . date('F j, Y, g:i a') . "</p>
                        <p><strong>💻 Browser/Device:</strong> {$user_agent}</p>
                        <p><strong>🌍 IP Address:</strong> {$ip_address}</p>
                    </div>
                    
                    <p class='warning'>⚠️ If this wasn't you, please secure your account immediately.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Login notification email failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Get user's real IP address
 */
function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Log login attempt (optional - for tracking)
 */
function logLoginAttempt($pdo, $user_id, $email, $success = true) {
    try {
        // Create table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                login_time DATETIME,
                success BOOLEAN DEFAULT TRUE,
                INDEX (user_id),
                INDEX (login_time)
            )
        ");
        
        $ip = getRealIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO login_logs (user_id, email, ip_address, user_agent, login_time, success)
            VALUES (?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$user_id, $email, $ip, $user_agent, $success ? 1 : 0]);
    } catch (PDOException $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}
?>