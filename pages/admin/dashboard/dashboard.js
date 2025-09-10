// Dashboard Admin JavaScript
(function() {
    'use strict';
    
    class DashboardAdmin {
        constructor() {
            if (document.querySelector('.dashboard-admin-container')) {
                this.init();
            }
        }
        
        init() {
            this.setupQuickActions();
            this.loadDashboardData();
            
            // Delay chart initialization to ensure DOM is ready
            setTimeout(() => {
                this.initCharts();
            }, 300);
        }
        
        setupQuickActions() {
            const actionBtns = document.querySelectorAll('.dashboard-admin-action-btn');
            actionBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = btn.getAttribute('onclick').match(/'([^']+)'/)[1];
                    if (window.mainLayoutComponent) {
                        window.mainLayoutComponent.loadPage(page);
                    }
                });
            });
        }
        
        loadDashboardData() {
            // Load dashboard statistics
            this.updateStats();
        }
        
        updateStats() {
            // Update dashboard statistics
        }
        
        initCharts() {
            // Check if data is available
            if (!window.trendData || !window.monthlyData) {
                this.fetchChartData().then(() => {
                    this.proceedWithCharts();
                });
            } else {
                this.proceedWithCharts();
            }
        }
        
        async fetchChartData() {
            try {
                const response = await fetch(`${pendataanDesa.baseURL}pages/admin/dashboard/dashboard.php`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const html = await response.text();
                
                // Extract script content with data
                const scriptMatch = html.match(/<script>[\s\S]*?window\.trendData[\s\S]*?<\/script>/);
                if (scriptMatch) {
                    // Execute the script to set global variables
                    const scriptContent = scriptMatch[0].replace(/<\/?script>/g, '');
                    eval(scriptContent);
                }
            } catch (error) {
                // Set default empty data
                window.trendData = { desa: 0, penduduk: 0, pendidikan: 0, umkm: 0, jalan: 0 };
                window.monthlyData = {};
            }
        }
        
        proceedWithCharts() {
            // Load Chart.js if not available
            if (typeof Chart === 'undefined') {
                this.loadChartJS().then(() => {
                    this.createTrendCharts();
                });
            } else {
                this.createTrendCharts();
            }
        }
        
        async loadChartJS() {
            return new Promise((resolve) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.onload = resolve;
                document.head.appendChild(script);
            });
        }
        
        createTrendCharts() {
            // Destroy existing charts first
            if (this.charts) {
                if (this.charts.barChart) {
                    this.charts.barChart.destroy();
                    this.charts.barChart = null;
                }
                if (this.charts.lineChart) {
                    this.charts.lineChart.destroy();
                    this.charts.lineChart = null;
                }
            }
            
            // Wait for container to be visible and have dimensions
            const waitForContainer = () => {
                const barCanvas = document.getElementById('trendBarChart');
                const lineCanvas = document.getElementById('trendLineChart');
                
                if (barCanvas && lineCanvas && 
                    barCanvas.offsetParent !== null && 
                    lineCanvas.offsetParent !== null &&
                    barCanvas.clientWidth > 0 && 
                    lineCanvas.clientWidth > 0) {
                    
                    this.createBarChart();
                    this.createLineChart();
                } else {
                    setTimeout(waitForContainer, 100);
                }
            };
            
            waitForContainer();
        }
        
        createBarChart() {
            const ctx = document.getElementById('trendBarChart');
            if (!ctx) {
                return;
            }
            
            // Initialize charts object
            this.charts = this.charts || {};
            
            // Get trend data from PHP variables (passed to JS)
            const trendData = window.trendData ? [
                window.trendData.desa || 0,
                window.trendData.penduduk || 0, 
                window.trendData.pendidikan || 0,
                window.trendData.umkm || 0,
                window.trendData.jalan || 0
            ] : [0, 0, 0, 0, 0];
            
            this.charts = this.charts || {};
            this.charts.barChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Desa', 'Penduduk', 'Pendidikan', 'UMKM', 'Jalan'],
                    datasets: [{
                        label: 'Tren 6 Bulan (%)',
                        data: trendData,
                        backgroundColor: [
                            '#27AE60',
                            '#27AE60', 
                            '#27AE60',
                            '#27AE60',
                            '#27AE60'
                        ],
                        borderColor: [
                            '#219A52',
                            '#219A52',
                            '#219A52', 
                            '#219A52',
                            '#219A52'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 2,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Perubahan Data (%)',
                            font: { size: 14, weight: 'bold' }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#E5E7EB'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        
        createLineChart() {
            const ctx = document.getElementById('trendLineChart');
            if (!ctx) {
                return;
            }
            
            // Initialize charts object
            this.charts = this.charts || {};
            
            const selectedPeriod = window.selectedPeriod || 'jan-jun';
            const months = selectedPeriod === 'jan-jun' ? 
                ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'] :
                ['Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            // Get monthly data from PHP
            const monthlyData = window.monthlyData || {};
            const startMonth = selectedPeriod === 'jan-jun' ? 1 : 7;
            
            // Convert monthly data to chart format
            const getMonthlyChartData = (category) => {
                const data = [];
                for (let i = 0; i < 6; i++) {
                    const month = startMonth + i;
                    const value = monthlyData[month] ? monthlyData[month][category] || 0 : 0;
                    data.push(value);
                }
                return data;
            };
            
            this.charts = this.charts || {};
            this.charts.lineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Desa',
                            data: getMonthlyChartData('desa'),
                            borderColor: '#3F72AF',
                            backgroundColor: 'rgba(63, 114, 175, 0.1)',
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Penduduk',
                            data: getMonthlyChartData('penduduk'),
                            borderColor: '#112D4E',
                            backgroundColor: 'rgba(17, 45, 78, 0.1)',
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Pendidikan',
                            data: getMonthlyChartData('pendidikan'),
                            borderColor: '#F39C12',
                            backgroundColor: 'rgba(243, 156, 18, 0.1)',
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'UMKM',
                            data: getMonthlyChartData('umkm'),
                            borderColor: '#27AE60',
                            backgroundColor: 'rgba(39, 174, 96, 0.1)',
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Jalan',
                            data: getMonthlyChartData('jalan'),
                            borderColor: '#8E44AD',
                            backgroundColor: 'rgba(142, 68, 173, 0.1)',
                            tension: 0.4,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 2,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: { size: 11 }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Tren 6 Bulan Terakhir',
                            font: { size: 14, weight: 'bold' }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: '#E5E7EB'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(1);
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }
    }
    
    // Global function to navigate to detail desa via AJAX
    window.navigateToDetailDesa = function(desaId, desaName) {
        if (window.mainLayoutComponent) {
            // Use AJAX navigation with parameters
            const detailUrl = `${pendataanDesa.baseURL}pages/admin/detail-desa/detail-desa.php?desa=${desaId}`;
            
            // Show content loader
            window.mainLayoutComponent.showContentLoader();
            
            // Load detail-desa content via AJAX
            fetch(detailUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Update content
                window.mainLayoutComponent.contentArea.innerHTML = html;
                
                // Update URL
                history.pushState({ page: 'detail-desa', desa: desaId }, '', detailUrl);
                
                // Update navigation
                window.mainLayoutComponent.updateActiveNavigation('detail-desa');
                window.mainLayoutComponent.updatePageTitle(`Detail Desa ${desaName}`);
                
                // Load detail-desa scripts
                setTimeout(() => {
                    window.mainLayoutComponent.executePageScript('detail-desa');
                }, 200);
            })
            .catch(error => {
                console.error('Error loading detail desa:', error);
                window.mainLayoutComponent.showError('Gagal memuat detail desa');
            })
            .finally(() => {
                window.mainLayoutComponent.hideContentLoader();
            });
        }
    };
    
    // Global functions for dashboard
    window.showDesaOverview = async function(desaId, desaName) {
        const modal = document.getElementById('desaOverviewModal');
        const modalDialog = modal.querySelector('.modal-overview-dialog');
        const modalTitle = modal.querySelector('.modal-overview-title');
        const modalContent = document.getElementById('desaOverviewContent');
        const mainContent = document.querySelector('.main-layout-main');
        
        modalTitle.textContent = `Overview ${desaName}`;
        modal.style.display = 'block';
        
        // Add backdrop to main content
        if (mainContent) {
            mainContent.style.background = 'rgba(0, 0, 0, 0.6)';
        }
        
        try {
            // Fetch desa data
            const response = await fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/desa/${desaId}`);
            const desaData = await response.json();
            
            // Fetch related data
            const [pendudukRes, ekonomiRes, pendidikanRes, infraRes] = await Promise.all([
                fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/penduduk/${desaId}`),
                fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/ekonomi/${desaId}`),
                fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/pendidikan/${desaId}`),
                fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/infrastruktur/${desaId}`)
            ]);
            
            const pendudukData = await pendudukRes.json();
            const ekonomiData = await ekonomiRes.json();
            const pendidikanData = await pendidikanRes.json();
            const infraData = await infraRes.json();
            
            // Calculate stats
            const totalPenduduk = pendudukData.length || 0;
            const luasWilayah = desaData.luas_wilayah || 0;
            const totalUMKM = ekonomiData.filter(e => e.jenis_data === 'umkm').length || 0;
            const totalSekolah = pendidikanData.length || 0;
            const jalanBaik = infraData.jalan ? infraData.jalan.filter(j => j.kondisi_jalan === 'baik').length : 0;
            const totalJalan = infraData.jalan ? infraData.jalan.length : 1;
            const persentaseJalanBaik = Math.round((jalanBaik / totalJalan) * 100);
            
            // Render overview content
            modalContent.innerHTML = `
                <div class="overview-stats">
                    <div class="overview-stat-item">
                        <div class="overview-stat-number">${totalPenduduk.toLocaleString()}</div>
                        <div class="overview-stat-label">Penduduk</div>
                    </div>
                    <div class="overview-stat-item">
                        <div class="overview-stat-number">${luasWilayah}</div>
                        <div class="overview-stat-label">Luas (Ha)</div>
                    </div>
                    <div class="overview-stat-item">
                        <div class="overview-stat-number">${totalUMKM}</div>
                        <div class="overview-stat-label">UMKM</div>
                    </div>
                    <div class="overview-stat-item">
                        <div class="overview-stat-number">${totalSekolah}</div>
                        <div class="overview-stat-label">Sekolah</div>
                    </div>
                    <div class="overview-stat-item">
                        <div class="overview-stat-number">${persentaseJalanBaik}%</div>
                        <div class="overview-stat-label">Jalan Baik</div>
                    </div>
                </div>
            `;
            
        } catch (error) {
            console.error('Error loading desa overview:', error);
            modalContent.innerHTML = `
                <div class="loading-text" style="color: #e74c3c;">
                    <i class="fas fa-exclamation-triangle"></i><br>
                    Gagal memuat data overview
                </div>
            `;
        }
    };
    
    window.closeDesaOverview = function() {
        const modal = document.getElementById('desaOverviewModal');
        const mainContent = document.querySelector('.main-layout-main');
        
        // Remove backdrop from main content
        if (mainContent) {
            mainContent.style.background = '';
        }
        
        modal.style.display = 'none';
    };
    
    window.updateTrendData = function() {
        const year = document.getElementById('yearFilter').value;
        const period = document.getElementById('periodFilter').value;
        
        // Show loader
        if (typeof showLoader === 'function') showLoader('.trend-charts');
        
        // Fetch new data from API
        const baseUrl = window.BASE_URL || '/Pendataan-desa/';
        fetch(`${baseUrl}api/dashboard_trend.php?year=${year}&period=${period}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update global variables
                    window.trendData = data.trendData;
                    window.monthlyData = data.monthlyData;
                    window.selectedYear = data.year;
                    window.selectedPeriod = data.period;
                    
                    // Update trend summary display
                    document.querySelectorAll('.trend-item').forEach((item, index) => {
                        const categories = ['desa', 'penduduk', 'pendidikan', 'umkm', 'jalan'];
                        const category = categories[index];
                        const value = data.trendData[category] || 0;
                        const valueEl = item.querySelector('.trend-value');
                        
                        if (valueEl) {
                            const displayText = (value >= 0 ? '+' : '') + value + '%';
                            valueEl.textContent = displayText;
                            valueEl.className = 'trend-value ' + (value >= 0 ? 'positive' : 'negative');
                        }
                    });
                    
                    // Recreate charts with new data
                    if (window.dashboardAdminInstance) {
                        window.dashboardAdminInstance.createTrendCharts();
                    }
                    
                    if (typeof showNotification === 'function') {
                        showNotification(`Data diperbarui untuk ${year} periode ${period}`, 'success');
                    }
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification('Gagal memperbarui data', 'error');
                    }
                }
            })
            .catch(error => {
                if (typeof showNotification === 'function') {
                    showNotification('Gagal memperbarui data', 'error');
                }
            })
            .finally(() => {
                if (typeof hideLoader === 'function') hideLoader();
            });
    };
    
    // Global function to reinitialize dashboard
    window.reinitializeDashboard = function() {
        if (window.dashboardAdminInstance) {
            window.dashboardAdminInstance.init();
        } else {
            window.dashboardAdminInstance = new DashboardAdmin();
        }
    };
    
    // Initialize dashboard admin
    const initDashboard = () => {
        if (document.querySelector('.dashboard-admin-container')) {
            if (!window.dashboardAdminInstance) {
                window.dashboardAdminInstance = new DashboardAdmin();
            } else {
                window.dashboardAdminInstance.init();
            }
        }
    };
    
    // Multiple initialization methods
    document.addEventListener('DOMContentLoaded', initDashboard);
    
    if (document.readyState !== 'loading') {
        initDashboard();
    }
    
    // Also initialize when page becomes visible (for SPA navigation)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && document.querySelector('.dashboard-admin-container')) {
            setTimeout(initDashboard, 100);
        }
    });
})();