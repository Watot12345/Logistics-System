<?php
// ===== DEBUG CONFIGURATION =====
// Set to true to bypass 2FA for testing, false for production with 2FA
define('DEBUG_MODE', false); // Change to false for production

// ===== SECURITY CONFIGURATION =====
define('SESSION_TIMEOUT', 1800); // 30 minutes (in seconds) - change as needed
define('MAX_LOGIN_ATTEMPTS', 5); // Maximum failed attempts before lockout
define('LOCKOUT_TIME', 900); // 15 minutes lockout (in seconds)

set_time_limit(120); // Give PHP more time
ini_set('max_execution_time', 120);
ini_set('memory_limit', '256M');

// Disable output buffering completely
while (ob_get_level()) ob_end_clean();
ob_implicit_flush(true);

// Start session FIRST before anything that uses $_SESSION
session_start();
ob_start(); // Keep this for now

// ===== SESSION TIMEOUT CHECK =====
function checkSessionTimeout() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time > SESSION_TIMEOUT) {
            // Session expired due to inactivity
            session_unset();
            session_destroy();
            
            // Redirect to login page with timeout message
            header('Location: auth.php?timeout=1');
            exit();
        }
    }
    
    // Update last activity time (but not for AJAX requests or API calls)
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        $_SESSION['last_activity'] = time();
    }
}

// Now call the timeout check AFTER session is started
checkSessionTimeout();

// Add this function for instant email sending
function sendEmailFast($to, $name, $code) {
    // Your Resend API key
    $api_key = 're_BvGKfNqY_QB1b894VrYEGkfkJwXKqpFtW';
    
    // Use exec to run in background (doesn't block PHP)
    $cmd = "curl -X POST https://api.resend.com/emails \\
        -H 'Authorization: Bearer $api_key' \\
        -H 'Content-Type: application/json' \\
        -d '{
            \"from\": \"onboarding@resend.dev\",
            \"to\": [\"$to\"],
            \"subject\": \"Your Login Code\",
            \"html\": \"<h2>Hello $name</h2><p>Your code: <strong>$code</strong></p>\"
        }' > /dev/null 2>&1 &";
    
    exec($cmd);
    return true; // Return immediately
}

// ===== LOGIN ATTEMPT TRACKING =====
function trackLoginAttempt($pdo, $identifier, $success = false) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $timestamp = date('Y-m-d H:i:s');
    
    // Log the attempt
    $stmt = $pdo->prepare("INSERT INTO login_attempts (identifier, ip_address, user_agent, attempt_time, success) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$identifier, $ip_address, $user_agent, $timestamp, $success ? 1 : 0]);
    
    // Clean up old attempts (keep last 24 hours)
    $cleanup = $pdo->prepare("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $cleanup->execute();
}

function isLoginLocked($pdo, $identifier) {
    $lockout_time = date('Y-m-d H:i:s', strtotime('-' . LOCKOUT_TIME . ' seconds'));
    
    // Count failed attempts from this identifier in the lockout period
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempt_count 
        FROM login_attempts 
        WHERE identifier = ? 
        AND attempt_time > ? 
        AND success = FALSE
    ");
    $stmt->execute([$identifier, $lockout_time]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['attempt_count'] >= MAX_LOGIN_ATTEMPTS;
}

function getRemainingLockoutTime($pdo, $identifier) {
    $lockout_time = date('Y-m-d H:i:s', strtotime('-' . LOCKOUT_TIME . ' seconds'));
    
    // Get the most recent failed attempt
    $stmt = $pdo->prepare("
        SELECT attempt_time 
        FROM login_attempts 
        WHERE identifier = ? 
        AND attempt_time > ? 
        AND success = FALSE
        ORDER BY attempt_time DESC 
        LIMIT 1
    ");
    $stmt->execute([$identifier, $lockout_time]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $last_attempt = strtotime($result['attempt_time']);
        $lockout_ends = $last_attempt + LOCKOUT_TIME;
        $remaining = $lockout_ends - time();
        
        return $remaining > 0 ? $remaining : 0;
    }
    
    return 0;
}

require_once 'config/db.php';
require_once 'includes/auth_functions.php';


// At the top of your auth.php, after session_start()
if (isset($_GET['agreed']) && $_GET['agreed'] === 'true') {
    $_SESSION['terms_agreed'] = true;
}

// Create login_verifications table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            verification_code VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (verification_code)
        )
    ");
} catch (PDOException $e) {
    error_log("Error creating login_verifications table: " . $e->getMessage());
}

// Handle form submissions
$message = '';
$messageType = '';
$employee_id = 'EMP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

// Variables for verification step
$showVerification = false;
$temp_user_id = null;
$temp_email = null;

// Handle verification code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify') {
    $code = trim($_POST['verification_code'] ?? '');
    $user_id = $_POST['user_id'] ?? '';
    
    if (empty($code) || empty($user_id)) {
        $message = 'Please enter the verification code';
        $messageType = 'error';
        $showVerification = true;
        $temp_user_id = $user_id;
    } else {
        // Use current time
        $current_time = date('Y-m-d H:i:s');
        
        error_log("🔍 VERIFY - Checking code: $code for user: $user_id at $current_time");
        
        $stmt = $pdo->prepare("
            SELECT * FROM login_verifications 
            WHERE user_id = ? AND verification_code = ? 
            AND expires_at > ? AND verified = FALSE
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$user_id, $code, $current_time]);
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($verification) {
            error_log("✅ Code valid for user $user_id");
            
            // Mark as verified
            $update = $pdo->prepare("UPDATE login_verifications SET verified = TRUE WHERE id = ?");
            $update->execute([$verification['id']]);
            
            // Get user data
            $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $userStmt->execute([$user_id]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time(); // Set initial activity time
                
                error_log("✅ User {$user['username']} logged in successfully");
                
                // Redirect immediately
                header('Location: dashboard.php');
                exit();
            }
        } else {
            error_log("❌ Invalid code for user $user_id");
            $message = 'Invalid or expired verification code';
            $messageType = 'error';
            $showVerification = true;
            $temp_user_id = $user_id;
            
            // Get email for display
            $emailStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $emailStmt->execute([$user_id]);
            $user = $emailStmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $temp_email = maskEmail($user['email']);
            }
        }
    }
}

