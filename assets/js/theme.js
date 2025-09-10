// Theme Management - Global
(function() {
    'use strict';
    
    // Apply theme immediately
    function applyTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        console.log('Applying theme:', savedTheme);
        
        document.documentElement.setAttribute('data-theme', savedTheme);
        console.log('Theme set on documentElement:', document.documentElement.getAttribute('data-theme'));
        
        // Update theme toggle icon
        const themeToggle = document.querySelector('.header-theme-toggle i');
        if (themeToggle) {
            themeToggle.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            console.log('Icon set to:', themeToggle.className);
        } else {
            console.log('Theme toggle icon not found during applyTheme');
        }
    }
    
    // Apply theme on script load
    console.log('Theme script loaded');
    applyTheme();
    
    // Disable MutationObserver to prevent infinite loops
    // const observer = new MutationObserver...
    
    // Global theme toggle function
    window.toggleTheme = function() {
        // Prevent infinite recursion
        if (window.toggleTheme.isRunning) {
            console.log('toggleTheme already running, skipping');
            return;
        }
        
        window.toggleTheme.isRunning = true;
        console.log('toggleTheme called');
        
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        console.log('Current theme:', currentTheme);
        
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        console.log('New theme:', newTheme);
        
        // Wait for any AJAX to complete
        setTimeout(() => {
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            console.log('Theme applied to documentElement:', document.documentElement.getAttribute('data-theme'));
            console.log('Theme saved to localStorage:', localStorage.getItem('theme'));
            
            // Update icon
            const themeToggle = document.querySelector('.header-theme-toggle i');
            if (themeToggle) {
                themeToggle.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
                console.log('Icon updated to:', themeToggle.className);
            } else {
                console.error('Theme toggle icon not found');
            }
            
            // Check if CSS variables are working
            const computedStyle = getComputedStyle(document.documentElement);
            const bgColor = computedStyle.getPropertyValue('--lightest-color');
            console.log('Current --lightest-color:', bgColor);
            
            // Reset running flag
            window.toggleTheme.isRunning = false;
        }, 100);
    };
})();