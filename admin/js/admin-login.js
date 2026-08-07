/**
 * Admin Login Validation Script
 * Handles authentication form validation and security for admin portal
 * Separate from contact form validation to prevent conflicts
 */

// Admin login namespace to prevent conflicts
const AdminLoginValidator = {
    // Initialize admin login validation
    init: function() {
        document.addEventListener('DOMContentLoaded', function() {
            AdminLoginValidator.setupLoginForm();
        });
    },

    // Setup login form with validation and debugging
    setupLoginForm: function() {
        console.log('=== ADMIN LOGIN DEBUG START ===');
        
        // Focus on username field when page loads
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.focus();
            console.log('Username field focused');
        }

        // Add real-time validation feedback
        AdminLoginValidator.setupInputListeners();
        AdminLoginValidator.setupFormSubmission();
        
        // Add error handling
        window.addEventListener('error', function(e) {
            console.error('❌ JavaScript error on login page:', e.message, 'at', e.filename + ':' + e.lineno);
        });

        console.log('✅ Admin login JavaScript loaded successfully');
        console.log('🔧 To test manual submission, run: AdminLoginValidator.testFormSubmission()');
        console.log('=== ADMIN LOGIN DEBUG READY ===');
    },

    // Setup input event listeners for real-time feedback
    setupInputListeners: function() {
        const usernameField = document.getElementById('username');
        const passwordField = document.getElementById('password');
        
        if (usernameField) {
            usernameField.addEventListener('input', function() {
                console.log('Username input changed, current value length:', this.value.length);
                console.log('Username value (first 3 chars):', this.value.substring(0, 3) + '...');
            });
        }
        
        if (passwordField) {
            passwordField.addEventListener('input', function() {
                console.log('Password input changed, current length:', this.value.length);
            });
        }
    },

    // Setup form submission with enhanced validation
    setupFormSubmission: function() {
        const loginForm = document.querySelector('form');
        if (!loginForm) return;

        loginForm.addEventListener('submit', function(e) {
            console.log('=== FORM SUBMISSION EVENT TRIGGERED ===');
            console.log('Event object:', e);
            console.log('Form element:', this);
            console.log('Form action:', this.action);
            console.log('Form method:', this.method);
            
            const username = document.getElementById('username')?.value.trim() || '';
            const password = document.getElementById('password')?.value || '';
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
            
            console.log('Form validation check:');
            console.log('  - Username:', username ? `"${username}" (length: ${username.length})` : 'EMPTY');
            console.log('  - Password:', password ? `[${password.length} characters]` : 'EMPTY');
            console.log('  - CSRF Token:', csrfToken ? `[${csrfToken.length} characters]` : 'MISSING');
            
            // Validate required fields
            const usernameValid = username && username.length > 0;
            const passwordValid = password && password.length > 0;
            
            console.log('Validation results:');
            console.log('  - Username valid:', usernameValid);
            console.log('  - Password valid:', passwordValid);
            
            if (!usernameValid || !passwordValid) {
                console.error('❌ VALIDATION FAILED - preventing form submission');
                console.log('Calling e.preventDefault()');
                e.preventDefault();
                console.log('Showing alert');
                alert('Please enter both username and password.');
                console.log('Returning false');
                return false;
            }
            
            console.log('✅ VALIDATION PASSED - form should submit');
            console.log('NOT calling e.preventDefault()');
            console.log('NOT returning false');
            
            // Log all form data for debugging
            AdminLoginValidator.logFormData(this);
            
            console.log('=== ALLOWING FORM SUBMISSION TO PROCEED ===');
            return true;
        });
    },

    // Log form data for debugging
    logFormData: function(form) {
        const formData = new FormData(form);
        console.log('Form data being submitted:');
        for (let [key, value] of formData.entries()) {
            if (key === 'password') {
                console.log(`  ${key}: [${value.length} characters]`);
            } else if (key === 'csrf_token') {
                console.log(`  ${key}: ${value.substring(0, 10)}...`);
            } else {
                console.log(`  ${key}: ${value}`);
            }
        }
    },

    // Manual form submission test function
    testFormSubmission: function() {
        console.log('=== MANUAL FORM SUBMISSION TEST ===');
        const form = document.querySelector('form');
        console.log('Form found:', !!form);
        if (form) {
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);
            console.log('Submitting form manually...');
            form.submit();
        }
    },

    // Validate admin login credentials format
    validateLoginCredentials: function(username, password) {
        const errors = [];
        
        if (!username || username.trim() === '') {
            errors.push('Username is required');
        }
        
        if (!password || password === '') {
            errors.push('Password is required');
        }
        
        if (username && username.length < 3) {
            errors.push('Username must be at least 3 characters');
        }
        
        if (password && password.length < 6) {
            errors.push('Password must be at least 6 characters');
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }
};

// Make testFormSubmission globally available for debugging
window.testFormSubmission = function() {
    AdminLoginValidator.testFormSubmission();
};

// Initialize when script loads
AdminLoginValidator.init();