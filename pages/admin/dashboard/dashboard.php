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
$total_desa = count($all_desa);

// Calculate aggregated statistics from all villages
$total_penduduk = 0;
$total_kk = 0;
$total_fasilitas_pendidikan = 0;
$total_umkm = 0;
$total_jalan_baik = 0;
$total_panjang_jalan = 0;
$total_jembatan = 0;
$total_luas_wilayah = 0;
$total_dusun = 0;
$total_rt = 0;
$total_rw = 0;
$total_anak_sekolah = 0;
$total_sektor_usaha = [];
$total_warga_miskin = 0;

foreach ($all_desa as $desa) {
    $id_desa = $desa['id_desa'];

    // Penduduk stats
    $stats_penduduk = $queries->getStatistikPenduduk($id_desa);
    $total_penduduk += $stats_penduduk['total'] ?? 0;
    $kk_data = $queries->analisisTingkat1Kependudukan($id_desa);
    $total_kk += $kk_data['kepala_keluarga']['jumlah_kk'] ?? 0;

    // Fasilitas pendidikan
    $fasilitas = $queries->getFasilitasPendidikan($id_desa);
    $total_fasilitas_pendidikan += count($fasilitas);

    // UMKM
    $umkm = $queries->getUMKM($id_desa);
    $total_umkm += count($umkm);
    foreach ($umkm as $u) {
        $total_sektor_usaha[$u['jenis_usaha']] = true;
    }

    // Jalan
    $jalan = $queries->getInfrastrukturJalan($id_desa);
    foreach ($jalan as $j) {
        $total_panjang_jalan += $j['panjang_jalan'];
        if ($j['kondisi_jalan'] === 'baik') {
            $total_jalan_baik += $j['panjang_jalan'];
        }
    }

    // Jembatan
    $jembatan = $queries->getInfrastrukturJembatan($id_desa);
    $total_jembatan += count($jembatan);

    // Wilayah
    $total_luas_wilayah += $desa['luas_wilayah'] ?? 0;
    $total_dusun += $desa['jumlah_dusun'] ?? 0;
    $total_rt += $desa['jumlah_rt'] ?? 0;
    $total_rw += $desa['jumlah_rw'] ?? 0;

    // Anak sekolah (usia 7-18)
    foreach ($stats_penduduk['kelompok_usia'] ?? [] as $kelompok) {
        if (in_array($kelompok['kelompok_usia'], ['Anak', 'Remaja'])) {
            $total_anak_sekolah += $kelompok['jumlah'];
        }
    }

    // Warga miskin
    $query_miskin = "SELECT COUNT(*) as total FROM warga_miskin WHERE id_desa = :id_desa AND status_penerima = 'aktif'";
    $stmt = $queries->db->prepare($query_miskin);
    $stmt->bindParam(':id_desa', $id_desa);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_warga_miskin += $result['total'] ?? 0;
}

$rata_anggota_kk = $total_kk > 0 ? round($total_penduduk / $total_kk, 1) : 0;
$persentase_jalan_baik = $total_panjang_jalan > 0 ? round(($total_jalan_baik / $total_panjang_jalan) * 100) : 0;
$jumlah_sektor_usaha = count($total_sektor_usaha);
$persentase_miskin = $total_penduduk > 0 ? round(($total_warga_miskin / $total_penduduk) * 100, 1) : 0;

// Check if this is AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    // Full page load
    $page_title = 'Dashboard Kecamatan';
    $current_page = 'dashboard';
    $page_css = ['dashboard.css'];
    $page_js = ['dashboard.js'];

    ob_start();
}
?>

