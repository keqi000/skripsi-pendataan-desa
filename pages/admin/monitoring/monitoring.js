// Monitoring Admin JavaScript
(function() {
    'use strict';
    
    class MonitoringAdmin {
    constructor() {
        if (document.querySelector('.monitoring-admin-container')) {
            this.init();
        }
    }
    
    init() {
        this.setupFilters();
        this.setupRealTimeUpdates();
    }
    
    setupFilters() {
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                this.filterByStatus(statusFilter.value);
            });
        }
    }
    
    filterByStatus(status) {
        const rows = document.querySelectorAll('#monitoringTable tbody tr');
        
        rows.forEach(row => {
            if (!status) {
                row.style.display = '';
                return;
            }
            
            const badge = row.querySelector('.badge');
            const badgeText = badge ? badge.textContent.toLowerCase() : '';
            
            let shouldShow = false;
            switch (status) {
                case 'complete':
                    shouldShow = badgeText.includes('lengkap');
                    break;
                case 'incomplete':
                    shouldShow = badgeText.includes('tidak lengkap');
                    break;
                case 'outdated':
                    shouldShow = badgeText.includes('perlu update');
                    break;
            }
            
            row.style.display = shouldShow ? '' : 'none';
        });
    }
    
    setupRealTimeUpdates() {
        // Update data every 5 minutes
        setInterval(() => {
            this.updateMonitoringData();
        }, 300000);
    }
    
    async updateMonitoringData() {
        try {
            if (typeof showLoader === 'function') showLoader('.monitoring-admin-content');
            
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            if (typeof showNotification === 'function') {
                showNotification('Data monitoring berhasil diperbarui', 'success');
            }
            
        } catch (error) {
            console.error('Failed to update monitoring data:', error);
            if (typeof showNotification === 'function') {
                showNotification('Gagal memperbarui data monitoring', 'error');
            }
        } finally {
            if (typeof hideLoader === 'function') hideLoader();
        }
    }
}

