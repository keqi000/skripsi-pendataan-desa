// Sidebar Component JavaScript
class SidebarComponent {
    constructor() {
        if (document.querySelector('.sidebar-main-sidebar')) {
            this.sidebar = document.getElementById('mainSidebar');
            this.init();
        }
    }
    
    init() {
        this.setupNavigation();
        this.setupMobileToggle();
    }
    
    setupNavigation() {
        const navLinks = document.querySelectorAll('.sidebar-nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                link.classList.add('active');
                
                // Close sidebar on mobile after navigation
                if (window.innerWidth <= 768) {
                    this.hide();
                }
            });
        });
    }
    
    setupMobileToggle() {
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!this.sidebar.contains(e.target) && 
                    !e.target.closest('.header-sidebar-toggle')) {
                    this.hide();
                }
            }
        });
    }
    
    toggle() {
        console.log('🔄 SIDEBAR TOGGLE called, window width:', window.innerWidth);
        
        if (window.innerWidth <= 768) {
            this.sidebar.classList.toggle('show');
            console.log('📱 Mobile toggle, sidebar classes:', this.sidebar.className);
        } else {
            this.sidebar.classList.toggle('collapsed');
            
            // Adjust main content margin
            const mainContent = document.querySelector('.main-layout-content');
            if (mainContent) {
                mainContent.classList.toggle('sidebar-collapsed');
            }
            
            // Add body class for modal responsiveness
            document.body.classList.toggle('sidebar-collapsed');
            
            // Log sidebar state
            const isCollapsed = this.sidebar.classList.contains('collapsed');
            console.log('💻 Desktop toggle:');
            console.log('- Sidebar collapsed:', isCollapsed);
            console.log('- Body classes:', document.body.className);
            console.log('- Main content classes:', mainContent ? mainContent.className : 'not found');
        }
    }
    
    show() {
        if (window.innerWidth <= 768) {
            this.sidebar.classList.add('show');
            console.log('Sidebar shown on mobile');
        } else {
            this.sidebar.classList.remove('collapsed');
        }
    }
    
    hide() {
        if (window.innerWidth <= 768) {
            this.sidebar.classList.remove('show');
        } else {
            this.sidebar.classList.add('collapsed');
        }
    }
}

// Global functions for sidebar
function loadPage(page) {
    if (window.mainLayoutComponent) {
        window.mainLayoutComponent.loadPage(page);
    }
}

// Initialize sidebar component
document.addEventListener('DOMContentLoaded', () => {
    window.sidebarComponent = new SidebarComponent();
});