// Handle initial login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $message = 'Please fill in all fields';
        $messageType = 'error';
    } else {
        try {
            // ===== CHECK LOGIN ATTEMPTS =====
            if (isLoginLocked($pdo, $username)) {
                $remaining = getRemainingLockoutTime($pdo, $username);
                $minutes = ceil($remaining / 60);
                $message = "Too many failed login attempts. Please try again in {$minutes} minute" . ($minutes > 1 ? 's' : '');
                $messageType = 'error';
                
                // Log the blocked attempt
                trackLoginAttempt($pdo, $username, false);
            } else {
                // Only proceed with login check if not locked out
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    // ===== SUCCESSFUL LOGIN =====
                    // Log successful attempt
                    trackLoginAttempt($pdo, $username, true);
                    
                    // ===== DEBUG MODE CHECK =====
                    if (DEBUG_MODE) {
                        // DEBUG MODE: Direct login without 2FA
                        error_log("🔧 DEBUG MODE: Direct login for {$user['email']}");
                        
                        // Create session immediately
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['debug_mode'] = true;
                        $_SESSION['last_activity'] = time();
                        
                        // Redirect to dashboard
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        // PRODUCTION MODE: Normal 2FA flow
                        // Generate and save code
                        $verification_code = sprintf("%06d", random_int(0, 999999));
                        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                        
                        // Delete old codes
                        $stmt = $pdo->prepare("DELETE FROM login_verifications WHERE user_id = ?");
                        $stmt->execute([$user['id']]);
                        
                        // Save new code
                        $stmt = $pdo->prepare("INSERT INTO login_verifications (user_id, email, verification_code, expires_at) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$user['id'], $user['email'], $verification_code, $expires]);
                        
                        // Send email in background
                        sendEmailFast($user['email'], $user['full_name'], $verification_code);
                        
                        // Show verification form
                        $showVerification = true;
                        $temp_user_id = $user['id'];
                        $temp_email = maskEmail($user['email']);
                        
                        $message = "✓ Verification code sent to " . $temp_email;
                        $messageType = 'success';
                        
                        error_log("✅ Login successful for {$user['email']}, code: $verification_code");
                    }
                } else {
                    // ===== FAILED LOGIN =====
                    // Log failed attempt
                    trackLoginAttempt($pdo, $username, false);
                    
                    $message = 'Invalid username/email or password';
                    $messageType = 'error';
                    
                    // Check if this failed attempt triggers lockout
                    if (isLoginLocked($pdo, $username)) {
                        $remaining = getRemainingLockoutTime($pdo, $username);
                        $minutes = ceil($remaining / 60);
                        $message = "Too many failed attempts. Account locked for {$minutes} minute" . ($minutes > 1 ? 's' : '');
                    }
                }
            }
        } catch(PDOException $e) {
            $message = 'Login failed: ' . $e->getMessage();
            $messageType = 'error';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Handle Signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    // Initialize errors array
    $errors = [];
    
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check terms agreement
    if (!isset($_SESSION['terms_agreed']) || $_SESSION['terms_agreed'] !== true) {
        $errors[] = 'You must agree to the Terms of Service and Privacy Policy';
    }
    
    if (empty($errors)) {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $message = 'Username or email already exists';
                $messageType = 'error';
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (employee_id, full_name, username, email, password) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$employee_id, $full_name, $username, $email, $hashed_password])) {
                    // Clear terms agreement after successful signup
                    unset($_SESSION['terms_agreed']);
                    
                    $message = 'Registration successful! You can now login.';
                    $messageType = 'success';
                    
                    // Clear POST data
                    $_POST = array();
                } else {
                    $message = 'Registration failed. Please try again.';
                    $messageType = 'error';
                }
            }
        } catch(PDOException $e) {
            $message = 'Registration failed: ' . $e->getMessage();
            $messageType = 'error';
            error_log("Signup error: " . $e->getMessage());
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

// Helper function to mask email
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

// Optional: Add a visual indicator in the login form for debug mode
if (DEBUG_MODE) {
    error_log("🔧 DEBUG MODE is ACTIVE - 2FA is bypassed");
}

// Handle session timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $message = 'Your session has expired due to inactivity. Please login again.';
    $messageType = 'info';
}

