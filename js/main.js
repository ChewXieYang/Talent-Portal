document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript Loaded - Client-side validation active');
    
    // Initialize all validation functions
    initializeFormValidation();
    initializePasswordStrength();
    initializeFileValidation();
    initializeRealTimeValidation();
});

// Main form validation initialization
function initializeFormValidation() {
    // Registration form validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', validateRegistrationForm);
    }
    
    // Login form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', validateLoginForm);
    }
    
    // Profile form validation
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', validateProfileForm);
    }
    
    // Forum topic/reply forms
    const topicForm = document.querySelector('form[action*="create_topic"]');
    if (topicForm) {
        topicForm.addEventListener('submit', validateTopicForm);
    }
    
    const replyForm = document.querySelector('form[method="POST"]');
    if (replyForm && replyForm.querySelector('textarea[name="reply_content"]')) {
        replyForm.addEventListener('submit', validateReplyForm);
    }
    
    // Service creation form
    const serviceForm = document.getElementById('serviceForm');
    if (serviceForm) {
        serviceForm.addEventListener('submit', validateServiceForm);
    }
}

// Registration form validation
function validateRegistrationForm(event) {
    const form = event.target;
    let isValid = true;
    let errorMessages = [];
    
    // Get form fields
    const username = form.querySelector('input[name="username"]');
    const email = form.querySelector('input[name="email"]');
    const password = form.querySelector('input[name="password"]');
    const confirmPassword = form.querySelector('input[name="confirm_password"]');
    const fullName = form.querySelector('input[name="full_name"]');
    const studentId = form.querySelector('input[name="student_id"]');
    const section = form.querySelector('select[name="section"]');
    
    // Clear previous error messages
    clearErrorMessages();
    
    // Username validation
    if (!username.value.trim()) {
        showFieldError(username, 'Username is required');
        isValid = false;
    } else if (username.value.length < 3) {
        showFieldError(username, 'Username must be at least 3 characters');
        isValid = false;
    } else if (!/^[a-zA-Z0-9_]+$/.test(username.value)) {
        showFieldError(username, 'Username can only contain letters, numbers, and underscores');
        isValid = false;
    }
    
    // Email validation
    if (!email.value.trim()) {
        showFieldError(email, 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        showFieldError(email, 'Please enter a valid email address');
        isValid = false;
    } else if (!email.value.includes('@student.mmu.edu.my')) {
        showFieldError(email, 'Please use your MMU student email (@student.mmu.edu.my)');
        isValid = false;
    }
    
    // Password validation
    if (!password.value) {
        showFieldError(password, 'Password is required');
        isValid = false;
    } else if (password.value.length < 8) {
        showFieldError(password, 'Password must be at least 8 characters');
        isValid = false;
    } else if (!isStrongPassword(password.value)) {
        showFieldError(password, 'Password must contain uppercase, lowercase, number, and special character');
        isValid = false;
    }
    
    // Confirm password validation
    if (confirmPassword && !confirmPassword.value) {
        showFieldError(confirmPassword, 'Please confirm your password');
        isValid = false;
    } else if (confirmPassword && password.value !== confirmPassword.value) {
        showFieldError(confirmPassword, 'Passwords do not match');
        isValid = false;
    }
    
    // Full name validation
    if (!fullName.value.trim()) {
        showFieldError(fullName, 'Full name is required');
        isValid = false;
    } else if (fullName.value.trim().length < 2) {
        showFieldError(fullName, 'Full name must be at least 2 characters');
        isValid = false;
    }
    
    // Student ID validation
    if (!studentId.value.trim()) {
        showFieldError(studentId, 'Student ID is required');
        isValid = false;
    } else if (!/^\d{10}$/.test(studentId.value)) {
        showFieldError(studentId, 'Student ID must be exactly 10 digits');
        isValid = false;
    }
    
    // Section validation
    if (!section.value) {
        showFieldError(section, 'Please select your section');
        isValid = false;
    }
    
    if (!isValid) {
        event.preventDefault();
        showValidationSummary(errorMessages);
    }
    
    return isValid;
}

// Login form validation
function validateLoginForm(event) {
    const form = event.target;
    let isValid = true;
    
    const username = form.querySelector('input[name="username"]');
    const password = form.querySelector('input[name="password"]');
    
    clearErrorMessages();
    
    if (!username.value.trim()) {
        showFieldError(username, 'Username is required');
        isValid = false;
    }
    
    if (!password.value) {
        showFieldError(password, 'Password is required');
        isValid = false;
    }
    
    if (!isValid) {
        event.preventDefault();
    }
    
    return isValid;
}

// Profile form validation
function validateProfileForm(event) {
    const form = event.target;
    let isValid = true;
    
    const fullName = form.querySelector('input[name="full_name"]');
    const email = form.querySelector('input[name="contact_email"]');
    const phone = form.querySelector('input[name="phone_number"]');
    
    clearErrorMessages();
    
    if (fullName && !fullName.value.trim()) {
        showFieldError(fullName, 'Full name is required');
        isValid = false;
    }
    
    if (email && email.value && !isValidEmail(email.value)) {
        showFieldError(email, 'Please enter a valid email address');
        isValid = false;
    }
    
    if (phone && phone.value && !isValidPhone(phone.value)) {
        showFieldError(phone, 'Please enter a valid phone number (010-1234567 or 0123456789)');
        isValid = false;
    }
    
    if (!isValid) {
        event.preventDefault();
    }
    
    return isValid;
}

// Forum topic validation
function validateTopicForm(event) {
    const form = event.target;
    let isValid = true;
    
    const category = form.querySelector('select[name="category_id"]');
    const title = form.querySelector('input[name="title"]');
    const content = form.querySelector('textarea[name="content"]');
    
    clearErrorMessages();
    
    if (!category.value) {
        showFieldError(category, 'Please select a category');
        isValid = false;
    }
    
    if (!title.value.trim()) {
        showFieldError(title, 'Topic title is required');
        isValid = false;
    } else if (title.value.trim().length < 5) {
        showFieldError(title, 'Topic title must be at least 5 characters');
        isValid = false;
    } else if (title.value.length > 200) {
        showFieldError(title, 'Topic title cannot exceed 200 characters');
        isValid = false;
    }
    
    if (!content.value.trim()) {
        showFieldError(content, 'Topic content is required');
        isValid = false;
    } else if (content.value.trim().length < 10) {
        showFieldError(content, 'Topic content must be at least 10 characters');
        isValid = false;
    }
    
    if (!isValid) {
        event.preventDefault();
    }
    
    return isValid;
}

// Forum reply validation
function validateReplyForm(event) {
    const form = event.target;
    const content = form.querySelector('textarea[name="reply_content"]');
    
    if (content && !content.value.trim()) {
        showFieldError(content, 'Reply content is required');
        event.preventDefault();
        return false;
    } else if (content && content.value.trim().length < 5) {
        showFieldError(content, 'Reply must be at least 5 characters');
        event.preventDefault();
        return false;
    }
    
    return true;
}

// Service form validation
function validateServiceForm(event) {
    const form = event.target;
    let isValid = true;
    
    const title = form.querySelector('input[name="service_title"]');
    const description = form.querySelector('textarea[name="service_description"]');
    const price = form.querySelector('input[name="price"]');
    
    clearErrorMessages();
    
    if (title && !title.value.trim()) {
        showFieldError(title, 'Service title is required');
        isValid = false;
    }
    
    if (description && !description.value.trim()) {
        showFieldError(description, 'Service description is required');
        isValid = false;
    }
    
    if (price && price.value && (isNaN(price.value) || parseFloat(price.value) < 0)) {
        showFieldError(price, 'Please enter a valid price');
        isValid = false;
    }
    
    if (!isValid) {
        event.preventDefault();
    }
    
    return isValid;
}

// File upload validation
function initializeFileValidation() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            validateFileUpload(this);
        });
    });
}

