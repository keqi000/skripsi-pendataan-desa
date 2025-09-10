// Laporan Admin JavaScript
(function() {
    'use strict';
    
    class LaporanAdmin {
        constructor() {
            if (document.querySelector('.laporan-admin-container')) {
                this.init();
            }
        }
        
        init() {
            this.setupEventListeners();
            this.loadFormState();
        }
        
        setupEventListeners() {
            // Form validation
            const reportForm = document.getElementById('reportForm');
            if (reportForm) {
                reportForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.validateForm();
                });
                
                // Save form state on changes
                reportForm.addEventListener('change', () => {
                    this.saveFormState();
                });
                
                reportForm.addEventListener('input', () => {
                    this.saveFormState();
                });
            }
            
            // Add specific listeners for comparison dropdowns
            const comparisonDesa1 = document.getElementById('comparisonDesa1');
            const comparisonDesa2 = document.getElementById('comparisonDesa2');
            
            if (comparisonDesa1) {
                comparisonDesa1.addEventListener('change', () => {
                    this.saveFormState();
                });
            }
            
            if (comparisonDesa2) {
                comparisonDesa2.addEventListener('change', () => {
                    this.saveFormState();
                });
            }
            
            // Add listener for section checkboxes
            document.querySelectorAll('input[name="sections"]').forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    this.saveFormState();
                });
            });
        }
        
        validateForm() {
            const reportName = document.getElementById('reportName').value.trim();
            const selectedSections = document.querySelectorAll('input[name="sections"]:checked');
            
            if (!reportName) {
                this.showNotification('Nama laporan harus diisi', 'warning');
                return false;
            }
            
            if (selectedSections.length === 0) {
                this.showNotification('Pilih minimal satu bagian data', 'warning');
                return false;
            }
            
            return true;
        }
        
        saveFormState() {
            const comparisonCheckbox = document.querySelector('input[name="sections"][value="perbandingan_desa"]');
            const isComparisonChecked = comparisonCheckbox?.checked || false;
            
            const formData = {
                reportName: document.getElementById('reportName')?.value || '',
                reportDesa: document.getElementById('reportDesa')?.value || 'all',
                reportYear: document.getElementById('reportYear')?.value || new Date().getFullYear(),
                sections: Array.from(document.querySelectorAll('input[name="sections"]:checked')).map(cb => cb.value),
                comparisonDesa1: document.getElementById('comparisonDesa1')?.value || '',
                comparisonDesa2: document.getElementById('comparisonDesa2')?.value || '',
                comparisonFormVisible: isComparisonChecked && document.getElementById('comparisonForm')?.style.display === 'block'
            };
            
            localStorage.setItem('laporan_form_state', JSON.stringify(formData));
        }
        
        loadFormState() {
            const savedState = localStorage.getItem('laporan_form_state');
            if (!savedState) return;
            
            try {
                const formData = JSON.parse(savedState);
                
                // Load basic form fields
                if (document.getElementById('reportName')) {
                    document.getElementById('reportName').value = formData.reportName || '';
                }
                if (document.getElementById('reportDesa')) {
                    document.getElementById('reportDesa').value = formData.reportDesa || 'all';
                }
                if (document.getElementById('reportYear')) {
                    document.getElementById('reportYear').value = formData.reportYear || new Date().getFullYear();
                }
                
                // Load sections checkboxes
                document.querySelectorAll('input[name="sections"]').forEach(checkbox => {
                    checkbox.checked = formData.sections?.includes(checkbox.value) || false;
                });
                
                // Load comparison form state
                if (formData.comparisonFormVisible) {
                    const comparisonCheckbox = document.querySelector('input[name="sections"][value="perbandingan_desa"]');
                    if (comparisonCheckbox) {
                        comparisonCheckbox.checked = true;
                    }
                    
                    const comparisonForm = document.getElementById('comparisonForm');
                    if (comparisonForm) {
                        comparisonForm.style.display = 'block';
                    }
                    
                    // Add small delay to ensure form is visible before setting values
                    setTimeout(() => {
                        if (document.getElementById('comparisonDesa1')) {
                            document.getElementById('comparisonDesa1').value = formData.comparisonDesa1 || '';
                        }
                        if (document.getElementById('comparisonDesa2')) {
                            document.getElementById('comparisonDesa2').value = formData.comparisonDesa2 || '';
                        }
                    }, 100);
                }
                
                // Show notification that form state was loaded
                if (formData.reportName || formData.sections?.length > 0) {
                    setTimeout(() => {
                        this.showNotification('Form state dimuat dari sesi sebelumnya', 'info');
                    }, 500);
                }
                
            } catch (error) {
                console.error('Error loading form state:', error);
                localStorage.removeItem('laporan_form_state');
            }
        }
        
        clearFormState() {
            localStorage.removeItem('laporan_form_state');
        }
        
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <span>${message}</span>
                <button onclick="this.parentElement.remove()">&times;</button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
    }
    
    // Global functions for laporan
    window.switchLaporanTab = function(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.laporan-admin-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Remove active class from all tab buttons
        document.querySelectorAll('.laporan-admin-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab content
        document.getElementById(tabName).classList.add('active');
        
        // Add active class to clicked button
        event.target.classList.add('active');
        
        // Clear form when switching to generate tab
        if (tabName === 'generate') {
            clearGenerateForm();
        }
    };
    
    function clearGenerateForm() {
        document.getElementById('reportName').value = '';
        document.getElementById('reportDesa').value = 'all';
        document.getElementById('reportYear').value = new Date().getFullYear();
        
        // Clear all checkboxes
        document.querySelectorAll('input[name="sections"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Hide comparison form
        const comparisonForm = document.getElementById('comparisonForm');
        if (comparisonForm) {
            comparisonForm.style.display = 'none';
        }
        
        // Clear comparison selects
        if (document.getElementById('comparisonDesa1')) {
            document.getElementById('comparisonDesa1').value = '';
        }
        if (document.getElementById('comparisonDesa2')) {
            document.getElementById('comparisonDesa2').value = '';
        }
        
        // Clear current template ID
        window.currentTemplateId = null;
        
        // Clear saved form state
        if (window.laporanAdminInstance) {
            window.laporanAdminInstance.clearFormState();
        }
    }
    
    window.useTemplate = function(templateId) {
        // Switch to generate tab and load template
        switchLaporanTab('generate');
        
        // Store current template ID for updates
        window.currentTemplateId = templateId;
        
        // Find template data and populate form
        fetch(`${window.location.origin}/Pendataan-desa/api/template.php?id=${templateId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let config;
                    try {
                        config = JSON.parse(data.template.konfigurasi);
                    } catch (e) {
                        config = data.template.konfigurasi;
                    }
                    
                    // Populate form
                    document.getElementById('reportName').value = data.template.nama_template.replace(' (Template)', '');
                    document.getElementById('reportDesa').value = config.desa || 'all';
                    document.getElementById('reportYear').value = config.tahun || new Date().getFullYear();
                    
                    // Clear all checkboxes first
                    document.querySelectorAll('input[name="sections"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    
                    // Check sections
                    if (config.sections && Array.isArray(config.sections)) {
                        document.querySelectorAll('input[name="sections"]').forEach(checkbox => {
                            checkbox.checked = config.sections.includes(checkbox.value);
                            
                            // Handle comparison form visibility
                            if (checkbox.value === 'perbandingan_desa' && checkbox.checked) {
                                const comparisonForm = document.getElementById('comparisonForm');
                                if (comparisonForm) {
                                    comparisonForm.style.display = 'block';
                                }
                                
                                // Load comparison desa values if available with delay
                                setTimeout(() => {
                                    if (config.desa1 && document.getElementById('comparisonDesa1')) {
                                        document.getElementById('comparisonDesa1').value = config.desa1;
                                    }
                                    if (config.desa2 && document.getElementById('comparisonDesa2')) {
                                        document.getElementById('comparisonDesa2').value = config.desa2;
                                    }
                                }, 100);
                            }
                        });
                    }
                    
                    showNotification('Template berhasil dimuat', 'success');
                    
                    // Save the loaded template state
                    if (window.laporanAdminInstance) {
                        window.laporanAdminInstance.saveFormState();
                    }
                } else {
                    showNotification('Gagal memuat template', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading template:', error);
                showNotification('Terjadi kesalahan saat memuat template', 'error');
            });
    };
    
    window.deleteTemplate = function(templateId) {
        if (confirm('Apakah Anda yakin ingin menghapus template ini?')) {
            fetch(`${window.location.origin}/Pendataan-desa/api/template.php`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: templateId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reorder IDs after deletion
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=reorder_template_ids'
                    })
                    .then(() => {
                        showNotification('Template berhasil dihapus', 'success');
                        setTimeout(() => location.reload(), 1000);
                    });
                } else {
                    showNotification('Gagal menghapus template', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting template:', error);
                showNotification('Terjadi kesalahan saat menghapus template', 'error');
            });
        }
    };
    
    window.saveAsTemplate = function() {
        if (!window.laporanAdminInstance.validateForm()) {
            return;
        }
        
        const templateName = document.getElementById('reportName').value.trim() + ' (Template)';
        const config = {
            nama_template: templateName,
            desa: document.getElementById('reportDesa').value,
            tahun: document.getElementById('reportYear').value,
            sections: Array.from(document.querySelectorAll('input[name="sections"]:checked')).map(cb => cb.value)
        };
        
        // Check if updating existing template
        const action = window.currentTemplateId ? 'update_template' : 'save_template';
        const body = window.currentTemplateId ? 
            `action=${action}&template_id=${window.currentTemplateId}&template_name=${encodeURIComponent(templateName)}&template_config=${encodeURIComponent(JSON.stringify(config))}` :
            `action=${action}&template_name=${encodeURIComponent(templateName)}&template_config=${encodeURIComponent(JSON.stringify(config))}`;
        
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(window.currentTemplateId ? 'Template berhasil diupdate' : 'Template berhasil disimpan', 'success');
                window.currentTemplateId = null;
                
                // Keep form state when saving template
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Gagal menyimpan template', 'error');
            }
        })
        .catch(error => {
            console.error('Error saving template:', error);
            showNotification('Terjadi kesalahan saat menyimpan template', 'error');
        });
    };
    
    window.generateReport = function() {
        if (!window.laporanAdminInstance.validateForm()) {
            return;
        }
        
        const selectedSections = Array.from(document.querySelectorAll('input[name="sections"]:checked')).map(cb => cb.value);
        
        let config = {
            nama_laporan: document.getElementById('reportName').value,
            desa: document.getElementById('reportDesa').value,
            tahun: document.getElementById('reportYear').value,
            sections: selectedSections
        };
        
        // Check if comparison is selected and validate
        if (selectedSections.includes('perbandingan_desa')) {
            const desa1 = document.getElementById('comparisonDesa1').value;
            const desa2 = document.getElementById('comparisonDesa2').value;
            
            if (!desa1 || !desa2) {
                showNotification('Pilih kedua desa untuk perbandingan', 'warning');
                return;
            }
            
            if (desa1 === desa2) {
                showNotification('Pilih desa yang berbeda untuk perbandingan', 'warning');
                return;
            }
            
            config.desa1 = desa1;
            config.desa2 = desa2;
        }
        
        showNotification('Sedang generate laporan...', 'info');
        
        // First generate preview content
        fetch(`${window.location.origin}/Pendataan-desa/api/preview.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(config)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Render preview content first
                renderPreview(config, data.data);
                
                // Then generate PDF
                generatePDF(config, data.data);
                
                // Save to history
                return fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=generate_report&template_config=${encodeURIComponent(JSON.stringify(config))}`
                });
            } else {
                throw new Error(data.error || 'Gagal generate preview');
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Laporan berhasil dibuat', 'success');
                
                // Optional: Clear form state after successful generation
                // Uncomment the line below if you want to clear the form after generation
                // window.laporanAdminInstance.clearFormState();
            } else {
                showNotification('Gagal menyimpan laporan', 'error');
            }
        })
        .catch(error => {
            console.error('Error generating report:', error);
            showNotification('Terjadi kesalahan saat membuat laporan: ' + error.message, 'error');
        });
    };
    
    function generatePDF(config, data) {
    // Clone preview content to avoid modifying original
    const previewElement = document.getElementById('previewContent');
    const clonedContent = previewElement.cloneNode(true);
    
    // Convert charts to images in cloned content
    const chartElements = clonedContent.querySelectorAll('canvas');
    
    // Wait for charts to be fully rendered
    setTimeout(() => {
        chartElements.forEach((canvas) => {
            if (canvas.id) {
                // Find original canvas to get image data
                const originalCanvas = document.getElementById(canvas.id);
                if (originalCanvas && originalCanvas.getContext) {
                    try {
                        const imgData = originalCanvas.toDataURL('image/png', 1.0);
                        const img = document.createElement('img');
                        img.src = imgData;
                        img.style.width = '100%';
                        img.style.height = 'auto';
                        img.style.maxWidth = '500px';
                        img.style.display = 'block';
                        img.style.margin = '0 auto';
                        img.className = 'chart-image';
                        
                        canvas.parentNode.replaceChild(img, canvas);
                    } catch (e) {
                        // Fallback if chart conversion fails
                        const fallbackDiv = document.createElement('div');
                        fallbackDiv.innerHTML = `<div style="text-align: center; padding: 40px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 8px;"><h4 style="margin: 0 0 10px 0; color: #112D4E;">${canvas.id.includes('Line') ? 'Tren Prediksi Penduduk' : 'Struktur Usia Prediksi'}</h4><p style="margin: 0; color: #666;">Chart akan muncul di versi digital</p></div>`;
                        canvas.parentNode.replaceChild(fallbackDiv, canvas);
                    }
                } else {
                    // No canvas found, create placeholder
                    const fallbackDiv = document.createElement('div');
                    fallbackDiv.innerHTML = `<div style="text-align: center; padding: 40px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 8px;"><h4 style="margin: 0 0 10px 0; color: #112D4E;">${canvas.id.includes('Line') ? 'Tren Prediksi Penduduk' : 'Struktur Usia Prediksi'}</h4><p style="margin: 0; color: #666;">Chart akan muncul di versi digital</p></div>`;
                    canvas.parentNode.replaceChild(fallbackDiv, canvas);
                }
            }
        });
    }, 1000);
    
    const previewContent = clonedContent.innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>${config.nama_laporan}</title>
                <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <style>
                    * { box-sizing: border-box; }
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; line-height: 1.6; color: #333; background: #fff; }
                    
                    /* Report Header */
                    .report-header-section { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; padding: 30px; margin-bottom: 30px; text-align: center; page-break-after: always; }
                    .report-title-page { padding: 40px 20px; text-align: center; }
                    .report-logo { margin-bottom: 30px; }
                    .logo-placeholder { display: inline-block; padding: 20px; background: #fff; border-radius: 50%; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
                    .report-main-title { font-size: 32px; color: #112D4E; margin: 20px 0; font-weight: bold; }
                    .report-subtitle { font-size: 22px; color: #3F72AF; margin: 15px 0; font-weight: 500; }
                    .report-location h3 { font-size: 18px; color: #112D4E; margin: 15px 0; font-weight: 500; }
                    .report-meta, .report-summary { background: #fff; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .report-meta-item { margin: 12px 0; padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
                    .report-meta-item:last-child { border-bottom: none; }
                    .report-summary h4 { color: #112D4E; margin-bottom: 15px; font-size: 20px; border-bottom: 2px solid #3F72AF; padding-bottom: 8px; }
                    .report-summary p { margin-bottom: 20px; text-align: justify; line-height: 1.7; color: #495057; }
                    .report-sections-included h5 { color: #3F72AF; margin: 15px 0 10px 0; font-size: 16px; font-weight: 600; }
                    .report-sections-included ul { margin: 0; padding-left: 20px; }
                    .report-sections-included li { margin: 8px 0; color: #495057; font-weight: 500; }
                    
                    /* Cards and Sections */
                    .card { page-break-before: always; page-break-inside: avoid; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0 30px 0; background: #fff; min-height: 700px; max-height: 1500px; overflow: hidden; }
                    .card:first-child { page-break-before: always; margin-top: 20px; }
                    .preview-report .card:first-child { page-break-before: always; margin-top: 20px; }
                    .card-header { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #ddd; border-radius: 8px 8px 0 0; }
                    .card-header h4 { margin: 0; color: #112D4E; font-size: 18px; font-weight: 600; }
                    .card-body { padding: 20px; }
                    
                    /* Tables */
                    .table { width: 100%; border-collapse: collapse; margin: 15px 0; page-break-inside: auto; }
                    .table th, .table td { border: 1px solid #ddd; padding: 12px 8px; text-align: left; font-size: 13px; vertical-align: top; }
                    .table th { background: #f8f9fa; font-weight: 600; color: #495057; }
                    .table tbody tr { page-break-inside: avoid; }
                    .table tbody tr:nth-child(20n) { page-break-after: always; }
                    .table-bordered { border: 1px solid #ddd; }
                    .table-responsive { overflow-x: auto; }
                    .data-table-section { page-break-inside: avoid; margin: 20px 0; }
                    .data-table-section h5 { color: #112D4E; font-size: 16px; font-weight: 600; margin-bottom: 10px; border-bottom: 2px solid #3F72AF; padding-bottom: 5px; }
                    
                    /* Stats Grid */
                    .analisis-stats-grid, .prediction-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
                    .stat-item { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px; border: 1px solid #ddd; }
                    .stat-item h4 { margin: 0 0 8px 0; font-size: 14px; color: #6c757d; font-weight: 500; }
                    .stat-value { font-size: 18px; font-weight: 700; color: #112D4E; }
                    
                    /* Breakdown */
                    .analisis-breakdown { margin: 20px 0; page-break-inside: avoid; }
                    .analisis-breakdown h5 { margin: 0 0 15px 0; font-size: 16px; color: #112D4E; border-bottom: 2px solid #3F72AF; padding-bottom: 5px; font-weight: 600; }
                    .breakdown-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f1f1; }
                    .breakdown-item span:first-child { color: #495057; font-weight: 500; }
                    .breakdown-item span:last-child { color: #3F72AF; font-weight: 600; }
                    .breakdown-section { font-size: 14px; font-weight: 600; color: #3F72AF; margin: 15px 0 8px 0; }
                    
                    /* Charts */
                    .prediction-charts-section, .charts-grid { margin: 20px 0; }
                    .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
                    .chart-container { text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background: #fff; }
                    .chart-container h4 { margin: 0 0 15px 0; color: #112D4E; font-size: 16px; font-weight: 600; }
                    .chart-image { max-width: 100%; height: auto; border-radius: 4px; }
                    
                    /* Prediction Items */
                    .prediction-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 20px 0; }
                    .prediction-item { display: flex; align-items: flex-start; gap: 16px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd; }
                    .prediction-icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; background: #3F72AF; flex-shrink: 0; }
                    .prediction-content h4 { font-size: 16px; font-weight: 600; color: #112D4E; margin: 0 0 8px 0; }
                    .prediction-content p { font-size: 13px; color: #6c757d; margin: 0 0 12px 0; }
                    .prediction-value { font-size: 14px; font-weight: 600; color: #3F72AF; }
                    
                    /* Status Badges */
                    .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
                    .status-complete { background: #d4edda; color: #155724; }
                    .status-partial { background: #fff3cd; color: #856404; }
                    .status-empty { background: #f8d7da; color: #721c24; }
                    .data-status { padding: 2px 6px; border-radius: 3px; font-size: 11px; }
                    .status-ok { background: #d4edda; color: #155724; }
                    
                    /* Print Styles */
                    @media print {
                        body { margin: 0; padding: 15px; font-size: 12px; color: #000; -webkit-print-color-adjust: exact; }
                        .card { page-break-before: always; page-break-inside: avoid !important; margin: 20px 0 20px 0; border: 1px solid #000 !important; border-bottom: 1px solid #000 !important; min-height: 1600px !important; max-height: 700px !important; overflow: hidden !important; }
                        .card:first-child { page-break-before: always; margin-top: 20px; }
                        .preview-report .card:first-child { page-break-before: always; margin-top: 20px; }
                        .report-header-section { page-break-after: always !important; background: #f8f9fa !important; }
                        .section-content { page-break-before: auto !important; }
                        .card-header { background: #f0f0f0 !important; border-bottom: 1px solid #000 !important; }
                        .card-header h4 { color: #000 !important; }
                        .card-body { !important; max-height: 1600px !important; }
                        .data-table-section { margin: 15px 0; }
                        .data-table-section h5 { color: #000 !important; border-bottom: 2px solid #000 !important; }
                        .analisis-breakdown { margin: 15px 0; }
                        .analisis-breakdown h5 { color: #000 !important; border-bottom: 2px solid #000 !important; }
                        .chart-container { margin: 15px 0; border: 1px solid #000 !important; }
                        .chart-image { max-width: 100% !important; height: auto !important; }
                        .prediction-item { border: 1px solid #000 !important; background: #f9f9f9 !important; }
                        .prediction-icon { background: #666 !important; }
                        .table { font-size: 11px; border-collapse: collapse !important; border: 1px solid #000 !important; }
                        .table th, .table td { padding: 8px 6px; border: 1px solid #000 !important; }
                        .table th { background: #f0f0f0 !important; color: #000 !important; }
                        .stat-item { border: 1px solid #000 !important; background: #f9f9f9 !important; }
                        .stat-value { font-size: 16px; color: #000 !important; }
                        .breakdown-item span:last-child { color: #000 !important; font-weight: bold; }
                        .status-badge { border: 1px solid #000 !important; }
                        .table tbody tr:nth-child(60n) { page-break-after: always; }
                        .prediction-table-section .table tbody tr:nth-child(60n) { page-break-after: always; }
                        .prediction-table-section h5 { color: #000 !important; border-bottom: 2px solid #000 !important; }
                        .logo-placeholder::before { content: '🏢'; font-size: 36px; }
                        .logo-placeholder i, .fas, .fa { display: none !important; }
                    }
                    
                    @page {
                        margin: 1cm 1cm 1cm 1cm;
                    }
                    

                </style>
            </head>
            <body>
                ${previewContent}
            </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Wait for images to load then print
    printWindow.onload = function() {
        setTimeout(() => {
            // Add logging script to print window
            const loggingScript = printWindow.document.createElement('script');
            loggingScript.innerHTML = `
                function logPageBreaks() {
                    console.log('=== PAGE BREAK ANALYSIS ===');
                    
                    // Get all elements
                    const reportHeader = document.querySelector('.report-header-section');
                    const cards = document.querySelectorAll('.card');
                    
                    // Log report header
                    if (reportHeader) {
                        const headerRect = reportHeader.getBoundingClientRect();
                        const headerStyle = window.getComputedStyle(reportHeader);
                        console.log('REPORT HEADER:');
                        console.log('- Height:', headerRect.height + 'px');
                        console.log('- Page Break After:', headerStyle.pageBreakAfter);
                        console.log('- Margin Bottom:', headerStyle.marginBottom);
                        console.log('- Is Standalone:', headerStyle.pageBreakAfter === 'always');
                    }
                    
                    // Log each card
                    cards.forEach((card, index) => {
                        const cardRect = card.getBoundingClientRect();
                        const cardStyle = window.getComputedStyle(card);
                        console.log('CARD ' + (index + 1) + ':');
                        console.log('- Height:', cardRect.height + 'px');
                        console.log('- Page Break Before:', cardStyle.pageBreakBefore);
                        console.log('- Page Break Inside:', cardStyle.pageBreakInside);
                        console.log('- Margin Top:', cardStyle.marginTop);
                        console.log('- Margin Bottom:', cardStyle.marginBottom);
                        console.log('- Is New Page:', cardStyle.pageBreakBefore === 'always');
                        console.log('- Can Break Inside:', cardStyle.pageBreakInside !== 'avoid');
                        
                        // Check if card content fits in one page (rough calculation)
                        const pageHeight = 842; // A4 height in pixels at 96dpi
                        const margins = 96; // 1cm margins in pixels
                        const availableHeight = pageHeight - margins;
                        console.log('- Fits in one page:', cardRect.height <= availableHeight);
                        console.log('---');
                    });
                    
                    // Check page structure with detailed analysis
                    const allElements = [reportHeader, ...cards].filter(el => el);
                    let currentPageHeight = 0;
                    let pageNumber = 1;
                    const pageHeight = 842;
                    const margins = 96;
                    const availableHeight = pageHeight - margins;
                    
                    console.log('DETAILED PAGE DISTRIBUTION:');
                    console.log('Available page height:', availableHeight + 'px');
                    console.log('---');
                    
                    allElements.forEach((element, index) => {
                        const elementRect = element.getBoundingClientRect();
                        const elementStyle = window.getComputedStyle(element);
                        const elementName = element.classList.contains('report-header-section') ? 'Report Header' : 'Card ' + (index);
                        const elementHeight = elementRect.height;
                        
                        let startPage = pageNumber;
                        let willBreakInside = false;
                        let endPage = pageNumber;
                        
                        if (elementStyle.pageBreakBefore === 'always' && index > 0) {
                            pageNumber++;
                            startPage = pageNumber;
                            currentPageHeight = 0;
                        }
                        
                        if (elementHeight > availableHeight) {
                            if (elementStyle.pageBreakInside !== 'avoid') {
                                willBreakInside = true;
                                const additionalPages = Math.ceil(elementHeight / availableHeight) - 1;
                                endPage = pageNumber + additionalPages;
                                pageNumber = endPage;
                                currentPageHeight = elementHeight % availableHeight;
                            } else {
                                // Element too big but can't break - force to single page
                                endPage = pageNumber;
                                currentPageHeight = elementHeight;
                                console.log('WARNING: Element too big for single page but has page-break-inside: avoid');
                            }
                        } else if (currentPageHeight + elementHeight > availableHeight) {
                            pageNumber++;
                            startPage = pageNumber;
                            endPage = pageNumber;
                            currentPageHeight = elementHeight;
                        } else {
                            endPage = pageNumber;
                            currentPageHeight += elementHeight;
                        }
                        
                        console.log(elementName + ':');
                        console.log('- Start Page:', startPage);
                        console.log('- End Page:', endPage);
                        console.log('- Height:', elementHeight + 'px');
                        console.log('- Breaks Inside:', willBreakInside ? 'YES (too long)' : 'NO');
                        console.log('- Page Break Before:', elementStyle.pageBreakBefore);
                        console.log('- Page Break After:', elementStyle.pageBreakAfter);
                        console.log('---');
                        
                        if (elementStyle.pageBreakAfter === 'always') {
                            pageNumber++;
                            currentPageHeight = 0;
                        }
                    });
                    
                    console.log('TOTAL PAGES ESTIMATED:', pageNumber);
                }
                
                // Run logging after a short delay
                setTimeout(logPageBreaks, 500);
            `;
            printWindow.document.head.appendChild(loggingScript);
            
            printWindow.focus();
            printWindow.print();
        }, 1500);
    };
    
    // Fallback timeout
    setTimeout(() => {
        printWindow.focus();
        printWindow.print();
    }, 3000);
}
    
    function generateKependudukanSection(data) {
        if (!data) return '';
        
        let content = '<h2>Statistik Kependudukan</h2>';
        
        Object.keys(data).forEach(desaName => {
            const stats = data[desaName];
            content += `
                <h3>${desaName}</h3>
                <table>
                    <tr><th>Indikator</th><th>Jumlah</th></tr>
                    <tr><td>Total Penduduk</td><td>${stats.total || 0} jiwa</td></tr>
                    <tr><td>Laki-laki</td><td>${stats.laki || 0} jiwa</td></tr>
                    <tr><td>Perempuan</td><td>${stats.perempuan || 0} jiwa</td></tr>
                </table>
            `;
        });
        
        return content;
    }
    
    function generateEkonomiSection(data) {
        if (!data) return '';
        
        let content = '<h2>Distribusi Ekonomi</h2>';
        
        Object.keys(data).forEach(desaName => {
            const ekonomi = data[desaName];
            content += `
                <h3>${desaName}</h3>
                <p>Total Data Ekonomi: ${ekonomi.length} unit</p>
            `;
        });
        
        return content;
    }
    
    function generatePendidikanSection(data) {
        if (!data) return '';
        
        let content = '<h2>Fasilitas Pendidikan</h2>';
        
        Object.keys(data).forEach(desaName => {
            const pendidikan = data[desaName];
            content += `
                <h3>${desaName}</h3>
                <p>Total Fasilitas: ${pendidikan.length} unit</p>
            `;
        });
        
        return content;
    }
    
    function generateInfrastrukturSection(data) {
        if (!data) return '';
        
        let content = '<h2>Status Infrastruktur</h2>';
        
        Object.keys(data).forEach(desaName => {
            const infra = data[desaName];
            content += `
                <h3>${desaName}</h3>
                <p>Total Jalan: ${infra.jalan?.length || 0} unit</p>
                <p>Total Jembatan: ${infra.jembatan?.length || 0} unit</p>
            `;
        });
        
        return content;
    }
    
    window.viewReport = function(reportId) {
        // Open report in new window
        window.open(`${window.location.origin}/Pendataan-desa/api/report.php?id=${reportId}&action=view`, '_blank');
    };
    
    window.downloadReport = function(reportId) {
        // Download report as PDF
        window.open(`${window.location.origin}/Pendataan-desa/api/report.php?id=${reportId}&action=download`, '_blank');
    };
    
    // Preview functions
    window.showPreview = function() {
        if (!window.laporanAdminInstance.validateForm()) {
            return;
        }
        
        const selectedSections = Array.from(document.querySelectorAll('input[name="sections"]:checked')).map(cb => cb.value);
        
        // Check if comparison is selected and validate
        if (selectedSections.includes('perbandingan_desa')) {
            const desa1 = document.getElementById('comparisonDesa1').value;
            const desa2 = document.getElementById('comparisonDesa2').value;
            
            if (!desa1 || !desa2) {
                showNotification('Pilih kedua desa untuk perbandingan', 'warning');
                return;
            }
            
            if (desa1 === desa2) {
                showNotification('Pilih desa yang berbeda untuk perbandingan', 'warning');
                return;
            }
            
            generatePreview(selectedSections, { desa1, desa2 });
            return;
        }
        
        generatePreview(selectedSections);
    };
    
    window.hidePreview = function() {
        document.getElementById('previewSection').style.display = 'none';
    };
    
    window.clearFormAndState = function() {
        if (confirm('Apakah Anda yakin ingin menghapus semua data form dan state tersimpan?')) {
            clearGenerateForm();
            if (window.laporanAdminInstance) {
                window.laporanAdminInstance.clearFormState();
            }
            showNotification('Form dan state tersimpan berhasil dihapus', 'success');
        }
    };
    
    window.toggleComparisonForm = function(checkbox) {
        const form = document.getElementById('comparisonForm');
        if (checkbox.checked) {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
            document.getElementById('comparisonDesa1').value = '';
            document.getElementById('comparisonDesa2').value = '';
        }
        
        // Save form state after toggle
        if (window.laporanAdminInstance) {
            window.laporanAdminInstance.saveFormState();
        }
    };
    
    // Make sure function is available globally
    if (typeof window.toggleComparisonForm === 'undefined') {
        window.toggleComparisonForm = function(checkbox) {
            const form = document.getElementById('comparisonForm');
            if (checkbox && form) {
                if (checkbox.checked) {
                    form.style.display = 'block';
                } else {
                    form.style.display = 'none';
                    const desa1 = document.getElementById('comparisonDesa1');
                    const desa2 = document.getElementById('comparisonDesa2');
                    if (desa1) desa1.value = '';
                    if (desa2) desa2.value = '';
                }
                
                // Save form state after toggle
                if (window.laporanAdminInstance) {
                    window.laporanAdminInstance.saveFormState();
                }
            }
        };
    }
    
    function generatePreview(sections, comparison = null) {
        let config = {
            nama_laporan: document.getElementById('reportName').value,
            desa: document.getElementById('reportDesa').value,
            tahun: document.getElementById('reportYear').value,
            sections: sections
        };
        
        if (comparison) {
            config.desa1 = comparison.desa1;
            config.desa2 = comparison.desa2;
        }
        
        showNotification('Memuat preview...', 'info');
        
        fetch(`${window.location.origin}/Pendataan-desa/api/preview.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(config)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderPreview(config, data.data);
                document.getElementById('previewSection').style.display = 'block';
                document.getElementById('previewSection').scrollIntoView({ behavior: 'smooth' });
            } else {
                showNotification('Gagal memuat preview: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error generating preview:', error);
            showNotification('Terjadi kesalahan saat memuat preview: ' + error.message, 'error');
        });
    }
    
    function renderPreview(config, data) {
        // Create comprehensive report header as Section 1
        let previewHTML = `
        <div class="report-header-section">
            <div class="report-title-page">
                    <div class="report-logo">
                        <div class="logo-placeholder">
                            <i class="fas fa-building" style="font-size: 48px; color: #112D4E;"></i>
                        </div>
                    </div>
                    <div class="report-title-content">
                        <h1 class="report-main-title">${config.nama_laporan}</h1>
                        <h2 class="report-subtitle">Sistem Informasi Pendataan Desa Terintegrasi</h2>
                        <div class="report-location">
                            <h3>Kecamatan Tibawa, Kabupaten Gorontalo</h3>
                        </div>
                    </div>
                    <div class="report-meta">
                        <div class="report-meta-item">
                            <strong>Tahun Data:</strong> ${config.tahun}
                        </div>
                        <div class="report-meta-item">
                            <strong>Cakupan Wilayah:</strong> ${config.desa === 'all' ? 'Semua Desa di Kecamatan Tibawa' : 'Desa Terpilih'}
                        </div>
                        <div class="report-meta-item">
                            <strong>Tanggal Generate:</strong> ${new Date().toLocaleDateString('id-ID', { 
                                weekday: 'long', 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric' 
                            })}
                        </div>
                        <div class="report-meta-item">
                            <strong>Jenis Analisis:</strong> ${config.sections.includes('analisis_kependudukan') || config.sections.includes('analisis_ekonomi') ? 'Analisis Lanjutan' : 'Analisis Dasar'}
                        </div>
                    </div>
                    <div class="report-summary">
                        <h4>Ringkasan Laporan</h4>
                        <p>Laporan ini berisi analisis komprehensif data kependudukan, ekonomi, pendidikan, dan infrastruktur 
                        di wilayah Kecamatan Tibawa. Data yang disajikan merupakan hasil pengolahan sistem informasi 
                        pendataan desa terintegrasi untuk mendukung kebijakan pemerintah pusat.</p>
                        
                        <div class="report-sections-included">
                            <h5>Bagian yang Disertakan:</h5>
                            <ul>
                                ${config.sections.map(section => {
                                    const sectionNames = {
                                        'statistik_kependudukan': 'Statistik Kependudukan',
                                        'distribusi_ekonomi': 'Distribusi Ekonomi', 
                                        'fasilitas_pendidikan': 'Fasilitas Pendidikan',
                                        'status_infrastruktur': 'Status Infrastruktur',
                                        'analisis_kependudukan': 'Analisis Kependudukan Lanjutan',
                                        'analisis_ekonomi': 'Analisis Ekonomi Lanjutan',
                                        'integrasi_data': 'Integrasi Data Lintas Kategori',
                                        'analisis_spasial': 'Analisis Spasial & Prediktif',
                                        'prediksi_penduduk': 'Prediksi Struktur Penduduk',
                                        'proyeksi_pembangunan': 'Proyeksi Kebutuhan Pembangunan'
                                    };
                                    return `<li>${sectionNames[section] || section}</li>`;
                                }).join('')}
                            </ul>
                        </div>
                </div>
            </div>
        `;
        
        // Add content sections starting from Section 2
        if (data.html_content) {
            previewHTML += `</div><div class="preview-section section-content">${data.html_content}</div>`;
        }
        
        document.getElementById('previewContent').innerHTML = previewHTML;
        
        // Load analisis CSS for proper styling
        loadAnalisisStyles();
        
        // Initialize charts after content is loaded
        setTimeout(() => {
            if (window.initPreviewCharts) {
                window.initPreviewCharts();
            }
        }, 1000);
    }
    
    function loadAnalisisStyles() {
        const cssId = 'analisis-preview-css';
        if (!document.getElementById(cssId)) {
            const link = document.createElement('link');
            link.id = cssId;
            link.rel = 'stylesheet';
            link.href = `${window.location.origin}/Pendataan-desa/pages/admin/analisis/analisis.css`;
            document.head.appendChild(link);
        }
        
        const detailCssId = 'detail-analisis-preview-css';
        if (!document.getElementById(detailCssId)) {
            const detailLink = document.createElement('link');
            detailLink.id = detailCssId;
            detailLink.rel = 'stylesheet';
            detailLink.href = `${window.location.origin}/Pendataan-desa/pages/admin/detail-analisis-2/detail-analisis.css`;
            document.head.appendChild(detailLink);
        }
    }
    
    function renderKependudukanPreview(data) {
        if (!data) return '';
        
        let html = '<div class="preview-card"><div class="preview-card-header"><h5>Statistik Kependudukan</h5></div><div class="preview-card-body">';
        
        Object.keys(data).forEach(desaName => {
            const stats = data[desaName];
            html += `
                <h6>${desaName}</h6>
                <div class="preview-stats-grid">
                    <div class="preview-stat-item">
                        <h6>Total Penduduk</h6>
                        <div class="preview-stat-value">${stats.total || 0}</div>
                    </div>
                    <div class="preview-stat-item">
                        <h6>Laki-laki</h6>
                        <div class="preview-stat-value">${stats.laki || 0}</div>
                    </div>
                    <div class="preview-stat-item">
                        <h6>Perempuan</h6>
                        <div class="preview-stat-value">${stats.perempuan || 0}</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div></div>';
        return html;
    }
    
    function renderAnalisisKependudukanPreview(data) {
        if (!data) return '';
        
        let html = '<div class="preview-card"><div class="preview-card-header"><h5>Analisis Kependudukan Lanjutan</h5></div><div class="preview-card-body">';
        
        html += `
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>Indikator</th>
                        <th>Jumlah</th>
                        <th>Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>KK Perempuan + Anak Sekolah</td>
                        <td>${data.kk_perempuan || 0} KK</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>Produktif Tanpa Kerja Tetap</td>
                        <td>${data.produktif_tanpa_kerja || 0} orang</td>
                        <td>${data.persen_produktif || 0}%</td>
                    </tr>
                    <tr>
                        <td>Rasio Ketergantungan</td>
                        <td>-</td>
                        <td>${data.rasio_ketergantungan || 0}%</td>
                    </tr>
                </tbody>
            </table>
        `;
        
        html += '</div></div>';
        return html;
    }
    
    function renderPrediksiPreview(data) {
        if (!data) return '';
        
        let html = '<div class="preview-card"><div class="preview-card-header"><h5>Prediksi Struktur Penduduk</h5></div><div class="preview-card-body">';
        
        html += '<div class="chart-preview">Chart: Prediksi Struktur Usia 5 Tahun ke Depan</div>';
        
        html += `
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>Tahun</th>
                        <th>Total Penduduk</th>
                        <th>Balita</th>
                        <th>Dewasa</th>
                        <th>Lansia</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        if (data.predictions) {
            data.predictions.slice(0, 5).forEach(pred => {
                html += `
                    <tr>
                        <td>${pred.tahun_prediksi}</td>
                        <td>${pred.total_penduduk_prediksi || 0}</td>
                        <td>${pred.total_balita_prediksi || 0}</td>
                        <td>${pred.total_dewasa_prediksi || 0}</td>
                        <td>${pred.total_lansia_prediksi || 0}</td>
                    </tr>
                `;
            });
        }
        
        html += '</tbody></table></div></div>';
        return html;
    }
    
    function renderPerbandinganPreview(data, comparison) {
        if (!data || !comparison) return '';
        
        let html = '<div class="preview-card"><div class="preview-card-header"><h5>Perbandingan Antar Desa</h5></div><div class="preview-card-body">';
        
        html += `
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>Indikator</th>
                        <th>${data.desa1_name || 'Desa 1'}</th>
                        <th>${data.desa2_name || 'Desa 2'}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total Penduduk</td>
                        <td>${data.desa1_stats?.total || 0} jiwa</td>
                        <td>${data.desa2_stats?.total || 0} jiwa</td>
                    </tr>
                    <tr>
                        <td>Laki-laki</td>
                        <td>${data.desa1_stats?.laki || 0} jiwa</td>
                        <td>${data.desa2_stats?.laki || 0} jiwa</td>
                    </tr>
                    <tr>
                        <td>Perempuan</td>
                        <td>${data.desa1_stats?.perempuan || 0} jiwa</td>
                        <td>${data.desa2_stats?.perempuan || 0} jiwa</td>
                    </tr>
                </tbody>
            </table>
        `;
        
        html += '</div></div>';
        return html;
    }
    
    function renderEkonomiPreview(data) {
        if (!data) return '';
        
        let html = '<div class="preview-card"><div class="preview-card-header"><h5>Distribusi Ekonomi</h5></div><div class="preview-card-body">';
        
        Object.keys(data).forEach(desaName => {
            const ekonomi = data[desaName];
            html += `
                <h6>${desaName}</h6>
                <div class="preview-stats-grid">
                    <div class="preview-stat-item">
                        <h6>Total Data Ekonomi</h6>
                        <div class="preview-stat-value">${ekonomi.length || 0}</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div></div>';
        return html;
    }
    
    function renderPendidikanPreview(data) {
        if (!data) return '';
        
        let html = '<div class="preview-card"><div class="preview-card-header"><h5>Fasilitas Pendidikan</h5></div><div class="preview-card-body">';
        
        Object.keys(data).forEach(desaName => {
            const pendidikan = data[desaName];
            html += `
                <h6>${desaName}</h6>
                <div class="preview-stats-grid">
                    <div class="preview-stat-item">
                        <h6>Total Fasilitas</h6>
                        <div class="preview-stat-value">${pendidikan.length || 0}</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div></div>';
        return html;
    }
    
    function renderInfrastrukturPreview(data) {
        if (!data) return '';
        
        let html = '<div class="preview-card"><div class="preview-card-header"><h5>Status Infrastruktur</h5></div><div class="preview-card-body">';
        
        Object.keys(data).forEach(desaName => {
            const infra = data[desaName];
            html += `
                <h6>${desaName}</h6>
                <div class="preview-stats-grid">
                    <div class="preview-stat-item">
                        <h6>Total Jalan</h6>
                        <div class="preview-stat-value">${infra.jalan?.length || 0}</div>
                    </div>
                    <div class="preview-stat-item">
                        <h6>Total Jembatan</h6>
                        <div class="preview-stat-value">${infra.jembatan?.length || 0}</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div></div>';
        return html;
    }
    
    function renderAnalisisEkonomiPreview(data) {
        if (!data) return '';
        
        let html = '<div class="preview-card"><div class="preview-card-header"><h5>Analisis Ekonomi Lanjutan</h5></div><div class="preview-card-body">';
        
        html += `
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>Indikator</th>
                        <th>Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Petani Lahan < 0.5 Ha</td>
                        <td>${data.petani_lahan_kecil || 0} orang</td>
                    </tr>
                    <tr>
                        <td>Pendapatan Pertanian</td>
                        <td>${data.persen_pertanian || 0}%</td>
                    </tr>
                    <tr>
                        <td>Indeks Gini</td>
                        <td>${data.indeks_gini || 0}</td>
                    </tr>
                </tbody>
            </table>
        `;
        
        html += '</div></div>';
        return html;
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease-out;
        `;
        
        switch (type) {
            case 'success':
                notification.style.backgroundColor = '#28a745';
                break;
            case 'error':
                notification.style.backgroundColor = '#dc3545';
                break;
            case 'warning':
                notification.style.backgroundColor = '#ffc107';
                notification.style.color = '#212529';
                break;
            default:
                notification.style.backgroundColor = '#3F72AF';
        }
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
    
    // Save form state before page unload
    window.addEventListener('beforeunload', () => {
        if (window.laporanAdminInstance) {
            window.laporanAdminInstance.saveFormState();
        }
    });
    
    // Chart initialization for preview
    window.initPreviewCharts = function() {
        // Load Chart.js if not already loaded
        if (typeof Chart === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = function() {
                setTimeout(createCharts, 500);
            };
            document.head.appendChild(script);
        } else {
            createCharts();
        }
        
        function createCharts() {
            // Line Chart
            const lineData = document.getElementById('chartLineData');
            if (lineData) {
                const years = JSON.parse(lineData.dataset.years || '[]');
                const values = JSON.parse(lineData.dataset.values || '[]');
                const ctx = document.getElementById('chartPrediksiLine');
                
                if (ctx && years.length > 0) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: years,
                            datasets: [{
                                label: 'Total Penduduk',
                                data: values,
                                borderColor: '#3F72AF',
                                backgroundColor: 'rgba(63, 114, 175, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                }
            }
            
            // Bar Chart
            const barData = document.getElementById('chartBarData');
            if (barData) {
                const labels = JSON.parse(barData.dataset.labels || '[]');
                const values = JSON.parse(barData.dataset.values || '[]');
                const ctx = document.getElementById('chartPrediksiBar');
                
                if (ctx && labels.length > 0) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Jumlah Penduduk',
                                data: values,
                                backgroundColor: ['#112D4E', '#3F72AF', '#DBE2EF', '#F9F7F7', '#112D4E']
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                }
            }
        }
    };
    
    // Initialize laporan admin
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.laporanAdminInstance) {
            window.laporanAdminInstance = new LaporanAdmin();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.laporanAdminInstance) {
            window.laporanAdminInstance = new LaporanAdmin();
        }
    }
})();