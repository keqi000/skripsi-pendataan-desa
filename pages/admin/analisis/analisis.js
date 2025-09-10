// Analisis Admin JavaScript
(function() {
    'use strict';
    
    class AnalisisAdmin {
    constructor() {
        if (document.querySelector('.analisis-admin-container')) {
            this.charts = {};
            this.currentTab = 'tingkat1';
            this.init();
        }
    }
    
    init() {
        this.setupTabs();
        this.loadChartLibrary().then(() => {
            this.initCharts();
        });
    }
    
    async loadChartLibrary() {
        if (typeof Chart === 'undefined') {
            return new Promise((resolve) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.onload = resolve;
                document.head.appendChild(script);
            });
        }
    }
    
    setupTabs() {
        const tabBtns = document.querySelectorAll('.analisis-admin-tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const text = e.target.textContent.toLowerCase();
                let tabName;
                if (text.includes('tingkat 1')) {
                    tabName = 'tingkat1';
                } else if (text.includes('tingkat 2')) {
                    tabName = 'tingkat2';
                } else if (text.includes('prediksi')) {
                    tabName = 'prediksi';
                } else {
                    tabName = 'perbandingan';
                }
                this.switchTab(tabName);
            });
        });
    }
    
    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.analisis-admin-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Find button by text content
        document.querySelectorAll('.analisis-admin-tab-btn').forEach(btn => {
            const text = btn.textContent.toLowerCase();
            if ((tabName === 'tingkat1' && text.includes('tingkat 1')) ||
                (tabName === 'tingkat2' && text.includes('tingkat 2')) ||
                (tabName === 'prediksi' && text.includes('prediksi'))) {
                btn.classList.add('active');
            }
        });
        
        // Update tab content
        document.querySelectorAll('.analisis-admin-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        const activeContent = document.getElementById(tabName);
        if (activeContent) {
            activeContent.classList.add('active');
        }
        
        this.currentTab = tabName;
        
        // Load tab-specific content
        if (tabName === 'prediksi') {
            setTimeout(() => {
                this.initPrediksiCharts();
                if (typeof initPredictionTable === 'function') {
                    initPredictionTable();
                }
            }, 500);
        }
    }
    
    initCharts() {
        this.initKependudukanChart();
        this.initEkonomiChart();
        this.initPendidikanChart();
        this.initInfrastrukturChart();
    }
    
    initKependudukanChart() {
        const ctx = document.getElementById('chartKependudukan');
        if (!ctx) return;
        
        // Get demographic data from tingkat1 breakdown
        let data = [0, 0, 0, 0, 0];
        const breakdownItems = document.querySelectorAll('#tingkat1 .analisis-breakdown .breakdown-item');
        
        breakdownItems.forEach(item => {
            const spans = item.querySelectorAll('span');
            if (spans.length >= 2) {
                const label = spans[0].textContent.trim();
                const valueText = spans[1].textContent.replace(/[^0-9]/g, '');
                const value = parseInt(valueText) || 0;
                
                if (label.includes('Laki-laki')) {
                    data[0] = value;
                } else if (label.includes('Perempuan')) {
                    data[1] = value;
                } else if (label.includes('Anak')) {
                    data[2] = value;
                } else if (label.includes('Dewasa')) {
                    data[3] = value;
                } else if (label.includes('Lansia')) {
                    data[4] = value;
                }
            }
        });
        
        this.charts.kependudukan = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Laki-laki', 'Perempuan', 'Anak', 'Dewasa', 'Lansia'],
                datasets: [{
                    label: 'Jumlah',
                    data: data,
                    backgroundColor: [
                        '#112D4E',
                        '#3F72AF',
                        '#27AE60',
                        '#F39C12',
                        '#E74C3C'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    initEkonomiChart() {
        const ctx = document.getElementById('chartEkonomi');
        if (!ctx) return;
        
        // Get age group data for demographic chart
        let labels = [];
        let data = [];
        const breakdownItems = document.querySelectorAll('#tingkat1 .analisis-breakdown h5');
        
        breakdownItems.forEach(heading => {
            if (heading.textContent.includes('Kelompok Usia')) {
                const items = heading.parentElement.querySelectorAll('.breakdown-item');
                items.forEach(item => {
                    const spans = item.querySelectorAll('span');
                    if (spans.length >= 2) {
                        const label = spans[0].textContent.trim();
                        const valueText = spans[1].textContent.replace(/[^0-9]/g, '');
                        const value = parseInt(valueText) || 0;
                        
                        if (label.includes('Balita') || label.includes('Anak') || label.includes('Remaja') || label.includes('Dewasa') || label.includes('Lansia')) {
                            labels.push(label.split('(')[0].trim());
                            data.push(value);
                        }
                    }
                });
            }
        });
        
        this.charts.ekonomi = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#112D4E',
                        '#3F72AF',
                        '#DBE2EF',
                        '#27AE60',
                        '#E74C3C'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribusi Kelompok Usia'
                    }
                }
            }
        });
    }
    
    initPendidikanChart() {
        const ctx = document.getElementById('chartPendidikan');
        if (!ctx) return;
        
        // Get gender distribution data
        let lakiLaki = 0;
        let perempuan = 0;
        
        const statsItems = document.querySelectorAll('#tingkat1 .analisis-stats-grid .stat-item');
        statsItems.forEach(item => {
            const title = item.querySelector('h4');
            const value = item.querySelector('.stat-value');
            
            if (title && value) {
                const titleText = title.textContent.trim();
                const valueText = value.textContent.replace(/[^0-9]/g, '');
                const numValue = parseInt(valueText) || 0;
                
                if (titleText.includes('Laki-laki')) {
                    lakiLaki = numValue;
                } else if (titleText.includes('Perempuan')) {
                    perempuan = numValue;
                }
            }
        });
        
        this.charts.pendidikan = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: [lakiLaki, perempuan],
                    borderColor: '#112D4E',
                    backgroundColor: 'rgba(17, 45, 78, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribusi Jenis Kelamin'
                    }
                }
            }
        });
    }
    
    initInfrastrukturChart() {
        const ctx = document.getElementById('chartInfrastruktur');
        if (!ctx) return;
        
        // Get total KK data for demographic visualization
        let totalKK = 0;
        let totalPenduduk = 0;
        
        const statsItems = document.querySelectorAll('#tingkat1 .analisis-stats-grid .stat-item');
        statsItems.forEach(item => {
            const title = item.querySelector('h4');
            const value = item.querySelector('.stat-value');
            
            if (title && value) {
                const titleText = title.textContent.trim();
                const valueText = value.textContent.replace(/[^0-9]/g, '');
                const numValue = parseInt(valueText) || 0;
                
                if (titleText.includes('Total KK')) {
                    totalKK = numValue;
                } else if (titleText.includes('Total Penduduk')) {
                    totalPenduduk = numValue;
                }
            }
        });
        
        const rataAnggotaKK = totalKK > 0 ? Math.round(totalPenduduk / totalKK) : 0;
        
        this.charts.infrastruktur = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Total KK', 'Rata-rata Anggota/KK'],
                datasets: [{
                    label: 'Jumlah',
                    data: [totalKK, rataAnggotaKK],
                    backgroundColor: [
                        '#112D4E',
                        '#3F72AF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Statistik Keluarga'
                    }
                }
            }
        });
    }
    
    initPrediksiCharts() {
        // Destroy existing charts
        if (this.charts.prediksiLine) {
            this.charts.prediksiLine.destroy();
        }
        if (this.charts.prediksiBar) {
            this.charts.prediksiBar.destroy();
        }
        
        setTimeout(() => {
            this.initPrediksiLineChart();
            this.initPrediksiBarChart();
        }, 100);
    }
    
    initPrediksiLineChart() {
        const ctx = document.getElementById('chartPrediksiLine');
        if (!ctx) {
            console.log('Chart canvas not found: chartPrediksiLine');
            return;
        }
        
        // Sample data if no table data
        let years = ['2025', '2026', '2027', '2028', '2029'];
        let totalPenduduk = [5500, 5610, 5722, 5836, 5952];
        let totalLaki = [2750, 2805, 2861, 2918, 2976];
        let totalPerempuan = [2750, 2805, 2861, 2918, 2976];
        
        // Get data from table if exists
        const tableRows = document.querySelectorAll('#prediksiTable tbody tr');
        if (tableRows.length > 0) {
            years = [];
            totalPenduduk = [];
            totalLaki = [];
            totalPerempuan = [];
            
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 4) {
                    const year = cells[1].textContent.trim();
                    const total = parseInt(cells[2].textContent.replace(/,/g, '')) || 0;
                    const laki = parseInt(cells[3].textContent.replace(/,/g, '')) || 0;
                    const perempuan = parseInt(cells[4].textContent.replace(/,/g, '')) || 0;
                    
                    if (!years.includes(year)) {
                        years.push(year);
                        totalPenduduk.push(total);
                        totalLaki.push(laki);
                        totalPerempuan.push(perempuan);
                    }
                }
            });
        }
        
        this.charts.prediksiLine = new Chart(ctx, {
            type: 'line',
            data: {
                labels: years,
                datasets: [{
                    label: 'Total Penduduk',
                    data: totalPenduduk,
                    borderColor: '#112D4E',
                    backgroundColor: 'rgba(17, 45, 78, 0.1)',
                    fill: true
                }, {
                    label: 'Laki-laki',
                    data: totalLaki,
                    borderColor: '#3F72AF',
                    backgroundColor: 'rgba(63, 114, 175, 0.1)',
                    fill: false
                }, {
                    label: 'Perempuan',
                    data: totalPerempuan,
                    borderColor: '#DBE2EF',
                    backgroundColor: 'rgba(219, 226, 239, 0.1)',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    initPrediksiBarChart() {
        const ctx = document.getElementById('chartPrediksiBar');
        if (!ctx) {
            console.log('Chart canvas not found: chartPrediksiBar');
            return;
        }
        
        // Get ONLY age structure data from prediction table
        let labels = ['Balita (0-5)', 'Anak (6-12)', 'Remaja (13-17)', 'Dewasa (18-64)', 'Lansia (65+)'];
        let data = [0, 0, 0, 0, 0];
        
        // Get data from prediction table columns 5-9 (Balita, Anak, Remaja, Dewasa, Lansia)
        const tableRows = document.querySelectorAll('#prediksiTable tbody tr');
        if (tableRows.length > 0) {
            // Sum data for final year (2029)
            const targetYear = new Date().getFullYear() + 5;
            
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 10) {
                    const year = parseInt(cells[1].textContent.trim());
                    if (year === targetYear) {
                        data[0] += parseInt(cells[5].textContent.replace(/,/g, '')) || 0; // Balita
                        data[1] += parseInt(cells[6].textContent.replace(/,/g, '')) || 0; // Anak
                        data[2] += parseInt(cells[7].textContent.replace(/,/g, '')) || 0; // Remaja
                        data[3] += parseInt(cells[8].textContent.replace(/,/g, '')) || 0; // Dewasa
                        data[4] += parseInt(cells[9].textContent.replace(/,/g, '')) || 0; // Lansia
                    }
                }
            });
        }
        
        this.charts.prediksiBar = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: `Prediksi Struktur Usia ${new Date().getFullYear() + 5}`,
                    data: data,
                    backgroundColor: [
                        '#112D4E',
                        '#3F72AF',
                        '#27AE60',
                        '#F39C12',
                        '#E74C3C'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: `Prediksi Struktur Usia ${new Date().getFullYear() + 5}`
                    }
                }
            }
        });
    }
}



    // Utility functions
    function showLoader(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.innerHTML = `
                <div class="loader-container">
                    <div class="loader"></div>
                    <p>Memuat data analisis...</p>
                </div>
            `;
        }
    }
    
    function hideLoader() {
        document.querySelectorAll('.loader-container').forEach(loader => {
            loader.remove();
        });
    }
    
    function showNotification(message, type = 'info') {
        console.log('Showing notification:', message, 'Type:', type);
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        console.log('Notification added to DOM');
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
                console.log('Notification removed');
            }
        }, 5000);
    }
    
    // Global functions for analisis
    window.switchTab = function(tabName) {
        if (window.analisisAdminInstance) {
            window.analisisAdminInstance.switchTab(tabName);
        }
    };
    
    // Desa data will be loaded from HTML
    
    window.showAllDesa = function() {
        const dropdown = document.getElementById('autocompleteDropdown');
        if (!dropdown) {
            return;
        }
        
        // Load desa data if not available
        loadDesaData();
        
        if (!window.desaData || window.desaData.length === 0) {
            return;
        }
        
        dropdown.innerHTML = window.desaData.slice(0, 6).map(desa => 
            `<div class="autocomplete-item" onclick="selectDesa('${desa.id}', '${desa.name}')">${desa.name}</div>`
        ).join('');
        dropdown.style.display = 'block';
    };
    
    window.searchDesa = function() {
        const input = document.getElementById('desaSearch');
        const dropdown = document.getElementById('autocompleteDropdown');
        
        if (!input || !dropdown) {
            return;
        }
        
        // Load desa data if not available
        loadDesaData();
        
        if (!window.desaData || window.desaData.length === 0) {
            return;
        }
        
        const query = input.value.toLowerCase();
        
        if (query.length === 0) {
            showAllDesa();
            return;
        }
        
        const filtered = window.desaData.filter(desa => 
            desa.name.toLowerCase().includes(query)
        );
        
        if (filtered.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        dropdown.innerHTML = filtered.slice(0, 6).map(desa => 
            `<div class="autocomplete-item" onclick="selectDesa('${desa.id}', '${desa.name}')">${desa.name}</div>`
        ).join('');
        
        dropdown.style.display = 'block';
    };
    
    window.selectDesa = function(id, name) {
        document.getElementById('desaSearch').value = name;
        document.getElementById('selectedDesaId').value = id;
        document.getElementById('autocompleteDropdown').style.display = 'none';
        showNotification(`${name} dipilih. Klik Generate untuk memuat data.`, 'info');
    };
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('autocompleteDropdown');
        if (dropdown && !e.target.closest('.desa-search-container')) {
            dropdown.style.display = 'none';
        }
    });
    
    // Initialize desaData if not available
    if (typeof window.desaData === 'undefined') {
        window.desaData = [];
    }
    
    // Function to load desa data from server if not available
    function loadDesaData() {
        if (!window.desaData || window.desaData.length === 0) {
            // Try to get data from script tag first
            const scriptTags = document.querySelectorAll('script');
            for (let script of scriptTags) {
                if (script.textContent && script.textContent.includes('window.desaData')) {
                    try {
                        eval(script.textContent);
                        break;
                    } catch (e) {
                        console.log('Could not load desa data from script');
                    }
                }
            }
        }
    }
    
    // Load desa data when page loads
    document.addEventListener('DOMContentLoaded', loadDesaData);
    if (document.readyState !== 'loading') {
        loadDesaData();
    }
    
    window.generateAnalysis = function() {
        const selectedDesa = document.getElementById('selectedDesaId').value;
        const desaName = document.getElementById('desaSearch').value;
        
        console.log('=== GENERATE ANALYSIS DEBUG ===');
        console.log('Selected Desa:', selectedDesa);
        console.log('Desa Name:', desaName);
        
        if (!selectedDesa) {
            console.log('ERROR: No desa selected');
            showNotification('Pilih desa terlebih dahulu', 'warning');
            return;
        }
        
        console.log('Starting AJAX request...');
        showLoader('.analisis-admin-tab-content.active');
        
        const requestBody = `selected_desa=${selectedDesa}&action=generate_analysis`;
        console.log('Request body:', requestBody);
        console.log('Request URL:', window.location.href);
        
        // AJAX request to load filtered data
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: requestBody
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text();
        })
        .then(data => {
            console.log('Response data:', data);
            console.log('Data length:', data.length);
            console.log('Data trimmed:', data.trim());
            
            hideLoader();
            if (data.trim() === 'success') {
                console.log('SUCCESS: Loading filtered content...');
                // Wait for data to be processed then load filtered content
                setTimeout(() => {
                    fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        console.log('Fetched new content, parsing...');
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const activeTabId = document.querySelector('.analisis-admin-tab-content.active').id;
                        const newContent = doc.querySelector(`#${activeTabId} .analisis-admin-grid`);
                        const currentTab = document.querySelector('.analisis-admin-tab-content.active');
                        
                        console.log('Active tab ID:', activeTabId);
                        console.log('New content found:', !!newContent);
                        console.log('Current tab found:', !!currentTab);
                        
                        // Simple page reload instead of complex DOM manipulation
                        showNotification('Analisis berhasil di-generate', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    })
                    .catch(error => {
                        console.log('Error loading new content:', error);
                        hideLoader();
                        showNotification('Terjadi kesalahan saat memuat konten', 'error');
                    });
                }, 100);
            } else {
                console.log('ERROR: Unexpected response:', data);
                showNotification('Terjadi kesalahan saat memuat data', 'error');
            }
        })
        .catch(error => {
            console.log('FETCH ERROR:', error);
            console.log('Error message:', error.message);
            console.log('Error stack:', error.stack);
            hideLoader();
            showNotification('Terjadi kesalahan saat memuat data', 'error');
        });
    };
    
    // Generate predictions function
    window.generatePredictions = function() {
        const baseUrl = window.location.origin + '/Pendataan-desa/';
        
        showNotification('Generating predictions...', 'info');
        
        fetch(`${baseUrl}api/prediction.php?action=generate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Prediksi berhasil dibuat! Refresh halaman untuk melihat hasil.', 'success');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showNotification('Gagal membuat prediksi: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error generating predictions:', error);
            showNotification('Terjadi kesalahan saat membuat prediksi', 'error');
        });
    };
    
    // Prediction table pagination and search
    class PredictionTable {
        constructor() {
            this.currentPage = 1;
            this.entriesPerPage = 10;
            this.filteredData = [];
            this.allData = [];
            this.init();
        }
        
        init() {
            this.loadTableData();
            this.setupEventListeners();
            this.renderTable();
        }
        
        loadTableData() {
            const rows = document.querySelectorAll('#prediksiTableBody tr');
            this.allData = Array.from(rows).map(row => {
                const cells = row.querySelectorAll('td');
                return {
                    element: row,
                    desa: cells[0]?.textContent || '',
                    tahun: cells[1]?.textContent || ''
                };
            });
            this.filteredData = [...this.allData];
        }
        
        setupEventListeners() {
            const searchInput = document.getElementById('searchPrediksi');
            const entriesSelect = document.getElementById('entriesPerPage');
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');
            
            if (searchInput) {
                searchInput.addEventListener('input', () => this.handleSearch());
            }
            
            if (entriesSelect) {
                entriesSelect.addEventListener('change', () => this.handleEntriesChange());
            }
            
            if (prevBtn) {
                prevBtn.addEventListener('click', () => this.goToPreviousPage());
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => this.goToNextPage());
            }
        }
        
        handleSearch() {
            const searchTerm = document.getElementById('searchPrediksi').value.toLowerCase();
            this.filteredData = this.allData.filter(item => 
                item.desa.toLowerCase().includes(searchTerm) ||
                item.tahun.toLowerCase().includes(searchTerm)
            );
            this.currentPage = 1;
            this.renderTable();
        }
        
        handleEntriesChange() {
            this.entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
            this.currentPage = 1;
            this.renderTable();
        }
        
        renderTable() {
            const startIndex = (this.currentPage - 1) * this.entriesPerPage;
            const endIndex = startIndex + this.entriesPerPage;
            const pageData = this.filteredData.slice(startIndex, endIndex);
            
            // Hide all rows first
            this.allData.forEach(item => {
                item.element.style.display = 'none';
            });
            
            // Show current page rows
            pageData.forEach(item => {
                item.element.style.display = '';
            });
            
            this.updatePaginationInfo();
            this.updatePaginationControls();
        }
        
        updatePaginationInfo() {
            const info = document.getElementById('paginationInfo');
            if (!info) return;
            
            const startIndex = (this.currentPage - 1) * this.entriesPerPage + 1;
            const endIndex = Math.min(this.currentPage * this.entriesPerPage, this.filteredData.length);
            const total = this.filteredData.length;
            
            info.textContent = `Showing ${startIndex} to ${endIndex} of ${total} entries`;
        }
        
        updatePaginationControls() {
            const totalPages = Math.ceil(this.filteredData.length / this.entriesPerPage);
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');
            const pageNumbers = document.getElementById('pageNumbers');
            
            if (prevBtn) {
                prevBtn.disabled = this.currentPage === 1;
            }
            
            if (nextBtn) {
                nextBtn.disabled = this.currentPage === totalPages || totalPages === 0;
            }
            
            if (pageNumbers) {
                pageNumbers.innerHTML = '';
                for (let i = 1; i <= totalPages; i++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.textContent = i;
                    pageBtn.className = `page-btn ${i === this.currentPage ? 'active' : ''}`;
                    pageBtn.addEventListener('click', () => this.goToPage(i));
                    pageNumbers.appendChild(pageBtn);
                }
            }
        }
        
        goToPage(page) {
            this.currentPage = page;
            this.renderTable();
        }
        
        goToPreviousPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.renderTable();
            }
        }
        
        goToNextPage() {
            const totalPages = Math.ceil(this.filteredData.length / this.entriesPerPage);
            if (this.currentPage < totalPages) {
                this.currentPage++;
                this.renderTable();
            }
        }
    }
    
    // Initialize prediction table when on prediksi tab
    window.initPredictionTable = function() {
        if (document.getElementById('prediksiTable')) {
            window.predictionTableInstance = new PredictionTable();
        }
    };
    
    // Initialize analisis admin
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.analisisAdminInstance) {
            window.analisisAdminInstance = new AnalisisAdmin();
        }
        setTimeout(() => {
            if (document.querySelector('.analisis-admin-tab-content.active')?.id === 'prediksi') {
                initPredictionTable();
            }
        }, 1000);
    });
    
    if (document.readyState !== 'loading') {
        if (!window.analisisAdminInstance) {
            window.analisisAdminInstance = new AnalisisAdmin();
        }
    }
})();
// Open detail analysis with AJAX like detail-desa.php
window.openDetailAnalisis = function(type) {
    const selectedDesa = document.getElementById('selectedDesaId').value || 'all';
    const baseUrl = window.location.origin + '/Pendataan-desa/';
    const url = `${baseUrl}pages/admin/detail-analisis-2/detail-analisis.php?desa=${selectedDesa}&type=${type}`;
    
    console.log('🔍 DETAIL ANALISIS NAVIGATION START');
    console.log('- Selected Desa:', selectedDesa);
    console.log('- Analysis Type:', type);
    console.log('- Target URL:', url);
    
    if (window.mainLayoutComponent) {
        console.log('✅ Using mainLayoutComponent for navigation');
        
        // Show content loader
        window.mainLayoutComponent.showContentLoader();
        
        // Load detail-analisis content via AJAX
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('📡 Response received:', response.status, response.statusText);
            return response.text();
        })
        .then(html => {
            console.log('📄 HTML content received, length:', html.length);
            
            // Update content using mainLayoutComponent
            window.mainLayoutComponent.contentArea.innerHTML = html;
            
            // Update URL
            history.pushState({ page: 'detail-analisis', type: type, desa: selectedDesa }, '', url);
            
            // Update navigation
            window.mainLayoutComponent.updateActiveNavigation('analisis');
            window.mainLayoutComponent.updatePageTitle(`Detail Analisis Tingkat 2`);
            
            // Load detail-analisis scripts manually and wait for completion
            loadDetailAnalisisAssets().then(() => {
                console.log('🎨 Assets loaded, applying styles...');
                // Force style recalculation
                const container = document.querySelector('.detail-analisis-container');
                if (container) {
                    container.style.display = 'none';
                    container.offsetHeight; // Force reflow
                    container.style.display = '';
                }
            });
            
            console.log('🎉 Detail analisis navigation completed');
        })
        .catch(error => {
            console.error('❌ Navigation error:', error);
            window.mainLayoutComponent.showError('Gagal memuat detail analisis');
        })
        .finally(() => {
            window.mainLayoutComponent.hideContentLoader();
        });
    } else {
        console.log('❌ mainLayoutComponent not found, using fallback');
        // Fallback to direct navigation
        window.location.href = url;
    }
};

// Function to load detail-analisis CSS and JS
function loadDetailAnalisisAssets() {
    const baseUrl = window.location.origin + '/Pendataan-desa/';
    
    return new Promise((resolve) => {
        let assetsLoaded = 0;
        const totalAssets = 2;
        
        function checkComplete() {
            assetsLoaded++;
            if (assetsLoaded === totalAssets) {
                console.log('✅ All detail-analisis assets loaded');
                resolve();
            }
        }
        
        // Load CSS
        const cssId = 'detail-analisis-css';
        if (!document.getElementById(cssId)) {
            console.log('📄 Loading detail-analisis CSS...');
            const link = document.createElement('link');
            link.id = cssId;
            link.rel = 'stylesheet';
            link.href = `${baseUrl}pages/admin/detail-analisis-2/detail-analisis.css`;
            link.onload = checkComplete;
            link.onerror = checkComplete;
            document.head.appendChild(link);
        } else {
            checkComplete();
        }
        
        // Load JS
        const jsId = 'detail-analisis-js';
        if (!document.getElementById(jsId)) {
            console.log('📄 Loading detail-analisis JS...');
            const script = document.createElement('script');
            script.id = jsId;
            script.src = `${baseUrl}pages/admin/detail-analisis-2/detail-analisis.js`;
            script.onload = () => {
                console.log('✅ Detail-analisis JS loaded and ready');
                // Initialize DetailAnalisis after script loads
                if (typeof DetailAnalisis !== 'undefined' && DetailAnalisis.init) {
                    DetailAnalisis.init();
                    window.detailAnalisisInstance = DetailAnalisis;
                }
                checkComplete();
            };
            script.onerror = checkComplete;
            document.head.appendChild(script);
        } else {
            checkComplete();
        }
    });
}



// Handle browser back/forward buttons
window.addEventListener('popstate', function(event) {
    console.log('🔙 Browser navigation detected:', event.state);
    
    if (event.state && event.state.page === 'detail-analisis') {
        console.log('📍 Navigating to detail-analisis:', event.state.type);
        if (typeof openDetailAnalisis === 'function') {
            openDetailAnalisis(event.state.type);
        }
    } else if (event.state && event.state.page === 'analisis') {
        console.log('📍 Navigating back to analisis');
        location.reload();
    } else {
        console.log('📍 Default navigation - reloading page');
        location.reload();
    }
});