/**
 * Contact Form Validation Script
 * Handles input validation and styling for contact forms
 * Separate from admin login validation to prevent conflicts
 */

// Contact form namespace to prevent conflicts
const ContactFormValidator = {
    // Initialize contact form validation
    init: function() {
        document.addEventListener('DOMContentLoaded', function() {
            const contactInputs = document.querySelectorAll('#name, #email, #verify');
            contactInputs.forEach(function(input) {
                ContactFormValidator.checkContactInput(input);
            });
        });
    },

    // Clear placeholder-like input values when focused
    clearContactInput: function(element) {
        if (!element || !element.value) return;
        
        const placeholderValues = ['name', 'Code', 'email@email.com'];
        if (placeholderValues.includes(element.value)) {
            element.value = '';
            element.style.color = '#000';
            element.style.fontStyle = 'normal';
        }
    },

    // Check and style inputs when they lose focus
    checkContactInput: function(element) {
        if (!element) return;
        
        if (element.value === '' || element.value.trim() === '') {
            // Style as placeholder text
            element.style.color = '#999';
            element.style.fontStyle = 'italic';
            
            // Set appropriate placeholder values
            switch(element.name) {
                case 'name':
                    element.value = 'name';
                    break;
                case 'verify':
                    element.value = 'Code';
                    break;
                case 'email':
                    element.value = 'email@email.com';
                    break;
            }
        } else {
            // User has entered content
            element.style.color = '#000';
            element.style.fontStyle = 'normal';
        }
    },

    // Validate contact form before submission
    validateContactForm: function(form) {
        if (!form) return false;
        
        const name = form.querySelector('#name');
        const email = form.querySelector('#email');
        const message = form.querySelector('#message');
        const verify = form.querySelector('#verify');
        
        let isValid = true;
        const errors = [];
        
        // Validate name
        if (!name || !name.value || name.value === 'name' || name.value.trim() === '') {
            errors.push('Please enter your name');
            isValid = false;
        }
        
        // Validate email
        if (!email || !email.value || email.value === 'email@email.com' || email.value.trim() === '') {
            errors.push('Please enter your email address');
            isValid = false;
        } else if (!ContactFormValidator.isValidEmail(email.value)) {
            errors.push('Please enter a valid email address');
            isValid = false;
        }
        
        // Validate message
        if (!message || !message.value || message.value.trim() === '') {
            errors.push('Please enter a message');
            isValid = false;
        }
        
        // Validate verification code
        if (!verify || !verify.value || verify.value === 'Code' || verify.value.trim() === '') {
            errors.push('Please enter the verification code');
            isValid = false;
        }
        
        // Display errors if any
        if (!isValid) {
            alert('Please correct the following errors:\n• ' + errors.join('\n• '));
        }
        
        return isValid;
    },

    // Email validation helper
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
};

// Legacy function names for backward compatibility
function clearInput(x) {
    ContactFormValidator.clearContactInput(x);
}

function checkInput(x) {
    ContactFormValidator.checkContactInput(x);
}

// Initialize when script loads
ContactFormValidator.init();