function validateFileUpload(input) {
    const file = input.files[0];
    if (!file) return true;
    
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif',
        'video/mp4', 'video/avi',
        'audio/mp3', 'audio/wav',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    if (file.size > maxSize) {
        showFieldError(input, 'File size must be less than 10MB');
        input.value = '';
        return false;
    }
    
    if (!allowedTypes.includes(file.type)) {
        showFieldError(input, 'Invalid file type. Please upload images, videos, audio, or documents only.');
        input.value = '';
        return false;
    }
    
    clearFieldError(input);
    return true;
}

// Real-time validation for better UX
function initializeRealTimeValidation() {
    // Email validation on blur
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                showFieldError(this, 'Please enter a valid email address');
            } else {
                clearFieldError(this);
            }
        });
    });
    
    // Password strength indicator
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        if (input.name === 'password') {
            input.addEventListener('input', function() {
                updatePasswordStrength(this);
            });
        }
    });
    
    // Character counters for text areas
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        if (maxLength) {
            textarea.addEventListener('input', function() {
                updateCharacterCount(this, maxLength);
            });
        }
    });
}

// Password strength checker
function initializePasswordStrength() {
    const passwordInput = document.querySelector('input[name="password"]');
    if (passwordInput) {
        // Create password strength indicator
        const strengthDiv = document.createElement('div');
        strengthDiv.className = 'password-strength';
        strengthDiv.innerHTML = '<div class="strength-bar"></div><div class="strength-text">Password strength: <span></span></div>';
        passwordInput.parentNode.appendChild(strengthDiv);
    }
}