<div class="dashboard-admin-container">
    <div class="dashboard-admin-header">
        <div class="dashboard-admin-welcome-card">
            <div class="dashboard-admin-welcome-content">
                <h2>Selamat Datang, <?php echo $_SESSION['user_name']; ?></h2>
                <p>Admin Kecamatan Tibawa</p>
                <div class="dashboard-admin-last-update">
                    <i class="fas fa-clock"></i>
                    Terakhir diperbarui: <?php echo date('d F Y, H:i'); ?>
                </div>
            </div>
            <div class="dashboard-admin-welcome-icon">
                <i class="fas fa-building"></i>
            </div>
        </div>
    </div>

    <div class="dashboard-admin-stats">
        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-school"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Fasilitas Pendidikan</div>
                    <div class="dashboard-admin-stat-number"><?php echo $total_fasilitas_pendidikan; ?></div>
                    <div class="dashboard-admin-stat-label">PAUD sampai SMP</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">UMKM Aktif</div>
                    <div class="dashboard-admin-stat-number"><?php echo $jumlah_sektor_usaha; ?></div>
                    <div class="dashboard-admin-stat-label">sektor usaha</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-road"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Infrastruktur</div>
                    <div class="dashboard-admin-stat-number"><?php echo $persentase_jalan_baik; ?>%</div>
                    <div class="dashboard-admin-stat-label">Kondisi baik</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Kepala Keluarga</div>
                    <div class="dashboard-admin-stat-number"><?php echo $rata_anggota_kk; ?></div>
                    <div class="dashboard-admin-stat-label">Rata-rata anggota/KK</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Warga Miskin</div>
                    <div class="dashboard-admin-stat-number"><?php echo $persentase_miskin; ?>%</div>
                    <div class="dashboard-admin-stat-label">dari total penduduk</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-road"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Jalan Desa</div>
                    <div class="dashboard-admin-stat-number"><?php echo $persentase_jalan_baik; ?>%</div>
                    <div class="dashboard-admin-stat-label">kondisi baik</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-map"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Luas Wilayah</div>
                    <div class="dashboard-admin-stat-number"><?php echo number_format($total_luas_wilayah); ?></div>
                    <div class="dashboard-admin-stat-label">hektar</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Dusun/RT/RW</div>
                    <div class="dashboard-admin-stat-number">
                        <?php echo $total_dusun . '/' . $total_rt . '/' . $total_rw; ?>
                    </div>
                    <div class="dashboard-admin-stat-label">wilayah administratif</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Usia Sekolah</div>
                    <div class="dashboard-admin-stat-number"><?php echo $total_anak_sekolah; ?></div>
                    <div class="dashboard-admin-stat-label">Anak usia 7–18 tahun</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Sektor Usaha</div>
                    <div class="dashboard-admin-stat-number"><?php echo $jumlah_sektor_usaha; ?></div>
                    <div class="dashboard-admin-stat-label">Jenis bidang usaha</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-bridge"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Jembatan</div>
                    <div class="dashboard-admin-stat-number"><?php echo $total_jembatan; ?></div>
                    <div class="dashboard-admin-stat-label">Unit infrastruktur</div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-admin-content">
        <div class="card">
            <div class="card-header">
                <h3>Tren Data 6 Bulan Terakhir</h3>
                <p class="card-subtitle">Tren Pertumbuhan Data Komprehensif</p>
                <div class="trend-filters">
                    <?php
                    // Get available years from all tables
                    $query_years = "SELECT DISTINCT tahun FROM (
                        SELECT YEAR(created_at) as tahun FROM desa
                        UNION
                        SELECT YEAR(created_at) as tahun FROM penduduk
                        UNION
                        SELECT YEAR(created_at) as tahun FROM fasilitas_pendidikan
                        UNION
                        SELECT YEAR(created_at) as tahun FROM umkm
                        UNION
                        SELECT YEAR(created_at) as tahun FROM infrastruktur_jalan
                    ) AS years ORDER BY tahun DESC";
                    $stmt = $queries->db->prepare($query_years);
                    $stmt->execute();
                    $available_years = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <select class="form-control" id="yearFilter" onchange="updateTrendData()">
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?php echo $year['tahun']; ?>" <?php echo $year['tahun'] == date('Y') ? 'selected' : ''; ?>>
                                <?php echo $year['tahun']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-control" id="periodFilter" onchange="updateTrendData()">
                        <option value="jan-jun">Jan - Jun</option>
                        <option value="jul-des">Jul - Des</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <p class="trend-description">Menampilkan tren semua kategori data</p>
                <div class="dashboard-admin-trends">
                    <div class="trend-charts">
                        <div class="trend-chart-item">
                            <h5 style="text-align: center; margin-bottom: 15px;">Tren 6 Bulan (%)</h5>
                            <canvas id="trendBarChart" width="400" height="200"></canvas>
                        </div>
                        <div class="trend-chart-item">
                            <h5 style="text-align: center; margin-bottom: 15px;">Progres Bulanan</h5>
                            <canvas id="trendLineChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    <?php
                    // Calculate 6-month trend based on selected period
                    $current_year = date('Y');
                    $selected_year = $current_year;
                    $selected_period = 'jan-jun'; // Default period
                    
                    // Set month range based on period
                    if ($selected_period == 'jan-jun') {
                        $start_month = 1;
                        $end_month = 6;
                    } else {
                        $start_month = 7;
                        $end_month = 12;
                    }

                    // Function to calculate trend percentage
                    function calculateTrend($table, $date_field, $year, $start_month, $end_month, $queries, $condition = '')
                    {
                        // Get total existing data
                        $query_total = "SELECT COUNT(*) as total FROM $table WHERE 1=1 $condition";
                        $stmt = $queries->db->prepare($query_total);
                        $stmt->execute();
                        $total_existing = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

                        // Get data added in selected period
                        $query_period = "SELECT COUNT(*) as period_count FROM $table 
                                       WHERE YEAR($date_field) = :year 
                                       AND MONTH($date_field) BETWEEN :start_month AND :end_month
                                       $condition";
                        $stmt = $queries->db->prepare($query_period);
                        $stmt->bindParam(':year', $year);
                        $stmt->bindParam(':start_month', $start_month);
                        $stmt->bindParam(':end_month', $end_month);
                        $stmt->execute();
                        $period_count = $stmt->fetch(PDO::FETCH_ASSOC)['period_count'] ?? 0;

                        // Calculate percentage
                        if ($total_existing == 0) {
                            return $period_count > 0 ? 100 : 0;
                        }
                        return round(($period_count / $total_existing) * 100, 1);
                    }

                    // Calculate trends for each category
                    $trend_desa = calculateTrend('desa', 'created_at', $selected_year, $start_month, $end_month, $queries);
                    $trend_penduduk = calculateTrend('penduduk', 'created_at', $selected_year, $start_month, $end_month, $queries);
                    $trend_pendidikan = calculateTrend('fasilitas_pendidikan', 'created_at', $selected_year, $start_month, $end_month, $queries);
                    $trend_umkm = calculateTrend('umkm', 'created_at', $selected_year, $start_month, $end_month, $queries);

                    // Calculate road infrastructure trend (new roads added)
                    $trend_jalan = calculateTrend('infrastruktur_jalan', 'created_at', $selected_year, $start_month, $end_month, $queries);

                    // Get monthly data for line chart
                    $monthly_data = [];
                    for ($month = $start_month; $month <= $end_month; $month++) {
                        $monthly_data[$month] = [
                            'desa' => 0,
                            'penduduk' => 0,
                            'pendidikan' => 0,
                            'umkm' => 0,
                            'jalan' => 0
                        ];

                        // Count data for each month
                        $tables = ['desa', 'penduduk', 'fasilitas_pendidikan', 'umkm'];
                        $keys = ['desa', 'penduduk', 'pendidikan', 'umkm'];

                        for ($i = 0; $i < count($tables); $i++) {
                            $query = "SELECT COUNT(*) as count FROM {$tables[$i]} 
                                     WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
                            $stmt = $queries->db->prepare($query);
                            $stmt->bindParam(':year', $selected_year);
                            $stmt->bindParam(':month', $month);
                            $stmt->execute();
                            $monthly_data[$month][$keys[$i]] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                        }

                        // New roads added for the month
                        $query = "SELECT COUNT(*) as count FROM infrastruktur_jalan 
                                 WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
                        $stmt = $queries->db->prepare($query);
                        $stmt->bindParam(':year', $selected_year);
                        $stmt->bindParam(':month', $month);
                        $stmt->execute();
                        $monthly_data[$month]['jalan'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                    }
                    ?>

                    <script>
                        // Pass PHP trend data to JavaScript - Always set these variables
                        window.trendData = {
                            desa: <?php echo $trend_desa; ?>,
                            penduduk: <?php echo $trend_penduduk; ?>,
                            pendidikan: <?php echo $trend_pendidikan; ?>,
                            umkm: <?php echo $trend_umkm; ?>,
                            jalan: <?php echo $trend_jalan; ?>
                        };

                        // Monthly data for line chart
                        window.monthlyData = <?php echo json_encode($monthly_data); ?>;

                        // Period info
                        window.selectedYear = <?php echo $selected_year; ?>;
                        window.selectedPeriod = '<?php echo $selected_period; ?>';

                        console.log('📊 DATA VARIABLES SET:');
                        console.log('- trendData:', window.trendData);
                        console.log('- monthlyData:', window.monthlyData);

                        // Ensure data is available and initialize dashboard
                        console.log('📊 Dashboard data set - trendData:', window.trendData);
                        console.log('📊 Dashboard data set - monthlyData:', window.monthlyData);

                        // Dashboard will auto-initialize when script loads
                        console.log('✅ Dashboard data ready for initialization');
                    </script>

                    <div class="trend-summary">
                        <div class="trend-item">
                            <div class="trend-value <?php echo $trend_desa >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($trend_desa >= 0 ? '+' : '') . $trend_desa; ?>%
                            </div>
                            <div class="trend-label">Desa</div>
                        </div>
                        <div class="trend-item">
                            <div class="trend-value <?php echo $trend_penduduk >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($trend_penduduk >= 0 ? '+' : '') . $trend_penduduk; ?>%
                            </div>
                            <div class="trend-label">Penduduk</div>
                        </div>
                        <div class="trend-item">
                            <div class="trend-value <?php echo $trend_pendidikan >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($trend_pendidikan >= 0 ? '+' : '') . $trend_pendidikan; ?>%
                            </div>
                            <div class="trend-label">Pendidikan</div>
                        </div>
                        <div class="trend-item">
                            <div class="trend-value <?php echo $trend_umkm >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($trend_umkm >= 0 ? '+' : '') . $trend_umkm; ?>%
                            </div>
                            <div class="trend-label">UMKM</div>
                        </div>
                        <div class="trend-item">
                            <div class="trend-value <?php echo $trend_jalan >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($trend_jalan >= 0 ? '+' : '') . $trend_jalan; ?>%
                            </div>
                            <div class="trend-label">Jalan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Overview Desa</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Desa</th>
                                <th>Status Data</th>
                                <th>Last Update</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_desa)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="no-data">
                                            <i class="fas fa-map-marked-alt fa-3x mb-3 text-muted"></i>
                                            <h5 class="text-muted">Tidak Ada Data</h5>
                                            <p class="text-muted">Belum ada data desa yang tersedia</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_desa as $desa): ?>
                                    <?php
                                    $id_desa = $desa['id_desa'];
                                    // Check data completeness
                                    $penduduk_count = $queries->db->prepare("SELECT COUNT(*) as total FROM penduduk WHERE id_desa = ?");
                                    $penduduk_count->execute([$id_desa]);
                                    $penduduk = $penduduk_count->fetch()['total'];

                                    $fasilitas_count = $queries->db->prepare("SELECT COUNT(*) as total FROM fasilitas_pendidikan WHERE id_desa = ?");
                                    $fasilitas_count->execute([$id_desa]);
                                    $fasilitas = $fasilitas_count->fetch()['total'];

                                    $umkm_count = $queries->db->prepare("SELECT COUNT(*) as total FROM umkm WHERE id_desa = ?");
                                    $umkm_count->execute([$id_desa]);
                                    $umkm = $umkm_count->fetch()['total'];

                                    $total_data = $penduduk + $fasilitas + $umkm;
                                    $status = $total_data > 20 ? 'Lengkap' : ($total_data > 10 ? 'Sebagian' : 'Minim');
                                    $badge_class = $total_data > 20 ? 'success' : ($total_data > 10 ? 'warning' : 'danger');
                                    ?>
                                    <tr>
                                        <td><?php echo $desa['nama_desa']; ?></td>
                                        <td><span class="badge badge-<?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($desa['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary"
                                                onclick="showDesaOverview(<?php echo $desa['id_desa']; ?>, '<?php echo $desa['nama_desa']; ?>')">
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
</div>

