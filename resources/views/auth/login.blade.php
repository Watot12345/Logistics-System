<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logistics System | Login & Sign in</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
    <!-- Static compiled Tailwind CSS (Pure CSS, 0% JS, 0% eval, CSP safe) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="/assets/css/output.css">
    <style>
        .form-transition {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center min-h-screen p-4">
    
    <div class="bg-white shadow-2xl rounded-2xl overflow-hidden w-full max-w-4xl flex flex-col md:flex-row">
        <!-- Left side - Branding/Info -->
        <div class="md:w-1/2 bg-gradient-to-br from-blue-600 to-purple-700 p-8 text-white flex flex-col justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-4">Logistics System</h2>
                <p class="text-blue-100 mb-6">Hotel & Restaurant Logistics Management</p>
                
                <div class="space-y-4 mb-8">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle w-6 h-6 mr-3"></i>
                        <span>Real-time shipment tracking</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle w-6 h-6 mr-3"></i>
                        <span>Inventory & smart warehousing</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle w-6 h-6 mr-3"></i>
                        <span>Fleet & vehicle management</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle w-6 h-6 mr-3"></i>
                        <span>Driver assignment & dispatch</span>
                    </div>
                    <div class="flex items-center text-yellow-300">
                        <i class="fas fa-shield-alt w-6 h-6 mr-3"></i>
                        <span>Role-based access enabled</span>
                    </div>
                </div>
            </div>
            
            <div class="text-sm text-blue-200">
                <p>© {{ date('Y') }} Logistics System. All rights reserved.</p>
            </div>
        </div>
        
        <!-- Right side - Forms -->
        <div class="md:w-1/2 p-8 flex flex-col justify-between">
            <div>
                <!-- Tabs -->
                <div class="flex mb-6 bg-gray-100 rounded-lg p-1">
                    <button type="button" onclick="switchForm('login')" 
                        class="flex-1 py-2 text-center rounded-md transition-all duration-300 bg-white shadow-md text-blue-600 font-medium"
                        id="loginTab">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                    <button type="button" onclick="switchForm('demo')" 
                        class="flex-1 py-2 text-center rounded-md transition-all duration-300 text-gray-600 hover:text-blue-600 font-medium"
                        id="demoTab">
                        <i class="fas fa-users-cog mr-2"></i>Demo Accounts
                    </button>
                </div>
                
                <!-- Messages -->
                @if(session('success'))
                    <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 border-l-4 border-green-500 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                
                @if(isset($errors) && $errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 border-l-4 border-red-500 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif
                
                <!-- Login Form -->
                <div id="loginForm" class="form-transition block">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <i class="fas fa-user-circle text-3xl text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">Sign in to your account</h3>
                    </div>
                    
                    <form method="POST" action="/login" class="space-y-4">
                        @csrf
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-1 text-sm">
                                <i class="fas fa-user mr-2 text-blue-500"></i>Username or Email
                            </label>
                            <input type="text" name="login" required id="loginInput"
                                   value="{{ old('login', 'joshua') }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="Enter your username or email">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-1 text-sm">
                                <i class="fas fa-lock mr-2 text-blue-500"></i>Password
                            </label>
                            <div class="relative">
                                <input type="password" name="password" required id="passwordInput"
                                       value="joshua"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                       placeholder="Enter your password">
                                <button type="button" onclick="togglePassword()" class="absolute right-3 top-2.5 text-gray-500">
                                    <i class="fas fa-eye" id="toggleEye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between text-xs">
                            <label class="flex items-center gap-1.5 text-gray-600 cursor-pointer">
                                <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Remember me</span>
                            </label>
                            <span class="text-blue-600 hover:text-blue-800 cursor-pointer">Logistics Operations</span>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-2.5 px-4 rounded-lg hover:from-blue-700 hover:to-purple-700 transition duration-300 shadow-md">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </button>
                    </form>
                </div>

                <!-- Demo Accounts Panel -->
                <div id="demoForm" class="form-transition hidden space-y-2 text-xs">
                    <p class="text-gray-500 mb-2">Click any role to auto-fill the login form:</p>
                    <button type="button" onclick="fillAndLogin('joshua', 'joshua')" class="w-full p-2.5 border rounded-lg text-left hover:bg-blue-50 flex items-center justify-between transition">
                        <div>
                            <strong class="text-blue-700 block">Administrator</strong>
                            <span class="text-gray-500">joshua / joshua</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                    <button type="button" onclick="fillAndLogin('jay jay', 'jayjay123')" class="w-full p-2.5 border rounded-lg text-left hover:bg-blue-50 flex items-center justify-between transition">
                        <div>
                            <strong class="text-blue-700 block">Fleet Manager</strong>
                            <span class="text-gray-500">jay jay / jayjay123</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                    <button type="button" onclick="fillAndLogin('john john', 'johnjohn123')" class="w-full p-2.5 border rounded-lg text-left hover:bg-blue-50 flex items-center justify-between transition">
                        <div>
                            <strong class="text-blue-700 block">Driver</strong>
                            <span class="text-gray-500">john john / johnjohn123</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                    <button type="button" onclick="fillAndLogin('john doe', 'johndoe123')" class="w-full p-2.5 border rounded-lg text-left hover:bg-blue-50 flex items-center justify-between transition">
                        <div>
                            <strong class="text-blue-700 block">Mechanic</strong>
                            <span class="text-gray-500">john doe / johndoe123</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                    <button type="button" onclick="fillAndLogin('jon jon', 'jonjon123')" class="w-full p-2.5 border rounded-lg text-left hover:bg-blue-50 flex items-center justify-between transition">
                        <div>
                            <strong class="text-blue-700 block">Dispatcher</strong>
                            <span class="text-gray-500">jon jon / jonjon123</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchForm(form) {
            const loginForm = document.getElementById('loginForm');
            const demoForm = document.getElementById('demoForm');
            const loginTab = document.getElementById('loginTab');
            const demoTab = document.getElementById('demoTab');

            if (form === 'login') {
                loginForm.classList.remove('hidden');
                demoForm.classList.add('hidden');
                loginTab.className = 'flex-1 py-2 text-center rounded-md transition-all duration-300 bg-white shadow-md text-blue-600 font-medium';
                demoTab.className = 'flex-1 py-2 text-center rounded-md transition-all duration-300 text-gray-600 hover:text-blue-600 font-medium';
            } else {
                loginForm.classList.add('hidden');
                demoForm.classList.remove('hidden');
                demoTab.className = 'flex-1 py-2 text-center rounded-md transition-all duration-300 bg-white shadow-md text-blue-600 font-medium';
                loginTab.className = 'flex-1 py-2 text-center rounded-md transition-all duration-300 text-gray-600 hover:text-blue-600 font-medium';
            }
        }

        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const eye = document.getElementById('toggleEye');
            if (input.type === 'password') {
                input.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }

        function fillAndLogin(user, pass) {
            document.getElementById('loginInput').value = user;
            document.getElementById('passwordInput').value = pass;
            switchForm('login');
        }
    </script>
</body>
</html>
