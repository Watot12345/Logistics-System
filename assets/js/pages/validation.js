/**
 * Logistics System - Client Side Validation
 * This file contains all JavaScript validation functions
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Get form elements
    const loginForm = document.getElementById('loginFormElement');
    const signupForm = document.getElementById('signupFormElement');
    const loginPassword = document.getElementById('loginPassword');
    const signupPassword = document.getElementById('signupPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    
    // Add real-time validation listeners
    if (signupForm) {
        // Full name validation
        const fullNameInput = document.querySelector('input[name="full_name"]');
        if (fullNameInput) {
            fullNameInput.addEventListener('input', function() {
                validateFullName(this);
            });
            fullNameInput.addEventListener('blur', function() {
                validateFullName(this, true);
            });
        }
        
        // Username validation
        const usernameInput = document.querySelector('input[name="username"]');
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                validateUsername(this);
            });
            usernameInput.addEventListener('blur', function() {
                validateUsername(this, true);
            });
        }
        
        // Email validation
        const emailInput = document.querySelector('input[name="email"]');
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                validateEmail(this);
            });
            emailInput.addEventListener('blur', function() {
                validateEmail(this, true);
            });
        }
        
        // Password validation
        if (signupPassword) {
            signupPassword.addEventListener('input', function() {
                validatePassword(this);
                checkPasswordStrength(this.value);
                // Also validate confirm password if it has value
                if (confirmPassword && confirmPassword.value) {
                    validateConfirmPassword(confirmPassword, signupPassword);
                }
            });
        }
        
        // Confirm password validation
        if (confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                validateConfirmPassword(this, signupPassword);
            });
            confirmPassword.addEventListener('blur', function() {
                validateConfirmPassword(this, signupPassword, true);
            });
        }
        
        // Terms checkbox validation
        const termsCheckbox = document.querySelector('input[type="checkbox"][required]');
        if (termsCheckbox) {
            termsCheckbox.addEventListener('change', function() {
                validateTerms(this);
            });
        }
    }
    
    // Login form real-time validation
    if (loginForm) {
        const loginUsername = document.querySelector('input[name="username"]');
        const loginPassword = document.getElementById('loginPassword');
        
        if (loginUsername) {
            loginUsername.addEventListener('input', function() {
                validateLoginUsername(this);
            });
        }
        
        if (loginPassword) {
            loginPassword.addEventListener('input', function() {
                validateLoginPassword(this);
            });
        }
    }
    
    // Form submission handlers
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!validateLoginForm(this)) {
                e.preventDefault();
                showNotification('Please fix all errors before submitting', 'error');
            }
        });
    }
    
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            if (!validateSignupForm(this)) {
                e.preventDefault();
                showNotification('Please fix all errors before submitting', 'error');
            }
        });
    }
});

/**
 * Full Name Validation
 */
function validateFullName(input, showError = false) {
    const value = input.value.trim();
    const errorElement = getOrCreateErrorElement(input);
    
    // Remove any existing error styles
    input.classList.remove('border-red-500', 'border-green-500');
    
    if (value.length === 0) {
        errorElement.textContent = 'Full name is required';
        input.classList.add('border-red-500');
        return false;
    } else if (value.length < 2) {
        errorElement.textContent = 'Full name must be at least 2 characters';
        input.classList.add('border-red-500');
        return false;
    } else if (!/^[a-zA-Z\s\'-]+$/.test(value)) {
        errorElement.textContent = 'Full name can only contain letters, spaces, hyphens and apostrophes';
        input.classList.add('border-red-500');
        return false;
    } else {
        errorElement.textContent = '';
        input.classList.add('border-green-500');
        return true;
    }
}

/**
 * Username Validation
 */
function validateUsername(input, showError = false) {
    const value = input.value.trim();
    const errorElement = getOrCreateErrorElement(input);
    
    input.classList.remove('border-red-500', 'border-green-500');
    
    if (value.length === 0) {
        errorElement.textContent = 'Username is required';
        input.classList.add('border-red-500');
        return false;
    } else if (value.length < 3) {
        errorElement.textContent = 'Username must be at least 3 characters';
        input.classList.add('border-red-500');
        return false;
    } else if (value.length > 20) {
        errorElement.textContent = 'Username must be less than 20 characters';
        input.classList.add('border-red-500');
        return false;
    } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
        errorElement.textContent = 'Username can only contain letters, numbers, and underscores';
        input.classList.add('border-red-500');
        return false;
    } else {
        errorElement.textContent = '';
        input.classList.add('border-green-500');
        return true;
    }
}

