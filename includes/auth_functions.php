<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// includes/auth_functions.php - All authentication functions
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/config/load_config.php';
// Site configuration
// Auto-detect local vs production
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];

// If running locally, use localhost
if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false || strpos($host, '::1') !== false) {
    define('SITE_URL', $protocol . $host);
} else {
    define('SITE_URL', 'https://logistics-system-production-ae8a.up.railway.app');
}
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
 * Send password reset code via Brevo
 */
function sendPasswordResetCode($to_email, $code) {
    // NEW - Add at the top of the file:
// Then inside your functions:
$brevo_api_key = getenv('BREVO_API_KEY') ?: $_ENV['BREVO_API_KEY'];
    
    error_log("📧 Sending password reset code to: $to_email");
    
    $data = [
        'sender' => ['name' => 'Logistics System', 'email' => 'asierra389@gmail.com'],
        'to' => [['email' => $to_email]],
        'subject' => 'Password Reset Code - Logistics System',
        'htmlContent' => "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 500px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 30px; background: #f9fafb; text-align: center; }
                    .code { font-size: 48px; font-weight: bold; color: #2563eb; letter-spacing: 8px; background: white; padding: 20px; border-radius: 10px; display: inline-block; margin: 20px 0; font-family: monospace; }
                    .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
                    .warning { color: #d97706; font-size: 14px; margin-top: 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>🔐 Password Reset Request</h2>
                    </div>
                    <div class='content'>
                        <p>Hello,</p>
                        <p>You requested to reset your password. Enter this code on your computer:</p>
                        <div class='code'>{$code}</div>
                        <p>⏰ This code expires in <strong>15 minutes</strong></p>
                        <div class='warning'>
                            <i class='fas fa-mobile-alt'></i> 
                            Enter this code on the device where you requested the password reset
                        </div>
                        <p style='margin-top: 20px; color: #6b7280; font-size: 12px;'>
                            If you didn't request this, ignore this email.
                        </p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Logistics System</p>
                    </div>
                </div>
            </body>
            </html>
        "
    ];
    
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201 || $httpCode === 200) {
        error_log("✅ Password reset code sent to: $to_email");
        return true;
    }
    
    error_log("❌ Password reset code failed: HTTP $httpCode, Response: $response");
    return false;
}

/**
 * Mask email for display
 */
function maskEmail($email) {
    if (empty($email)) return '';
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1] ?? '';
    
    if (strlen($name) > 2) {
        $masked_name = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
    } else {
        $masked_name = $name . '***';
    }
    
    return $masked_name . '@' . $domain;
}
function sendPasswordResetEmailViaResend($to_email, $raw_token) {
    $reset_link = SITE_URL . "/reset_password.php?token=" . urlencode($raw_token);
    
    $api_key = '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';
    
    error_log("📧 Sending password reset email to: $to_email");
    
    $data = [
        'sender' => ['name' => 'Logistics System', 'email' => 'asierra389@gmail.com'],
        'to' => [['email' => $to_email]],
        'subject' => 'Reset Your Password - Logistics System',
        'htmlContent' => "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 20px; background-color: #f9f9f9; text-align: center; }
                    .button-box { margin: 30px 0; }
                    .reset-button { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 18px; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>🔐 Password Reset Request</h2>
                    </div>
                    <div class='content'>
                        <p>Hello,</p>
                        <p>Click the button below to reset your password:</p>
                        
                        <div class='button-box'>
                            <a href='{$reset_link}' class='reset-button'>Reset Password</a>
                        </div>
                        
                        <p><strong>⚠️ This link expires in 1 hour</strong></p>
                        
                        <p style='color: #666; font-size: 14px; margin-top: 20px;'>
                            If you didn't request this, ignore this email.
                        </p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Logistics System</p>
                    </div>
                </div>
            </body>
            </html>
        "
    ];
    
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201 || $httpCode === 200) {
        error_log("✅ Password reset email sent to: $to_email");
        return true;
    }
    
    error_log("❌ Password reset failed: HTTP $httpCode");
    return false;
}
/**
 * Send login notification email using Resend
 */
