<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$queries = new Queries();
$desa_id = $_GET['desa'] ?? null;

if (!$desa_id) {
    header('Location: ' . BASE_URL . 'pages/admin/monitoring/monitoring.php');
    exit();
}

$desa_data = $queries->getDesaById($desa_id);
$stats_penduduk = $queries->getStatistikPenduduk($desa_id);
$penduduk_list = $queries->getPendudukByDesa($desa_id);
$fasilitas_pendidikan = $queries->getFasilitasPendidikan($desa_id);
$umkm_list = $queries->getUMKM($desa_id);
$pasar_list = $queries->getPasarByDesa($desa_id);
$jalan_list = $queries->getInfrastrukturJalan($desa_id);
$jembatan_list = $queries->getInfrastrukturJembatan($desa_id);

// Calculate statistics
$total_penduduk = $stats_penduduk['total'] ?? 0;

// Get total KK directly from keluarga table
$query_kk = "SELECT COUNT(*) as total_kk FROM keluarga WHERE id_desa = :id_desa";
$stmt = $queries->db->prepare($query_kk);
$stmt->bindParam(':id_desa', $desa_id);
$stmt->execute();
$total_kk = $stmt->fetch(PDO::FETCH_ASSOC)['total_kk'] ?? 0;

$rata_per_kk = $total_kk > 0 ? round($total_penduduk / $total_kk, 1) : 0;
// Convert hectares to km² (1 hectare = 0.01 km²) for proper density calculation
$luas_km2 = ($desa_data['luas_wilayah'] ?? 0) * 0.01;
$kepadatan = $luas_km2 > 0 ? round($total_penduduk / $luas_km2, 2) : 0;

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $page_title = 'Detail Desa ' . $desa_data['nama_desa'];
    $current_page = 'detail-desa';
    $page_css = ['detail-desa.css'];
    $page_js = ['detail-desa.js'];
    ob_start();
}
?>

