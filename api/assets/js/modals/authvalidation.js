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
    try {
        // Get the signup form specifically
        const signupForm = document.getElementById('signupFormElement');
        
        const fullName = signupForm.querySelector('input[name="full_name"]').value;
        const username = signupForm.querySelector('input[name="username"]').value;
        const email = signupForm.querySelector('input[name="email"]').value;
        const password = signupForm.querySelector('input[name="password"]').value;
        const confirmPassword = signupForm.querySelector('input[name="confirm_password"]').value;
        const termsCheckbox = signupForm.querySelector('input[type="checkbox"]');
        
        console.log('Validating signup:', {fullName, username, email, password, confirmPassword, termsChecked: termsCheckbox.checked});
        
        // Full name validation
        if (!fullName.trim()) {
            alert('Please enter your full name');
            return false;
        }
        
        // Username validation
        if (username.length < 3) {
            alert('Username must be at least 3 characters long');
            return false;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address');
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
        
        // Terms checkbox validation
        if (!termsCheckbox.checked) {
            alert('You must agree to the Terms of Service and Privacy Policy');
            return false;
        }
        
        console.log('All validations passed - submitting form');
        return true;
    } catch (e) {
        console.error('Validation error:', e);
        alert('An error occurred during validation: ' + e.message);
        return false;
    }
}
        // Add to your authvalidation.js file

// Handle login form submission with loading state
document.getElementById('loginFormElement')?.addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('loginSubmitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Verifying...';
    submitBtn.disabled = true;
});

// Handle verification form submission
document.getElementById('verificationFormElement')?.addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('verifyBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Verifying code...';
    submitBtn.disabled = true;
});

// Auto-focus and auto-submit when 6 digits entered
document.querySelector('input[name="verification_code"]')?.addEventListener('input', function(e) {
    if (this.value.length === 6) {
        document.getElementById('verifyBtn').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Verifying...';
        document.getElementById('verifyBtn').disabled = true;
        document.getElementById('verificationFormElement').submit();
    }
});

// Resend code function
function resendCode(userId) {
    const resendBtn = document.getElementById('resendBtn');
    resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
    resendBtn.disabled = true;
    
    fetch('../includes/resend_verification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('New verification code sent to your email!');
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

