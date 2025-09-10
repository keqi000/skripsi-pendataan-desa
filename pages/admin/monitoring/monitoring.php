<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$queries = new Queries();
$all_desa = $queries->getAllDesa();

// Check if this is AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $page_title = 'Monitoring Desa';
    $current_page = 'monitoring';
    $page_css = ['monitoring.css'];
    $page_js = ['monitoring.js'];
    
    ob_start();
}
?>

<div class="monitoring-admin-container">
    <div class="monitoring-admin-header">
        <div class="monitoring-admin-title">
            <h2>Monitoring Data Desa</h2>
            <p>Pantau status kelengkapan dan kualitas data dari semua desa</p>
        </div>
        <div class="monitoring-admin-actions">
            <button class="btn btn-primary" onclick="refreshAllData()">
                <i class="fas fa-sync-alt"></i>
                Refresh Data
            </button>
        </div>
    </div>

    <?php
    // Calculate monitoring statistics
    $data_lengkap = 0;
    $perlu_update = 0;
    $data_kosong = 0;
    
    foreach ($all_desa as $desa) {
        $id_desa = $desa['id_desa'];
        $stats_penduduk = $queries->getStatistikPenduduk($id_desa);
        $fasilitas = $queries->getFasilitasPendidikan($id_desa);
        $ekonomi = $queries->getDataEkonomi($id_desa);
        
        $total_data = ($stats_penduduk['total'] ?? 0) + count($fasilitas) + count($ekonomi);
        
        if ($total_data == 0) {
            $data_kosong++;
        } elseif ($total_data > 0 && count($fasilitas) > 0 && count($ekonomi) > 0) {
            $data_lengkap++;
        } else {
            $perlu_update++;
        }
    }
    ?>
    
    <div class="monitoring-admin-stats">
        <div class="monitoring-admin-stat-card">
            <div class="monitoring-admin-stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="monitoring-admin-stat-content">
                <div class="monitoring-admin-stat-number"><?php echo $data_lengkap; ?></div>
                <div class="monitoring-admin-stat-label">Data Lengkap</div>
            </div>
        </div>
        
        <div class="monitoring-admin-stat-card">
            <div class="monitoring-admin-stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="monitoring-admin-stat-content">
                <div class="monitoring-admin-stat-number"><?php echo $perlu_update; ?></div>
                <div class="monitoring-admin-stat-label">Perlu Update</div>
            </div>
        </div>
        
        <div class="monitoring-admin-stat-card">
            <div class="monitoring-admin-stat-icon danger">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="monitoring-admin-stat-content">
                <div class="monitoring-admin-stat-number"><?php echo $data_kosong; ?></div>
                <div class="monitoring-admin-stat-label">Data Kosong</div>
            </div>
        </div>
    </div>

    <div class="monitoring-admin-tabs">
        <div class="monitoring-admin-tab-nav">
            <button class="monitoring-admin-tab-btn active" onclick="switchMonitoringTab('status')">
                Status Data Per Desa
            </button>
            <button class="monitoring-admin-tab-btn" onclick="switchMonitoringTab('perbandingan')">
                Perbandingan Desa
            </button>
            <button class="monitoring-admin-tab-btn" onclick="switchMonitoringTab('quality')">
                Quality Check
            </button>
        </div>
    </div>

    <div class="monitoring-admin-content">
        <!-- Status Data Tab -->
        <div class="monitoring-admin-tab-content active" id="status">
            <div class="card">
                <div class="card-header">
                    <h3>Status Data Per Desa</h3>
                    <div class="monitoring-admin-filters">
                        <select class="form-control" id="statusFilter" onchange="filterByStatus()">
                            <option value="">Semua Status</option>
                            <option value="complete">Lengkap</option>
                            <option value="incomplete">Tidak Lengkap</option>
                            <option value="outdated">Perlu Update</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="monitoringTable">
                            <thead>
                                <tr>
                                    <th>Nama Desa</th>
                                    <th>Data Kependudukan</th>
                                    <th>Data Ekonomi</th>
                                    <th>Data Pendidikan</th>
                                    <th>Data Infrastruktur</th>
                                    <th>Last Update</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_desa)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="no-data">
                                            <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                            <h5 class="text-muted">Tidak Ada Data</h5>
                                            <p class="text-muted">Belum ada data desa yang tersedia</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($all_desa as $desa): 
                                        $id_desa = $desa['id_desa'];
                                        $stats = $queries->getStatistikPenduduk($id_desa);
                                        $fasilitas = $queries->getFasilitasPendidikan($id_desa);
                                        $ekonomi = $queries->getDataEkonomi($id_desa);
                                        $jalan = $queries->getInfrastrukturJalan($id_desa);
                                        
                                        $total_penduduk = $stats['total'] ?? 0;
                                        $total_fasilitas = count($fasilitas);
                                        $total_ekonomi = count($ekonomi);
                                        $total_jalan = count($jalan);
                                        
                                        // Determine status
                                        $status_class = 'badge-warning';
                                        $status_text = 'Belum Ada Data';
                                        
                                        if ($total_penduduk > 0 && $total_fasilitas > 0 && $total_ekonomi > 0) {
                                            $status_class = 'badge-success';
                                            $status_text = 'Lengkap';
                                        } elseif ($total_penduduk > 0 || $total_fasilitas > 0 || $total_ekonomi > 0) {
                                            $status_class = 'badge-warning';
                                            $status_text = 'Tidak Lengkap';
                                        }
                                    ?>
                                    <tr data-desa="<?php echo $desa['id_desa']; ?>">
                                        <td><strong><?php echo $desa['nama_desa']; ?></strong></td>
                                        <td><?php echo $total_penduduk; ?> orang</td>
                                        <td><?php echo $total_ekonomi; ?> data</td>
                                        <td><?php echo $total_fasilitas; ?> fasilitas</td>
                                        <td><?php echo $total_jalan; ?> jalan</td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($desa['created_at'])); ?></td>
                                        <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                        <td>
                                            <button class="monitoring-btn-view" onclick="navigateToDetailDesa(<?php echo $desa['id_desa']; ?>, '<?php echo $desa['nama_desa']; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Perbandingan Tab -->
        <div class="monitoring-admin-tab-content" id="perbandingan">
            <!-- Gambaran Umum -->
            <div class="card monitoring-card-spacing">
                <div class="card-header">
                    <h3>Gambaran Umum Pembangunan Desa</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Desa</th>
                                    <th>Rasio Produktif</th>
                                    <th>Rasio Ekonomi</th>
                                    <th>Rasio Pendidikan</th>
                                    <th>Rasio Jalan</th>
                                    <th>Rasio Jembatan</th>
                                    <th>Skor Pembangunan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_desa)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="no-data">
                                            <i class="fas fa-chart-bar fa-3x mb-3 text-muted"></i>
                                            <h5 class="text-muted">Tidak Ada Data</h5>
                                            <p class="text-muted">Belum ada data untuk dianalisis</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($all_desa as $desa): 
                                        $id_desa = $desa['id_desa'];
                                        $stats = $queries->getStatistikPenduduk($id_desa);
                                        $fasilitas = $queries->getFasilitasPendidikan($id_desa);
                                        $ekonomi = $queries->getDataEkonomi($id_desa);
                                        $jalan = $queries->getInfrastrukturJalan($id_desa);
                                        
                                        $total_penduduk = $stats['total'] ?? 0;
                                        $produktif = 0;
                                        
                                        foreach ($stats['kelompok_usia'] ?? [] as $kelompok) {
                                            if ($kelompok['kelompok_usia'] === 'Dewasa') {
                                                $produktif += $kelompok['jumlah'];
                                            }
                                        }
                                        
                                        $rasio_produktif = $total_penduduk > 0 ? round(($produktif / $total_penduduk) * 100, 1) : 0;
                                        
                                        $total_pelaku_ekonomi = $queries->getTotalPelakuEkonomiByDesa($id_desa);
                                        $penduduk_bekerja = $queries->getPendudukBekerja($id_desa);
                                        $belum_bekerja = $total_penduduk - $penduduk_bekerja;
                                        $total_basis_ekonomi = $total_pelaku_ekonomi + $belum_bekerja;
                                        $skor_ekonomi = $total_basis_ekonomi > 0 ? round(($total_pelaku_ekonomi / $total_basis_ekonomi) * 100, 1) : 0;
                                        
                                        $total_kapasitas = 0;
                                        foreach ($fasilitas as $fas) {
                                            $total_kapasitas += $fas['kapasitas_siswa'] ?? 0;
                                        }
                                        $usia_sekolah = $queries->getPendudukUsiaSekolah($id_desa);
                                        $skor_pendidikan = count($usia_sekolah) > 0 ? round(min($total_kapasitas / count($usia_sekolah), 1) * 100, 1) : 0;
                                        
                                        $skor_jalan_total = 0;
                                        foreach ($jalan as $j) {
                                            $skor_jalan_total += ($j['kondisi_jalan'] == 'baik') ? 3 : (($j['kondisi_jalan'] == 'sedang') ? 2 : 1);
                                        }
                                        $skor_jalan = count($jalan) > 0 ? round(($skor_jalan_total / (count($jalan) * 3)) * 100, 1) : 0;
                                        
                                        $jembatan = $queries->getInfrastrukturJembatan($id_desa);
                                        $skor_jembatan_total = 0;
                                        foreach ($jembatan as $jmb) {
                                            $skor_jembatan_total += ($jmb['kondisi_jembatan'] == 'baik') ? 3 : (($jmb['kondisi_jembatan'] == 'sedang') ? 2 : 1);
                                        }
                                        $skor_jembatan = count($jembatan) > 0 ? round(($skor_jembatan_total / (count($jembatan) * 3)) * 100, 1) : 0;
                                        
                                        $total_skor = $rasio_produktif + $skor_ekonomi + $skor_pendidikan + $skor_jalan + $skor_jembatan;
                                        $skor_pembangunan = round(($total_skor / 500) * 100, 1);
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $desa['nama_desa']; ?></strong></td>
                                        <td><?php echo $rasio_produktif; ?>%</td>
                                        <td><?php echo $skor_ekonomi; ?>%</td>
                                        <td><?php echo $skor_pendidikan; ?>%</td>
                                        <td><?php echo $skor_jalan; ?>%</td>
                                        <td><?php echo $skor_jembatan; ?>%</td>
                                        <td><?php echo $skor_pembangunan; ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Perbandingan Detail 2 Desa -->
            <div class="card">
                <div class="card-header">
                    <h3>Perbandingan Detail Antar Desa</h3>
                </div>
                <div class="card-body">
                    <div class="monitoring-row-spacing">
                        <div class="monitoring-col-5">
                            <label>Pilih Desa Pertama:</label>
                            <select class="form-control" id="desa1Select" onchange="updateComparison()">
                                <option value="">-- Pilih Desa --</option>
                                <?php foreach ($all_desa as $desa): ?>
                                <option value="<?php echo $desa['id_desa']; ?>"><?php echo $desa['nama_desa']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="monitoring-col-2 monitoring-text-center">
                            <label>&nbsp;</label>
                            <div class="monitoring-pt-2">
                                <i class="fas fa-exchange-alt fa-2x text-muted"></i>
                            </div>
                        </div>
                        <div class="monitoring-col-5">
                            <label>Pilih Desa Kedua:</label>
                            <select class="form-control" id="desa2Select" onchange="updateComparison()">
                                <option value="">-- Pilih Desa --</option>
                                <?php foreach ($all_desa as $desa): ?>
                                <option value="<?php echo $desa['id_desa']; ?>"><?php echo $desa['nama_desa']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="comparisonResult" style="display: none;">
                        <div class="comparison-tabs">
                            <div class="monitoring-admin-tab-nav">
                                <button class="monitoring-admin-tab-btn active" onclick="showComparisonTab('dataUmum')">Data Umum Desa</button>
                                <button class="monitoring-admin-tab-btn" onclick="showComparisonTab('demografis')">Demografis</button>
                                <button class="monitoring-admin-tab-btn" onclick="showComparisonTab('pendidikan')">Pendidikan</button>
                                <button class="monitoring-admin-tab-btn" onclick="showComparisonTab('ekonomi')">Ekonomi</button>
                                <button class="monitoring-admin-tab-btn" onclick="showComparisonTab('infrastruktur')">Infrastruktur</button>
                            </div>
                            
                            <div class="tab-content monitoring-mt-3">
                                <div class="tab-pane show active" id="dataUmum">
                                    <div id="dataUmumContent">Loading...</div>
                                </div>
                                <div class="tab-pane" id="demografis" style="display: none;">
                                    <div id="demografisContent">Loading...</div>
                                </div>
                                <div class="tab-pane" id="pendidikan" style="display: none;">
                                    <div id="pendidikanContent">Loading...</div>
                                </div>
                                <div class="tab-pane" id="ekonomi" style="display: none;">
                                    <div id="ekonomiContent">Loading...</div>
                                </div>
                                <div class="tab-pane" id="infrastruktur" style="display: none;">
                                    <div id="infrastrukturContent">Loading...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quality Check Tab -->
        <div class="monitoring-admin-tab-content" id="quality">
            <div class="card">
                <div class="card-header">
                    <h3>Data Quality Check</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Desa</th>
                                    <th>Data Penduduk</th>
                                    <th>Data Ekonomi</th>
                                    <th>Data Pendidikan</th>
                                    <th>Data Infrastruktur</th>
                                    <th>Konsistensi</th>
                                    <th>Status Quality</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_desa as $desa): 
                                    $id_desa = $desa['id_desa'];
                                    $stats = $queries->getStatistikPenduduk($id_desa);
                                    $fasilitas = $queries->getFasilitasPendidikan($id_desa);
                                    $ekonomi = $queries->getDataEkonomi($id_desa);
                                    $jalan = $queries->getInfrastrukturJalan($id_desa);
                                    $jembatan = $queries->getInfrastrukturJembatan($id_desa);
                                    $mataPencaharian = $queries->getMataPencaharianByDesa($id_desa);
                                    
                                    $total_penduduk = $stats['total'] ?? 0;
                                    $total_fasilitas = count($fasilitas);
                                    $total_ekonomi = count($ekonomi);
                                    $total_infrastruktur = count($jalan) + count($jembatan);
                                    
                                    // Check consistency
                                    $konsistensi_pekerjaan = $total_penduduk > 0 && count($mataPencaharian) > 0;
                                    
                                    // Calculate quality score
                                    $quality_score = 0;
                                    if ($total_penduduk > 0) $quality_score += 25;
                                    if ($total_ekonomi > 0) $quality_score += 25;
                                    if ($total_fasilitas > 0) $quality_score += 25;
                                    if ($total_infrastruktur > 0) $quality_score += 25;
                                    
                                    $quality_class = 'warning';
                                    $quality_text = 'Perlu Perhatian';
                                    if ($quality_score >= 75) {
                                        $quality_class = 'good';
                                        $quality_text = 'Baik';
                                    } elseif ($quality_score >= 50) {
                                        $quality_class = 'warning';
                                        $quality_text = 'Sedang';
                                    }
                                ?>
                                <tr>
                                    <td><strong><?php echo $desa['nama_desa']; ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo $total_penduduk > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $total_penduduk; ?> orang
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $total_ekonomi > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $total_ekonomi; ?> data
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $total_fasilitas > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $total_fasilitas; ?> fasilitas
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $total_infrastruktur > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $total_infrastruktur; ?> unit
                                        </span>
                                    </td>
                                    <td>
                                        <div class="quality-status <?php echo $konsistensi_pekerjaan ? 'good' : 'warning'; ?>">
                                            <i class="fas fa-<?php echo $konsistensi_pekerjaan ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                                            <span><?php echo $konsistensi_pekerjaan ? 'Konsisten' : 'Tidak Konsisten'; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="quality-status <?php echo $quality_class; ?>">
                                            <i class="fas fa-<?php echo $quality_class == 'good' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                                            <span><?php echo $quality_text; ?> (<?php echo $quality_score; ?>%)</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>