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

// Check if this is AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $page_title = 'Detail Analisis Tingkat 2';
    $current_page = 'analisis';
    $page_css = ['detail-analisis.css'];
    $page_js = ['detail-analisis.js'];

    ob_start();
}
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
        <div class="detail-analisis-breadcrumb">
            <a href="<?php echo BASE_URL; ?>pages/admin/analisis/analisis.php">← Kembali ke Analisis</a>
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
            <button class="detail-analisis-tab-btn <?php echo $analysis_type === 'spasial' ? 'active' : ''; ?>" 
                    onclick="switchAnalysisType('spasial')">
                Analisis Spasial & Prediktif
            </button>
        </div>
    </div>

    <div class="detail-analisis-content">
        <?php if ($analysis_type === 'kependudukan'): ?>
            <!-- Analisis Kependudukan Lanjutan Detail -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-users"></i> Detail KK Perempuan + Anak Sekolah</h3>
                </div>
                <div class="section-content">
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
                    
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>KK Perempuan + Anak Sekolah</h4>
                            <div class="stat-value"><?php echo count($kk_perempuan_data); ?> KK</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar KK Perempuan + Anak Sekolah</h4>
                            <input type="text" id="searchKKPerempuan" class="search-input" placeholder="Cari nama kepala keluarga...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="kkPerempuanTable">
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
            </div>

            <!-- Detail Produktif Tanpa Kerja Tetap -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-briefcase"></i> Detail Produktif Tanpa Kerja Tetap</h3>
                </div>
                <div class="section-content">
                    <?php
                    $produktif_detail = ['sd' => 0, 'smp' => 0, 'sma' => 0, 'diploma' => 0, 'sarjana' => 0, 'laki' => 0, 'perempuan' => 0];
                    $produktif_data = [];
                    
                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $penduduk = $queries->getPendudukByDesa($id_desa);
                        $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                        
                        foreach ($penduduk as $p) {
                            if ($p['usia'] >= 18 && $p['usia'] <= 64) {
                                $punya_kerja_tetap = false;
                                foreach ($mata_pencaharian as $mp) {
                                    if ($mp['nik'] == $p['nik'] && $mp['status_pekerjaan'] == 'tetap') {
                                        $punya_kerja_tetap = true;
                                        break;
                                    }
                                }
                                
                                if (!$punya_kerja_tetap) {
                                    $produktif_data[] = ['penduduk' => $p, 'desa' => $desa['nama_desa']];
                                    
                                    switch ($p['pendidikan_terakhir']) {
                                        case 'SD': $produktif_detail['sd']++; break;
                                        case 'SMP': $produktif_detail['smp']++; break;
                                        case 'SMA': $produktif_detail['sma']++; break;
                                        case 'D3': $produktif_detail['diploma']++; break;
                                        case 'S1': case 'S2': case 'S3': $produktif_detail['sarjana']++; break;
                                    }
                                    
                                    if ($p['jenis_kelamin'] == 'L') $produktif_detail['laki']++;
                                    else $produktif_detail['perempuan']++;
                                }
                            }
                        }
                    }
                    ?>
                    
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Produktif Tanpa Kerja Tetap</h4>
                            <div class="stat-value"><?php echo count($produktif_data); ?> orang</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Produktif Tanpa Kerja Tetap</h4>
                            <input type="text" id="searchProduktif" class="search-input" placeholder="Cari nama penduduk...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="produktifTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Desa</th>
                                    <th>Pendidikan</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Usia</th>
                                    <th>Status Pekerjaan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produktif_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo htmlspecialchars($data['penduduk']['pendidikan_terakhir']); ?></td>
                                    <td><?php echo $data['penduduk']['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                                    <td><?php echo $data['penduduk']['usia']; ?> tahun</td>
                                    <td><span class="status-badge status-tidak-tetap">Tidak Tetap</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <div class="analisis-breakdown">
                        <h5>Berdasarkan Pendidikan:</h5>
                        <div class="breakdown-item"><span>SD</span><span><?php echo $produktif_detail['sd']; ?> orang</span></div>
                        <div class="breakdown-item"><span>SMP</span><span><?php echo $produktif_detail['smp']; ?> orang</span></div>
                        <div class="breakdown-item"><span>SMA</span><span><?php echo $produktif_detail['sma']; ?> orang</span></div>
                        <div class="breakdown-item"><span>Diploma</span><span><?php echo $produktif_detail['diploma']; ?> orang</span></div>
                        <div class="breakdown-item"><span>Sarjana</span><span><?php echo $produktif_detail['sarjana']; ?> orang</span></div>
                        
                        <h5>Berdasarkan Jenis Kelamin:</h5>
                        <div class="breakdown-item"><span>Laki-laki</span><span><?php echo $produktif_detail['laki']; ?> orang</span></div>
                        <div class="breakdown-item"><span>Perempuan</span><span><?php echo $produktif_detail['perempuan']; ?> orang</span></div>
                    </div>
                </div>
            </div>

            <!-- Detail KK Lansia + Tanggungan -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-user-clock"></i> Detail KK Lansia + Tanggungan</h3>
                </div>
                <div class="section-content">
                    <?php
                    $lansia_data = [];
                    
                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $keluarga = $queries->getKeluargaByDesa($id_desa);
                        $penduduk = $queries->getPendudukByDesa($id_desa);
                        
                        foreach ($keluarga as $kk) {
                            $kepala_keluarga = null;
                            $anak_sekolah = [];
                            
                            foreach ($penduduk as $p) {
                                if ($p['id_keluarga'] == $kk['id_keluarga']) {
                                    if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                                        $kepala_keluarga = $p;
                                    }
                                    if ($p['usia'] >= 7 && $p['usia'] <= 18) {
                                        $anak_sekolah[] = $p;
                                    }
                                }
                            }
                            
                            if ($kepala_keluarga && $kepala_keluarga['usia'] > 65 && count($anak_sekolah) > 0) {
                                $lansia_data[] = [
                                    'kepala_keluarga' => $kepala_keluarga,
                                    'anak_sekolah' => $anak_sekolah,
                                    'desa' => $desa['nama_desa']
                                ];
                            }
                        }
                    }
                    ?>
                    
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>KK Lansia + Tanggungan</h4>
                            <div class="stat-value"><?php echo count($lansia_data); ?> KK</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar KK Lansia + Tanggungan</h4>
                            <input type="text" id="searchLansia" class="search-input" placeholder="Cari nama kepala keluarga...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="lansiaTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kepala Keluarga</th>
                                    <th>Desa</th>
                                    <th>Usia</th>
                                    <th>Jumlah Tanggungan</th>
                                    <th>Usia Tanggungan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lansia_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['kepala_keluarga']['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo $data['kepala_keluarga']['usia']; ?> tahun</td>
                                    <td><?php echo count($data['anak_sekolah']); ?> anak</td>
                                    <td>
                                        <?php 
                                        $usia_list = array_map(function($anak) { return $anak['usia']; }, $data['anak_sekolah']);
                                        echo implode(', ', $usia_list) . ' tahun';
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>



        <?php elseif ($analysis_type === 'ekonomi'): ?>
            <!-- Analisis Ekonomi Lanjutan Detail -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-seedling"></i> Detail Petani Lahan < 0.5 Ha Tanpa Bantuan</h3>
                </div>
                <div class="section-content">
                    <?php
                    $petani_data = [];
                    $petani_breakdown = ['0.1-0.25' => 0, '0.26-0.5' => 0, 'sayuran' => 0, 'buah' => 0, 'padi' => 0];
                    
                    // Dynamic komoditas breakdown based on actual data
                    $komoditas_detail = [];
                    
                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        
                        // Join with penduduk using nik_petani
                        $query = "SELECT p.*, de.id_desa, pd.nama_lengkap 
                                 FROM pertanian p 
                                 JOIN data_ekonomi de ON p.id_ekonomi = de.id_ekonomi 
                                 LEFT JOIN penduduk pd ON pd.nik = p.nik_petani
                                 WHERE de.id_desa = :id_desa AND p.luas_lahan < 0.5 AND p.bantuan_pertanian = 'tidak_ada'";
                        $stmt = $queries->db->prepare($query);
                        $stmt->bindParam(':id_desa', $id_desa);
                        $stmt->execute();
                        $petani_kecil = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($petani_kecil as $pk) {
                            $petani_data[] = [
                                'nama_petani' => $pk['nama_lengkap'] ?? 'Petani',
                                'desa' => $desa['nama_desa'],
                                'luas_lahan' => $pk['luas_lahan'],
                                'jenis_komoditas' => $pk['jenis_komoditas'],
                                'pendapatan_per_musim' => $pk['pendapatan_per_musim'] ?? 0,
                                'penghasilan_perbulan' => ($pk['pendapatan_per_musim'] ?? 0) / 3
                            ];
                            
                            // Breakdown luas lahan
                            if ($pk['luas_lahan'] <= 0.25) $petani_breakdown['0.1-0.25']++;
                            else $petani_breakdown['0.26-0.5']++;
                            
                            // Dynamic komoditas breakdown - berdasarkan data aktual
                            $komoditas = $pk['jenis_komoditas'];
                            $komoditas_detail[$komoditas] = ($komoditas_detail[$komoditas] ?? 0) + 1;
                        }
                    }
                    ?>
                    
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Petani Lahan < 0.5 Ha</h4>
                            <div class="stat-value"><?php echo count($petani_data); ?> orang</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Petani Lahan Kecil</h4>
                            <input type="text" id="searchPetani" class="search-input" placeholder="Cari nama petani...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="petaniTable">
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
                                    <td><?php echo htmlspecialchars($data['nama_petani']); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo number_format($data['luas_lahan'], 2); ?> Ha</td>
                                    <td><?php echo htmlspecialchars($data['jenis_komoditas']); ?></td>
                                    <td>Rp <?php echo number_format($data['pendapatan_per_musim']); ?></td>
                                    <td>
                                        <?php 
                                        $luas = $data['luas_lahan'];
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
                    
                    <div class="analisis-breakdown">
                        <h5>Berdasarkan Luas Lahan:</h5>
                        <div class="breakdown-item"><span>0.1-0.25 Ha</span><span><?php echo $petani_breakdown['0.1-0.25']; ?> petani</span></div>
                        <div class="breakdown-item"><span>0.26-0.5 Ha</span><span><?php echo $petani_breakdown['0.26-0.5']; ?> petani</span></div>
                        
                        <h5>Berdasarkan Jenis Komoditas:</h5>
                        <?php if (!empty($komoditas_detail)): ?>
                            <?php foreach ($komoditas_detail as $komoditas => $jumlah): ?>
                                <div class="breakdown-item"><span><?php echo $komoditas; ?></span><span><?php echo $jumlah; ?> petani</span></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="breakdown-item"><span>Tidak ada data</span><span>-</span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-graduation-cap"></i> Detail Korelasi Pendidikan-Pekerjaan</h3>
                </div>
                <div class="section-content">
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
                    
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Korelasi Pendidikan-Pekerjaan</h4>
                            <div class="stat-value"><?php echo count($pendidikan_pekerjaan_data); ?> orang</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Korelasi Pendidikan-Pekerjaan</h4>
                            <input type="text" id="searchKorelasi" class="search-input" placeholder="Cari nama penduduk...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="korelasiTable">
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
                    

                    
                    <?php
                    // Calculate dynamic education-occupation correlation with averages
                    $pendidikan_pekerjaan = [
                        'SD' => ['pertanian' => 0, 'non_pertanian' => 0, 'total_pendapatan' => 0, 'jumlah' => 0],
                        'SMP' => ['pertanian' => 0, 'non_pertanian' => 0, 'total_pendapatan' => 0, 'jumlah' => 0],
                        'SMA' => ['pertanian' => 0, 'non_pertanian' => 0, 'total_pendapatan' => 0, 'jumlah' => 0],
                        'Tinggi' => ['pertanian' => 0, 'non_pertanian' => 0, 'total_pendapatan' => 0, 'jumlah' => 0]
                    ];

                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                        $penduduk = $queries->getPendudukByDesa($id_desa);

                        $nik_pendidikan = [];
                        foreach ($penduduk as $p) {
                            $nik_pendidikan[$p['nik']] = $p['pendidikan_terakhir'];
                        }

                        foreach ($mata_pencaharian as $mp) {
                            $pendidikan = $nik_pendidikan[$mp['nik']] ?? '';
                            $kategori_pendidikan = '';

                            if ($pendidikan == 'SD') $kategori_pendidikan = 'SD';
                            elseif ($pendidikan == 'SMP') $kategori_pendidikan = 'SMP';
                            elseif ($pendidikan == 'SMA') $kategori_pendidikan = 'SMA';
                            elseif (in_array($pendidikan, ['D3', 'S1', 'S2', 'S3'])) $kategori_pendidikan = 'Tinggi';

                            if ($kategori_pendidikan && isset($pendidikan_pekerjaan[$kategori_pendidikan])) {
                                if ($mp['sektor_pekerjaan'] == 'pertanian') {
                                    $pendidikan_pekerjaan[$kategori_pendidikan]['pertanian']++;
                                } else {
                                    $pendidikan_pekerjaan[$kategori_pendidikan]['non_pertanian']++;
                                }
                                $pendidikan_pekerjaan[$kategori_pendidikan]['total_pendapatan'] += $mp['penghasilan_perbulan'] ?? 0;
                                $pendidikan_pekerjaan[$kategori_pendidikan]['jumlah']++;
                            }
                        }
                    }
                    ?>
                    
                    <div class="analisis-breakdown">
                        <h5>Pola Korelasi Pendidikan-Pekerjaan:</h5>
                        <?php foreach ($pendidikan_pekerjaan as $tingkat => $data): ?>
                            <?php if ($data['jumlah'] > 0): ?>
                                <?php
                                $rata_pendapatan = round($data['total_pendapatan'] / $data['jumlah']);
                                $persen_pertanian = round(($data['pertanian'] / $data['jumlah']) * 100, 1);
                                $persen_non_pertanian = round(($data['non_pertanian'] / $data['jumlah']) * 100, 1);
                                
                                // Determine dominant sector
                                $dominant_sector = $data['pertanian'] > $data['non_pertanian'] ? 'Pertanian' : 'Non-Pertanian';
                                $total_people = $data['pertanian'] + $data['non_pertanian'];
                                ?>
                                <div class="breakdown-item">
                                    <span><?php echo $tingkat; ?> → <?php echo $dominant_sector; ?></span>
                                    <span><?php echo $total_people; ?> orang</span>
                                </div>
                                <div class="breakdown-sub-item">
                                    <span><?php echo $tingkat; ?> - Rata-rata: Rp <?php echo number_format($rata_pendapatan); ?></span>
                                    <span><?php echo $persen_pertanian; ?>% Pertanian | <?php echo $persen_non_pertanian; ?>% Non-Pertanian</span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Detail Kesenjangan Ekonomi Antar Desa -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-chart-bar"></i> Detail Kesenjangan Ekonomi Antar Desa</h3>
                </div>
                <div class="section-content">
                    <?php
                    $pendapatan_semua_desa = [];
                    $kesenjangan_data = [];
                    
                    // Hitung pendapatan untuk semua desa
                    foreach ($all_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                        
                        $total_pendapatan = 0;
                        $jumlah_pekerja = 0;
                        
                        foreach ($mata_pencaharian as $mp) {
                            $total_pendapatan += $mp['penghasilan_perbulan'] ?? 0;
                            $jumlah_pekerja++;
                        }
                        
                        if ($jumlah_pekerja > 0) {
                            $rata_pendapatan = round($total_pendapatan / $jumlah_pekerja);
                            $pendapatan_semua_desa[$desa['nama_desa']] = $rata_pendapatan;
                            
                            $kesenjangan_data[] = [
                                'nama_desa' => $desa['nama_desa'],
                                'total_pendapatan' => $total_pendapatan,
                                'jumlah_pekerja' => $jumlah_pekerja,
                                'rata_pendapatan' => $rata_pendapatan
                            ];
                        }
                    }
                    
                    // Sort by rata pendapatan
                    usort($kesenjangan_data, function($a, $b) {
                        return $b['rata_pendapatan'] <=> $a['rata_pendapatan'];
                    });
                    ?>
                    
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Kesenjangan Ekonomi Antar Desa</h4>
                            <div class="stat-value"><?php echo count($kesenjangan_data); ?> desa</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Pendapatan Per Desa</h4>
                            <input type="text" id="searchKesenjangan" placeholder="Cari nama desa..." class="search-input">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="kesenjanganTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Desa</th>
                                        <th>Total Pendapatan</th>
                                        <th>Jumlah Pekerja</th>
                                        <th>Rata-rata Pendapatan</th>
                                        <th>Kategori</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kesenjangan_data as $index => $data): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($data['nama_desa']); ?></td>
                                        <td>Rp <?php echo number_format($data['total_pendapatan']); ?></td>
                                        <td><?php echo $data['jumlah_pekerja']; ?> orang</td>
                                        <td>Rp <?php echo number_format($data['rata_pendapatan']); ?></td>
                                        <td>
                                            <?php 
                                            if ($index < count($kesenjangan_data) / 3) {
                                                echo '<span class="kategori-badge kategori-tinggi">Tinggi</span>';
                                            } elseif ($index < (count($kesenjangan_data) * 2) / 3) {
                                                echo '<span class="kategori-badge kategori-sedang">Sedang</span>';
                                            } else {
                                                echo '<span class="kategori-badge kategori-rendah">Rendah</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (!empty($pendapatan_semua_desa)): ?>
                    <div class="analisis-breakdown">
                        <h5>Analisis Kesenjangan:</h5>
                        <?php
                        $pendapatan_tertinggi = max($pendapatan_semua_desa);
                        $pendapatan_terendah = min($pendapatan_semua_desa);
                        $selisih = $pendapatan_tertinggi - $pendapatan_terendah;
                        $persen_selisih = $pendapatan_terendah > 0 ? round(($selisih / $pendapatan_terendah) * 100, 1) : 0;
                        ?>
                        <div class="breakdown-item"><span>Pendapatan Tertinggi</span><span>Rp <?php echo number_format($pendapatan_tertinggi); ?></span></div>
                        <div class="breakdown-item"><span>Pendapatan Terendah</span><span>Rp <?php echo number_format($pendapatan_terendah); ?></span></div>
                        <div class="breakdown-item"><span>Selisih Pendapatan</span><span>Rp <?php echo number_format($selisih); ?> (<?php echo $persen_selisih; ?>%)</span></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detail Distribusi Pendapatan Desa -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-pie-chart"></i> Detail Distribusi Pendapatan Desa</h3>
                </div>
                <div class="section-content">
                    <?php
                    $distribusi_data = [];
                    $total_pertanian_all = 0;
                    $total_non_pertanian_all = 0;
                    
                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                        
                        $pendapatan_pertanian = 0;
                        $pendapatan_non_pertanian = 0;
                        $jumlah_pertanian = 0;
                        $jumlah_non_pertanian = 0;
                        
                        foreach ($mata_pencaharian as $mp) {
                            $penghasilan = $mp['penghasilan_perbulan'] ?? 0;
                            if ($mp['sektor_pekerjaan'] === 'pertanian') {
                                $pendapatan_pertanian += $penghasilan;
                                $jumlah_pertanian++;
                            } else {
                                $pendapatan_non_pertanian += $penghasilan;
                                $jumlah_non_pertanian++;
                            }
                        }
                        
                        $total_pertanian_all += $pendapatan_pertanian;
                        $total_non_pertanian_all += $pendapatan_non_pertanian;
                        
                        $distribusi_data[] = [
                            'nama_desa' => $desa['nama_desa'],
                            'pendapatan_pertanian' => $pendapatan_pertanian,
                            'pendapatan_non_pertanian' => $pendapatan_non_pertanian,
                            'jumlah_pertanian' => $jumlah_pertanian,
                            'jumlah_non_pertanian' => $jumlah_non_pertanian,
                            'total_pendapatan' => $pendapatan_pertanian + $pendapatan_non_pertanian
                        ];
                    }
                    ?>
                    
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Distribusi Pendapatan Desa</h4>
                            <div class="stat-value"><?php echo count($distribusi_data); ?> desa</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Distribusi Pendapatan Per Desa</h4>
                            <input type="text" id="searchDistribusi" placeholder="Cari nama desa..." class="search-input">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="distribusiTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Desa</th>
                                        <th>Pendapatan Pertanian</th>
                                        <th>Pendapatan Non-Pertanian</th>
                                        <th>Total Pendapatan</th>
                                        <th>Dominasi Sektor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($distribusi_data as $index => $data): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($data['nama_desa']); ?></td>
                                        <td>Rp <?php echo number_format($data['pendapatan_pertanian']); ?></td>
                                        <td>Rp <?php echo number_format($data['pendapatan_non_pertanian']); ?></td>
                                        <td>Rp <?php echo number_format($data['total_pendapatan']); ?></td>
                                        <td>
                                            <?php 
                                            if ($data['pendapatan_pertanian'] > $data['pendapatan_non_pertanian']) {
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
                    
                    <div class="analisis-breakdown">
                        <h5>Total Distribusi Pendapatan:</h5>
                        <?php
                        $total_semua = $total_pertanian_all + $total_non_pertanian_all;
                        $persen_pertanian = $total_semua > 0 ? round(($total_pertanian_all / $total_semua) * 100, 1) : 0;
                        $persen_non_pertanian = $total_semua > 0 ? round(($total_non_pertanian_all / $total_semua) * 100, 1) : 0;
                        ?>
                        <div class="breakdown-item"><span>Sektor Pertanian</span><span>Rp <?php echo number_format($total_pertanian_all); ?> (<?php echo $persen_pertanian; ?>%)</span></div>
                        <div class="breakdown-item"><span>Sektor Non-Pertanian</span><span>Rp <?php echo number_format($total_non_pertanian_all); ?> (<?php echo $persen_non_pertanian; ?>%)</span></div>
                        <div class="breakdown-item"><span>Total Pendapatan</span><span>Rp <?php echo number_format($total_semua); ?></span></div>
                    </div>
                </div>
            </div>

            <!-- Detail Rincian Non-Pertanian -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-industry"></i> Detail Rincian Non-Pertanian</h3>
                </div>
                <div class="section-content">
                    <?php
                    $non_pertanian_detail = [];
                    $non_pertanian_data = [];
                    
                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                        $penduduk = $queries->getPendudukByDesa($id_desa);
                        
                        // Buat mapping NIK ke penduduk
                        $nik_penduduk = [];
                        foreach ($penduduk as $p) {
                            $nik_penduduk[$p['nik']] = $p;
                        }
                        
                        foreach ($mata_pencaharian as $mp) {
                            if ($mp['sektor_pekerjaan'] !== 'pertanian' && isset($nik_penduduk[$mp['nik']])) {
                                $sektor = ucfirst($mp['sektor_pekerjaan']);
                                $non_pertanian_detail[$sektor] = ($non_pertanian_detail[$sektor] ?? 0) + 1;
                                
                                $non_pertanian_data[] = [
                                    'penduduk' => $nik_penduduk[$mp['nik']],
                                    'pekerjaan' => $mp,
                                    'desa' => $desa['nama_desa']
                                ];
                            }
                        }
                    }
                    ?>
                    
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Rincian Non-Pertanian</h4>
                            <div class="stat-value"><?php echo count($non_pertanian_data); ?> orang</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Pekerja Non-Pertanian</h4>
                            <input type="text" id="searchNonPertanian" placeholder="Cari nama pekerja..." class="search-input">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="nonPertanianTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Desa</th>
                                        <th>Sektor Pekerjaan</th>
                                        <th>Jenis Pekerjaan</th>
                                        <th>Penghasilan/Bulan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($non_pertanian_data as $index => $data): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                                        <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($data['pekerjaan']['sektor_pekerjaan'])); ?></td>
                                        <td><?php echo htmlspecialchars($data['pekerjaan']['jenis_pekerjaan']); ?></td>
                                        <td>Rp <?php echo number_format($data['pekerjaan']['penghasilan_perbulan'] ?? 0); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $data['pekerjaan']['status_pekerjaan'] == 'tetap' ? 'status-tetap' : 'status-tidak-tetap'; ?>">
                                                <?php echo ucfirst($data['pekerjaan']['status_pekerjaan']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (!empty($non_pertanian_detail)): ?>
                    <div class="analisis-breakdown">
                        <h5>Distribusi Sektor Non-Pertanian:</h5>
                        <?php
                        $total_non_pertanian = array_sum($non_pertanian_detail);
                        foreach ($non_pertanian_detail as $sektor => $jumlah):
                            $persen = $total_non_pertanian > 0 ? round(($jumlah / $total_non_pertanian) * 100, 1) : 0;
                        ?>
                        <div class="breakdown-item"><span><?php echo $sektor; ?></span><span><?php echo $jumlah; ?> orang (<?php echo $persen; ?>%)</span></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($analysis_type === 'integrasi'): ?>
            <?php
            // Calculate integration data first
            $kk_miskin_data = [];
            $pendidikan_tinggi_miskin_data = [];
            $rasio_ketergantungan_data = [];
            $anak_tidak_sekolah_data = [];
            $anak_petani_data = [];
            
            $kk_miskin_jenjang = ['SD' => 0, 'SMP' => 0, 'SMA' => 0];
            $pendidikan_tinggi_miskin = 0;
            $faktor_penyebab = [];
            $rasio_ketergantungan = ['0-1' => 0, '1-2' => 0, '2-3' => 0, '3+' => 0];
            $anak_tidak_sekolah_ekonomi = ['miskin' => 0, 'menengah' => 0, 'mampu' => 0];
            $anak_petani_pendidikan = ['D1' => 0, 'D2' => 0, 'D3' => 0, 'S1' => 0, 'S2' => 0, 'S3' => 0];
            $keluarga_miskin_anak_sekolah = 0;

            foreach ($filtered_desa as $desa) {
                $id_desa = $desa['id_desa'];
                $penduduk = $queries->getPendudukByDesa($id_desa);
                $keluarga = $queries->getKeluargaByDesa($id_desa);
                $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);

                // Get warga miskin dengan full data
                $query = "SELECT * FROM warga_miskin WHERE id_desa = :id_desa";
                $stmt = $queries->db->prepare($query);
                $stmt->bindParam(':id_desa', $id_desa);
                $stmt->execute();
                $warga_miskin = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($keluarga as $kk) {
                    $is_kk_miskin = false;
                    $anak_sekolah = [];
                    $kepala_keluarga = null;
                    $anggota_bekerja = 0;
                    $total_anggota = 0;

                    // Cek apakah KK miskin
                    foreach ($warga_miskin as $wm) {
                        foreach ($penduduk as $p) {
                            if ($p['nik'] == $wm['nik'] && $p['id_keluarga'] == $kk['id_keluarga']) {
                                $is_kk_miskin = true;
                                break 2;
                            }
                        }
                    }

                    // Jika KK miskin, cari kepala keluarga dan anak sekolah
                    if ($is_kk_miskin) {
                        foreach ($penduduk as $p) {
                            if ($p['id_keluarga'] == $kk['id_keluarga']) {
                                $total_anggota++;
                                if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                                    $kepala_keluarga = $p;
                                }
                                // Cek anak sekolah berdasarkan pekerjaan = 'Pelajar'
                                if ($p['pekerjaan'] == 'Pelajar') {
                                    $anak_sekolah[] = $p;
                                }
                                foreach ($mata_pencaharian as $mp) {
                                    if ($mp['nik'] == $p['nik']) {
                                        $anggota_bekerja++;
                                        break;
                                    }
                                }
                            }
                        }

                        if (count($anak_sekolah) > 0) {
                            $keluarga_miskin_anak_sekolah++;
                            $kk_miskin_data[] = [
                                'kepala_keluarga' => $kepala_keluarga,
                                'anak_sekolah' => $anak_sekolah,
                                'desa' => $desa['nama_desa']
                            ];
                            foreach ($anak_sekolah as $anak) {
                                if ($anak['usia'] >= 7 && $anak['usia'] <= 12) $kk_miskin_jenjang['SD']++;
                                elseif ($anak['usia'] >= 13 && $anak['usia'] <= 15) $kk_miskin_jenjang['SMP']++;
                                elseif ($anak['usia'] >= 16 && $anak['usia'] <= 18) $kk_miskin_jenjang['SMA']++;
                            }
                        }
                    }

                    // Untuk rasio ketergantungan (semua KK)
                    if (!$kepala_keluarga) {
                        foreach ($penduduk as $p) {
                            if ($p['id_keluarga'] == $kk['id_keluarga']) {
                                if (!$total_anggota) $total_anggota++;
                                if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                                    $kepala_keluarga = $p;
                                }
                                if (!$anggota_bekerja) {
                                    foreach ($mata_pencaharian as $mp) {
                                        if ($mp['nik'] == $p['nik']) {
                                            $anggota_bekerja++;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $tanggungan = $total_anggota - $anggota_bekerja;
                    if ($tanggungan >= 0 && $tanggungan <= 1) $rasio_ketergantungan['0-1']++;
                    elseif ($tanggungan >= 1 && $tanggungan <= 2) $rasio_ketergantungan['1-2']++;
                    elseif ($tanggungan >= 2 && $tanggungan <= 3) $rasio_ketergantungan['2-3']++;
                    else $rasio_ketergantungan['3+']++;
                    
                    if ($kepala_keluarga) {
                        $rasio_ketergantungan_data[] = [
                            'kepala_keluarga' => $kepala_keluarga['nama_lengkap'],
                            'desa' => $desa['nama_desa'],
                            'total_anggota' => $total_anggota,
                            'anggota_bekerja' => $anggota_bekerja,
                            'tanggungan' => $tanggungan
                        ];
                    }
                }

                // Cek pendidikan tinggi tapi miskin dari warga_miskin
                foreach ($warga_miskin as $wm) {
                    // Cari data penduduk berdasarkan NIK
                    $penduduk_miskin = null;
                    foreach ($penduduk as $p) {
                        if ($p['nik'] == $wm['nik']) {
                            $penduduk_miskin = $p;
                            break;
                        }
                    }
                    
                    if ($penduduk_miskin) {
                        // Cek apakah kepala keluarga
                        $is_kepala_keluarga = false;
                        foreach ($keluarga as $kk) {
                            if ($kk['nama_kepala_keluarga'] == $penduduk_miskin['nama_lengkap'] && 
                                $penduduk_miskin['id_keluarga'] == $kk['id_keluarga']) {
                                $is_kepala_keluarga = true;
                                break;
                            }
                        }
                        
                        // Jika kepala keluarga dan pendidikan tinggi
                        if ($is_kepala_keluarga && in_array($penduduk_miskin['pendidikan_terakhir'], ['D1', 'D2', 'D3', 'S1', 'S2', 'S3'])) {
                            $pendidikan_tinggi_miskin++;
                            
                            // Tentukan faktor penyebab
                            $faktor = (!$penduduk_miskin['pekerjaan'] || $penduduk_miskin['pekerjaan'] == 'Belum Bekerja') ? 
                                     'tidak bekerja' : 'ketidaksesuaian tempat kerja';
                            $faktor_penyebab[$faktor] = ($faktor_penyebab[$faktor] ?? 0) + 1;
                            
                            $pendidikan_tinggi_miskin_data[] = [
                                'penduduk' => $penduduk_miskin,
                                'desa' => $desa['nama_desa'],
                                'faktor_penyebab' => $faktor
                            ];
                        }
                    }
                }

                // Cek anak tidak sekolah karena ekonomi keluarga
                foreach ($keluarga as $kk) {
                    // Cari kepala keluarga
                    $kepala_keluarga = null;
                    foreach ($penduduk as $p) {
                        if ($p['id_keluarga'] == $kk['id_keluarga'] && $p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                            $kepala_keluarga = $p;
                            break;
                        }
                    }
                    
                    if ($kepala_keluarga) {
                        // Cek apakah kepala keluarga miskin
                        $is_kk_miskin = false;
                        foreach ($warga_miskin as $wm) {
                            if ($kepala_keluarga['nik'] == $wm['nik']) {
                                $is_kk_miskin = true;
                                break;
                            }
                        }
                        
                        // Jika KK miskin, cek anggota keluarga yang tidak sekolah
                        if ($is_kk_miskin) {
                            foreach ($penduduk as $p) {
                                if ($p['id_keluarga'] == $kk['id_keluarga'] && 
                                    $p['usia'] > 6 && 
                                    $p['pekerjaan'] != 'Pelajar' && 
                                    $p['pendidikan_terakhir'] == 'Tidak Sekolah') {
                                    $anak_tidak_sekolah_ekonomi['miskin']++;
                                    $anak_tidak_sekolah_data[] = ['penduduk' => $p, 'desa' => $desa['nama_desa'], 'status' => 'miskin'];
                                }
                            }
                        }
                    }
                }

                // Cek anak petani dengan pendidikan tinggi
                foreach ($keluarga as $kk) {
                    // Cari kepala keluarga
                    $kepala_keluarga = null;
                    foreach ($penduduk as $p) {
                        if ($p['id_keluarga'] == $kk['id_keluarga'] && $p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                            $kepala_keluarga = $p;
                            break;
                        }
                    }
                    
                    if ($kepala_keluarga && $kepala_keluarga['pekerjaan'] == 'Petani') {
                        // Jika kepala keluarga petani, cek anggota keluarga lain
                        foreach ($penduduk as $p) {
                            if ($p['id_keluarga'] == $kk['id_keluarga'] && 
                                $p['nama_lengkap'] != $kk['nama_kepala_keluarga'] && 
                                in_array($p['pendidikan_terakhir'], ['D1', 'D2', 'D3', 'S1', 'S2', 'S3'])) {
                                $anak_petani_pendidikan[$p['pendidikan_terakhir']]++;
                                $anak_petani_data[] = ['penduduk' => $p, 'desa' => $desa['nama_desa'], 'parent' => $kepala_keluarga];
                            }
                        }
                    }
                }
            }
            ?>
            
            <!-- Detail KK Miskin + Anak Sekolah -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-users"></i> Detail KK Miskin + Anak Sekolah</h3>
                </div>
                <div class="section-content">
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>KK Miskin + Anak Sekolah</h4>
                            <div class="stat-value"><?php echo number_format($keluarga_miskin_anak_sekolah); ?> KK</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar KK Miskin + Anak Sekolah</h4>
                            <input type="text" id="searchKKMiskin" class="search-input" placeholder="Cari nama kepala keluarga...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="kkMiskinTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kepala Keluarga</th>
                                    <th>Desa</th>
                                    <th>Jumlah Anak Sekolah</th>
                                    <th>Anak Sekolah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kk_miskin_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['kepala_keluarga']['nama_lengkap'] ?? 'Data Kepala Keluarga Tidak Tersedia'); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo count($data['anak_sekolah']); ?> anak</td>
                                    <td>
                                        <?php 
                                        if (!empty($data['anak_sekolah'])) {
                                            $anak_list = array_map(function($anak) { 
                                                $nama = $anak['nama_lengkap'] ?? 'Nama Tidak Tersedia';
                                                $usia = $anak['usia'] ?? 'Usia Tidak Diketahui';
                                                return $nama . ' (' . $usia . ' tahun)';
                                            }, $data['anak_sekolah']);
                                            echo implode(', ', $anak_list);
                                        } else {
                                            echo 'Data Anak Sekolah Tidak Tersedia';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <div class="analisis-breakdown">
                        <h5>Distribusi Jenjang KK Miskin + Anak Sekolah</h5>
                        <?php if (array_sum($kk_miskin_jenjang) > 0): ?>
                            <div class="breakdown-item"><span>SD</span><span><?php echo number_format($kk_miskin_jenjang['SD']); ?></span></div>
                            <div class="breakdown-item"><span>SMP</span><span><?php echo number_format($kk_miskin_jenjang['SMP']); ?></span></div>
                            <div class="breakdown-item"><span>SMA</span><span><?php echo number_format($kk_miskin_jenjang['SMA']); ?></span></div>
                        <?php else: ?>
                            <div class="breakdown-item"><span>Tidak ada data</span><span>-</span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Detail Rasio Ketergantungan Ekonomi -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-balance-scale"></i> Detail Rasio Ketergantungan Ekonomi</h3>
                </div>
                <div class="section-content">
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Rasio Ketergantungan Ekonomi</h4>
                            <div class="stat-value"><?php echo number_format(array_sum($rasio_ketergantungan)); ?> KK</div>
                        </div>
                    </div>
                    
                    <?php
                    // Hitung ulang rasio ketergantungan dengan logic yang benar
                    $rasio_detail_ekonomi = ['0-1' => 0, '2' => 0, '3' => 0, '3+' => 0];
                    $rasio_data_ekonomi = [];
                    
                    foreach ($filtered_desa as $desa) {
                        $id_desa = $desa['id_desa'];
                        $keluarga = $queries->getKeluargaByDesa($id_desa);
                        $penduduk = $queries->getPendudukByDesa($id_desa);
                        
                        foreach ($keluarga as $kk) {
                            $yang_bekerja = 0;
                            $total_anggota = 0;
                            $kepala_keluarga = null;
                            
                            // Hitung anggota keluarga berdasarkan id_keluarga yang sama
                            foreach ($penduduk as $p) {
                                if ($p['id_keluarga'] == $kk['id_keluarga']) {
                                    $total_anggota++;
                                    if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                                        $kepala_keluarga = $p;
                                    }
                                    // Yang bekerja = yang pekerjaannya bukan 'Belum Bekerja'
                                    if ($p['pekerjaan'] && $p['pekerjaan'] != 'Belum Bekerja') {
                                        $yang_bekerja++;
                                    }
                                }
                            }
                            
                            // Tanggungan = yang belum bekerja
                            $tanggungan = $total_anggota - $yang_bekerja;
                            
                            // Distribusi: 0-1, 2, 3, dan lebih dari 3
                            if ($tanggungan >= 0 && $tanggungan <= 1) {
                                $rasio_detail_ekonomi['0-1']++;
                            } elseif ($tanggungan == 2) {
                                $rasio_detail_ekonomi['2']++;
                            } elseif ($tanggungan == 3) {
                                $rasio_detail_ekonomi['3']++;
                            } elseif ($tanggungan > 3) {
                                $rasio_detail_ekonomi['3+']++;
                            }
                            
                            if ($kepala_keluarga) {
                                $rasio_data_ekonomi[] = [
                                    'kepala_keluarga' => $kepala_keluarga['nama_lengkap'],
                                    'desa' => $desa['nama_desa'],
                                    'total_anggota' => $total_anggota,
                                    'anggota_bekerja' => $yang_bekerja,
                                    'tanggungan' => $tanggungan
                                ];
                            }
                        }
                    }
                    ?>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Rasio Ketergantungan</h4>
                            <input type="text" id="searchRasioEkonomi" class="search-input" placeholder="Cari nama kepala keluarga...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="rasioEkonomiTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kepala Keluarga</th>
                                    <th>Desa</th>
                                    <th>Total Anggota</th>
                                    <th>Yang Bekerja</th>
                                    <th>Tanggungan</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rasio_data_ekonomi as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['kepala_keluarga']); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo $data['total_anggota']; ?> orang</td>
                                    <td><?php echo $data['anggota_bekerja']; ?> orang</td>
                                    <td><?php echo $data['tanggungan']; ?> orang</td>
                                    <td>
                                        <?php 
                                        $t = $data['tanggungan'];
                                        if ($t >= 0 && $t <= 1) echo '<span class="kategori-badge kategori-rendah">Rendah</span>';
                                        elseif ($t == 2) echo '<span class="kategori-badge kategori-sedang">Sedang</span>';
                                        elseif ($t == 3) echo '<span class="kategori-badge kategori-tinggi">Tinggi</span>';
                                        else echo '<span class="kategori-badge kategori-sangat-tinggi">Sangat Tinggi</span>';
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <div class="analisis-breakdown">
                        <h5>Distribusi Keluarga Rasio Ketergantungan</h5>
                        <div class="breakdown-item"><span>0-1 tanggungan</span><span><?php echo $rasio_detail_ekonomi['0-1']; ?> KK</span></div>
                        <div class="breakdown-item"><span>2 tanggungan</span><span><?php echo $rasio_detail_ekonomi['2']; ?> KK</span></div>
                        <div class="breakdown-item"><span>3 tanggungan</span><span><?php echo $rasio_detail_ekonomi['3']; ?> KK</span></div>
                        <div class="breakdown-item"><span>3+ tanggungan</span><span><?php echo $rasio_detail_ekonomi['3+']; ?> KK</span></div>
                    </div>
                </div>
            </div>

            <!-- Detail Pendidikan Tinggi Tapi Miskin -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-user-graduate"></i> Detail Pendidikan Tinggi Tapi Miskin</h3>
                </div>
                <div class="section-content">
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Pendidikan Tinggi Tapi Miskin</h4>
                            <div class="stat-value"><?php echo number_format($pendidikan_tinggi_miskin); ?> orang</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Pendidikan Tinggi Tapi Miskin</h4>
                            <input type="text" id="searchPendidikanTinggiMiskin" class="search-input" placeholder="Cari nama penduduk...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="pendidikanTinggiMiskinTable">
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
                    
                    <div class="analisis-breakdown">
                        <h5>Faktor Penyebab Pendidikan Tinggi Tapi Miskin</h5>
                        <?php if (!empty($faktor_penyebab)): ?>
                            <?php foreach ($faktor_penyebab as $faktor => $jumlah): ?>
                                <div class="breakdown-item"><span><?php echo ucfirst($faktor); ?></span><span><?php echo number_format($jumlah); ?> KK</span></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="breakdown-item"><span>Tidak ada data</span><span>-</span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Detail Anak Tidak Sekolah -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-child"></i> Detail Anak Tidak Sekolah karena Ekonomi Keluarga</h3>
                </div>
                <div class="section-content">
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Anak Tidak Sekolah karena Ekonomi Keluarga</h4>
                            <div class="stat-value"><?php echo number_format($anak_tidak_sekolah_ekonomi['miskin']); ?> anak</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Anak Tidak Sekolah</h4>
                            <input type="text" id="searchAnakTidakSekolah" class="search-input" placeholder="Cari nama anak...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="anakTidakSekolahTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Desa</th>
                                    <th>Usia</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Status Ekonomi Keluarga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($anak_tidak_sekolah_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo $data['penduduk']['usia']; ?> tahun</td>
                                    <td><?php echo $data['penduduk']['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $data['status'] == 'miskin' ? 'status-miskin' : 'status-mampu'; ?>">
                                            <?php echo ucfirst($data['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <div class="analisis-breakdown">
                        <h5>Detail Anak Tidak Sekolah karena Ekonomi Keluarga</h5>
                        <?php if ($anak_tidak_sekolah_ekonomi['miskin'] > 0): ?>
                            <div class="breakdown-item"><span>Dari Keluarga Miskin</span><span><?php echo number_format($anak_tidak_sekolah_ekonomi['miskin']); ?> anak</span></div>
                            <div class="breakdown-item"><span>Usia > 6 tahun</span><span><?php echo number_format($anak_tidak_sekolah_ekonomi['miskin']); ?> anak</span></div>
                            <div class="breakdown-item"><span>Pekerjaan bukan Pelajar</span><span><?php echo number_format($anak_tidak_sekolah_ekonomi['miskin']); ?> anak</span></div>
                            <div class="breakdown-item"><span>Pendidikan: Tidak Sekolah</span><span><?php echo number_format($anak_tidak_sekolah_ekonomi['miskin']); ?> anak</span></div>
                        <?php else: ?>
                            <div class="breakdown-item"><span>Tidak ada data</span><span>-</span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Detail Anak Petani Pendidikan Tinggi -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-seedling"></i> Detail Anak Petani → Pendidikan Tinggi</h3>
                </div>
                <div class="section-content">
                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Anak Petani → Pendidikan Tinggi</h4>
                            <div class="stat-value"><?php echo number_format(array_sum($anak_petani_pendidikan)); ?> orang</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Anak Petani Pendidikan Tinggi</h4>
                            <input type="text" id="searchAnakPetani" class="search-input" placeholder="Cari nama...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="anakPetaniTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Desa</th>
                                    <th>Pendidikan</th>
                                    <th>Usia</th>
                                    <th>Nama Orang Tua</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($anak_petani_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                    <td><?php echo htmlspecialchars($data['penduduk']['pendidikan_terakhir']); ?></td>
                                    <td><?php echo $data['penduduk']['usia']; ?> tahun</td>
                                    <td><?php echo htmlspecialchars($data['parent']['nama_lengkap']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <div class="analisis-breakdown">
                        <h5>Anak Petani → Pendidikan Tinggi</h5>
                        <?php if (array_sum($anak_petani_pendidikan) > 0): ?>
                            <div class="breakdown-item"><span>D1</span><span><?php echo number_format($anak_petani_pendidikan['D1']); ?></span></div>
                            <div class="breakdown-item"><span>D2</span><span><?php echo number_format($anak_petani_pendidikan['D2']); ?></span></div>
                            <div class="breakdown-item"><span>D3</span><span><?php echo number_format($anak_petani_pendidikan['D3']); ?></span></div>
                            <div class="breakdown-item"><span>S1</span><span><?php echo number_format($anak_petani_pendidikan['S1']); ?></span></div>
                            <div class="breakdown-item"><span>S2</span><span><?php echo number_format($anak_petani_pendidikan['S2']); ?></span></div>
                            <div class="breakdown-item"><span>S3</span><span><?php echo number_format($anak_petani_pendidikan['S3']); ?></span></div>
                        <?php else: ?>
                            <div class="breakdown-item"><span>Tidak ada data</span><span>-</span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php elseif ($analysis_type === 'spasial'): ?>
            <?php
            // Calculate spasial data - Hard reset with unique array
            $desa_padat_minim_fasilitas = 0;
            $indeks_aksesibilitas_rata = 0;
            $potensi_pengembangan = [];
            $spasial_data = [];
            $aksesibilitas_data = [];
            
            foreach ($filtered_desa as $desa) {
                // Check if this desa already exists to prevent duplicates
                $desa_exists = false;
                foreach ($spasial_data as $existing) {
                    if ($existing['nama_desa'] === $desa['nama_desa']) {
                        $desa_exists = true;
                        break;
                    }
                }
                
                if ($desa_exists) continue;
                
                $id_desa = $desa['id_desa'];
                $penduduk = $queries->getPendudukByDesa($id_desa);
                $fasilitas = $queries->getFasilitasPendidikan($id_desa);
                $ekonomi = $queries->getDataEkonomi($id_desa);
                $jalan = $queries->getInfrastrukturJalan($id_desa);
                
                // Convert hectares to km² for proper density calculation
                $luas_ha = $desa['luas_wilayah'] ?? 1;
                $luas_km2 = $luas_ha * 0.01; // 1 hectare = 0.01 km²
                $kepadatan = $luas_km2 > 0 ? round(count($penduduk) / $luas_km2, 2) : 0;
                $total_fasilitas_pendidikan = count($fasilitas);
                
                // Hitung kapasitas per jenjang
                $kapasitas_per_jenjang = [];
                foreach ($fasilitas as $f) {
                    $jenis = $f['jenis_pendidikan'];
                    $kapasitas_per_jenjang[$jenis] = ($kapasitas_per_jenjang[$jenis] ?? 0) + ($f['kapasitas_siswa'] ?? 0);
                }
                
                // Hitung anak per jenjang berdasarkan usia
                $anak_per_jenjang = ['SD' => 0, 'SMP' => 0, 'SMA' => 0];
                foreach ($penduduk as $p) {
                    if ($p['usia'] >= 7 && $p['usia'] <= 12) $anak_per_jenjang['SD']++;
                    elseif ($p['usia'] >= 13 && $p['usia'] <= 15) $anak_per_jenjang['SMP']++;
                    elseif ($p['usia'] >= 16 && $p['usia'] <= 18) $anak_per_jenjang['SMA']++;
                }
                
                // Cek status SD (wajib per desa)
                $status_sd_normal = ($kapasitas_per_jenjang['SD'] ?? 0) >= $anak_per_jenjang['SD'];
                
                // Indeks aksesibilitas
                $jalan_baik = 0;
                $total_jalan = count($jalan);
                foreach ($jalan as $j) {
                    if ($j['kondisi_jalan'] == 'baik') {
                        $jalan_baik++;
                    }
                }
                $aksesibilitas = $total_jalan > 0 ? ($jalan_baik / $total_jalan) * 100 : 0;
                $indeks_aksesibilitas_rata += $aksesibilitas;
                
                // Potensi pengembangan
                $nilai_ekonomi_total = 0;
                foreach ($ekonomi as $e) {
                    $nilai_ekonomi_total += $e['nilai_ekonomi'] ?? 0;
                }
                
                $spasial_data[] = [
                    'nama_desa' => $desa['nama_desa'],
                    'kepadatan' => $kepadatan,
                    'fasilitas_pendidikan' => $total_fasilitas_pendidikan,
                    'kapasitas_per_jenjang' => $kapasitas_per_jenjang,
                    'anak_per_jenjang' => $anak_per_jenjang,
                    'status_sd_normal' => $status_sd_normal,
                    'nilai_ekonomi' => $nilai_ekonomi_total,
                    'aksesibilitas' => round($aksesibilitas, 1),
                    'jalan_baik' => $jalan_baik,
                    'total_jalan' => $total_jalan,
                    'total_penduduk' => count($penduduk),
                    'luas_wilayah' => $desa['luas_wilayah'] ?? 0
                ];
                
                

                
                $aksesibilitas_data[] = [
                    'nama_desa' => $desa['nama_desa'],
                    'jalan_baik' => $jalan_baik,
                    'total_jalan' => $total_jalan,
                    'aksesibilitas' => round($aksesibilitas, 1)
                ];
                
                $potensi_pengembangan[] = [
                    'nama_desa' => $desa['nama_desa'],
                    'kepadatan' => $kepadatan,
                    'fasilitas' => $total_fasilitas_pendidikan,
                    'nilai_ekonomi' => $nilai_ekonomi_total,
                    'aksesibilitas' => round($aksesibilitas, 1)
                ];
            }
            
            
            // Hitung status SMP dan SMA untuk semua desa
            $total_smp = 0;
            $total_sma = 0;
            $total_desa = count($spasial_data);
            
            foreach ($spasial_data as $data) {
                $total_smp += $data['kapasitas_per_jenjang']['SMP'] ?? 0;
                $total_sma += $data['kapasitas_per_jenjang']['SMA'] ?? 0;
            }
            
            // Status SMP: minimal 1 SMP per 3 desa
            $status_smp_normal = $total_smp > 0 && ($total_desa / 3) <= $total_smp;
            // Status SMA: minimal 1 SMA per 15 desa  
            $status_sma_normal = $total_sma > 0 && ($total_desa / 15) <= $total_sma;
            
            // Update status untuk setiap desa
            for ($i = 0; $i < count($spasial_data); $i++) {
                $spasial_data[$i]['status_smp_normal'] = $status_smp_normal;
                $spasial_data[$i]['status_sma_normal'] = $status_sma_normal;
                $spasial_data[$i]['status_normal'] = $spasial_data[$i]['status_sd_normal'] && $status_smp_normal && $status_sma_normal;
            }
            
            $indeks_aksesibilitas_rata = count($aksesibilitas_data) > 0 ? round($indeks_aksesibilitas_rata / count($aksesibilitas_data), 1) : 0;
            
            // Sort by potential
            usort($potensi_pengembangan, function ($a, $b) {
                $score_a = ($a['kepadatan'] * 0.2) + ($a['nilai_ekonomi'] / 1000000 * 0.5) + ($a['aksesibilitas'] / 100 * 0.3);
                $score_b = ($b['kepadatan'] * 0.2) + ($b['nilai_ekonomi'] / 1000000 * 0.5) + ($b['aksesibilitas'] / 100 * 0.3);
                return $score_b <=> $score_a;
            });
            ?>
            
            <!-- Detail Desa Padat Minim Fasilitas -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-city"></i> Detail Desa Padat Minim Fasilitas</h3>
                </div>
                <div class="section-content">

                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Desa Padat Minim Fasilitas</h4>
                            <div class="stat-value"><?php echo number_format($desa_padat_minim_fasilitas); ?> desa</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Analisis Kepadatan & Fasilitas</h4>
                            <input type="text" id="searchSpasial" class="search-input" placeholder="Cari nama desa...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="spasialTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Desa</th>
                                    <th>Kepadatan (jiwa/km²)</th>
                                    <th>Fasilitas Pendidikan</th>
                                    <th>Kapasitas Per Jenjang</th>
                                    <th>Total Penduduk</th>
                                    <th>Luas Wilayah (km²)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($spasial_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['nama_desa']); ?></td>
                                    <td><?php echo number_format($data['kepadatan'], 2); ?></td>
                                    <td><?php echo $data['fasilitas_pendidikan']; ?> unit</td>
                                    <td>
                                        <?php 
                                        $kapasitas_text = [];
                                        foreach (['SD', 'SMP', 'SMA'] as $jenjang) {
                                            $kapasitas = $data['kapasitas_per_jenjang'][$jenjang] ?? 0;
                                            $kebutuhan = $data['anak_per_jenjang'][$jenjang] ?? 0;
                                            $kapasitas_text[] = $jenjang . ': ' . $kapasitas . '/' . $kebutuhan;
                                        }
                                        echo implode('<br>', $kapasitas_text);
                                        ?>
                                    </td>
                                    <td><?php echo $data['total_penduduk']; ?> jiwa</td>
                                    <td><?php echo $data['luas_wilayah']; ?> km²</td>
                                    <td>
                                        <?php 
                                        if ($data['status_normal']) {
                                            echo '<span class="status-badge status-mampu">Normal</span>';
                                        } else {
                                            echo '<span class="status-badge status-miskin">Tidak Normal</span>';
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
            </div>
            
            <!-- Detail Indeks Aksesibilitas -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-road"></i> Detail Indeks Aksesibilitas</h3>
                </div>
                <div class="section-content">

                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Indeks Aksesibilitas Rata-rata</h4>
                            <div class="stat-value"><?php echo $indeks_aksesibilitas_rata; ?>%</div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Daftar Aksesibilitas Per Desa</h4>
                            <input type="text" id="searchAksesibilitas" class="search-input" placeholder="Cari nama desa...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="aksesibilitasTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Desa</th>
                                    <th>Jalan Baik</th>
                                    <th>Total Jalan</th>
                                    <th>Aksesibilitas (%)</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aksesibilitas_data as $index => $data): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['nama_desa']); ?></td>
                                    <td><?php echo $data['jalan_baik']; ?> unit</td>
                                    <td><?php echo $data['total_jalan']; ?> unit</td>
                                    <td><?php echo $data['aksesibilitas']; ?>%</td>
                                    <td>
                                        <?php 
                                        $aks = $data['aksesibilitas'];
                                        if ($aks >= 80) {
                                            echo '<span class="kategori-badge kategori-tinggi">Sangat Baik</span>';
                                        } elseif ($aks >= 60) {
                                            echo '<span class="kategori-badge kategori-sedang">Baik</span>';
                                        } elseif ($aks >= 40) {
                                            echo '<span class="kategori-badge kategori-rendah">Sedang</span>';
                                        } else {
                                            echo '<span class="kategori-badge kategori-sangat-rendah">Buruk</span>';
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
            </div>
            
            <!-- Detail Potensi Pengembangan -->
            <div class="detail-section">
                <div class="section-header">
                    <h3><i class="fas fa-chart-line"></i> Detail Potensi Pengembangan Desa</h3>
                </div>
                <div class="section-content">

                    <div class="analisis-stats-grid">
                        <div class="stat-item">
                            <h4>Desa Berpotensi Tinggi</h4>
                            <div class="stat-value"><?php echo count($potensi_pengembangan) > 0 ? $potensi_pengembangan[0]['nama_desa'] : 'N/A'; ?></div>
                        </div>
                    </div>
                    
                    <div class="data-table-section">
                        <div class="table-header">
                            <h4>Ranking Potensi Pengembangan</h4>
                            <input type="text" id="searchPotensi" class="search-input" placeholder="Cari nama desa...">
                        </div>
                        <div class="table-responsive">
                            <table class="data-table" id="potensiTable">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Nama Desa</th>
                                    <th>Kepadatan (jiwa/km²)</th>
                                    <th>Total Fasilitas</th>
                                    <th>Nilai Ekonomi</th>
                                    <th>Aksesibilitas (%)</th>
                                    <th>Skor Potensi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($potensi_pengembangan as $index => $data): ?>
                                <?php 
                                $skor = ($data['kepadatan'] * 0.2) + ($data['nilai_ekonomi'] / 1000000 * 0.5) + ($data['aksesibilitas'] / 100 * 0.3);
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($data['nama_desa']); ?></td>
                                    <td><?php echo number_format($data['kepadatan'], 2); ?></td>
                                    <td><?php echo $data['fasilitas']; ?> unit</td>
                                    <td>Rp <?php echo number_format($data['nilai_ekonomi']); ?></td>
                                    <td><?php echo $data['aksesibilitas']; ?>%</td>
                                    <td>
                                        <?php 
                                        if ($index == 0) {
                                            echo '<span class="kategori-badge kategori-tinggi">Tinggi</span>';
                                        } elseif ($index >= 1 && $index <= 2) {
                                            echo '<span class="kategori-badge kategori-sedang">Sedang</span>';
                                        } else {
                                            echo '<span class="kategori-badge kategori-rendah">Rendah</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <div class="analisis-breakdown">
                        <h5>Ranking Potensi Pengembangan Desa</h5>
                        <?php foreach (array_slice($potensi_pengembangan, 0, 5) as $index => $desa_potensi): ?>
                            <div class="breakdown-item">
                                <span><?php echo ($index + 1) . '. ' . $desa_potensi['nama_desa']; ?></span>
                                <span>Kepadatan: <?php echo number_format($desa_potensi['kepadatan'], 2); ?>/km² | Aksesibilitas: <?php echo $desa_potensi['aksesibilitas']; ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            

        <?php endif; ?>
    </div>
</div>

<script>
function switchAnalysisType(type) {
    if (window.detailAnalisisInstance && window.detailAnalisisInstance.switchAnalysisType) {
        window.detailAnalisisInstance.switchAnalysisType(type);
    } else {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('type', type);
        window.location.search = urlParams.toString();
    }
}

function goBackToAnalisis() {
    if (typeof goBackToAnalisis === 'function') {
        goBackToAnalisis();
    } else {
        window.history.back();
    }
}
</script>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
} else {
    // For AJAX requests, return just the detail-analisis-container
    $content = ob_get_clean();
    echo $content;
    exit();
}
?>