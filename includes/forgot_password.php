<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';
require_once 'auth_functions.php'; // This already has maskEmail() function

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$showCodeForm = false;
$showResetForm = false;
$reset_token = null;
$error = '';
$success = '';

// Handle code verification submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_code') {
    $code = trim($_POST['code'] ?? '');
    $email = $_SESSION['reset_email'] ?? '';
    
    if (empty($code)) {
        $error = 'Please enter the verification code';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND used = FALSE AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset && password_verify($code, $reset['token'])) {
            $stmt = $pdo->prepare("UPDATE password_resets SET used = TRUE WHERE id = ?");
            $stmt->execute([$reset['id']]);
            
            $showResetForm = true;
            $reset_token = $reset['id'];
            $_SESSION['reset_token'] = $reset_token;
            $success = 'Code verified! Enter your new password below.';
        } else {
            $error = 'Invalid or expired verification code';
        }
    }
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE id = ? AND used = TRUE");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $reset['email']]);
            
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_token']);
            
            $_SESSION['success'] = 'Password reset successful! You can now login with your new password.';
            header('Location: ../index.php');
            exit();
        } else {
            $error = 'Invalid reset request';
        }
    }
}

// Check if we're in code verification mode
if (isset($_GET['code_sent']) && isset($_SESSION['reset_email'])) {
    $showCodeForm = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Logistics System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 400px; width: 90%; margin: 0 auto; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #e2e8f0; }
        .card-header { padding: 24px 24px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px; }
        .card-header i { width: 40px; height: 40px; background: linear-gradient(135deg, #2563eb, #1d4ed8); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; }
        .card-header h2 { font-size: 20px; font-weight: 700; color: #1e293b; }
        .card-body { padding: 24px; }
        .message { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .message.success { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
        .message.error { background: #ffe4e6; color: #e11d48; border: 1px solid #fecdd3; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: #1e293b; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        input { width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 14px; outline: none; transition: all 0.2s; }
        input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .code-input { text-align: center; letter-spacing: 8px; font-size: 24px; font-weight: bold; }
        .btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none; border-radius: 12px; font-size: 14px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; }
        .btn:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-1px); }
        .btn-secondary { background: #fff; color: #475569; border: 1px solid #e2e8f0; margin-top: 10px; }
        .btn-secondary:hover { background: #f8fafc; transform: none; }
        .footer { text-align: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
        .footer a { color: #64748b; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; }
        .footer a:hover { color: #2563eb; }
        .info-text { font-size: 13px; color: #64748b; margin-top: 8px; text-align: center; }
        .resend-link { text-align: center; margin-top: 15px; }
        .resend-link a { color: #2563eb; text-decoration: none; font-size: 13px; }
        .resend-link a:hover { text-decoration: underline; }
        .timer-text { font-size: 12px; color: #64748b; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock"></i>
                <h2><?php echo $showResetForm ? 'Reset Password' : ($showCodeForm ? 'Verify Code' : 'Forgot Password?'); ?></h2>
            </div>
            
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="message error"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="message success"><i class="fas fa-check-circle"></i><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <!-- Password Reset Form -->
                <?php if ($showResetForm): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($reset_token); ?>">
                        <div class="form-group">
                            <label>New Password</label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" required minlength="8" placeholder="Enter new password">
                            </div>
                            <div class="info-text" style="text-align: left;"><i class="fas fa-info-circle"></i> Minimum 8 characters</div>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="confirm_password" required minlength="8" placeholder="Confirm new password">
                            </div>
                        </div>
                        <button type="submit" class="btn"><i class="fas fa-save"></i> Reset Password</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='../index.php'"><i class="fas fa-times"></i> Cancel</button>
                    </form>
                
                <!-- Code Verification Form -->
                <?php elseif ($showCodeForm): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="verify_code">
                        <div class="text-center" style="margin-bottom: 20px;">
                            <div class="message success" style="display: inline-block; margin-bottom: 0;">
                                <i class="fas fa-envelope"></i> Code sent to <?php echo htmlspecialchars(maskEmail($_SESSION['reset_email'] ?? '')); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Enter 6-Digit Code</label>
                            <div class="input-group">
                                <i class="fas fa-key"></i>
                                <input type="text" name="code" class="code-input" maxlength="6" placeholder="000000" required autocomplete="off" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,6)">
                            </div>
                            <div class="info-text"><i class="fas fa-phone-alt"></i> Check your phone for the code</div>
                        </div>
                        <button type="submit" class="btn"><i class="fas fa-check-circle"></i> Verify Code</button>
                        <div class="resend-link"><a href="process_forgot.php?resend=1"><i class="fas fa-redo-alt"></i> Resend Code</a></div>
                        <div class="timer-text"><i class="fas fa-clock"></i> Code expires in 15 minutes</div>
                    </form>
                
                <!-- Email Form -->
                <?php else: ?>
                    <form action="process_forgot.php" method="POST">
                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" required placeholder="Enter your registered email">
                            </div>
                        </div>
                        <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Send Reset Code</button>
                    </form>
                    <div class="info-text"><i class="fas fa-info-circle"></i> We'll send a 6-digit code to your email. Enter it on this device to reset your password.</div>
                <?php endif; ?>
                
                <div class="footer">
                    <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.querySelector('input[name="code"]')?.addEventListener('input', function(e) {
            if (this.value.length === 6) this.form.submit();
        });
    </script>
</body>
</html>