/**
 * Email Validation
 */
function validateEmail(input, showError = false) {
    const value = input.value.trim();
    const errorElement = getOrCreateErrorElement(input);
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    input.classList.remove('border-red-500', 'border-green-500');
    
    if (value.length === 0) {
        errorElement.textContent = 'Email is required';
        input.classList.add('border-red-500');
        return false;
    } else if (!emailRegex.test(value)) {
        errorElement.textContent = 'Please enter a valid email address';
        input.classList.add('border-red-500');
        return false;
    } else {
        errorElement.textContent = '';
        input.classList.add('border-green-500');
        return true;
    }
}

/**
 * Password Validation
 */
function validatePassword(input, showError = false) {
    const value = input.value;
    const errorElement = getOrCreateErrorElement(input);
    
    input.classList.remove('border-red-500', 'border-green-500');
    
    if (value.length === 0) {
        errorElement.textContent = 'Password is required';
        input.classList.add('border-red-500');
        return false;
    } else if (value.length < 6) {
        errorElement.textContent = 'Password must be at least 6 characters';
        input.classList.add('border-red-500');
        return false;
    } else if (value.length > 50) {
        errorElement.textContent = 'Password must be less than 50 characters';
        input.classList.add('border-red-500');
        return false;
    } else {
        // Check password strength
        const strength = checkPasswordStrength(value);
        if (strength === 'weak') {
            errorElement.textContent = 'Password is too weak. Add uppercase, numbers, or special characters';
            input.classList.add('border-yellow-500');
        } else {
            errorElement.textContent = '';
            input.classList.add('border-green-500');
        }
        return strength !== 'weak';
    }
}

/**
 * Confirm Password Validation
 */
function validateConfirmPassword(input, passwordInput, showError = false) {
    const value = input.value;
    const password = passwordInput.value;
    const errorElement = getOrCreateErrorElement(input);
    
    input.classList.remove('border-red-500', 'border-green-500');
    
    if (value.length === 0) {
        errorElement.textContent = 'Please confirm your password';
        input.classList.add('border-red-500');
        return false;
    } else if (value !== password) {
        errorElement.textContent = 'Passwords do not match';
        input.classList.add('border-red-500');
        return false;
    } else {
        errorElement.textContent = '';
        input.classList.add('border-green-500');
        return true;
    }
}

/**
 * Login Username Validation
 */
function validateLoginUsername(input) {
    const value = input.value.trim();
    const errorElement = getOrCreateErrorElement(input);
    
    input.classList.remove('border-red-500', 'border-green-500');
    
    if (value.length === 0) {
        errorElement.textContent = 'Username or email is required';
        input.classList.add('border-red-500');
        return false;
    } else {
        errorElement.textContent = '';
        input.classList.add('border-green-500');
        return true;
    }
}

/**
 * Login Password Validation
 */
function validateLoginPassword(input) {
    const value = input.value;
    const errorElement = getOrCreateErrorElement(input);
    
    input.classList.remove('border-red-500', 'border-green-500');
    
    if (value.length === 0) {
        errorElement.textContent = 'Password is required';
        input.classList.add('border-red-500');
        return false;
    } else {
        errorElement.textContent = '';
        input.classList.add('border-green-500');
        return true;
    }
}

/**
 * Terms Checkbox Validation
 */
function validateTerms(checkbox) {
    const container = checkbox.closest('.flex');
    let errorElement = container.nextElementSibling;
    
    if (!errorElement || !errorElement.classList.contains('text-red-500')) {
        errorElement = document.createElement('p');
        errorElement.className = 'text-red-500 text-xs mt-1';
        container.parentNode.insertBefore(errorElement, container.nextSibling);
    }
    
    if (!checkbox.checked) {
        errorElement.textContent = 'You must agree to the Terms of Service';
        return false;
    } else {
        errorElement.textContent = '';
        return true;
    }
}

