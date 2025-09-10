// Detail Analisis Namespace
const DetailAnalisis = {
    init: function() {
        if (!document.querySelector('.detail-analisis-container')) return;
        
        console.log('Detail Analisis initialized');
        this.initializeTableFeatures();
        this.initializeTabSwitching();
        this.setupNavigation();
        this.setupSearchFilters();
        this.setupPagination();
    },

    setupNavigation: function() {
        const breadcrumbLink = document.querySelector('.detail-analisis-breadcrumb a');
        if (breadcrumbLink) {
            breadcrumbLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.navigateToAnalisis();
            });
        }
    },

    navigateToAnalisis: function() {
        if (window.mainLayoutComponent) {
            window.mainLayoutComponent.loadPage('analisis');
        }
    },

    setupSearchFilters: function() {
        this.setupTableSearch('searchKKPerempuan', 'kkPerempuanTable');
        this.setupTableSearch('searchProduktif', 'produktifTable');
        this.setupTableSearch('searchLansia', 'lansiaTable');
        this.setupTableSearch('searchRasio', 'rasioTable');
        this.setupTableSearch('searchPetani', 'petaniTable');
        this.setupTableSearch('searchKorelasi', 'korelasiTable');
        this.setupTableSearch('searchPendidikanTinggi', 'pendidikanTinggiTable');
        this.setupTableSearch('searchKesenjangan', 'kesenjanganTable');
        this.setupTableSearch('searchDistribusi', 'distribusiTable');
        this.setupTableSearch('searchNonPertanian', 'nonPertanianTable');
        // Integrasi tab
        this.setupTableSearch('searchKKMiskin', 'kkMiskinTable');
        this.setupTableSearch('searchRasioEkonomi', 'rasioEkonomiTable');
        this.setupTableSearch('searchPendidikanTinggiMiskin', 'pendidikanTinggiMiskinTable');
        this.setupTableSearch('searchAnakTidakSekolah', 'anakTidakSekolahTable');
        this.setupTableSearch('searchAnakPetani', 'anakPetaniTable');
        // Spasial tab
        this.setupTableSearch('searchSpasial', 'spasialTable');
        this.setupTableSearch('searchAksesibilitas', 'aksesibilitasTable');
        this.setupTableSearch('searchPotensi', 'potensiTable');
    },

    setupPagination: function() {
        const tables = ['kkPerempuanTable', 'produktifTable', 'lansiaTable', 'rasioTable', 'petaniTable', 'korelasiTable', 'pendidikanTinggiTable', 'kesenjanganTable', 'distribusiTable', 'nonPertanianTable', 'kkMiskinTable', 'rasioEkonomiTable', 'pendidikanTinggiMiskinTable', 'anakTidakSekolahTable', 'anakPetaniTable', 'spasialTable', 'aksesibilitasTable', 'potensiTable'];
        
        tables.forEach(tableId => {
            this.initTablePagination(tableId);
        });
    },

    initTablePagination: function(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tableSection = table.closest('.data-table-section');
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        
        // Remove existing pagination controls
        const existingPagination = document.getElementById(`${tableId}Pagination`);
        if (existingPagination) {
            existingPagination.remove();
        }
        
        this.pagination = this.pagination || {};
        this.pagination[tableId] = {
            currentPage: 1,
            itemsPerPage: 5,
            totalItems: rows.length,
            filteredRows: rows
        };
        
        this.addPaginationControls(tableSection, tableId);
        this.updatePagination(tableId);
    },

    addPaginationControls: function(tableSection, tableId) {
        const paginationHtml = `
            <div class="pagination-controls" id="${tableId}Pagination">
                <div class="pagination-info">
                    <select class="items-per-page" data-table="${tableId}">
                        <option value="5">5 per halaman</option>
                        <option value="10">10 per halaman</option>
                        <option value="25">25 per halaman</option>
                        <option value="50">50 per halaman</option>
                    </select>
                    <span class="pagination-text">Menampilkan <span class="start-item">1</span>-<span class="end-item">5</span> dari <span class="total-items">0</span> data</span>
                </div>
                <div class="pagination-buttons">
                    <button class="pagination-btn prev-btn" data-table="${tableId}" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="page-info">Halaman <span class="current-page">1</span> dari <span class="total-pages">1</span></span>
                    <button class="pagination-btn next-btn" data-table="${tableId}" disabled>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        `;
        
        tableSection.insertAdjacentHTML('beforeend', paginationHtml);
        
        const itemsPerPageSelect = tableSection.querySelector('.items-per-page');
        const prevBtn = tableSection.querySelector('.prev-btn');
        const nextBtn = tableSection.querySelector('.next-btn');
        
        itemsPerPageSelect.addEventListener('change', (e) => {
            this.pagination[tableId].itemsPerPage = parseInt(e.target.value);
            this.pagination[tableId].currentPage = 1;
            this.updatePagination(tableId);
        });
        
        prevBtn.addEventListener('click', () => {
            if (this.pagination[tableId].currentPage > 1) {
                this.pagination[tableId].currentPage--;
                this.updatePagination(tableId);
            }
        });
        
        nextBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(this.pagination[tableId].filteredRows.length / this.pagination[tableId].itemsPerPage);
            if (this.pagination[tableId].currentPage < totalPages) {
                this.pagination[tableId].currentPage++;
                this.updatePagination(tableId);
            }
        });
    },

    updatePagination: function(tableId) {
        const pagination = this.pagination[tableId];
        const table = document.getElementById(tableId);
        const paginationControls = document.getElementById(`${tableId}Pagination`);
        
        if (!table || !paginationControls || !pagination) return;
        
        const { currentPage, itemsPerPage, filteredRows } = pagination;
        const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        
        const allRows = table.querySelectorAll('tbody tr');
        allRows.forEach(row => row.style.display = 'none');
        
        filteredRows.slice(startIndex, endIndex).forEach(row => {
            row.style.display = '';
        });
        
        const startItem = filteredRows.length > 0 ? startIndex + 1 : 0;
        const endItem = Math.min(endIndex, filteredRows.length);
        
        paginationControls.querySelector('.start-item').textContent = startItem;
        paginationControls.querySelector('.end-item').textContent = endItem;
        paginationControls.querySelector('.total-items').textContent = filteredRows.length;
        paginationControls.querySelector('.current-page').textContent = currentPage;
        paginationControls.querySelector('.total-pages').textContent = Math.max(totalPages, 1);
        
        const prevBtn = paginationControls.querySelector('.prev-btn');
        const nextBtn = paginationControls.querySelector('.next-btn');
        
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages || totalPages <= 1;
    },

    setupTableSearch: function(searchInputId, tableId) {
        const searchInput = document.getElementById(searchInputId);
        const table = document.getElementById(tableId);
        
        if (!searchInput || !table) return;
        
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const allRows = Array.from(table.querySelectorAll('tbody tr:not(.no-results-row)'));
            
            const filteredRows = allRows.filter(row => {
                const text = row.textContent.toLowerCase();
                return text.includes(searchTerm);
            });
            
            if (this.pagination && this.pagination[tableId]) {
                this.pagination[tableId].filteredRows = filteredRows;
                this.pagination[tableId].currentPage = 1;
                this.updatePagination(tableId);
            }
            
            this.toggleNoResultsMessage(table, searchTerm, filteredRows);
        });
    },

    toggleNoResultsMessage: function(table, searchTerm, filteredRows) {
        const tbody = table.querySelector('tbody');
        let noResultsRow = tbody.querySelector('.no-results-row');
        
        if (searchTerm && filteredRows.length === 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results-row';
                noResultsRow.innerHTML = `
                    <td colspan="100%" style="text-align: center; padding: 32px; color: #666;">
                        <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        Tidak ada data yang sesuai dengan pencarian "${searchTerm}"
                    </td>
                `;
                tbody.appendChild(noResultsRow);
            }
            noResultsRow.style.display = '';
        } else if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    },

    initializeTableFeatures: function() {
        const tables = document.querySelectorAll('.data-table');
        
        tables.forEach(table => {
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                row.addEventListener('click', function() {
                    // Remove previous selection
                    rows.forEach(r => r.classList.remove('selected'));
                    // Add selection to current row
                    this.classList.add('selected');
                });
            });
        });
    },

    initializeTabSwitching: function() {
        const tabButtons = document.querySelectorAll('.detail-analisis-tab-btn');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const type = this.getAttribute('onclick').match(/'([^']+)'/)[1];
                DetailAnalisis.switchAnalysisType(type);
            });
        });
    },

    switchAnalysisType: function(type) {
        console.log('🔄 SWITCH ANALYSIS TYPE:', type);
        
        const urlParams = new URLSearchParams(window.location.search);
        const currentDesa = urlParams.get('desa') || 'all';
        const baseUrl = window.location.origin + '/Pendataan-desa/';
        const newUrl = `${baseUrl}pages/admin/detail-analisis-2/detail-analisis.php?desa=${currentDesa}&type=${type}`;
        
        console.log('- Current desa:', currentDesa);
        console.log('- New URL:', newUrl);
        
        // Show loader
        const container = document.querySelector('.detail-analisis-content');
        if (container) {
            container.innerHTML = '<div class="loader-container"><div class="loader"></div><p>Memuat analisis...</p></div>';
        }
        
        // Update URL
        history.pushState({page: 'detail-analisis', type: type, desa: currentDesa}, '', newUrl);
        
        // Load new content via AJAX
        fetch(newUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('📡 Tab switch response:', response.status);
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector('.detail-analisis-content');
            
            if (newContent && container) {
                console.log('✅ Updating tab content...');
                container.innerHTML = newContent.innerHTML;
                
                // Update active tab
                document.querySelectorAll('.detail-analisis-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                const activeBtn = document.querySelector(`[onclick*="'${type}'"]`);
                if (activeBtn) {
                    activeBtn.classList.add('active');
                }
                
                // Reinitialize search and pagination for new content
                setTimeout(() => {
                    this.setupSearchFilters();
                    this.setupPagination();
                }, 100);
                
                console.log('🎉 Tab switched successfully');
            }
        })
        .catch(error => {
            console.error('❌ Tab switch error:', error);
        });
    },

    goBackToAnalisis: function() {
        window.history.back();
    }
};