function sendLoginNotificationEmailViaResend($to_email, $full_name, $user_agent, $ip_address) {
    $api_key = 're_BvGKfNqY_QB1b894VrYEGkfkJwXKqpFtW';
    
    $data = [
        'from' => 'onboarding@resend.dev',
        'to' => [$to_email],
        'subject' => '🔐 New Login Detected',
        'html' => "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 20px; background-color: #f9f9f9; }
                    .info-box { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #3b82f6; }
                    .warning { color: #d97706; font-weight: bold; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
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
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Logistics System</p>
                    </div>
                </div>
            </body>
            </html>
        ",
        'text' => "New login detected:\n\nDate & Time: " . date('F j, Y, g:i a') . "\nBrowser: {$user_agent}\nIP Address: {$ip_address}\n\nIf this wasn't you, secure your account immediately."
    ];
    
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        error_log("✅ Login notification sent to {$to_email}");
        return true;
    } else {
        error_log("❌ Login notification failed: HTTP {$httpCode}");
        return false;
    }
}

// ============ KEEP YOUR ORIGINAL PHPMailer FUNCTIONS ============

/**
 * Send password reset email (original PHPMailer version)
 */
function handleForgotPassword($email, $pdo) {
    error_log("🔍 handleForgotPassword STARTED for: $email");
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        error_log("❌ User not found: $email");
        return "If an account exists, you'll receive a reset link."; // Generic message for security
    }
    
    error_log("✅ User found. ID: " . $user['id']);
    
    // Generate token
    $raw_token = generateResetToken($email, $pdo);
    error_log("✅ Token generated: " . substr($raw_token, 0, 10) . "...");
    
    // Try to send email
    error_log("📧 Attempting to send email via Resend...");
    $sent = sendPasswordResetEmailViaResend($email, $raw_token);
    
    if ($sent) {
        error_log("✅ Email sent successfully!");
        return true;
    } else {
        error_log("❌ Email sending FAILED!");
        // Don't tell user it failed (security), but log it
        return "If an account exists, you'll receive a reset link.";
    }
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
 * Send login verification email using Resend (FIXED - uses cURL)
 */
function sendVerificationEmailViaResend($to_email, $full_name, $code) {
    $api_key = getenv('RESEND_API_KEY');
    
    // Fallback to hardcoded key if env not set (for testing)
    if (!$api_key) {
        $api_key = 're_BvGKfNqY_QB1b894VrYEGkfkJwXKqpFtW';
    }
    
    error_log("📧 Attempting to send email to: $to_email");
    
    // Prepare email data
    $data = [
        'from' => 'onboarding@resend.dev',
        'to' => [$to_email],
        'subject' => '🔐 Your Login Verification Code',
        'html' => "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 20px; background-color: #f9f9f9; text-align: center; }
                    .code-box { background: white; padding: 30px; border-radius: 10px; margin: 20px 0; border: 2px dashed #3b82f6; }
                    .verification-code { font-size: 48px; font-weight: bold; letter-spacing: 10px; color: #3b82f6; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
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
                        
                        <p style='color: #d97706; font-size: 14px;'>If you didn't try to log in, ignore this email.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Logistics System</p>
                    </div>
                </div>
            </body>
            </html>
        ",
        'text' => "Your verification code is: {$code}\n\nThis code expires in 10 minutes.\n\nIf you didn't try to log in, ignore this email."
    ];
    
    // Send via cURL (works without Composer)
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log the result
    if ($httpCode === 200) {
        error_log("✅ Email sent successfully to {$to_email}");
        return true;
    } else {
        error_log("❌ Email failed: HTTP {$httpCode}, Response: {$response}, Error: {$error}");
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