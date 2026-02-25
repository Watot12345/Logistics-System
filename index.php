
<?php
session_start();
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logistics System | Login & Signup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/output.css">
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center min-h-screen p-4">
    
    <div class="bg-white shadow-2xl rounded-2xl overflow-hidden w-full max-w-4xl flex flex-col md:flex-row">
        <!-- Left side - Branding/Info -->
        <div class="md:w-1/2 bg-gradient-to-br from-blue-600 to-purple-700 p-8 text-white flex flex-col justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-4">Logistics System</h2>
                <p class="text-blue-100 mb-6">Streamline your logistics operations with our comprehensive management system.</p>
                
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
                        <span>Route optimization</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle w-6 h-6 mr-3"></i>
                        <span>Driver management</span>
                    </div>
                </div>
            </div>
            
            <div class="text-sm text-blue-200">
                <p>© 2024 Logistics System. All rights reserved.</p>
            </div>
        </div>
        
        <!-- Right side - Forms -->
        <div class="md:w-1/2 p-8">
            <!-- Toggle Buttons -->
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
            
            <!-- Message Display -->
            <?php if ($message): ?>
                <div class="mb-4 p-3 rounded-lg <?php echo $messageType === 'error' ? 'bg-red-100 text-red-700 border-l-4 border-red-500 error-shake' : 'bg-green-100 text-green-700 border-l-4 border-green-500'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <div id="loginForm" class="form-transition <?php echo $activeForm === 'login' ? 'block' : 'hidden'; ?>">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Welcome Back</h3>
                <form method="POST" action="" id="loginFormElement" class="space-y-4">
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
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800">Forgot password?</a>
                    </div>
                    
                    <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-2 px-4 rounded-lg hover:from-blue-700 hover:to-purple-700 transition duration-300 transform hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                </form>
            </div>
            
            <!-- Signup Form -->
            <div id="signupForm" class="form-transition <?php echo $activeForm === 'signup' ? 'block' : 'hidden'; ?>">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Create Account</h3>
                <form method="POST" action="" id="signupFormElement" class="space-y-4" onsubmit="return validateSignup()">
                    <input type="hidden" name="action" value="signup">
                    
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
                        <div class="mt-1 text-xs text-gray-500">
                            Password strength: <span id="passwordStrength" class="font-medium">Not entered</span>
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
                            I agree to the <a href="#" class="text-blue-600 hover:text-blue-800">Terms of Service</a> and <a href="#" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                        </span>
                    </div>
                    
                    <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-2 px-4 rounded-lg hover:from-blue-700 hover:to-purple-700 transition duration-300 transform hover:scale-105">
                        <i class="fas fa-user-plus mr-2"></i>Sign Up
                    </button>
                </form>
            </div>
        </div>
    </div>
    
  
    
    <!-- Create a simple dashboard.php for redirection -->
    <?php
    // If this file is accessed directly and user is logged in, create a simple dashboard
    if (isset($_GET['dashboard']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        ?>
        <div style="position: fixed; top: 20px; right: 20px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.2);">
            <h2>Welcome to Dashboard, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
            <p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
            <a href="?logout=1" class="bg-red-500 text-white px-4 py-2 rounded inline-block mt-2">Logout</a>
        </div>
        <?php
    }
    
    // Handle logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: index.php');
        exit();
    }
    ?>
</body>
<script src="assets/js/modals/authvalidation.js"></script>
</html>