// Determine which form to show
$activeForm = isset($_GET['form']) && $_GET['form'] === 'signup' ? 'signup' : 'login';
if ($showVerification) {
    $activeForm = 'verify';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logistics System | Login & Signup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/output.css">
    <style>
        /* Loading spinner */
        .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Verification code input */
        .verification-input {
            letter-spacing: 8px;
            font-size: 2rem;
            text-align: center;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-overlay.active {
            display: flex;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center min-h-screen p-4">
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="loading-spinner mx-auto mb-4"></div>
            <p class="text-gray-700 font-medium" id="loadingMessage">Processing...</p>
        </div>
    </div>
    
    <div class="bg-white shadow-2xl rounded-2xl overflow-hidden w-full max-w-4xl flex flex-col md:flex-row">
        <!-- Left side - Branding/Info (unchanged) -->
        <div class="md:w-1/2 bg-gradient-to-br from-blue-600 to-purple-700 p-8 text-white flex flex-col justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-4">Logistics System</h2>
                <p class="text-blue-100 mb-6">Secure access with 2-step verification</p>
                
                <div class="space-y-4 mb-8">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle w-6 h-6 mr-3"></i>
                        <span>Real-time shipment tracking</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle w-6 h-6 mr-3"></i>
                        <span>Inventory management</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle w-6 h-6 mr-3"></i>
                        <span>Easy account recovery</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle w-6 h-6 mr-3"></i>
                        <span>Driver management</span>
                    </div>
                    <div class="flex items-center text-yellow-300">
                        <i class="fas fa-shield-alt w-6 h-6 mr-3"></i>
                        <span>2-step verification enabled</span>
                    </div>
                </div>
            </div>
            
            <div class="text-sm text-blue-200">
                <p>© 2026 Logistics System. All rights reserved.</p>
            </div>
        </div>
        
        <!-- Right side - Forms -->
        <div class="md:w-1/2 p-8">
            <!-- Show tabs only when not in verification mode -->
            <?php if (!$showVerification): ?>
            <div class="flex mb-6 bg-gray-100 rounded-lg p-1">
                <button onclick="switchForm('login')" 
                    class="flex-1 py-2 text-center rounded-md transition-all duration-300 <?php echo $activeForm === 'login' ? 'bg-white shadow-md text-blue-600' : 'text-gray-600 hover:text-blue-600'; ?>"
                    id="loginTab">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
                <button onclick="switchForm('signup')" 
                    class="flex-1 py-2 text-center rounded-md transition-all duration-300 <?php echo $activeForm === 'signup' ? 'bg-white shadow-md text-blue-600' : 'text-gray-600 hover:text-blue-600'; ?>"
                    id="signupTab">
                    <i class="fas fa-user-plus mr-2"></i>Sign Up
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Message Display -->
            <?php if ($message): ?>
                <div class="mb-4 p-3 rounded-lg <?php echo $messageType === 'error' ? 'bg-red-100 text-red-700 border-l-4 border-red-500' : 'bg-green-100 text-green-700 border-l-4 border-green-500'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Verification Form (shown after successful login) -->
            <?php if ($showVerification): ?>
            <div id="verificationForm" class="form-transition block">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-envelope-open-text text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Verify Your Login</h3>
                    <p class="text-gray-600">We've sent a 6-digit code to</p>
                    <p class="font-semibold text-blue-600"><?php echo $temp_email; ?></p>
                </div>
                
                <form method="POST" action="" id="verificationForm" class="space-y-4" onsubmit="showLoading('Verifying code...')">
                    <input type="hidden" name="action" value="verify">
                    <input type="hidden" name="user_id" value="<?php echo $temp_user_id; ?>">
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2 text-center">Enter 6-digit code</label>
                        <input type="text" 
                               name="verification_code" 
                               maxlength="6" 
                               class="verification-input w-full border-2 border-blue-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="000000"
                               autocomplete="off"
                               pattern="[0-9]{6}"
                               inputmode="numeric"
                               required
                               oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length === 6) document.getElementById('verifyBtn').click();">
                        <p class="text-sm text-gray-500 mt-2 text-center">Code expires in 10 minutes</p>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:from-blue-700 hover:to-purple-700 transition duration-300"
                            id="verifyBtn">
                        <i class="fas fa-check-circle mr-2"></i>Verify & Continue
                    </button>
                    
                    <div class="text-center mt-4">
                        <button type="button" onclick="resendCode(<?php echo $temp_user_id; ?>)" 
                                class="text-blue-600 hover:text-blue-800 text-sm" id="resendBtn">
                            <i class="fas fa-redo-alt mr-1"></i> Resend code
                        </button>
                    </div>
                    
                    <div class="text-center mt-2">
                        <a href="index.php" class="text-gray-500 hover:text-gray-700 text-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Back to login
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Login Form (shown only when not in verification mode) -->
            <?php if (!$showVerification): ?>
            <div id="loginForm" class="form-transition <?php echo $activeForm === 'login' ? 'block' : 'hidden'; ?>">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Welcome Back</h3>
                <form method="POST" action="" id="loginFormElement" class="space-y-4" onsubmit="showLoading('Verifying credentials...')">
                    <input type="hidden" name="action" value="login">
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">
                            <i class="fas fa-user mr-2 text-blue-500"></i>Username or Email
                        </label>
                        <input type="text" name="username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="Enter your username or email">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">
                            <i class="fas fa-lock mr-2 text-blue-500"></i>Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password" required 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="Enter your password" id="loginPassword">
                            <button type="button" onclick="togglePassword('loginPassword')" class="absolute right-3 top-2.5 text-gray-500">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <a href="includes/forgot_password.php" class="text-sm text-blue-600 hover:text-blue-800">Forgot password?</a>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-2 px-4 rounded-lg hover:from-blue-700 hover:to-purple-700 transition duration-300 transform hover:scale-105"
                            id="loginSubmitBtn">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                </form>
            </div>
            
            <!-- Signup Form (unchanged) -->
            <div id="signupForm" class="form-transition <?php echo $activeForm === 'signup' ? 'block' : 'hidden'; ?>">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Create Account</h3>
                <form method="POST" action="" id="signupFormElement" class="space-y-4" onsubmit="showLoading('Creating your account...')">
                    <input type="hidden" name="action" value="signup">
                    
                    <!-- Your existing signup form fields (unchanged) -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">
                            <i class="fas fa-user mr-2 text-blue-500"></i>Full Name
                        </label>
                        <input type="text" name="full_name" required 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="Enter your full name">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">
                            <i class="fas fa-at mr-2 text-blue-500"></i>Username
                        </label>
                        <input type="text" name="username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="Choose a username">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">
                            <i class="fas fa-envelope mr-2 text-blue-500"></i>Email
                        </label>
                        <input type="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="Enter your email">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">
                            <i class="fas fa-lock mr-2 text-blue-500"></i>Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password" required 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="Create a password (min. 6 characters)" id="signupPassword">
                            <button type="button" onclick="togglePassword('signupPassword')" class="absolute right-3 top-2.5 text-gray-500">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">
                            <i class="fas fa-lock mr-2 text-blue-500"></i>Confirm Password
                        </label>
                        <div class="relative">
                            <input type="password" name="confirm_password" required 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="Confirm your password" id="confirmPassword">
                            <button type="button" onclick="togglePassword('confirmPassword')" class="absolute right-3 top-2.5 text-gray-500">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <input type="checkbox" required class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
<span class="ml-2 text-sm text-gray-600">
    I agree to the 
    <a href="policy.php?from=signup" target="_blank" class="text-blue-600 hover:text-blue-800">Terms of Service</a> 
    and 
    <a href="policy.php?from=signup#privacy" target="_blank" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
</span>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-2 px-4 rounded-lg hover:from-blue-700 hover:to-purple-700 transition duration-300 transform hover:scale-105">
                        <i class="fas fa-user-plus mr-2"></i>Sign Up
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Loading overlay
        function showLoading(message = 'Processing...') {
            document.getElementById('loadingMessage').textContent = message;
            document.getElementById('loadingOverlay').classList.add('active');
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }
        
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Switch between login and signup forms
        function switchForm(form) {
            if (form === 'login') {
                document.getElementById('loginForm').classList.remove('hidden');
                document.getElementById('signupForm').classList.add('hidden');
                document.getElementById('loginTab').classList.add('bg-white', 'shadow-md', 'text-blue-600');
                document.getElementById('loginTab').classList.remove('text-gray-600');
                document.getElementById('signupTab').classList.remove('bg-white', 'shadow-md', 'text-blue-600');
                document.getElementById('signupTab').classList.add('text-gray-600');
                
                // Update URL without page reload
                const url = new URL(window.location);
                url.searchParams.set('form', 'login');
                window.history.pushState({}, '', url);
            } else {
                document.getElementById('loginForm').classList.add('hidden');
                document.getElementById('signupForm').classList.remove('hidden');
                document.getElementById('signupTab').classList.add('bg-white', 'shadow-md', 'text-blue-600');
                document.getElementById('signupTab').classList.remove('text-gray-600');
                document.getElementById('loginTab').classList.remove('bg-white', 'shadow-md', 'text-blue-600');
                document.getElementById('loginTab').classList.add('text-gray-600');
                
                // Update URL without page reload
                const url = new URL(window.location);
                url.searchParams.set('form', 'signup');
                window.history.pushState({}, '', url);
            }
        }
        
        // Resend verification code
        function resendCode(userId) {
            const resendBtn = document.getElementById('resendBtn');
            resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
            resendBtn.disabled = true;
            
            showLoading('Sending new code...');
            
            fetch('includes/resend_verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    alert('✓ New verification code sent to your email!');
                    resendBtn.innerHTML = '<i class="fas fa-redo-alt mr-1"></i> Resend code';
                    resendBtn.disabled = false;
                    
                    // Start countdown
                    startResendCountdown();
                } else {
                    alert('Failed to send code. Please try again.');
                    resendBtn.innerHTML = '<i class="fas fa-redo-alt mr-1"></i> Resend code';
                    resendBtn.disabled = false;
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                alert('Error sending code');
                resendBtn.innerHTML = '<i class="fas fa-redo-alt mr-1"></i> Resend code';
                resendBtn.disabled = false;
            });
        }
        
        // Resend countdown
        function startResendCountdown() {
            let seconds = 60;
            const resendBtn = document.getElementById('resendBtn');
            
            const timer = setInterval(() => {
                seconds--;
                if (seconds > 0) {
                    resendBtn.innerHTML = `<i class="fas fa-clock mr-1"></i> Resend in ${seconds}s`;
                    resendBtn.disabled = true;
                } else {
                    clearInterval(timer);
                    resendBtn.innerHTML = '<i class="fas fa-redo-alt mr-1"></i> Resend code';
                    resendBtn.disabled = false;
                }
            }, 1000);
        }
        
        // Auto-hide loading when page loads
        window.addEventListener('load', function() {
            hideLoading();
        });
        
        // Handle form submissions with loading
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }
            });
        });
    </script>
</body>
</html>