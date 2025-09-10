// Main Layout JavaScript - AJAX Content Loading
class MainLayoutComponent {
    constructor() {
        this.contentArea = document.getElementById('dynamicContent');
        this.contentLoader = document.getElementById('contentLoader');
        this.currentPage = '';
        this.init();
    }
    
    init() {
        this.setupAjaxNavigation();
        this.setupPopState();
        this.currentPage = this.getCurrentPageFromURL();
    }
    
    getCurrentPageFromURL() {
        const path = window.location.pathname;
        const segments = path.split('/').filter(segment => segment);
        return segments[segments.length - 1] || 'dashboard';
    }
    
    setupAjaxNavigation() {
        // Override default navigation for sidebar links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.sidebar-nav-link');
            if (link && link.hasAttribute('onclick')) {
                e.preventDefault();
            }
        });
    }
    
    setupPopState() {
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.page) {
                this.loadPageContent(e.state.page, false);
            }
        });
    }
    
    async loadPage(page) {
        if (page === this.currentPage) return;
        
        this.showContentLoader();
        
        try {
            await this.loadPageContent(page, true);
            this.currentPage = page;
            this.updateActiveNavigation(page);
            
        } catch (error) {
            console.error('Failed to load page:', error);
            this.showError('Gagal memuat halaman. Silakan coba lagi.');
        } finally {
            this.hideContentLoader();
        }
    }
    
    async loadPageContent(page, updateHistory = true) {
        const userRole = this.getUserRole();
        const pageUrl = this.getPageUrl(page, userRole);
        
        try {
            const response = await fetch(pageUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const html = await response.text();
            
            // Update content
            this.contentArea.innerHTML = html;
            
            // Update page title
            this.updatePageTitle(page);
            
            // Update URL without page reload
            if (updateHistory) {
                const newUrl = `${pendataanDesa.baseURL}${page}`;
                history.pushState({ page: page }, '', newUrl);
            }
            
            // Execute page-specific JavaScript if exists
            this.executePageScript(page);
            
        } catch (error) {
            throw error;
        }
    }
    
    getUserRole() {
        // Get user role from session or DOM
        const userInfo = document.querySelector('.sidebar-user-role');
        if (userInfo) {
            const roleText = userInfo.textContent.toLowerCase();
            return roleText.includes('admin') ? 'admin' : 'user';
        }
        return 'user';
    }
    
    getPageUrl(page, userRole) {
        const basePath = pendataanDesa.baseURL + 'pages/';
        const rolePath = userRole === 'admin' ? 'admin/' : 'user/';
        return `${basePath}${rolePath}${page}/${page}.php`;
    }
    
    updatePageTitle(page) {
        const pageTitles = {
            'dashboard': 'Dashboard',
            'data-umum': 'Data Umum Desa',
            'kependudukan': 'Data Kependudukan',
            'ekonomi': 'Data Ekonomi',
            'pendidikan': 'Data Pendidikan',
            'infrastruktur': 'Data Infrastruktur',
            'analisis': 'Analisis Data',
            'laporan': 'Laporan',
            'monitoring': 'Monitoring Desa',
            'peta': 'Peta & Geografis'
        };
        
        const title = pageTitles[page] || 'Dashboard';
        document.title = `${title} - Sistem Pendataan Desa`;
        
        // Update header title
        const headerTitle = document.querySelector('.header-page-title');
        if (headerTitle) {
            headerTitle.textContent = title;
        }
    }
    
    updateActiveNavigation(page) {
        // Remove active class from all nav links
        const navLinks = document.querySelectorAll('.sidebar-nav-link');
        navLinks.forEach(link => link.classList.remove('active'));
        
        // Add active class to current page link
        const activeLink = document.querySelector(`[onclick="loadPage('${page}')"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
    
    executePageScript(page) {
        // Load page-specific JavaScript dynamically
        const existingScript = document.getElementById(`page-script-${page}`);
        if (existingScript) {
            existingScript.remove();
        }
        
        const script = document.createElement('script');
        script.id = `page-script-${page}`;
        script.src = `${pendataanDesa.baseURL}assets/js/${page}.js`;
        script.onerror = () => {
            // Script doesn't exist, that's okay
            console.log(`No specific script found for page: ${page}`);
        };
        
        document.head.appendChild(script);
    }
    
    showContentLoader() {
        if (this.contentLoader) {
            this.contentLoader.classList.remove('hidden');
        }
    }
    
    hideContentLoader() {
        if (this.contentLoader) {
            setTimeout(() => {
                this.contentLoader.classList.add('hidden');
            }, 300);
        }
    }
    
    showError(message) {
        this.contentArea.innerHTML = `
            <div class="error-container" style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--danger); margin-bottom: 20px;"></i>
                <h3 style="color: var(--text-dark); margin-bottom: 16px;">Terjadi Kesalahan</h3>
                <p style="color: var(--text-light); margin-bottom: 24px;">${message}</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-redo"></i>
                    Muat Ulang Halaman
                </button>
            </div>
        `;
    }
}

// Initialize main layout component
document.addEventListener('DOMContentLoaded', () => {
    window.mainLayoutComponent = new MainLayoutComponent();
});