<!-- Modal Overview Desa -->
<div class="modal-overview" id="desaOverviewModal" style="display: none;">
    <div class="modal-overview-backdrop" onclick="closeDesaOverview()"></div>
    <div class="modal-overview-dialog">
        <div class="modal-overview-content">
            <div class="modal-overview-header">
                <h3 class="modal-overview-title">Overview Desa</h3>
                <button class="modal-overview-close" onclick="closeDesaOverview()">&times;</button>
            </div>
            <div class="modal-overview-body" id="desaOverviewContent">
                <div class="loading-text">Loading...</div>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-admin-container {
        position: relative;
    }

    .modal-overview {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1050;
    }

    .modal-overview-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
    }

    .modal-overview-dialog {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 90%;
        height: 40%;
        transform: translateX(-50%);
        background: white;
        border-radius: 12px 12px 0 0;
        box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
    }

    .modal-overview-content {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .modal-overview-header {
        padding: 24px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
        border-radius: 12px 12px 0 0;
    }

    .modal-overview-title {
        margin: 0;
        color: #112D4E;
        font-size: 20px;
        font-weight: 600;
    }

    .modal-overview-close {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #666;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-overview-close:hover {
        color: #333;
    }

    .modal-overview-body {
        padding: 32px;
        flex: 1;
        overflow-y: auto;
    }

    .overview-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 24px;
        height: 100%;
    }

    .overview-stat-item {
        background: linear-gradient(135deg, #3F72AF 0%, #112D4E 100%);
        color: white;
        padding: 16px 12px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(63, 114, 175, 0.3);
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 80px;
    }

    .overview-stat-number {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 4px;
        color: #DBE2EF;
    }

    .overview-stat-label {
        font-size: 12px;
        font-weight: 500;
        opacity: 0.9;
    }

    .loading-text {
        text-align: center;
        padding: 60px;
        font-size: 18px;
        color: #666;
    }
</style>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>