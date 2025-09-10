// Login Page JavaScript
class LoginPage {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupPasswordToggle();
        this.setupFormValidation();
        this.setupThemeToggle();
    }
    
    setupPasswordToggle() {
        // Password toggle sudah ada di global function
    }
    
    setupFormValidation() {
        const form = document.querySelector('.login-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                const username = form.querySelector('#username').value.trim();
                const password = form.querySelector('#password').value;
                
                if (!username || !password) {
                    e.preventDefault();
                    this.showError('Username dan password harus diisi');
                    return;
                }
                
                // Show loading state
                const submitBtn = form.querySelector('.btn-login');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                submitBtn.disabled = true;
                
                // Reset button after 3 seconds if form doesn't submit
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            });
        }
    }
    
    setupThemeToggle() {
        // Theme toggle functionality (optional for login page)
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
    
    showError(message) {
        // Remove existing alerts
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Create new alert
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            ${message}
        `;
        
        // Insert after login header
        const loginHeader = document.querySelector('.login-header');
        loginHeader.insertAdjacentElement('afterend', alert);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

// Global function for password toggle
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.password-toggle i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleBtn.className = 'fas fa-eye';
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new LoginPage();
});