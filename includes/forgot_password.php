<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db.php';

// START SESSION HERE!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Logistics System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 400px;
            width: 90%;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .card-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header i {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);
        }

        .card-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }

        .card-body {
            padding: 24px;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message i {
            font-size: 16px;
        }

        .message.success {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .message.success i {
            color: #059669;
        }

        .message.error {
            background: #ffe4e6;
            color: #e11d48;
            border: 1px solid #fecdd3;
        }

        .message.error i {
            color: #e11d48;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        input[type="email"]:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }

        .btn:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }

        .btn i {
            font-size: 16px;
        }

        .footer {
            text-align: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }

        .footer a {
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .footer a:hover {
            color: #2563eb;
        }

        .footer a i {
            font-size: 12px;
        }

        .info-text {
            font-size: 13px;
            color: #64748b;
            margin-top: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock"></i>
                <h2>Forgot Password?</h2>
            </div>
            
            <div class="card-body">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <form action="process_forgot.php" method="POST">
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" required placeholder="Enter your registered email">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i>
                        Send Reset Link
                    </button>
                </form>
                
                <div class="info-text">
                    <i class="fas fa-info-circle"></i>
                    We'll send a password reset link to your email
                </div>
                
                <div class="footer">
                    <a href="../index.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>