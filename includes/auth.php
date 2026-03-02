<?php
require_once 'config/db.php';

// Handle form submissions
$message = '';
$messageType = '';
$employee_id = 'EMP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Handle Login
        if ($_POST['action'] === 'login') {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            
            if (empty($username) || empty($password)) {
                $message = 'Please fill in all fields';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $username]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user && password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in'] = true;
                        
                        // Redirect to dashboard
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $message = 'Invalid username/email or password';
                        $messageType = 'error';
                    }
                } catch(PDOException $e) {
                    $message = 'Login failed: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Handle Signup
        if ($_POST['action'] === 'signup') {
            $full_name = trim($_POST['full_name']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validation
            $errors = [];
            
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
                }
            } else {
                $message = implode('<br>', $errors);
                $messageType = 'error';
            }
        }
    }
}

// Determine which form to show (default to login)
$activeForm = isset($_GET['form']) && $_GET['form'] === 'signup' ? 'signup' : 'login';
?>