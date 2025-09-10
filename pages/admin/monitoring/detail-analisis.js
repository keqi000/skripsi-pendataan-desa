// Detail Analisis JavaScript Functions
document.addEventListener('DOMContentLoaded', function() {
    console.log('Detail Analisis page loaded');
    
    // Initialize any interactive features
    initializeTableFeatures();
});

function initializeTableFeatures() {
    // Add search functionality to tables if needed
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // Add hover effects or other interactive features
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            row.addEventListener('click', function() {
                // Optional: Add row selection or detail view
                console.log('Row clicked:', this);
            });
        });
    });
}

function switchAnalysisType(type) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('type', type);
    window.location.search = urlParams.toString();
}