function updatePasswordStrength(input) {
    const password = input.value;
    const strengthBar = input.parentNode.querySelector('.strength-bar');
    const strengthText = input.parentNode.querySelector('.strength-text span');
    
    if (!strengthBar || !strengthText) return;
    
    let strength = 0;
    let strengthLabel = '';
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    switch (strength) {
        case 0:
        case 1:
            strengthLabel = 'Very Weak';
            strengthBar.className = 'strength-bar very-weak';
            break;
        case 2:
            strengthLabel = 'Weak';
            strengthBar.className = 'strength-bar weak';
            break;
        case 3:
            strengthLabel = 'Medium';
            strengthBar.className = 'strength-bar medium';
            break;
        case 4:
            strengthLabel = 'Strong';
            strengthBar.className = 'strength-bar strong';
            break;
        case 5:
            strengthLabel = 'Very Strong';
            strengthBar.className = 'strength-bar very-strong';
            break;
    }
    
    strengthText.textContent = strengthLabel;
}

function updateCharacterCount(textarea, maxLength) {
    const currentLength = textarea.value.length;
    let counter = textarea.parentNode.querySelector('.char-counter');
    
    if (!counter) {
        counter = document.createElement('div');
        counter.className = 'char-counter';
        textarea.parentNode.appendChild(counter);
    }
    
    counter.textContent = `${currentLength}/${maxLength} characters`;
    
    if (currentLength > maxLength * 0.9) {
        counter.style.color = '#dc3545';
    } else {
        counter.style.color = '#666';
    }
}

// Utility functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^(\+?6?01[0-46-9][-\s]?\d{7,8}|\d{10,11})$/;
    return phoneRegex.test(phone.replace(/[-\s]/g, ''));
}

function isStrongPassword(password) {
    return password.length >= 8 &&
           /[a-z]/.test(password) &&
           /[A-Z]/.test(password) &&
           /[0-9]/.test(password) &&
           /[^a-zA-Z0-9]/.test(password);
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('error');
    
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

function clearErrorMessages() {
    const errorMessages = document.querySelectorAll('.field-error');
    errorMessages.forEach(error => error.remove());
    
    const errorFields = document.querySelectorAll('.error');
    errorFields.forEach(field => field.classList.remove('error'));
}

function showValidationSummary(messages) {
    const existingSummary = document.querySelector('.validation-summary');
    if (existingSummary) {
        existingSummary.remove();
    }
    
    if (messages.length > 0) {
        const summaryDiv = document.createElement('div');
        summaryDiv.className = 'validation-summary error-message';
        summaryDiv.innerHTML = '<h4>Please fix the following errors:</h4><ul>' + 
                              messages.map(msg => `<li>${msg}</li>`).join('') + '</ul>';
        
        const form = document.querySelector('form');
        if (form) {
            form.insertBefore(summaryDiv, form.firstChild);
        }
    }
}

// Smooth scroll for better UX
function smoothScrollToError() {
    const firstError = document.querySelector('.field-error');
    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}
