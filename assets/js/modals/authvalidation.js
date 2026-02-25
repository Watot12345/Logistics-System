 // Toggle between login and signup forms
        function switchForm(form) {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const loginTab = document.getElementById('loginTab');
            const signupTab = document.getElementById('signupTab');
            
            if (form === 'login') {
                loginForm.classList.remove('hidden');
                signupForm.classList.add('hidden');
                loginTab.classList.add('bg-white', 'shadow-md', 'text-blue-600');
                loginTab.classList.remove('text-gray-600');
                signupTab.classList.remove('bg-white', 'shadow-md', 'text-blue-600');
                signupTab.classList.add('text-gray-600');
                
                // Update URL without page reload
                window.history.pushState({}, '', '?form=login');
            } else {
                signupForm.classList.remove('hidden');
                loginForm.classList.add('hidden');
                signupTab.classList.add('bg-white', 'shadow-md', 'text-blue-600');
                signupTab.classList.remove('text-gray-600');
                loginTab.classList.remove('bg-white', 'shadow-md', 'text-blue-600');
                loginTab.classList.add('text-gray-600');
                
                // Update URL without page reload
                window.history.pushState({}, '', '?form=signup');
            }
        }
        
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
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
        
        // Validate signup form
        function validateSignup() {
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const email = document.querySelector('input[name="email"]').value;
            const username = document.querySelector('input[name="username"]').value;
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return false;
            }
            
            // Username validation
            if (username.length < 3) {
                alert('Username must be at least 3 characters long');
                return false;
            }
            
            // Password validation
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                return false;
            }
            
            // Password match validation
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return false;
            }
            
            return true;
        }
        
        // Check password strength
        document.getElementById('signupPassword')?.addEventListener('input', function() {
            const password = this.value;
            const strengthSpan = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthSpan.textContent = 'Not entered';
                strengthSpan.className = 'font-medium';
            } else if (password.length < 6) {
                strengthSpan.textContent = 'Weak';
                strengthSpan.className = 'font-medium text-red-500';
            } else if (password.length < 8) {
                strengthSpan.textContent = 'Medium';
                strengthSpan.className = 'font-medium text-yellow-500';
            } else if (password.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)) {
                strengthSpan.textContent = 'Strong';
                strengthSpan.className = 'font-medium text-green-500';
            } else {
                strengthSpan.textContent = 'Good';
                strengthSpan.className = 'font-medium text-blue-500';
            }
        });
        
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.bg-red-100, .bg-green-100');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);

