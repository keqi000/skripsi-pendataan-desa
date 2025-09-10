// Analisis User JavaScript
class AnalisisUser {
    constructor() {
        this.currentTab = 'tingkat1';
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
    }

    bindEvents() {
        // Tab switching
        document.addEventListener('click', (e) => {
            if (e.target.matches('.analisis-user-tab-btn')) {
                const tabId = e.target.getAttribute('onclick').match(/'([^']+)'/)[1];
                this.switchTab(tabId);
            }
        });

        // Responsive handling
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }

    switchTab(tabId) {
        // Remove active class from all tabs and contents
        document.querySelectorAll('.analisis-user-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('.analisis-user-tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Add active class to selected tab and content
        document.querySelector(`[onclick="switchTab('${tabId}')"]`).classList.add('active');
        document.getElementById(tabId).classList.add('active');

        this.currentTab = tabId;
        this.loadTabData(tabId);
    }

    loadInitialData() {
        // Load data for the initial tab
        this.loadTabData(this.currentTab);
    }

    loadTabData(tabId) {
        switch(tabId) {
            case 'tingkat1':
                this.loadTingkat1Data();
                break;
            case 'tingkat2':
                this.loadTingkat2Data();
                break;
        }
    }

    loadTingkat1Data() {
        // Data is already loaded from PHP, just add any dynamic interactions
        this.addStatItemAnimations();
        this.addBreakdownItemHovers();
    }

    loadTingkat2Data() {
        // For future implementation of advanced analytics
        console.log('Loading Tingkat 2 data...');
    }

    addStatItemAnimations() {
        const statItems = document.querySelectorAll('.stat-item');
        statItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.classList.add('fade-in-up');
        });
    }

    addBreakdownItemHovers() {
        const breakdownItems = document.querySelectorAll('.breakdown-item');
        breakdownItems.forEach(item => {
            item.addEventListener('mouseenter', () => {
                item.style.transform = 'translateX(5px)';
            });
            item.addEventListener('mouseleave', () => {
                item.style.transform = 'translateX(0)';
            });
        });
    }

    handleResize() {
        // Handle responsive layout changes
        const container = document.querySelector('.analisis-user-container');
        if (window.innerWidth <= 768) {
            container.classList.add('mobile-view');
        } else {
            container.classList.remove('mobile-view');
        }
    }

    // Utility methods
    formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    formatPercentage(num) {
        return `${num.toFixed(1)}%`;
    }

    showLoading(element) {
        element.innerHTML = `
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Memuat data...</span>
            </div>
        `;
    }

    hideLoading() {
        document.querySelectorAll('.loading-spinner').forEach(spinner => {
            spinner.remove();
        });
    }
}

// Global function for tab switching (called from HTML)
function switchTab(tabId) {
    if (window.analisisUser) {
        window.analisisUser.switchTab(tabId);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.analisisUser = new AnalisisUser();
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    .fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
        transform: translateY(20px);
    }

    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
        color: var(--text-muted);
    }

    .loading-spinner i {
        font-size: 2rem;
        margin-bottom: 10px;
        color: var(--secondary-color);
    }

    .mobile-view .analisis-stats-grid {
        grid-template-columns: 1fr;
    }

    .mobile-view .stat-item {
        margin-bottom: 10px;
    }
`;
document.head.appendChild(style);