// Global functions for monitoring
    window.switchMonitoringTab = function(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.monitoring-admin-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Remove active class from all tab buttons
        document.querySelectorAll('.monitoring-admin-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab content
        document.getElementById(tabName).classList.add('active');
        
        // Add active class to clicked button
        event.target.classList.add('active');
    };
    
    window.refreshAllData = function() {
        if (window.monitoringAdminInstance) {
            window.monitoringAdminInstance.updateMonitoringData();
        }
    };
    
    window.filterByStatus = function() {
        const statusFilter = document.getElementById('statusFilter');
        if (window.monitoringAdminInstance && statusFilter) {
            window.monitoringAdminInstance.filterByStatus(statusFilter.value);
        }
    };
    
    window.showDesaModal = async function(desaId, desaName) {
        const modal = document.getElementById('desaDetailModal');
        const modalTitle = modal.querySelector('.modal-title');
        const modalContent = document.getElementById('desaDetailContent');
        
        modalTitle.textContent = `Detail Data ${desaName}`;
        
        // Show modal (vanilla JS)
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        
        try {
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
            
            // Render modal content
            modalContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6><i class="fas fa-users"></i> Data Kependudukan</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Total Penduduk:</strong> ${pendudukData.length || 0} orang</p>
                                <p><strong>Laki-laki:</strong> ${pendudukData.filter(p => p.jenis_kelamin === 'L').length || 0} orang</p>
                                <p><strong>Perempuan:</strong> ${pendudukData.filter(p => p.jenis_kelamin === 'P').length || 0} orang</p>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6><i class="fas fa-chart-line"></i> Data Ekonomi</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Total Data Ekonomi:</strong> ${ekonomiData.length || 0} data</p>
                                <p><strong>Jenis Usaha:</strong> ${[...new Set(ekonomiData.map(e => e.jenis_data))].join(', ') || 'Belum ada data'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6><i class="fas fa-school"></i> Data Pendidikan</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Total Fasilitas:</strong> ${pendidikanData.length || 0} fasilitas</p>
                                <p><strong>PAUD/TK:</strong> ${pendidikanData.filter(p => ['PAUD', 'TK'].includes(p.jenis_pendidikan)).length || 0}</p>
                                <p><strong>SD/SMP/SMA:</strong> ${pendidikanData.filter(p => ['SD', 'SMP', 'SMA'].includes(p.jenis_pendidikan)).length || 0}</p>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6><i class="fas fa-road"></i> Data Infrastruktur</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Total Jalan:</strong> ${infraData.jalan?.length || 0} jalan</p>
                                <p><strong>Kondisi Baik:</strong> ${infraData.jalan?.filter(j => j.kondisi_jalan === 'baik').length || 0}</p>
                                <p><strong>Total Jembatan:</strong> ${infraData.jembatan?.length || 0} jembatan</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
        } catch (error) {
            console.error('Error loading desa detail:', error);
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Gagal memuat detail data desa. Silakan coba lagi.
                </div>
            `;
        }
    };
    
    window.closeDesaModal = function() {
        const modal = document.getElementById('desaDetailModal');
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    };
    
    // Global functions for comparison
    window.updateComparison = function() {
        const desa1 = document.getElementById('desa1Select').value;
        const desa2 = document.getElementById('desa2Select').value;
        
        if (desa1 && desa2 && desa1 !== desa2) {
            document.getElementById('comparisonResult').style.display = 'block';
            loadComparisonData(desa1, desa2);
        } else {
            document.getElementById('comparisonResult').style.display = 'none';
        }
    };
    
    window.showComparisonTab = function(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-pane').forEach(tab => {
            tab.style.display = 'none';
            tab.classList.remove('show', 'active');
        });
        
        // Remove active class from all tab buttons
        document.querySelectorAll('.comparison-tabs .monitoring-admin-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName).style.display = 'block';
        document.getElementById(tabName).classList.add('show', 'active');
        
        // Add active class to clicked button
        event.target.classList.add('active');
    };
    
    async function loadComparisonData(desa1Id, desa2Id) {
        try {
            const [desa1Data, desa2Data] = await Promise.all([
                fetchDesaData(desa1Id),
                fetchDesaData(desa2Id)
            ]);
            
            renderDataUmum(desa1Data, desa2Data);
            renderDemografis(desa1Data, desa2Data);
            renderPendidikan(desa1Data, desa2Data);
            renderEkonomi(desa1Data, desa2Data);
            renderInfrastruktur(desa1Data, desa2Data);
            
        } catch (error) {
            console.error('Error loading comparison data:', error);
        }
    }
    
    async function fetchDesaData(desaId) {
        const [desa, penduduk, fasilitas, ekonomi, jalan, mataPencaharian] = await Promise.all([
            fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/desa/${desaId}`).then(r => r.json()),
            fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/penduduk/${desaId}`).then(r => r.json()),
            fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/pendidikan/${desaId}`).then(r => r.json()),
            fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/ekonomi/${desaId}`).then(r => r.json()),
            fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/infrastruktur/${desaId}`).then(r => r.json()),
            fetch(`${window.BASE_URL || '/Pendataan-desa/'}api/mata-pencaharian/${desaId}`).then(r => r.json())
        ]);
        
        return { desa, penduduk, fasilitas, ekonomi, jalan: jalan.jalan, jembatan: jalan.jembatan, mataPencaharian };
    }
    
    function renderDataUmum(data1, data2) {
        const totalKK1 = [...new Set(data1.penduduk.map(p => p.id_keluarga))].length;
        const totalKK2 = [...new Set(data2.penduduk.map(p => p.id_keluarga))].length;
        const rataKK1 = totalKK1 > 0 ? (data1.penduduk.length / totalKK1).toFixed(2) : 0;
        const rataKK2 = totalKK2 > 0 ? (data2.penduduk.length / totalKK2).toFixed(2) : 0;
        
        const laki1 = data1.penduduk.filter(p => p.jenis_kelamin === 'L').length;
        const perempuan1 = data1.penduduk.filter(p => p.jenis_kelamin === 'P').length;
        const rasio1 = perempuan1 > 0 ? (laki1 / perempuan1).toFixed(2) : 0;
        
        const laki2 = data2.penduduk.filter(p => p.jenis_kelamin === 'L').length;
        const perempuan2 = data2.penduduk.filter(p => p.jenis_kelamin === 'P').length;
        const rasio2 = perempuan2 > 0 ? (laki2 / perempuan2).toFixed(2) : 0;
        
        const content = `
            <table>
                <thead>
                    <tr>
                        <th>Indikator</th>
                        <th>${data1.desa.nama_desa}</th>
                        <th>${data2.desa.nama_desa}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Luas Wilayah (km²)</td>
                        <td>${data1.desa.luas_wilayah || 0}</td>
                        <td>${data2.desa.luas_wilayah || 0}</td>
                    </tr>
                    <tr>
                        <td>Jumlah Dusun</td>
                        <td>${data1.desa.jumlah_dusun || 0}</td>
                        <td>${data2.desa.jumlah_dusun || 0}</td>
                    </tr>
                    <tr>
                        <td>Jumlah RW</td>
                        <td>${data1.desa.jumlah_rw || 0}</td>
                        <td>${data2.desa.jumlah_rw || 0}</td>
                    </tr>
                    <tr>
                        <td>Jumlah RT</td>
                        <td>${data1.desa.jumlah_rt || 0}</td>
                        <td>${data2.desa.jumlah_rt || 0}</td>
                    </tr>
                    <tr>
                        <td>Total Penduduk</td>
                        <td>${data1.penduduk.length} jiwa</td>
                        <td>${data2.penduduk.length} jiwa</td>
                    </tr>
                    <tr>
                        <td>Total KK</td>
                        <td>${totalKK1} KK</td>
                        <td>${totalKK2} KK</td>
                    </tr>
                    <tr>
                        <td>Rata-rata per KK</td>
                        <td>${rataKK1} jiwa</td>
                        <td>${rataKK2} jiwa</td>
                    </tr>
                    <tr>
                        <td>Rasio Jenis Kelamin</td>
                        <td>${rasio1}</td>
                        <td>${rasio2}</td>
                    </tr>
                </tbody>
            </table>
        `;
        document.getElementById('dataUmumContent').innerHTML = content;
    }
    
    function renderDemografis(data1, data2) {
        // Get unique agama from both datasets
        const allAgama1 = [...new Set(data1.penduduk.filter(p => p.agama).map(p => p.agama))];
        const allAgama2 = [...new Set(data2.penduduk.filter(p => p.agama).map(p => p.agama))];
        const uniqueAgama = [...new Set([...allAgama1, ...allAgama2])].sort();
        
        let agamaRows = '';
        uniqueAgama.forEach(agama => {
            const count1 = data1.penduduk.filter(p => p.agama === agama).length;
            const count2 = data2.penduduk.filter(p => p.agama === agama).length;
            const pct1 = data1.penduduk.length > 0 ? ((count1 / data1.penduduk.length) * 100).toFixed(1) : 0;
            const pct2 = data2.penduduk.length > 0 ? ((count2 / data2.penduduk.length) * 100).toFixed(1) : 0;
            
            if (count1 > 0 || count2 > 0) {
                agamaRows += `
                    <tr>
                        <td>${agama}</td>
                        <td>${count1} (${pct1}%)</td>
                        <td>${count2} (${pct2}%)</td>
                    </tr>
                `;
            }
        });
        
        // Get unique status pernikahan from both datasets
        const allStatus1 = [...new Set(data1.penduduk.filter(p => p.status_pernikahan).map(p => p.status_pernikahan))];
        const allStatus2 = [...new Set(data2.penduduk.filter(p => p.status_pernikahan).map(p => p.status_pernikahan))];
        const uniqueStatus = [...new Set([...allStatus1, ...allStatus2])].sort();
        
        let statusRows = '';
        uniqueStatus.forEach(status => {
            const count1 = data1.penduduk.filter(p => p.status_pernikahan === status).length;
            const count2 = data2.penduduk.filter(p => p.status_pernikahan === status).length;
            const pct1 = data1.penduduk.length > 0 ? ((count1 / data1.penduduk.length) * 100).toFixed(1) : 0;
            const pct2 = data2.penduduk.length > 0 ? ((count2 / data2.penduduk.length) * 100).toFixed(1) : 0;
            
            if (count1 > 0 || count2 > 0) {
                statusRows += `
                    <tr>
                        <td>${status}</td>
                        <td>${count1} (${pct1}%)</td>
                        <td>${count2} (${pct2}%)</td>
                    </tr>
                `;
            }
        });
        
        const content = `
            <h5>Struktur Usia Penduduk</h5>
            <table>
                <thead>
                    <tr>
                        <th>Kelompok Usia</th>
                        <th>${data1.desa.nama_desa}</th>
                        <th>${data2.desa.nama_desa}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>0-14 tahun</td>
                        <td>${data1.penduduk.filter(p => p.usia <= 14).length} (${((data1.penduduk.filter(p => p.usia <= 14).length / data1.penduduk.length) * 100).toFixed(1)}%)</td>
                        <td>${data2.penduduk.filter(p => p.usia <= 14).length} (${((data2.penduduk.filter(p => p.usia <= 14).length / data2.penduduk.length) * 100).toFixed(1)}%)</td>
                    </tr>
                    <tr>
                        <td>15-64 tahun</td>
                        <td>${data1.penduduk.filter(p => p.usia >= 15 && p.usia <= 64).length} (${((data1.penduduk.filter(p => p.usia >= 15 && p.usia <= 64).length / data1.penduduk.length) * 100).toFixed(1)}%)</td>
                        <td>${data2.penduduk.filter(p => p.usia >= 15 && p.usia <= 64).length} (${((data2.penduduk.filter(p => p.usia >= 15 && p.usia <= 64).length / data2.penduduk.length) * 100).toFixed(1)}%)</td>
                    </tr>
                    <tr>
                        <td>65+ tahun</td>
                        <td>${data1.penduduk.filter(p => p.usia >= 65).length} (${((data1.penduduk.filter(p => p.usia >= 65).length / data1.penduduk.length) * 100).toFixed(1)}%)</td>
                        <td>${data2.penduduk.filter(p => p.usia >= 65).length} (${((data2.penduduk.filter(p => p.usia >= 65).length / data2.penduduk.length) * 100).toFixed(1)}%)</td>
                    </tr>
                </tbody>
            </table>
            
            <h5>Komposisi Agama</h5>
            <table>
                <thead>
                    <tr>
                        <th>Agama</th>
                        <th>${data1.desa.nama_desa}</th>
                        <th>${data2.desa.nama_desa}</th>
                    </tr>
                </thead>
                <tbody>
                    ${agamaRows || '<tr><td colspan="3">Tidak ada data agama</td></tr>'}
                </tbody>
            </table>
            
            <h5>Status Pernikahan</h5>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>${data1.desa.nama_desa}</th>
                        <th>${data2.desa.nama_desa}</th>
                    </tr>
                </thead>
                <tbody>
                    ${statusRows || '<tr><td colspan="3">Tidak ada data status pernikahan</td></tr>'}
                </tbody>
            </table>
        `;
        document.getElementById('demografisContent').innerHTML = content;
    }
    
    function renderPendidikan(data1, data2) {
        // Get unique pendidikan levels from both datasets
        const allPendidikan1 = [...new Set(data1.penduduk.filter(p => p.pendidikan_terakhir).map(p => p.pendidikan_terakhir))];
        const allPendidikan2 = [...new Set(data2.penduduk.filter(p => p.pendidikan_terakhir).map(p => p.pendidikan_terakhir))];
        const uniquePendidikan = [...new Set([...allPendidikan1, ...allPendidikan2])].sort();
        
        let pendidikanRows = '';
        uniquePendidikan.forEach(tingkat => {
            const count1 = data1.penduduk.filter(p => p.pendidikan_terakhir === tingkat).length;
            const count2 = data2.penduduk.filter(p => p.pendidikan_terakhir === tingkat).length;
            const pct1 = data1.penduduk.length > 0 ? ((count1 / data1.penduduk.length) * 100).toFixed(1) : 0;
            const pct2 = data2.penduduk.length > 0 ? ((count2 / data2.penduduk.length) * 100).toFixed(1) : 0;
            
            let label = tingkat;
            if (tingkat === 'D3') label = 'Tamat Diploma';
            else if (tingkat === 'S1') label = 'Tamat S1';
            else if (tingkat === 'S2') label = 'Tamat S2';
            else if (tingkat === 'S3') label = 'Tamat S3';
            
            if (count1 > 0 || count2 > 0) {
                pendidikanRows += `
                    <tr>
                        <td>${label}</td>
                        <td>${count1} (${pct1}%)</td>
                        <td>${count2} (${pct2}%)</td>
                    </tr>
                `;
            }
        });
        
        // Get unique fasilitas types from both datasets
        const allFasilitas1 = [...new Set(data1.fasilitas.map(f => f.jenis_pendidikan))];
        const allFasilitas2 = [...new Set(data2.fasilitas.map(f => f.jenis_pendidikan))];
        const uniqueFasilitas = [...new Set([...allFasilitas1, ...allFasilitas2])].sort();
        
        let fasilitasRows = '';
        uniqueFasilitas.forEach(jenis => {
            const count1 = data1.fasilitas.filter(f => f.jenis_pendidikan === jenis).length;
            const count2 = data2.fasilitas.filter(f => f.jenis_pendidikan === jenis).length;
            
            let label = jenis;
            if (jenis === 'SD') label = 'SD/MI';
            else if (jenis === 'SMP') label = 'SMP/MTs';
            else if (jenis === 'SMA') label = 'SMA/MA';
            
            fasilitasRows += `
                <tr>
                    <td>${label}</td>
                    <td>${count1} unit</td>
                    <td>${count2} unit</td>
                </tr>
            `;
        });
        
        const content = `
            <h5>Tingkat Pendidikan Penduduk</h5>
            <table>
                <thead>
                    <tr>
                        <th>Tingkat Pendidikan</th>
                        <th>${data1.desa.nama_desa}</th>
                        <th>${data2.desa.nama_desa}</th>
                    </tr>
                </thead>
                <tbody>
                    ${pendidikanRows || '<tr><td colspan="3">Tidak ada data pendidikan</td></tr>'}
                </tbody>
            </table>
            
            <h5>Fasilitas Pendidikan</h5>
            <table>
                <thead>
                    <tr>
                        <th>Jenis Fasilitas</th>
                        <th>${data1.desa.nama_desa}</th>
                        <th>${data2.desa.nama_desa}</th>
                    </tr>
                </thead>
                <tbody>
                    ${fasilitasRows || '<tr><td colspan="3">Tidak ada fasilitas pendidikan</td></tr>'}
                </tbody>
            </table>
        `;
        document.getElementById('pendidikanContent').innerHTML = content;
    }
    
    function renderEkonomi(data1, data2) {
        // Get unique pekerjaan from mata_pencaharian
        const allPekerjaan1 = [...new Set(data1.mataPencaharian.map(mp => mp.jenis_pekerjaan))];
        const allPekerjaan2 = [...new Set(data2.mataPencaharian.map(mp => mp.jenis_pekerjaan))];
        const uniquePekerjaan = [...new Set([...allPekerjaan1, ...allPekerjaan2])].sort();
        
        let pekerjaanRows = '';
        uniquePekerjaan.forEach(pekerjaan => {
            const count1 = data1.mataPencaharian.filter(mp => mp.jenis_pekerjaan === pekerjaan).length;
            const count2 = data2.mataPencaharian.filter(mp => mp.jenis_pekerjaan === pekerjaan).length;
            const pct1 = data1.penduduk.length > 0 ? ((count1 / data1.penduduk.length) * 100).toFixed(1) : 0;
            const pct2 = data2.penduduk.length > 0 ? ((count2 / data2.penduduk.length) * 100).toFixed(1) : 0;
            
            if (count1 > 0 || count2 > 0) {
                pekerjaanRows += `
                    <tr>
                        <td>${pekerjaan}</td>
                        <td>${count1} (${pct1}%)</td>
                        <td>${count2} (${pct2}%)</td>
                    </tr>
                `;
            }
        });
        
        // Get economic data dynamically
        const ekonomi1ByType = {};
        const ekonomi2ByType = {};
        
        data1.ekonomi.forEach(e => {
            if (!ekonomi1ByType[e.jenis_data]) ekonomi1ByType[e.jenis_data] = 0;
            ekonomi1ByType[e.jenis_data]++;
        });
        
        data2.ekonomi.forEach(e => {
            if (!ekonomi2ByType[e.jenis_data]) ekonomi2ByType[e.jenis_data] = 0;
            ekonomi2ByType[e.jenis_data]++;
        });
        
        const allEkonomiTypes = [...new Set([...Object.keys(ekonomi1ByType), ...Object.keys(ekonomi2ByType)])].sort();
        
        let ekonomiRows = '';
        allEkonomiTypes.forEach(type => {
            const count1 = ekonomi1ByType[type] || 0;
            const count2 = ekonomi2ByType[type] || 0;
            
            let label = type.charAt(0).toUpperCase() + type.slice(1);
            if (type === 'umkm') label = 'UMKM';
            
            ekonomiRows += `
                <tr>
                    <td>${label}</td>
                    <td>${count1} unit</td>
                    <td>${count2} unit</td>
                </tr>
            `;
        });
        
        const content = `
            <h5>Mata Pencaharian Penduduk</h5>
            <table>
                <thead>
                    <tr>
                        <th>Jenis Pekerjaan</th>
                        <th>${data1.desa.nama_desa}</th>
                        <th>${data2.desa.nama_desa}</th>
                    </tr>
                </thead>
                <tbody>
                    ${pekerjaanRows || '<tr><td colspan="3">Tidak ada data pekerjaan</td></tr>'}
                </tbody>
            </table>
            
            <h5>Potensi Ekonomi Desa</h5>
            <table>
                <thead>
                    <tr>
                        <th>Indikator</th>
                        <th>${data1.desa.nama_desa}</th>
                        <th>${data2.desa.nama_desa}</th>
                    </tr>
                </thead>
                <tbody>
                    ${ekonomiRows || '<tr><td colspan="3">Tidak ada data ekonomi</td></tr>'}
                </tbody>
            </table>
        `;
        document.getElementById('ekonomiContent').innerHTML = content;
    }
    
    function renderInfrastruktur(data1, data2) {
        const jalan1Baik = data1.jalan.filter(j => j.kondisi_jalan === 'baik').length;
        const jalan1Sedang = data1.jalan.filter(j => j.kondisi_jalan === 'sedang').length;
        const jalan1Rusak = data1.jalan.filter(j => j.kondisi_jalan === 'rusak').length;
        
        const jalan2Baik = data2.jalan.filter(j => j.kondisi_jalan === 'baik').length;
        const jalan2Sedang = data2.jalan.filter(j => j.kondisi_jalan === 'sedang').length;
        const jalan2Rusak = data2.jalan.filter(j => j.kondisi_jalan === 'rusak').length;
        
        const content = `
            <h5>Kondisi Infrastruktur Jalan</h5>
            <table>
                <thead>
                    <tr>
                        <th>Kondisi Jalan</th>
                        <th>${data1.desa.nama_desa}</th>
                        <th>${data2.desa.nama_desa}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Jalan Baik</td>
                        <td>${jalan1Baik} unit (${data1.jalan.length > 0 ? ((jalan1Baik/data1.jalan.length)*100).toFixed(1) : 0}%)</td>
                        <td>${jalan2Baik} unit (${data2.jalan.length > 0 ? ((jalan2Baik/data2.jalan.length)*100).toFixed(1) : 0}%)</td>
                    </tr>
                    <tr>
                        <td>Jalan Sedang</td>
                        <td>${jalan1Sedang} unit (${data1.jalan.length > 0 ? ((jalan1Sedang/data1.jalan.length)*100).toFixed(1) : 0}%)</td>
                        <td>${jalan2Sedang} unit (${data2.jalan.length > 0 ? ((jalan2Sedang/data2.jalan.length)*100).toFixed(1) : 0}%)</td>
                    </tr>
                    <tr>
                        <td>Jalan Rusak</td>
                        <td>${jalan1Rusak} unit (${data1.jalan.length > 0 ? ((jalan1Rusak/data1.jalan.length)*100).toFixed(1) : 0}%)</td>
                        <td>${jalan2Rusak} unit (${data2.jalan.length > 0 ? ((jalan2Rusak/data2.jalan.length)*100).toFixed(1) : 0}%)</td>
                    </tr>
                    <tr>
                        <td>Total Jalan</td>
                        <td>${data1.jalan.length} unit</td>
                        <td>${data2.jalan.length} unit</td>
                    </tr>
                </tbody>
            </table>
            
            <h5>Kondisi Infrastruktur Jembatan</h5>
            <table>
                <thead>
                    <tr>
                        <th>Kondisi Jembatan</th>
                        <th>${data1.desa.nama_desa}</th>
                        <th>${data2.desa.nama_desa}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Jembatan Baik</td>
                        <td>${data1.jembatan.filter(j => j.kondisi_jembatan === 'baik').length} unit</td>
                        <td>${data2.jembatan.filter(j => j.kondisi_jembatan === 'baik').length} unit</td>
                    </tr>
                    <tr>
                        <td>Jembatan Rusak</td>
                        <td>${data1.jembatan.filter(j => j.kondisi_jembatan === 'rusak').length} unit</td>
                        <td>${data2.jembatan.filter(j => j.kondisi_jembatan === 'rusak').length} unit</td>
                    </tr>
                    <tr>
                        <td>Total Jembatan</td>
                        <td>${data1.jembatan.length} unit</td>
                        <td>${data2.jembatan.length} unit</td>
                    </tr>
                </tbody>
            </table>
        `;
        document.getElementById('infrastrukturContent').innerHTML = content;
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

    // Initialize monitoring admin
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.monitoringAdminInstance) {
            window.monitoringAdminInstance = new MonitoringAdmin();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.monitoringAdminInstance) {
            window.monitoringAdminInstance = new MonitoringAdmin();
        }
    }
})();