/**
 * Password Strength Checker
 */
function checkPasswordStrength(password) {
    const strengthSpan = document.getElementById('passwordStrength');
    if (!strengthSpan) return;
    
    let strength = 'weak';
    let color = 'text-red-500';
    
    if (password.length === 0) {
        strengthSpan.textContent = 'Not entered';
        strengthSpan.className = 'font-medium';
        return;
    }
    
    // Check password strength
    const hasLowerCase = /[a-z]/.test(password);
    const hasUpperCase = /[A-Z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
    
    const strengthScore = [hasLowerCase, hasUpperCase, hasNumbers, hasSpecialChar].filter(Boolean).length;
    
    if (password.length >= 8 && strengthScore >= 3) {
        strength = 'strong';
        color = 'text-green-500';
    } else if (password.length >= 6 && strengthScore >= 2) {
        strength = 'medium';
        color = 'text-yellow-500';
    } else {
        strength = 'weak';
        color = 'text-red-500';
    }
    
    strengthSpan.textContent = strength.charAt(0).toUpperCase() + strength.slice(1);
    strengthSpan.className = `font-medium ${color}`;
    
    return strength;
}

/**
 * Get or create error element for input
 */
function getOrCreateErrorElement(input) {
    const parent = input.parentElement;
    let errorElement = parent.nextElementSibling;
    
    // Check if next element is an error element
    if (!errorElement || !errorElement.classList.contains('error-message')) {
        errorElement = document.createElement('p');
        errorElement.className = 'error-message text-red-500 text-xs mt-1';
        parent.parentNode.insertBefore(errorElement, parent.nextSibling);
    }
    
    return errorElement;
}

/**
 * Validate entire login form
 */
function validateLoginForm(form) {
    const username = form.querySelector('input[name="username"]');
    const password = document.getElementById('loginPassword');
    
    const isUsernameValid = validateLoginUsername(username);
    const isPasswordValid = validateLoginPassword(password);
    
    return isUsernameValid && isPasswordValid;
}

/**
 * Validate entire signup form
 */
function validateSignupForm(form) {
    const fullName = form.querySelector('input[name="full_name"]');
    const username = form.querySelector('input[name="username"]');
    const email = form.querySelector('input[name="email"]');
    const password = document.getElementById('signupPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const termsCheckbox = form.querySelector('input[type="checkbox"][required]');
    
    const isFullNameValid = validateFullName(fullName, true);
    const isUsernameValid = validateUsername(username, true);
    const isEmailValid = validateEmail(email, true);
    const isPasswordValid = validatePassword(password, true);
    const isConfirmPasswordValid = validateConfirmPassword(confirmPassword, password, true);
    const isTermsValid = validateTerms(termsCheckbox);
    
    return isFullNameValid && isUsernameValid && isEmailValid && 
           isPasswordValid && isConfirmPasswordValid && isTermsValid;
}

/**
 * Toggle password visibility
 */
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

/**
 * Switch between login and signup forms
 */
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
    
    // Clear any error messages
    clearAllErrors();
}

/**
 * Clear all error messages
 */
function clearAllErrors() {
    const errorMessages = document.querySelectorAll('.error-message');
    errorMessages.forEach(msg => msg.remove());
    
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.classList.remove('border-red-500', 'border-green-500', 'border-yellow-500');
    });
}

/**
 * Show notification message
 */
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existingNotification = document.querySelector('.custom-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `custom-notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transition-all duration-500 ${
        type === 'error' ? 'bg-red-500' : 
        type === 'success' ? 'bg-green-500' : 
        'bg-blue-500'
    } text-white`;
    
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 
                           type === 'success' ? 'fa-check-circle' : 
                           'fa-info-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

// Export functions for global use
window.togglePassword = togglePassword;
window.switchForm = switchForm;
window.validateSignup = function() {
    const form = document.getElementById('signupFormElement');
    return validateSignupForm(form);
};