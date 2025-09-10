// Footer Component JavaScript
class FooterComponent {
    constructor() {
        if (document.querySelector('.footer-main-footer')) {
            this.init();
        }
    }
    
    init() {
        this.updateTime();
        this.startTimeUpdate();
    }
    
    updateTime() {
        const timeElements = document.querySelectorAll('.footer-info-item span');
        timeElements.forEach(element => {
            if (element.textContent.includes('Last Update:')) {
                const now = new Date();
                const options = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                };
                element.textContent = 'Last Update: ' + now.toLocaleDateString('id-ID', options);
            }
        });
    }
    
    startTimeUpdate() {
        // Update time every minute
        setInterval(() => {
            this.updateTime();
        }, 60000);
    }
}

// Initialize footer component
document.addEventListener('DOMContentLoaded', () => {
    window.footerComponent = new FooterComponent();
});