// Global functions for backward compatibility
function switchAnalysisType(type) {
    if (window.detailAnalisisInstance) {
        window.detailAnalisisInstance.switchAnalysisType(type);
    } else {
        DetailAnalisis.switchAnalysisType(type);
    }
}

function goBackToAnalisis() {
    const baseUrl = window.location.origin + '/Pendataan-desa/';
    const selectedDesa = new URLSearchParams(window.location.search).get('desa') || 'all';
    const url = `${baseUrl}pages/admin/analisis/analisis.php`;
    
    console.log('🔙 BACK TO ANALISIS START');
    console.log('- Current URL:', window.location.href);
    console.log('- Selected Desa:', selectedDesa);
    console.log('- Target URL:', url);
    
    // Show loader
    const container = document.querySelector('.detail-analisis-container');
    if (container) {
        console.log('✅ Detail analisis container found');
        container.innerHTML = '<div class="loader-container"><div class="loader"></div><p>Memuat halaman analisis...</p></div>';
    } else {
        console.log('❌ Detail analisis container not found');
    }
    
    // Update URL
    history.pushState({page: 'analisis'}, '', url);
    console.log('📍 URL updated to:', url);
    
    // Load analisis page via AJAX
    console.log('🌐 Starting AJAX request to analisis page...');
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('📡 Analisis response received:', response.status, response.statusText);
        return response.text();
    })
    .then(html => {
        console.log('📄 Analisis HTML content received, length:', html.length);
        
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newContent = doc.querySelector('.analisis-admin-container');
        
        console.log('🔍 Parsing analisis HTML...');
        console.log('- New analisis content found:', !!newContent);
        console.log('- Container exists:', !!container);
        
        if (newContent && container) {
            const mainContent = container.parentElement;
            console.log('- Main content parent found:', !!mainContent);
            
            if (mainContent) {
                console.log('✅ Replacing with analisis content...');
                mainContent.innerHTML = newContent.outerHTML;
                
                // Switch to tingkat 2 tab
                console.log('🔄 Switching to tingkat 2 tab...');
                setTimeout(() => {
                    // Initialize analisis JS first
                    if (typeof switchTab === 'function') {
                        switchTab('tingkat2');
                        console.log('✅ Tingkat 2 tab activated via switchTab');
                    } else {
                        const tingkat2Tab = document.querySelector('.analisis-admin-tab-btn:nth-child(2)');
                        console.log('- Tingkat 2 tab found:', !!tingkat2Tab);
                        if (tingkat2Tab) {
                            // Manually trigger tab switch
                            document.querySelectorAll('.analisis-admin-tab-btn').forEach(btn => btn.classList.remove('active'));
                            tingkat2Tab.classList.add('active');
                            
                            document.querySelectorAll('.analisis-admin-tab-content').forEach(content => content.classList.remove('active'));
                            const tingkat2Content = document.getElementById('tingkat2');
                            if (tingkat2Content) {
                                tingkat2Content.classList.add('active');
                            }
                            
                            console.log('✅ Tingkat 2 tab activated manually');
                        }
                    }
                }, 300);
                
                // Load analisis JS assets
                const analisisJsId = 'analisis-js';
                if (!document.getElementById(analisisJsId)) {
                    const script = document.createElement('script');
                    script.id = analisisJsId;
                    script.src = `${baseUrl}pages/admin/analisis/analisis.js`;
                    document.head.appendChild(script);
                }
                
                console.log('🎉 Back to analisis completed successfully');
            }
        } else {
            console.log('❌ Failed to find analisis content elements');
        }
    })
    .catch(error => {
        console.error('❌ Back to analisis error:', error);
        window.history.back();
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.detail-analisis-container')) {
        window.detailAnalisisInstance = DetailAnalisis;
        DetailAnalisis.init();
    }
});

// Also initialize if DOM is already loaded
if (document.readyState !== 'loading' && document.querySelector('.detail-analisis-container')) {
    window.detailAnalisisInstance = DetailAnalisis;
    DetailAnalisis.init();
}