<div class="detail-desa-container">
    <div class="detail-desa-header">
        <div class="detail-desa-breadcrumb">
            <a href="<?php echo BASE_URL; ?>pages/admin/monitoring/monitoring.php">← Kembali ke Monitoring</a>
        </div>
        <h2>Detail Data <?php echo $desa_data['nama_desa']; ?></h2>
    </div>

    <!-- Data Umum Desa -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-map-marker-alt"></i> Data Umum Desa</h3>
        </div>
        <div class="section-content">
            <div class="info-grid">
                <div class="info-item">
                    <label>Nama Desa:</label>
                    <span><?php echo $desa_data['nama_desa']; ?></span>
                </div>
                <div class="info-item">
                    <label>Kecamatan:</label>
                    <span><?php echo $desa_data['nama_kecamatan']; ?></span>
                </div>
                <div class="info-item">
                    <label>Kabupaten:</label>
                    <span><?php echo $desa_data['nama_kabupaten']; ?></span>
                </div>
                <div class="info-item">
                    <label>Provinsi:</label>
                    <span><?php echo $desa_data['nama_provinsi']; ?></span>
                </div>
                <div class="info-item">
                    <label>Luas Wilayah:</label>
                    <span><?php echo $desa_data['luas_wilayah']; ?> Ha</span>
                </div>
                <div class="info-item">
                    <label>Jumlah Dusun:</label>
                    <span><?php echo $desa_data['jumlah_dusun']; ?></span>
                </div>
                <div class="info-item">
                    <label>Jumlah RT:</label>
                    <span><?php echo $desa_data['jumlah_rt']; ?></span>
                </div>
                <div class="info-item">
                    <label>Jumlah RW:</label>
                    <span><?php echo $desa_data['jumlah_rw']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Kependudukan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-users"></i> Data Kependudukan</h3>
        </div>
        <div class="section-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_penduduk); ?></div>
                    <div class="stat-label">Total Penduduk</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_kk); ?></div>
                    <div class="stat-label">Total KK</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $rata_per_kk; ?></div>
                    <div class="stat-label">Rata-rata per KK</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $kepadatan; ?></div>
                    <div class="stat-label">Kepadatan (jiwa/km²)</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Penduduk</h4>
                    <input type="text" id="searchPenduduk" placeholder="Cari nama penduduk..." class="search-input">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="pendudukTable">
                        <thead>
                            <tr>
                                <th>NIK</th>
                                <th>Nama Lengkap</th>
                                <th>JK</th>
                                <th>Usia</th>
                                <th>Pendidikan</th>
                                <th>Pekerjaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($penduduk_list as $penduduk): ?>
                            <tr>
                                <td><?php echo $penduduk['nik']; ?></td>
                                <td><?php echo $penduduk['nama_lengkap']; ?></td>
                                <td><?php echo $penduduk['jenis_kelamin']; ?></td>
                                <td><?php echo $penduduk['usia']; ?></td>
                                <td><?php echo $penduduk['pendidikan_terakhir']; ?></td>
                                <td><?php echo $penduduk['pekerjaan']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Pendidikan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-school"></i> Data Pendidikan</h3>
        </div>
        <div class="section-content">
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Fasilitas Pendidikan</h4>
                    <input type="text" id="searchPendidikan" placeholder="Cari fasilitas..." class="search-input">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="pendidikanTable">
                        <thead>
                            <tr>
                                <th>Nama Fasilitas</th>
                                <th>Jenis</th>
                                <th>Alamat</th>
                                <th>Kapasitas</th>
                                <th>Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fasilitas_pendidikan as $fasilitas): ?>
                            <tr>
                                <td><?php echo $fasilitas['nama_fasilitas']; ?></td>
                                <td><?php echo $fasilitas['jenis_pendidikan']; ?></td>
                                <td><?php echo $fasilitas['alamat_fasilitas']; ?></td>
                                <td><?php echo $fasilitas['kapasitas_siswa']; ?></td>
                                <td><?php echo $fasilitas['kondisi_bangunan']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Ekonomi -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-chart-line"></i> Data Ekonomi</h3>
        </div>
        <div class="section-content">
            <div class="data-table-section">
                <div class="table-header">
                    <h4>UMKM</h4>
                    <input type="text" id="searchUMKM" placeholder="Cari UMKM..." class="search-input">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="umkmTable">
                        <thead>
                            <tr>
                                <th>Nama Usaha</th>
                                <th>Jenis</th>
                                <th>Modal</th>
                                <th>Omzet/Bulan</th>
                                <th>Karyawan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($umkm_list as $umkm): ?>
                            <tr>
                                <td><?php echo $umkm['nama_usaha']; ?></td>
                                <td><?php echo $umkm['jenis_usaha']; ?></td>
                                <td>Rp <?php echo number_format($umkm['modal_usaha']); ?></td>
                                <td>Rp <?php echo number_format($umkm['omzet_perbulan']); ?></td>
                                <td><?php echo $umkm['jumlah_karyawan']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="data-table-section">
                <div class="table-header">
                    <h4>Pasar</h4>
                    <input type="text" id="searchPasar" placeholder="Cari pasar..." class="search-input">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="pasarTable">
                        <thead>
                            <tr>
                                <th>Nama Pasar</th>
                                <th>Jenis</th>
                                <th>Jumlah Pedagang</th>
                                <th>Hari Operasional</th>
                                <th>Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pasar_list as $pasar): ?>
                            <tr>
                                <td><?php echo $pasar['nama_pasar']; ?></td>
                                <td><?php echo $pasar['jenis_pasar']; ?></td>
                                <td><?php echo $pasar['jumlah_pedagang']; ?></td>
                                <td><?php echo $pasar['hari_operasional']; ?></td>
                                <td><?php echo $pasar['kondisi_fasilitas']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Infrastruktur -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-road"></i> Data Infrastruktur</h3>
        </div>
        <div class="section-content">
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Jalan Desa</h4>
                    <input type="text" id="searchJalan" placeholder="Cari jalan..." class="search-input">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="jalanTable">
                        <thead>
                            <tr>
                                <th>Nama Jalan</th>
                                <th>Panjang (m)</th>
                                <th>Lebar (m)</th>
                                <th>Kondisi</th>
                                <th>Jenis Permukaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jalan_list as $jalan): ?>
                            <tr>
                                <td><?php echo $jalan['nama_jalan']; ?></td>
                                <td><?php echo $jalan['panjang_jalan']; ?></td>
                                <td><?php echo $jalan['lebar_jalan']; ?></td>
                                <td><span class="status-badge status-<?php echo $jalan['kondisi_jalan']; ?>"><?php echo ucfirst($jalan['kondisi_jalan']); ?></span></td>
                                <td><?php echo $jalan['jenis_permukaan']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="data-table-section">
                <div class="table-header">
                    <h4>Jembatan</h4>
                    <input type="text" id="searchJembatan" placeholder="Cari jembatan..." class="search-input">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="jembatanTable">
                        <thead>
                            <tr>
                                <th>Nama Jembatan</th>
                                <th>Panjang (m)</th>
                                <th>Lebar (m)</th>
                                <th>Kondisi</th>
                                <th>Material</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jembatan_list as $jembatan): ?>
                            <tr>
                                <td><?php echo $jembatan['nama_jembatan']; ?></td>
                                <td><?php echo $jembatan['panjang_jembatan']; ?></td>
                                <td><?php echo $jembatan['lebar_jembatan']; ?></td>
                                <td><span class="status-badge status-<?php echo $jembatan['kondisi_jembatan']; ?>"><?php echo ucfirst($jembatan['kondisi_jembatan']); ?></span></td>
                                <td><?php echo $jembatan['material_jembatan']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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