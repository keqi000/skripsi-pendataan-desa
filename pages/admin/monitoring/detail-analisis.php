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

// Get parameters
$selected_desa = $_GET['desa'] ?? 'all';
$analysis_type = $_GET['type'] ?? 'kependudukan';

// Set filtered desa
if ($selected_desa === 'all') {
    $filtered_desa = $all_desa;
} else {
    $filtered_desa = array_filter($all_desa, function ($desa) use ($selected_desa) {
        return $desa['id_desa'] == $selected_desa;
    });
}

$page_title = 'Detail Analisis Tingkat 2';
$current_page = 'monitoring';
$page_css = ['detail-analisis.css'];
$page_js = ['detail-analisis.js'];

ob_start();
?>

<div class="detail-analisis-container">
    <div class="detail-analisis-header">
        <div class="detail-analisis-title">
            <h2>Detail Analisis Tingkat 2</h2>
            <p>Data detail untuk <?php 
            if ($selected_desa === 'all') {
                echo 'Semua Desa';
            } else {
                foreach ($all_desa as $desa) {
                    if ($desa['id_desa'] == $selected_desa) {
                        echo $desa['nama_desa'];
                        break;
                    }
                }
            }
            ?></p>
        </div>
        <div class="detail-analisis-actions">
            <button onclick="goBackToAnalisis()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Analisis
            </button>
        </div>
    </div>

    <div class="detail-analisis-tabs">
        <div class="detail-analisis-tab-nav">
            <button class="detail-analisis-tab-btn <?php echo $analysis_type === 'kependudukan' ? 'active' : ''; ?>" 
                    onclick="switchAnalysisType('kependudukan')">
                Analisis Kependudukan Lanjutan
            </button>
            <button class="detail-analisis-tab-btn <?php echo $analysis_type === 'ekonomi' ? 'active' : ''; ?>" 
                    onclick="switchAnalysisType('ekonomi')">
                Analisis Ekonomi Lanjutan
            </button>
            <button class="detail-analisis-tab-btn <?php echo $analysis_type === 'integrasi' ? 'active' : ''; ?>" 
                    onclick="switchAnalysisType('integrasi')">
                Analisis Integrasi Data
            </button>
        </div>
    </div>

    <div class="detail-analisis-content">
        <?php if ($analysis_type === 'kependudukan'): ?>
            <!-- Analisis Kependudukan Lanjutan Detail -->
            <div class="card">
                <div class="card-header">
                    <h3>Detail KK Perempuan + Anak Sekolah</h3>
                </div>
                <div class="card-body">
                    <?php
                    $kk_perempuan_data = [];
                    
                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $keluarga = $queries->getKeluargaByDesa($id_desa);
                        $penduduk = $queries->getPendudukByDesa($id_desa);
                        
                        // Get warga miskin
                        $query = "SELECT nik FROM warga_miskin WHERE id_desa = :id_desa";
                        $stmt = $queries->db->prepare($query);
                        $stmt->bindParam(':id_desa', $id_desa);
                        $stmt->execute();
                        $warga_miskin_niks = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nik');
                        
                        foreach ($keluarga as $kk) {
                            $kepala_keluarga = null;
                            $anak_sekolah = [];
                            $is_kk_miskin = false;
                            
                            foreach ($penduduk as $p) {
                                if ($p['id_keluarga'] == $kk['id_keluarga']) {
                                    if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                                        $kepala_keluarga = $p;
                                        if (in_array($p['nik'], $warga_miskin_niks)) {
                                            $is_kk_miskin = true;
                                        }
                                    }
                                    if ($p['usia'] >= 7 && $p['usia'] <= 18) {
                                        $anak_sekolah[] = $p;
                                    }
                                }
                            }
                            
                            if ($kepala_keluarga && $kepala_keluarga['jenis_kelamin'] == 'P' && count($anak_sekolah) > 0) {
                                $kk_perempuan_data[] = [
                                    'kepala_keluarga' => $kepala_keluarga,
                                    'anak_sekolah' => $anak_sekolah,
                                    'is_miskin' => $is_kk_miskin,
                                    'desa' => $desa['nama_desa']
                                ];
                            }
                        }
                    }
                    ?>
                    
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kepala Keluarga</th>
                                    <th>Desa</th>
                                    <th>Jumlah Anak Sekolah</th>
                                    <th>Usia Anak</th>
                                    <th>Status Ekonomi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kk_perempuan_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['kepala_keluarga']['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo count($data['anak_sekolah']); ?> anak</td>
                                    <td>
                                        <?php 
                                        $usia_list = array_map(function($anak) { return $anak['usia']; }, $data['anak_sekolah']);
                                        echo implode(', ', $usia_list) . ' tahun';
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $data['is_miskin'] ? 'status-miskin' : 'status-mampu'; ?>">
                                            <?php echo $data['is_miskin'] ? 'Miskin' : 'Mampu'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($analysis_type === 'ekonomi'): ?>
            <!-- Analisis Ekonomi Lanjutan Detail -->
            <div class="card">
                <div class="card-header">
                    <h3>Detail Petani Lahan < 0.5 Ha Tanpa Bantuan</h3>
                </div>
                <div class="card-body">
                    <?php
                    $petani_data = [];
                    
                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $query = "SELECT p.*, de.id_desa FROM pertanian p 
                                 JOIN data_ekonomi de ON p.id_ekonomi = de.id_ekonomi 
                                 WHERE de.id_desa = :id_desa AND p.luas_lahan < 0.5 AND p.bantuan_pertanian = 'tidak_ada'";
                        $stmt = $queries->db->prepare($query);
                        $stmt->bindParam(':id_desa', $id_desa);
                        $stmt->execute();
                        $petani_kecil = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($petani_kecil as $pk) {
                            $petani_data[] = [
                                'data' => $pk,
                                'desa' => $desa['nama_desa']
                            ];
                        }
                    }
                    ?>
                    
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Petani</th>
                                    <th>Desa</th>
                                    <th>Luas Lahan (Ha)</th>
                                    <th>Jenis Komoditas</th>
                                    <th>Pendapatan/Musim</th>
                                    <th>Kategori Lahan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($petani_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['data']['nama_petani'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo number_format($data['data']['luas_lahan'], 2); ?> Ha</td>
                                    <td><?php echo htmlspecialchars($data['data']['jenis_komoditas']); ?></td>
                                    <td>Rp <?php echo number_format($data['data']['pendapatan_per_musim'] ?? 0); ?></td>
                                    <td>
                                        <?php 
                                        $luas = $data['data']['luas_lahan'];
                                        if ($luas >= 0.1 && $luas <= 0.25) {
                                            echo '<span class="kategori-badge kategori-kecil">0.1-0.25 Ha</span>';
                                        } elseif ($luas >= 0.26 && $luas <= 0.5) {
                                            echo '<span class="kategori-badge kategori-sedang">0.26-0.5 Ha</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Detail Korelasi Pendidikan-Pekerjaan</h3>
                </div>
                <div class="card-body">
                    <?php
                    $pendidikan_pekerjaan_data = [];
                    
                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                        $penduduk = $queries->getPendudukByDesa($id_desa);
                        
                        // Buat mapping NIK ke pendidikan
                        $nik_pendidikan = [];
                        foreach ($penduduk as $p) {
                            $nik_pendidikan[$p['nik']] = $p;
                        }
                        
                        foreach ($mata_pencaharian as $mp) {
                            if (isset($nik_pendidikan[$mp['nik']])) {
                                $penduduk_data = $nik_pendidikan[$mp['nik']];
                                $pendidikan_pekerjaan_data[] = [
                                    'penduduk' => $penduduk_data,
                                    'pekerjaan' => $mp,
                                    'desa' => $desa['nama_desa']
                                ];
                            }
                        }
                    }
                    ?>
                    
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Desa</th>
                                    <th>Pendidikan</th>
                                    <th>Sektor Pekerjaan</th>
                                    <th>Penghasilan/Bulan</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendidikan_pekerjaan_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo htmlspecialchars($data['penduduk']['pendidikan_terakhir']); ?></td>
                                    <td><?php echo htmlspecialchars($data['pekerjaan']['sektor_pekerjaan']); ?></td>
                                    <td>Rp <?php echo number_format($data['pekerjaan']['penghasilan_perbulan'] ?? 0); ?></td>
                                    <td>
                                        <?php 
                                        $sektor = $data['pekerjaan']['sektor_pekerjaan'];
                                        if ($sektor === 'pertanian') {
                                            echo '<span class="sektor-badge sektor-pertanian">Pertanian</span>';
                                        } else {
                                            echo '<span class="sektor-badge sektor-non-pertanian">Non-Pertanian</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($analysis_type === 'integrasi'): ?>
            <!-- Analisis Integrasi Data Detail -->
            <div class="card">
                <div class="card-header">
                    <h3>Detail Pendidikan Tinggi Tapi Miskin</h3>
                </div>
                <div class="card-body">
                    <?php
                    $pendidikan_tinggi_miskin_data = [];
                    
                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $penduduk = $queries->getPendudukByDesa($id_desa);
                        
                        // Get warga miskin
                        $query = "SELECT nik FROM warga_miskin WHERE id_desa = :id_desa";
                        $stmt = $queries->db->prepare($query);
                        $stmt->bindParam(':id_desa', $id_desa);
                        $stmt->execute();
                        $warga_miskin_niks = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nik');
                        
                        foreach ($penduduk as $p) {
                            if (in_array($p['pendidikan_terakhir'], ['D3', 'S1', 'S2', 'S3']) && in_array($p['nik'], $warga_miskin_niks)) {
                                $faktor_penyebab = '';
                                if (!$p['pekerjaan'] || $p['pekerjaan'] == 'Belum Bekerja') {
                                    $faktor_penyebab = 'Keterbatasan lapangan kerja';
                                } else {
                                    $faktor_penyebab = 'Ketidaksesuaian skill dengan pekerjaan';
                                }
                                
                                $pendidikan_tinggi_miskin_data[] = [
                                    'penduduk' => $p,
                                    'desa' => $desa['nama_desa'],
                                    'faktor_penyebab' => $faktor_penyebab
                                ];
                            }
                        }
                    }
                    ?>
                    
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Desa</th>
                                    <th>Pendidikan</th>
                                    <th>Pekerjaan</th>
                                    <th>Usia</th>
                                    <th>Faktor Penyebab</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendidikan_tinggi_miskin_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo htmlspecialchars($data['penduduk']['pendidikan_terakhir']); ?></td>
                                    <td><?php echo htmlspecialchars($data['penduduk']['pekerjaan'] ?: 'Belum Bekerja'); ?></td>
                                    <td><?php echo $data['penduduk']['usia']; ?> tahun</td>
                                    <td>
                                        <span class="faktor-badge">
                                            <?php echo htmlspecialchars($data['faktor_penyebab']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function switchAnalysisType(type) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('type', type);
    window.location.search = urlParams.toString();
}

function goBackToAnalisis() {
    window.history.back();
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../../layout/main.php';
?>