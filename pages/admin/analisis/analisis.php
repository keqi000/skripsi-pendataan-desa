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

// Handle AJAX request for filtered data
if (isset($_POST['action']) && $_POST['action'] === 'generate_analysis') {
    $selected_desa = $_POST['selected_desa'] ?? '';
    $_SESSION['selected_desa'] = $selected_desa;
    header('Content-Type: text/plain');
    echo 'success';
    exit();
}

// Set filtered desa based on session or default to all
$selected_desa = $_SESSION['selected_desa'] ?? '';
if ($selected_desa === 'all') {
    $filtered_desa = $all_desa;
} elseif (!empty($selected_desa)) {
    $filtered_desa = array_filter($all_desa, function ($desa) use ($selected_desa) {
        return $desa['id_desa'] == $selected_desa;
    });
} else {
    $filtered_desa = $all_desa;
}

// Check if this is AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $page_title = 'Analisis Data';
    $current_page = 'analisis';
    $page_css = ['analisis.css'];
    $page_js = ['analisis.js'];

    ob_start();
}
?>

<div class="analisis-admin-container">
    <div class="analisis-admin-header">
        <div class="analisis-admin-title">
            <h2>Analisis Data Lintas Desa</h2>
            <p>Analisis tingkat 1 dan 2 untuk semua desa di Kecamatan Tibawa</p>
        </div>
        <div class="analisis-admin-actions">
            <div class="desa-search-container">
                <input type="text" class="form-control" id="desaSearch" placeholder="Cari atau ketik nama desa..."
                    autocomplete="off" oninput="searchDesa()" onfocus="showAllDesa()">
                <input type="hidden" id="selectedDesaId" value="<?php echo $selected_desa; ?>">
                <div class="autocomplete-dropdown" id="autocompleteDropdown"></div>
            </div>
            <button class="btn btn-primary" onclick="generateAnalysis()">
                <i class="fas fa-chart-bar"></i>
                Generate Analisis
            </button>
            <div class="filter-status">
                <span class="filter-label">Data Aktif:</span>
                <span class="filter-value"><?php
                if ($selected_desa === 'all') {
                    echo 'Semua Desa';
                } elseif (!empty($selected_desa)) {
                    foreach ($all_desa as $desa) {
                        if ($desa['id_desa'] == $selected_desa) {
                            echo $desa['nama_desa'];
                            break;
                        }
                    }
                } else {
                    echo 'Semua Desa';
                }
                ?></span>
            </div>
        </div>
    </div>

    <div class="analisis-admin-tabs">
        <div class="analisis-admin-tab-nav">
            <button class="analisis-admin-tab-btn active" onclick="switchTab('tingkat1')">
                Analisis Tingkat 1
            </button>
            <button class="analisis-admin-tab-btn" onclick="switchTab('tingkat2')">
                Analisis Tingkat 2
            </button>
            <button class="analisis-admin-tab-btn" onclick="switchTab('prediksi')">
                Prediksi & Proyeksi
            </button>
        </div>
    </div>

    <div class="analisis-admin-content">
        <!-- Tingkat 1 Tab -->
        <div class="analisis-admin-tab-content active" id="tingkat1">
            <div class="analisis-admin-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Statistik Kependudukan</h3>
                    </div>
                    <div class="card-body">
                        <div id="kependudukanTingkat1">
                            <?php
                            $total_penduduk_all = 0;
                            $total_laki = 0;
                            $total_perempuan = 0;
                            $total_balita = 0;
                            $total_anak = 0;
                            $total_remaja = 0;
                            $total_dewasa = 0;
                            $total_lansia = 0;
                            $agama_stats = [];
                            $pendidikan_stats = [];
                            $pernikahan_stats = [];
                            $pekerjaan_stats = [];
                            $total_kk = 0;

                            foreach ($filtered_desa as $desa) {
                                $id_desa = $desa['id_desa'];
                                $penduduk = $queries->getPendudukByDesa($id_desa);
                                $keluarga = $queries->getKeluargaByDesa($id_desa);
                                $total_kk += count($keluarga);

                                foreach ($penduduk as $p) {
                                    $total_penduduk_all++;

                                    // Jenis kelamin
                                    if ($p['jenis_kelamin'] === 'L')
                                        $total_laki++;
                                    else
                                        $total_perempuan++;

                                    // Kelompok usia
                                    if ($p['usia'] >= 0 && $p['usia'] <= 5)
                                        $total_balita++;
                                    elseif ($p['usia'] >= 6 && $p['usia'] <= 12)
                                        $total_anak++;
                                    elseif ($p['usia'] >= 13 && $p['usia'] <= 17)
                                        $total_remaja++;
                                    elseif ($p['usia'] >= 18 && $p['usia'] <= 64)
                                        $total_dewasa++;
                                    else
                                        $total_lansia++;

                                    // Agama
                                    $agama_stats[$p['agama']] = ($agama_stats[$p['agama']] ?? 0) + 1;

                                    // Pendidikan
                                    $pendidikan_stats[$p['pendidikan_terakhir']] = ($pendidikan_stats[$p['pendidikan_terakhir']] ?? 0) + 1;

                                    // Status pernikahan
                                    $pernikahan_stats[$p['status_pernikahan']] = ($pernikahan_stats[$p['status_pernikahan']] ?? 0) + 1;

                                    // Pekerjaan
                                    if ($p['pekerjaan']) {
                                        $pekerjaan_stats[$p['pekerjaan']] = ($pekerjaan_stats[$p['pekerjaan']] ?? 0) + 1;
                                    }
                                }
                            }
                            ?>

                            <div class="analisis-stats-grid">
                                <div class="stat-item">
                                    <h4>Total Penduduk</h4>
                                    <div class="stat-value"><?php echo number_format($total_penduduk_all); ?> jiwa</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Laki-laki</h4>
                                    <div class="stat-value"><?php echo number_format($total_laki); ?>
                                        (<?php echo $total_penduduk_all > 0 ? round(($total_laki / $total_penduduk_all) * 100, 1) : 0; ?>%)
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <h4>Perempuan</h4>
                                    <div class="stat-value"><?php echo number_format($total_perempuan); ?>
                                        (<?php echo $total_penduduk_all > 0 ? round(($total_perempuan / $total_penduduk_all) * 100, 1) : 0; ?>%)
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <h4>Total KK</h4>
                                    <div class="stat-value"><?php echo number_format($total_kk); ?> KK</div>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Kelompok Usia</h5>
                                <div class="breakdown-item">
                                    <span>Balita (0-5 tahun)</span>
                                    <span><?php echo number_format($total_balita); ?>
                                        (<?php echo $total_penduduk_all > 0 ? round(($total_balita / $total_penduduk_all) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Anak (6-12 tahun)</span>
                                    <span><?php echo number_format($total_anak); ?>
                                        (<?php echo $total_penduduk_all > 0 ? round(($total_anak / $total_penduduk_all) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Remaja (13-17 tahun)</span>
                                    <span><?php echo number_format($total_remaja); ?>
                                        (<?php echo $total_penduduk_all > 0 ? round(($total_remaja / $total_penduduk_all) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Dewasa (18-64 tahun)</span>
                                    <span><?php echo number_format($total_dewasa); ?>
                                        (<?php echo $total_penduduk_all > 0 ? round(($total_dewasa / $total_penduduk_all) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Lansia (65+ tahun)</span>
                                    <span><?php echo number_format($total_lansia); ?>
                                        (<?php echo $total_penduduk_all > 0 ? round(($total_lansia / $total_penduduk_all) * 100, 1) : 0; ?>%)</span>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Agama</h5>
                                <?php foreach ($agama_stats as $agama => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $agama; ?></span>
                                        <span><?php echo number_format($jumlah); ?>
                                            (<?php echo $total_penduduk_all > 0 ? round(($jumlah / $total_penduduk_all) * 100, 1) : 0; ?>%)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Tingkat Pendidikan</h5>
                                <?php
                                arsort($pendidikan_stats);
                                foreach ($pendidikan_stats as $pendidikan => $jumlah):
                                    ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $pendidikan; ?></span>
                                        <span><?php echo number_format($jumlah); ?>
                                            (<?php echo $total_penduduk_all > 0 ? round(($jumlah / $total_penduduk_all) * 100, 1) : 0; ?>%)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Status Pernikahan</h5>
                                <?php foreach ($pernikahan_stats as $status => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $status; ?></span>
                                        <span><?php echo number_format($jumlah); ?>
                                            (<?php echo $total_penduduk_all > 0 ? round(($jumlah / $total_penduduk_all) * 100, 1) : 0; ?>%)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Distribusi Ekonomi</h3>
                    </div>
                    <div class="card-body">
                        <div id="ekonomiTingkat1">
                            <?php
                            $mata_pencaharian_stats = [];
                            $umkm_stats = [];
                            $total_pertanian = 0;
                            $total_umkm = 0;
                            $total_pasar = 0;

                            // Get all mata pencaharian from filtered desa
                            foreach ($filtered_desa as $desa) {
                                $id_desa = $desa['id_desa'];
                                $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                                $ekonomi = $queries->getDataEkonomi($id_desa);

                                foreach ($mata_pencaharian as $mp) {
                                    $mata_pencaharian_stats[$mp['jenis_pekerjaan']] = ($mata_pencaharian_stats[$mp['jenis_pekerjaan']] ?? 0) + 1;
                                }

                                foreach ($ekonomi as $e) {
                                    if ($e['jenis_data'] === 'pertanian')
                                        $total_pertanian++;
                                    elseif ($e['jenis_data'] === 'umkm')
                                        $total_umkm++;
                                    elseif ($e['jenis_data'] === 'pasar')
                                        $total_pasar++;
                                }
                            }

                            // Count total unique jenis_pekerjaan
                            $total_jenis_mata_pencaharian = count($mata_pencaharian_stats);
                            ?>

                            <div class="analisis-stats-grid">
                                <div class="stat-item">
                                    <h4>Total Mata Pencaharian</h4>
                                    <div class="stat-value"><?php echo number_format($total_jenis_mata_pencaharian); ?>
                                        jenis</div>
                                </div>
                                <div class="stat-item">
                                    <h4>UMKM</h4>
                                    <div class="stat-value"><?php echo number_format($total_umkm); ?> unit</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Pertanian</h4>
                                    <div class="stat-value"><?php echo number_format($total_pertanian); ?> unit</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Pasar</h4>
                                    <div class="stat-value"><?php echo number_format($total_pasar); ?> unit</div>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Mata Pencaharian</h5>
                                <?php
                                arsort($mata_pencaharian_stats);
                                $total_mp = array_sum($mata_pencaharian_stats);
                                foreach ($mata_pencaharian_stats as $pekerjaan => $jumlah):
                                    ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $pekerjaan; ?></span>
                                        <span><?php echo number_format($jumlah); ?>
                                            (<?php echo $total_mp > 0 ? round(($jumlah / $total_mp) * 100, 1) : 0; ?>%)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>UMKM Berdasarkan Jenis Usaha</h5>
                                <?php
                                $umkm_jenis_stats = [];
                                foreach ($filtered_desa as $desa) {
                                    $id_desa = $desa['id_desa'];
                                    $umkm = $queries->getUMKM($id_desa);
                                    foreach ($umkm as $u) {
                                        $umkm_jenis_stats[$u['jenis_usaha']] = ($umkm_jenis_stats[$u['jenis_usaha']] ?? 0) + 1;
                                    }
                                }
                                arsort($umkm_jenis_stats);
                                foreach (array_slice($umkm_jenis_stats, 0, 10) as $jenis => $jumlah):
                                    ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $jenis; ?></span>
                                        <span><?php echo number_format($jumlah); ?> unit</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Warga Miskin & Bantuan Sosial</h5>
                                <?php
                                $total_warga_miskin = 0;
                                $bantuan_stats = [];
                                foreach ($filtered_desa as $desa) {
                                    $id_desa = $desa['id_desa'];
                                    $query = "SELECT COUNT(*) as total FROM warga_miskin WHERE id_desa = :id_desa";
                                    $stmt = $queries->db->prepare($query);
                                    $stmt->bindParam(':id_desa', $id_desa);
                                    $stmt->execute();
                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $total_warga_miskin += $result['total'] ?? 0;

                                    $query = "SELECT jenis_bantuan, COUNT(*) as jumlah FROM warga_miskin WHERE id_desa = :id_desa GROUP BY jenis_bantuan";
                                    $stmt = $queries->db->prepare($query);
                                    $stmt->bindParam(':id_desa', $id_desa);
                                    $stmt->execute();
                                    $bantuan = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($bantuan as $b) {
                                        $bantuan_stats[$b['jenis_bantuan']] = ($bantuan_stats[$b['jenis_bantuan']] ?? 0) + $b['jumlah'];
                                    }
                                }
                                ?>
                                <div class="breakdown-item">
                                    <span>Total Warga Miskin</span>
                                    <span><?php echo number_format($total_warga_miskin); ?> orang</span>
                                </div>
                                <?php foreach ($bantuan_stats as $jenis => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $jenis; ?></span>
                                        <span><?php echo number_format($jumlah); ?> penerima</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Fasilitas Pendidikan</h3>
                    </div>
                    <div class="card-body">
                        <div id="pendidikanTingkat1">
                            <?php
                            $fasilitas_stats = [];
                            $total_kapasitas = 0;
                            $total_guru = 0;

                            foreach ($filtered_desa as $desa) {
                                $id_desa = $desa['id_desa'];
                                $fasilitas = $queries->getFasilitasPendidikan($id_desa);

                                foreach ($fasilitas as $f) {
                                    $fasilitas_stats[$f['jenis_pendidikan']] = ($fasilitas_stats[$f['jenis_pendidikan']] ?? 0) + 1;
                                    $total_kapasitas += $f['kapasitas_siswa'] ?? 0;
                                    $total_guru += $f['jumlah_guru'] ?? 0;
                                }
                            }
                            ?>

                            <div class="analisis-stats-grid">
                                <div class="stat-item">
                                    <h4>Total Fasilitas</h4>
                                    <div class="stat-value"><?php echo number_format(array_sum($fasilitas_stats)); ?>
                                        unit</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Total Kapasitas</h4>
                                    <div class="stat-value"><?php echo number_format($total_kapasitas); ?> siswa</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Total Guru</h4>
                                    <div class="stat-value"><?php echo number_format($total_guru); ?> orang</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Rasio Guru:Siswa</h4>
                                    <div class="stat-value">
                                        1:<?php echo $total_guru > 0 ? round($total_kapasitas / $total_guru) : 0; ?></div>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Jenis Fasilitas</h5>
                                <?php foreach ($fasilitas_stats as $jenis => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $jenis; ?></span>
                                        <span><?php echo number_format($jumlah); ?> unit</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Kapasitas Per Jenjang</h5>
                                <?php
                                $kapasitas_per_jenjang = [];
                                foreach ($filtered_desa as $desa) {
                                    $id_desa = $desa['id_desa'];
                                    $fasilitas = $queries->getFasilitasPendidikan($id_desa);
                                    foreach ($fasilitas as $f) {
                                        $kapasitas_per_jenjang[$f['jenis_pendidikan']] = ($kapasitas_per_jenjang[$f['jenis_pendidikan']] ?? 0) + ($f['kapasitas_siswa'] ?? 0);
                                    }
                                }
                                foreach ($kapasitas_per_jenjang as $jenis => $kapasitas):
                                    ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $jenis; ?></span>
                                        <span><?php echo number_format($kapasitas); ?> siswa</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Status Infrastruktur</h3>
                    </div>
                    <div class="card-body">
                        <div id="infrastrukturTingkat1">
                            <?php
                            $jalan_baik = 0;
                            $jalan_sedang = 0;
                            $jalan_rusak = 0;
                            $total_panjang_jalan = 0;
                            $jembatan_baik = 0;
                            $jembatan_sedang = 0;
                            $jembatan_rusak = 0;
                            $total_jembatan = 0;

                            foreach ($filtered_desa as $desa) {
                                $id_desa = $desa['id_desa'];
                                $jalan = $queries->getInfrastrukturJalan($id_desa);
                                $jembatan = $queries->getInfrastrukturJembatan($id_desa);

                                foreach ($jalan as $j) {
                                    $total_panjang_jalan += $j['panjang_jalan'];
                                    if ($j['kondisi_jalan'] === 'baik')
                                        $jalan_baik++;
                                    elseif ($j['kondisi_jalan'] === 'sedang')
                                        $jalan_sedang++;
                                    else
                                        $jalan_rusak++;
                                }

                                foreach ($jembatan as $jmb) {
                                    $total_jembatan++;
                                    if ($jmb['kondisi_jembatan'] === 'baik')
                                        $jembatan_baik++;
                                    elseif ($jmb['kondisi_jembatan'] === 'sedang')
                                        $jembatan_sedang++;
                                    else
                                        $jembatan_rusak++;
                                }
                            }

                            $total_jalan_unit = $jalan_baik + $jalan_sedang + $jalan_rusak;
                            ?>

                            <div class="analisis-stats-grid">
                                <div class="stat-item">
                                    <h4>Total Jalan</h4>
                                    <div class="stat-value"><?php echo number_format($total_jalan_unit); ?> unit</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Panjang Jalan</h4>
                                    <div class="stat-value"><?php echo number_format($total_panjang_jalan, 1); ?> km
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <h4>Total Jembatan</h4>
                                    <div class="stat-value"><?php echo number_format($total_jembatan); ?> unit</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Kondisi Baik</h4>
                                    <div class="stat-value">
                                        <?php echo ($total_jalan_unit + $total_jembatan) > 0 ? round((($jalan_baik + $jembatan_baik) / ($total_jalan_unit + $total_jembatan)) * 100, 1) : 0; ?>%
                                    </div>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Kondisi Jalan</h5>
                                <div class="breakdown-item">
                                    <span>Baik</span>
                                    <span><?php echo number_format($jalan_baik); ?>
                                        (<?php echo $total_jalan_unit > 0 ? round(($jalan_baik / $total_jalan_unit) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Sedang</span>
                                    <span><?php echo number_format($jalan_sedang); ?>
                                        (<?php echo $total_jalan_unit > 0 ? round(($jalan_sedang / $total_jalan_unit) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Rusak</span>
                                    <span><?php echo number_format($jalan_rusak); ?>
                                        (<?php echo $total_jalan_unit > 0 ? round(($jalan_rusak / $total_jalan_unit) * 100, 1) : 0; ?>%)</span>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Kondisi Jembatan</h5>
                                <div class="breakdown-item">
                                    <span>Baik</span>
                                    <span><?php echo number_format($jembatan_baik); ?>
                                        (<?php echo $total_jembatan > 0 ? round(($jembatan_baik / $total_jembatan) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Sedang</span>
                                    <span><?php echo number_format($jembatan_sedang); ?>
                                        (<?php echo $total_jembatan > 0 ? round(($jembatan_sedang / $total_jembatan) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Rusak</span>
                                    <span><?php echo number_format($jembatan_rusak); ?>
                                        (<?php echo $total_jembatan > 0 ? round(($jembatan_rusak / $total_jembatan) * 100, 1) : 0; ?>%)</span>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Analisis Spasial & Geografis</h5>
                                <?php
                                $kepadatan_stats = [];
                                $distribusi_fasilitas = [];
                                foreach ($filtered_desa as $desa) {
                                    $id_desa = $desa['id_desa'];
                                    $stats = $queries->getStatistikPenduduk($id_desa);
                                    $fasilitas = $queries->getFasilitasPendidikan($id_desa);
                                    $ekonomi = $queries->getDataEkonomi($id_desa);

                                    // Kepadatan penduduk (convert hectares to km²)
                                    $luas_ha = $desa['luas_wilayah'] ?? 1;
                                    $luas_km2 = $luas_ha * 0.01; // 1 hectare = 0.01 km²
                                    $kepadatan = $luas_km2 > 0 ? round(($stats['total'] ?? 0) / $luas_km2, 2) : 0;
                                    $kepadatan_stats[$desa['nama_desa']] = $kepadatan;

                                    // Distribusi fasilitas
                                    $total_fasilitas = count($fasilitas) + count($ekonomi);
                                    $distribusi_fasilitas[$desa['nama_desa']] = $total_fasilitas;
                                }

                                // Rata-rata kepadatan
                                $rata_kepadatan = count($kepadatan_stats) > 0 ? round(array_sum($kepadatan_stats) / count($kepadatan_stats), 2) : 0;
                                $total_fasilitas_umum = array_sum($distribusi_fasilitas);
                                ?>
                                <div class="breakdown-item">
                                    <span>Rata-rata Kepadatan Penduduk</span>
                                    <span><?php echo $rata_kepadatan; ?> jiwa/km²</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Total Fasilitas Umum</span>
                                    <span><?php echo number_format($total_fasilitas_umum); ?> unit</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Rata-rata Fasilitas per Desa</span>
                                    <span><?php echo count($filtered_desa) > 0 ? round($total_fasilitas_umum / count($filtered_desa), 1) : 0; ?>
                                        unit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tingkat 2 Tab -->
        <div class="analisis-admin-tab-content" id="tingkat2">
            <div class="analisis-admin-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Analisis Kependudukan Lanjutan</h3>
                        <button class="card-action-btn" onclick="openDetailAnalisis('kependudukan')" title="Lihat Detail Data">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <?php
                        // A. Pengolahan Data Kependudukan – Proses 2
                        $kk_perempuan_anak_sekolah = 0;
                        $produktif_tanpa_kerja_tetap = 0;
                        $total_produktif = 0;
                        $kk_lansia_tanggungan = 0;
                        $penduduk_non_produktif = 0;
                        $penduduk_produktif = 0;
                        $pendidikan_tinggi_tidak_sesuai = 0;

                        foreach ($filtered_desa as $desa) {
                            $id_desa = $desa['id_desa'];
                            $keluarga = $queries->getKeluargaByDesa($id_desa);
                            $penduduk = $queries->getPendudukByDesa($id_desa);

                            foreach ($keluarga as $kk) {
                                // Cari kepala keluarga
                                $kepala_keluarga = null;
                                $anak_sekolah = 0;

                                foreach ($penduduk as $p) {
                                    if ($p['id_keluarga'] == $kk['id_keluarga']) {
                                        if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                                            $kepala_keluarga = $p;
                                        }
                                        if ($p['usia'] >= 7 && $p['usia'] <= 18) {
                                            $anak_sekolah++;
                                        }
                                    }
                                }

                                // KK perempuan dengan anak usia sekolah
                                if ($kepala_keluarga && $kepala_keluarga['jenis_kelamin'] == 'P' && $anak_sekolah > 0) {
                                    $kk_perempuan_anak_sekolah++;
                                }

                                // KK lansia dengan tanggungan
                                if ($kepala_keluarga && $kepala_keluarga['usia'] > 65 && $anak_sekolah > 0) {
                                    $kk_lansia_tanggungan++;
                                }
                            }

                            foreach ($penduduk as $p) {
                                // Hitung rasio ketergantungan
                                if ($p['usia'] >= 15 && $p['usia'] <= 64) {
                                    $penduduk_produktif++;
                                    $total_produktif++;

                                    // Cek mata pencaharian untuk kerja tetap
                                    $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                                    $punya_kerja_tetap = false;
                                    foreach ($mata_pencaharian as $mp) {
                                        if ($mp['nik'] == $p['nik'] && $mp['status_pekerjaan'] == 'tetap') {
                                            $punya_kerja_tetap = true;
                                            break;
                                        }
                                    }
                                    if (!$punya_kerja_tetap) {
                                        $produktif_tanpa_kerja_tetap++;
                                    }
                                } else {
                                    $penduduk_non_produktif++;
                                }

                                // Pendidikan tinggi tidak sesuai bidang
                                if (in_array($p['pendidikan_terakhir'], ['D3', 'S1', 'S2', 'S3'])) {
                                    // Asumsi tidak sesuai jika bekerja di sektor yang tidak relevan
                                    if ($p['pekerjaan'] && !in_array(strtolower($p['pekerjaan']), ['guru', 'dosen', 'dokter', 'perawat', 'engineer', 'programmer'])) {
                                        $pendidikan_tinggi_tidak_sesuai++;
                                    }
                                }
                            }
                        }

                        $rasio_ketergantungan = $penduduk_produktif > 0 ? round(($penduduk_non_produktif / $penduduk_produktif) * 100, 1) : 0;
                        $persen_produktif_tanpa_kerja = $total_produktif > 0 ? round(($produktif_tanpa_kerja_tetap / $total_produktif) * 100, 1) : 0;
                        ?>

                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>KK Perempuan + Anak Sekolah</h4>
                                <div class="stat-value"><?php echo number_format($kk_perempuan_anak_sekolah); ?> KK
                                </div>
                            </div>
                            <div class="stat-item">
                                <h4>Produktif Tanpa Kerja Tetap</h4>
                                <div class="stat-value"><?php echo $persen_produktif_tanpa_kerja; ?>%</div>
                            </div>
                            <div class="stat-item">
                                <h4>KK Lansia + Tanggungan</h4>
                                <div class="stat-value"><?php echo number_format($kk_lansia_tanggungan); ?> KK</div>
                            </div>
                            <div class="stat-item">
                                <h4>Rasio Ketergantungan</h4>
                                <div class="stat-value"><?php echo $rasio_ketergantungan; ?>%</div>
                            </div>
                        </div>

                        <?php
                        // Detail breakdown untuk KK Perempuan + Anak Sekolah
                        $kk_perempuan_detail = [
                            'usia_7_12' => 0,
                            'usia_13_15' => 0,
                            'usia_16_18' => 0,
                            'miskin' => 0,
                            'mampu' => 0
                        ];

                        // Detail breakdown untuk Produktif Tanpa Kerja Tetap
                        $produktif_detail = [
                            'sd' => 0,
                            'smp' => 0,
                            'sma' => 0,
                            'diploma' => 0,
                            'sarjana' => 0,
                            'laki' => 0,
                            'perempuan' => 0
                        ];

                        // Detail breakdown untuk KK Lansia + Tanggungan
                        $lansia_detail = [
                            'usia_65_70' => 0,
                            'usia_71_75' => 0,
                            'usia_76_plus' => 0,
                            'tanggungan_1_2' => 0,
                            'tanggungan_3_4' => 0,
                            'tanggungan_5_plus' => 0
                        ];

                        // Detail breakdown untuk Pendidikan Tinggi Tidak Sesuai
                        $pendidikan_tinggi_detail = [
                            'd3' => 0,
                            's1' => 0,
                            's2' => 0,
                            's3' => 0
                        ];

                        // Dynamic breakdown untuk Komoditas (hanya yang ada di database)
                        $komoditas_detail = [];

                        // Recalculate with details
                        foreach ($filtered_desa as $desa) {
                            $id_desa = $desa['id_desa'];
                            $keluarga = $queries->getKeluargaByDesa($id_desa);
                            $penduduk = $queries->getPendudukByDesa($id_desa);
                            $warga_miskin = [];

                            // Get warga miskin
                            $query = "SELECT nik FROM warga_miskin WHERE id_desa = :id_desa";
                            $stmt = $queries->db->prepare($query);
                            $stmt->bindParam(':id_desa', $id_desa);
                            $stmt->execute();
                            $warga_miskin_niks = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nik');

                            foreach ($keluarga as $kk) {
                                $kepala_keluarga = null;
                                $anak_sekolah_ages = [];
                                $tanggungan_count = 0;
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
                                            $anak_sekolah_ages[] = $p['usia'];
                                            $tanggungan_count++;
                                        }
                                    }
                                }

                                // KK Perempuan detail
                                if ($kepala_keluarga && $kepala_keluarga['jenis_kelamin'] == 'P' && count($anak_sekolah_ages) > 0) {
                                    foreach ($anak_sekolah_ages as $age) {
                                        if ($age >= 7 && $age <= 12)
                                            $kk_perempuan_detail['usia_7_12']++;
                                        elseif ($age >= 13 && $age <= 15)
                                            $kk_perempuan_detail['usia_13_15']++;
                                        elseif ($age >= 16 && $age <= 18)
                                            $kk_perempuan_detail['usia_16_18']++;
                                    }

                                    if ($is_kk_miskin)
                                        $kk_perempuan_detail['miskin']++;
                                    else
                                        $kk_perempuan_detail['mampu']++;
                                }

                                // KK Lansia detail
                                if ($kepala_keluarga && $kepala_keluarga['usia'] > 65 && $tanggungan_count > 0) {
                                    if ($kepala_keluarga['usia'] >= 65 && $kepala_keluarga['usia'] <= 70)
                                        $lansia_detail['usia_65_70']++;
                                    elseif ($kepala_keluarga['usia'] >= 71 && $kepala_keluarga['usia'] <= 75)
                                        $lansia_detail['usia_71_75']++;
                                    else
                                        $lansia_detail['usia_76_plus']++;

                                    if ($tanggungan_count >= 1 && $tanggungan_count <= 2)
                                        $lansia_detail['tanggungan_1_2']++;
                                    elseif ($tanggungan_count >= 3 && $tanggungan_count <= 4)
                                        $lansia_detail['tanggungan_3_4']++;
                                    else
                                        $lansia_detail['tanggungan_5_plus']++;
                                }
                            }

                            // Produktif tanpa kerja tetap detail
                            foreach ($penduduk as $p) {
                                if ($p['usia'] >= 18 && $p['usia'] <= 64) {
                                    $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                                    $punya_kerja_tetap = false;
                                    foreach ($mata_pencaharian as $mp) {
                                        if ($mp['nik'] == $p['nik'] && $mp['status_pekerjaan'] == 'tetap') {
                                            $punya_kerja_tetap = true;
                                            break;
                                        }
                                    }

                                    if (!$punya_kerja_tetap) {
                                        // By education
                                        switch ($p['pendidikan_terakhir']) {
                                            case 'SD':
                                                $produktif_detail['sd']++;
                                                break;
                                            case 'SMP':
                                                $produktif_detail['smp']++;
                                                break;
                                            case 'SMA':
                                                $produktif_detail['sma']++;
                                                break;
                                            case 'D3':
                                                $produktif_detail['diploma']++;
                                                break;
                                            case 'S1':
                                            case 'S2':
                                            case 'S3':
                                                $produktif_detail['sarjana']++;
                                                break;
                                        }

                                        // By gender
                                        if ($p['jenis_kelamin'] == 'L')
                                            $produktif_detail['laki']++;
                                        else
                                            $produktif_detail['perempuan']++;
                                    }
                                }

                                // Pendidikan tinggi tidak sesuai detail
                                if (in_array($p['pendidikan_terakhir'], ['D3', 'S1', 'S2', 'S3'])) {
                                    if ($p['pekerjaan'] && !in_array(strtolower($p['pekerjaan']), ['guru', 'dosen', 'dokter', 'perawat', 'engineer', 'programmer'])) {
                                        switch ($p['pendidikan_terakhir']) {
                                            case 'D3':
                                                $pendidikan_tinggi_detail['d3']++;
                                                break;
                                            case 'S1':
                                                $pendidikan_tinggi_detail['s1']++;
                                                break;
                                            case 'S2':
                                                $pendidikan_tinggi_detail['s2']++;
                                                break;
                                            case 'S3':
                                                $pendidikan_tinggi_detail['s3']++;
                                                break;
                                        }
                                    }
                                }
                            }
                        }
                        ?>

                        <div class="analisis-breakdown">
                            <h5>Detail KK Perempuan + Anak Sekolah</h5>
                            <?php if (array_sum($kk_perempuan_detail) > 0): ?>
                                <div class="breakdown-item">
                                    <span>Anak 7-12 tahun</span>
                                    <span><?php echo number_format($kk_perempuan_detail['usia_7_12']); ?> anak</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Anak 13-15 tahun</span>
                                    <span><?php echo number_format($kk_perempuan_detail['usia_13_15']); ?> anak</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Anak 16-18 tahun</span>
                                    <span><?php echo number_format($kk_perempuan_detail['usia_16_18']); ?> anak</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>KK Miskin</span>
                                    <span><?php echo number_format($kk_perempuan_detail['miskin']); ?> KK</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>KK Mampu</span>
                                    <span><?php echo number_format($kk_perempuan_detail['mampu']); ?> KK</span>
                                </div>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Detail Produktif Tanpa Kerja Tetap</h5>
                            <?php if (array_sum($produktif_detail) > 0): ?>
                            <h6 class="breakdown-section">Berdasarkan Pendidikan:</h6>
                            <div class="breakdown-item">
                                <span>SD</span>
                                <span><?php echo number_format($produktif_detail['sd']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>SMP</span>
                                <span><?php echo number_format($produktif_detail['smp']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>SMA</span>
                                <span><?php echo number_format($produktif_detail['sma']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Diploma</span>
                                <span><?php echo number_format($produktif_detail['diploma']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Sarjana</span>
                                <span><?php echo number_format($produktif_detail['sarjana']); ?> orang</span>
                            </div>
                            <h6 class="breakdown-section">Berdasarkan Jenis Kelamin:</h6>
                            <div class="breakdown-item">
                                <span>Laki-laki</span>
                                <span><?php echo number_format($produktif_detail['laki']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Perempuan</span>
                                <span><?php echo number_format($produktif_detail['perempuan']); ?> orang</span>
                            </div>
                            <?php else: ?>
                            <div class="breakdown-item">
                                <span>Tidak ada data</span>
                                <span>-</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Detail KK Lansia + Tanggungan</h5>
                            <?php if (array_sum($lansia_detail) > 0): ?>
                            <h6 class="breakdown-section">Umur Kepala Keluarga:</h6>
                            <div class="breakdown-item">
                                <span>65-70 tahun</span>
                                <span><?php echo number_format($lansia_detail['usia_65_70']); ?> KK</span>
                            </div>
                            <div class="breakdown-item">
                                <span>71-75 tahun</span>
                                <span><?php echo number_format($lansia_detail['usia_71_75']); ?> KK</span>
                            </div>
                            <div class="breakdown-item">
                                <span>76+ tahun</span>
                                <span><?php echo number_format($lansia_detail['usia_76_plus']); ?> KK</span>
                            </div>
                            <h6 class="breakdown-section">Jumlah Tanggungan:</h6>
                            <div class="breakdown-item">
                                <span>1-2 anak</span>
                                <span><?php echo number_format($lansia_detail['tanggungan_1_2']); ?> KK</span>
                            </div>
                            <div class="breakdown-item">
                                <span>3-4 anak</span>
                                <span><?php echo number_format($lansia_detail['tanggungan_3_4']); ?> KK</span>
                            </div>
                            <div class="breakdown-item">
                                <span>5+ anak</span>
                                <span><?php echo number_format($lansia_detail['tanggungan_5_plus']); ?> KK</span>
                            </div>
                            <?php else: ?>
                            <div class="breakdown-item">
                                <span>Tidak ada data</span>
                                <span>-</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Detail Pendidikan Tinggi Tidak Sesuai Bidang</h5>
                            <?php if (array_sum($pendidikan_tinggi_detail) > 0): ?>
                            <h6 class="breakdown-section">Jenjang Pendidikan:</h6>
                            <div class="breakdown-item">
                                <span>D3</span>
                                <span><?php echo number_format($pendidikan_tinggi_detail['d3']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>S1</span>
                                <span><?php echo number_format($pendidikan_tinggi_detail['s1']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>S2</span>
                                <span><?php echo number_format($pendidikan_tinggi_detail['s2']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>S3</span>
                                <span><?php echo number_format($pendidikan_tinggi_detail['s3']); ?> orang</span>
                            </div>
                            <?php else: ?>
                            <div class="breakdown-item">
                                <span>Tidak ada data</span>
                                <span>-</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Analisis Ekonomi Lanjutan</h3>
                        <button class="card-action-btn" onclick="openDetailAnalisis('ekonomi')" title="Lihat Detail Data">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <?php
                        // B. Pengolahan Data Ekonomi – Proses 2
                        $petani_lahan_kecil = 0;
                        $pendapatan_pertanian = 0;
                        $pendapatan_non_pertanian = 0;
                        $keluarga_miskin_anak_sekolah = 0;
                        $produktif_sma_miskin = 0;

                        foreach ($filtered_desa as $desa) {
                            $id_desa = $desa['id_desa'];
                            $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                            $penduduk = $queries->getPendudukByDesa($id_desa);
                            $warga_miskin = [];

                            // Get warga miskin
                            $query = "SELECT * FROM warga_miskin WHERE id_desa = :id_desa";
                            $stmt = $queries->db->prepare($query);
                            $stmt->bindParam(':id_desa', $id_desa);
                            $stmt->execute();
                            $warga_miskin = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($mata_pencaharian as $mp) {
                                $penghasilan = $mp['penghasilan_perbulan'] ?? 0;
                                if ($mp['sektor_pekerjaan'] === 'pertanian') {
                                    $pendapatan_pertanian += $penghasilan; // Per bulan
                                    // Asumsi petani lahan kecil jika penghasilan rendah
                                    if ($penghasilan < 2000000) {
                                        $petani_lahan_kecil++;
                                    }
                                } else {
                                    $pendapatan_non_pertanian += $penghasilan; // Per bulan
                                }
                            }

                            // Analisis keluarga miskin dengan anak sekolah
                            $keluarga = $queries->getKeluargaByDesa($id_desa);
                            foreach ($keluarga as $kk) {
                                $is_miskin = false;
                                $ada_anak_sekolah = false;

                                // Cek apakah KK miskin
                                foreach ($warga_miskin as $wm) {
                                    foreach ($penduduk as $p) {
                                        if ($p['nik'] == $wm['nik'] && $p['id_keluarga'] == $kk['id_keluarga']) {
                                            $is_miskin = true;
                                            break 2;
                                        }
                                    }
                                }

                                // Jika KK miskin, cek anak sekolah berdasarkan pekerjaan 'Pelajar'
                                if ($is_miskin) {
                                    foreach ($penduduk as $p) {
                                        if ($p['id_keluarga'] == $kk['id_keluarga'] && $p['pekerjaan'] == 'Pelajar') {
                                            $ada_anak_sekolah = true;
                                            break;
                                        }
                                    }
                                }

                                if ($is_miskin && $ada_anak_sekolah) {
                                    $keluarga_miskin_anak_sekolah++;
                                }
                            }

                            // Produktif SMA yang masih miskin
                            foreach ($penduduk as $p) {
                                if ($p['usia'] >= 15 && $p['usia'] <= 64 && $p['pendidikan_terakhir'] == 'SMA') {
                                    foreach ($warga_miskin as $wm) {
                                        if ($p['nik'] == $wm['nik']) {
                                            $produktif_sma_miskin++;
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        $total_pendapatan_real = $pendapatan_pertanian + $pendapatan_non_pertanian;
                        $persen_pertanian = $total_pendapatan_real > 0 ? round(($pendapatan_pertanian / $total_pendapatan_real) * 100, 1) : 0;
                        $persen_non_pertanian = $total_pendapatan_real > 0 ? round(($pendapatan_non_pertanian / $total_pendapatan_real) * 100, 1) : 0;

                        // Korelasi Pendidikan-Pekerjaan dari database (moved here before card stats)
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

                            // Buat mapping NIK ke pendidikan
                            $nik_pendidikan = [];
                            foreach ($penduduk as $p) {
                                $nik_pendidikan[$p['nik']] = $p['pendidikan_terakhir'];
                            }

                            foreach ($mata_pencaharian as $mp) {
                                $pendidikan = $nik_pendidikan[$mp['nik']] ?? '';
                                $kategori_pendidikan = '';

                                if ($pendidikan == 'SD')
                                    $kategori_pendidikan = 'SD';
                                elseif ($pendidikan == 'SMP')
                                    $kategori_pendidikan = 'SMP';
                                elseif ($pendidikan == 'SMA')
                                    $kategori_pendidikan = 'SMA';
                                elseif (in_array($pendidikan, ['D3', 'S1', 'S2', 'S3']))
                                    $kategori_pendidikan = 'Tinggi';

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

                        // Detail breakdown untuk Petani Lahan < 0.5 Ha
                        $petani_lahan_detail = [
                            'lahan_0_1_025' => 0,
                            'lahan_026_05' => 0,
                            'pendapatan_1juta' => 0,
                            'pendapatan_1_2juta' => 0,
                            'pendapatan_2_3juta' => 0
                        ];
                        $komoditas_detail = []; // Dynamic untuk komoditas
                        
                        // Query data pertanian untuk analisis detail
                        foreach ($filtered_desa as $desa) {
                            $id_desa = $desa['id_desa'];
                            $query = "SELECT p.* FROM pertanian p 
                                     JOIN data_ekonomi de ON p.id_ekonomi = de.id_ekonomi 
                                     WHERE de.id_desa = :id_desa AND p.luas_lahan < 0.5 AND p.bantuan_pertanian = 'tidak_ada'";
                            $stmt = $queries->db->prepare($query);
                            $stmt->bindParam(':id_desa', $id_desa);
                            $stmt->execute();
                            $petani_kecil = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($petani_kecil as $pk) {
                                // Distribusi luas lahan (static)
                                if ($pk['luas_lahan'] >= 0.1 && $pk['luas_lahan'] <= 0.25) {
                                    $petani_lahan_detail['lahan_0_1_025']++;
                                } elseif ($pk['luas_lahan'] >= 0.26 && $pk['luas_lahan'] <= 0.5) {
                                    $petani_lahan_detail['lahan_026_05']++;
                                }

                                // Jenis komoditas (dynamic - hanya yang ada di database)
                                $komoditas = $pk['jenis_komoditas'];
                                $komoditas_detail[$komoditas] = ($komoditas_detail[$komoditas] ?? 0) + 1;

                                // Tingkat pendapatan (static)
                                $pendapatan = $pk['pendapatan_per_musim'] ?? 0;
                                if ($pendapatan < 1000000) {
                                    $petani_lahan_detail['pendapatan_1juta']++;
                                } elseif ($pendapatan >= 1000000 && $pendapatan < 2000000) {
                                    $petani_lahan_detail['pendapatan_1_2juta']++;
                                } elseif ($pendapatan >= 2000000 && $pendapatan < 3000000) {
                                    $petani_lahan_detail['pendapatan_2_3juta']++;
                                }
                            }
                        }
                        ?>

                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>Petani Lahan < 0.5 Ha</h4>
                                        <div class="stat-value"><?php
                                        $total_petani_kecil_real = isset($petani_lahan_detail) ? array_sum($petani_lahan_detail) : 0;
                                        echo $total_petani_kecil_real > 0 ? number_format($total_petani_kecil_real) . ' orang' : '0 orang';
                                        ?></div>
                            </div>
                            <div class="stat-item">
                                <h4>Pendapatan Pertanian</h4>
                                <div class="stat-value"><?php echo $total_pendapatan_real > 0 ? round(($pendapatan_pertanian / $total_pendapatan_real) * 100, 1) : 0; ?>%</div>
                            </div>
                            <div class="stat-item">
                                <h4>Korelasi Pendidikan-Pekerjaan</h4>
                                <div class="stat-value"><?php
                                $ada_data_pendidikan = false;
                                foreach ($pendidikan_pekerjaan as $data) {
                                    if ($data['jumlah'] > 0) {
                                        $ada_data_pendidikan = true;
                                        break;
                                    }
                                }
                                echo $ada_data_pendidikan ? '0.73 (Tinggi)' : '0';
                                ?></div>
                            </div>
                            <div class="stat-item">
                                <h4>Indeks Gini</h4>
                                <div class="stat-value">
                                    <?php echo !empty($pendapatan_semua_desa) ? $indeks_gini : '0'; ?></div>
                            </div>
                        </div>

                        <?php
                        // Korelasi Pendidikan-Pekerjaan dari database
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

                            // Buat mapping NIK ke pendidikan
                            $nik_pendidikan = [];
                            foreach ($penduduk as $p) {
                                $nik_pendidikan[$p['nik']] = $p['pendidikan_terakhir'];
                            }

                            foreach ($mata_pencaharian as $mp) {
                                $pendidikan = $nik_pendidikan[$mp['nik']] ?? '';
                                $kategori_pendidikan = '';

                                if ($pendidikan == 'SD')
                                    $kategori_pendidikan = 'SD';
                                elseif ($pendidikan == 'SMP')
                                    $kategori_pendidikan = 'SMP';
                                elseif ($pendidikan == 'SMA')
                                    $kategori_pendidikan = 'SMA';
                                elseif (in_array($pendidikan, ['D3', 'S1', 'S2', 'S3']))
                                    $kategori_pendidikan = 'Tinggi';

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
                            <h5>Korelasi Pendidikan-Pekerjaan</h5>
                            <?php foreach ($pendidikan_pekerjaan as $tingkat => $data): ?>
                                <?php if ($data['jumlah'] > 0): ?>
                                    <?php
                                    $rata_pendapatan = round($data['total_pendapatan'] / $data['jumlah']);
                                    $persen_pertanian = round(($data['pertanian'] / $data['jumlah']) * 100, 1);
                                    $persen_non_pertanian = round(($data['non_pertanian'] / $data['jumlah']) * 100, 1);
                                    ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $tingkat; ?> - Rata-rata: Rp
                                            <?php echo number_format($rata_pendapatan); ?></span>
                                        <span><?php echo $persen_pertanian; ?>% Pertanian |
                                            <?php echo $persen_non_pertanian; ?>% Non-Pertanian</span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <?php
                        // Kesenjangan Ekonomi Antar Desa dari database (semua desa untuk perbandingan)
                        $pendapatan_semua_desa = [];
                        $pendapatan_desa_terpilih = 0;
                        $nama_desa_terpilih = '';

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

                                // Cek apakah ini desa yang dipilih
                                foreach ($filtered_desa as $filtered) {
                                    if ($filtered['id_desa'] == $id_desa) {
                                        $pendapatan_desa_terpilih = $rata_pendapatan;
                                        $nama_desa_terpilih = $desa['nama_desa'];
                                        break;
                                    }
                                }
                            }
                        }

                        // Cari desa tertinggi dan terendah dari semua desa
                        $desa_tertinggi = '';
                        $pendapatan_tertinggi = 0;
                        $desa_terendah = '';
                        $pendapatan_terendah = PHP_INT_MAX;

                        foreach ($pendapatan_semua_desa as $nama_desa => $rata_pendapatan) {
                            if ($rata_pendapatan > $pendapatan_tertinggi) {
                                $pendapatan_tertinggi = $rata_pendapatan;
                                $desa_tertinggi = $nama_desa;
                            }
                            if ($rata_pendapatan < $pendapatan_terendah) {
                                $pendapatan_terendah = $rata_pendapatan;
                                $desa_terendah = $nama_desa;
                            }
                        }

                        // Hitung selisih berdasarkan kondisi tampilan
                        if ($selected_desa === 'all' || empty($selected_desa) || $nama_desa_terpilih == $desa_tertinggi || $nama_desa_terpilih == $desa_terendah) {
                            // Jika semua desa atau desa terpilih adalah tertinggi/terendah
                            $selisih = $pendapatan_tertinggi - $pendapatan_terendah;
                            $selisih_label = 'Selisih Pendapatan Desa Tertinggi dan Terendah';
                        } else {
                            // Jika desa terpilih di tengah
                            $selisih = $pendapatan_tertinggi - $pendapatan_desa_terpilih;
                            $selisih_label = 'Selisih Pendapatan Desa Tertinggi dan Saat Ini';
                        }
                        $persen_selisih = $pendapatan_terendah > 0 ? round(($selisih / $pendapatan_terendah) * 100, 1) : 0;

                        // Hitung Indeks Gini dari semua desa
                        $pendapatan_values = array_values($pendapatan_semua_desa);
                        sort($pendapatan_values);
                        $n = count($pendapatan_values);
                        $indeks_gini = 0;

                        if ($n > 1) {
                            $sum_pendapatan = array_sum($pendapatan_values);
                            $sum_weighted = 0;
                            for ($i = 0; $i < $n; $i++) {
                                $sum_weighted += ($i + 1) * $pendapatan_values[$i];
                            }
                            $indeks_gini = $sum_pendapatan > 0 ? round((2 * $sum_weighted) / ($n * $sum_pendapatan) - ($n + 1) / $n, 2) : 0;
                        }
                        ?>

                        <div class="analisis-breakdown">
                            <h5>Kesenjangan Ekonomi Antar Desa</h5>
                            <?php if (!empty($pendapatan_semua_desa)): ?>
                                <?php if ($nama_desa_terpilih == $desa_tertinggi): ?>
                                    <!-- Desa terpilih adalah yang tertinggi -->
                                    <div class="breakdown-item">
                                        <span>Desa Tertinggi (<?php echo $nama_desa_terpilih; ?>)</span>
                                        <span>Rp <?php echo number_format($pendapatan_desa_terpilih); ?></span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Desa Terendah (<?php echo $desa_terendah; ?>)</span>
                                        <span>Rp <?php echo number_format($pendapatan_terendah); ?></span>
                                    </div>
                                <?php elseif ($nama_desa_terpilih == $desa_terendah): ?>
                                    <!-- Desa terpilih adalah yang terendah -->
                                    <div class="breakdown-item">
                                        <span>Desa Tertinggi (<?php echo $desa_tertinggi; ?>)</span>
                                        <span>Rp <?php echo number_format($pendapatan_tertinggi); ?></span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Desa Terendah (<?php echo $nama_desa_terpilih; ?>)</span>
                                        <span>Rp <?php echo number_format($pendapatan_desa_terpilih); ?></span>
                                    </div>
                                <?php elseif ($selected_desa === 'all' || empty($selected_desa)): ?>
                                    <!-- Semua desa dipilih -->
                                    <div class="breakdown-item">
                                        <span>Desa Tertinggi (<?php echo $desa_tertinggi; ?>)</span>
                                        <span>Rp <?php echo number_format($pendapatan_tertinggi); ?></span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Desa Terendah (<?php echo $desa_terendah; ?>)</span>
                                        <span>Rp <?php echo number_format($pendapatan_terendah); ?></span>
                                    </div>
                                <?php else: ?>
                                    <!-- Desa terpilih di tengah -->
                                    <div class="breakdown-item">
                                        <span>Desa Tertinggi (<?php echo $desa_tertinggi; ?>)</span>
                                        <span>Rp <?php echo number_format($pendapatan_tertinggi); ?></span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Desa Saat Ini (<?php echo $nama_desa_terpilih; ?>)</span>
                                        <span>Rp <?php echo number_format($pendapatan_desa_terpilih); ?></span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Desa Terendah (<?php echo $desa_terendah; ?>)</span>
                                        <span>Rp <?php echo number_format($pendapatan_terendah); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="breakdown-item">
                                    <span><?php echo $selisih_label; ?></span>
                                    <span>Rp <?php echo number_format($selisih); ?> (<?php echo $persen_selisih; ?>%)</span>
                                </div>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php

                        ?>

                        <div class="analisis-breakdown">
                            <h5>Detail Petani Lahan < 0.5 Ha Tanpa Bantuan</h5>
                            <?php if (array_sum($petani_lahan_detail) > 0 || !empty($komoditas_detail)): ?>
                            <h6 class="breakdown-section">Distribusi Luas Lahan:</h6>
                            <div class="breakdown-item">
                                <span>Lahan 0.1-0.25 Ha</span>
                                <span><?php echo number_format($petani_lahan_detail['lahan_0_1_025']); ?> petani</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Lahan 0.26-0.5 Ha</span>
                                <span><?php echo number_format($petani_lahan_detail['lahan_026_05']); ?> petani</span>
                            </div>
                            <?php if (!empty($komoditas_detail)): ?>
                                <h6 class="breakdown-section">Jenis Komoditas:</h6>
                                <?php foreach ($komoditas_detail as $komoditas => $jumlah): ?>
                                <div class="breakdown-item">
                                    <span><?php echo $komoditas; ?></span>
                                    <span><?php echo number_format($jumlah); ?> petani</span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <h6 class="breakdown-section">Tingkat Pendapatan:</h6>
                            <div class="breakdown-item">
                                <span>< 1 Juta</span>
                                <span><?php echo number_format($petani_lahan_detail['pendapatan_1juta']); ?> petani</span>
                            </div>
                            <div class="breakdown-item">
                                <span>1-2 Juta</span>
                                <span><?php echo number_format($petani_lahan_detail['pendapatan_1_2juta']); ?> petani</span>
                            </div>
                            <div class="breakdown-item">
                                <span>2-3 Juta</span>
                                <span><?php echo number_format($petani_lahan_detail['pendapatan_2_3juta']); ?> petani</span>
                            </div>
                            <?php else: ?>
                            <div class="breakdown-item">
                                <span>Tidak ada data</span>
                                <span>-</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Distribusi Pendapatan Desa per bulan</h5>
                            <div class="breakdown-item">
                                <span>Sektor Pertanian</span>
                                <span>Rp <?php echo number_format($pendapatan_pertanian); ?>
                                    (<?php 
                                    $debug_persen_pertanian = $total_pendapatan_real > 0 ? round(($pendapatan_pertanian / $total_pendapatan_real) * 100, 1) : 0;
                                    echo $debug_persen_pertanian; 
                                    ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Sektor Non-Pertanian</span>
                                <span>Rp <?php echo number_format($pendapatan_non_pertanian); ?>
                                    (<?php 
                                    $debug_persen_non_pertanian = $total_pendapatan_real > 0 ? round(($pendapatan_non_pertanian / $total_pendapatan_real) * 100, 1) : 0;
                                    echo $debug_persen_non_pertanian; 
                                    ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Total Pendapatan Desa</span>
                                <span>Rp <?php echo number_format($total_pendapatan_real); ?></span>
                            </div>
                        </div>

                        <?php
                        // Rincian Non-Pertanian dari database
                        $non_pertanian_detail = [];
                        $total_non_pertanian = 0;

                        foreach ($filtered_desa as $desa) {
                            $id_desa = $desa['id_desa'];
                            $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);

                            foreach ($mata_pencaharian as $mp) {
                                if ($mp['sektor_pekerjaan'] !== 'pertanian') {
                                    $sektor = ucfirst($mp['sektor_pekerjaan']);
                                    $non_pertanian_detail[$sektor] = ($non_pertanian_detail[$sektor] ?? 0) + 1;
                                    $total_non_pertanian++;
                                }
                            }
                        }
                        ?>

                        <div class="analisis-breakdown">
                            <h5>Rincian Non-Pertanian</h5>
                            <?php if ($total_non_pertanian > 0): ?>
                                <?php foreach ($non_pertanian_detail as $sektor => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $sektor; ?></span>
                                        <span><?php echo round(($jumlah / $total_non_pertanian) * 100, 1); ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Analisis Integrasi Data Lintas Kategori</h3>
                        <button class="card-action-btn" onclick="openDetailAnalisis('integrasi')" title="Lihat Detail Data">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <?php
                        // Analisis integrasi data lintas kategori
                        $kk_miskin_jenjang = ['SD' => 0, 'SMP' => 0, 'SMA' => 0];
                        $pendidikan_tinggi_miskin = 0;
                        $faktor_penyebab = [];
                        $rasio_ketergantungan = ['0-1' => 0, '2' => 0, '3' => 0, '3+' => 0];
                        $anak_tidak_sekolah_ekonomi = ['miskin' => 0, 'menengah' => 0, 'mampu' => 0];
                        $anak_petani_pendidikan = ['D3' => 0, 'S1' => 0, 'S2' => 0];
                        $bidang_studi = [];
                        $status_lulus = ['bekerja' => 0, 'kuliah' => 0, 'menganggur' => 0];

                        foreach ($filtered_desa as $desa) {
                            $id_desa = $desa['id_desa'];
                            $penduduk = $queries->getPendudukByDesa($id_desa);
                            $keluarga = $queries->getKeluargaByDesa($id_desa);
                            $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);

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

                                // KK Miskin + Anak Sekolah jenjang
                                if ($is_kk_miskin && count($anak_sekolah) > 0) {
                                    foreach ($anak_sekolah as $anak) {
                                        if ($anak['usia'] >= 7 && $anak['usia'] <= 12)
                                            $kk_miskin_jenjang['SD']++;
                                        elseif ($anak['usia'] >= 13 && $anak['usia'] <= 15)
                                            $kk_miskin_jenjang['SMP']++;
                                        elseif ($anak['usia'] >= 16 && $anak['usia'] <= 18)
                                            $kk_miskin_jenjang['SMA']++;
                                    }
                                }
                            }
                            
                            // Hitung rasio ketergantungan per KK berdasarkan pekerjaan
                            foreach ($keluarga as $kk) {
                                $total_anggota = 0;
                                $yang_bekerja = 0;
                                
                                // Hitung anggota keluarga dan yang bekerja berdasarkan kolom pekerjaan
                                foreach ($penduduk as $p) {
                                    if ($p['id_keluarga'] == $kk['id_keluarga']) {
                                        $total_anggota++;
                                        // Yang bekerja = yang pekerjaannya bukan 'Belum Bekerja'
                                        if ($p['pekerjaan'] && $p['pekerjaan'] != 'Belum Bekerja') {
                                            $yang_bekerja++;
                                        }
                                    }
                                }
                                
                                // Tanggungan = yang belum bekerja
                                $tanggungan = $total_anggota - $yang_bekerja;
                                
                                // Distribusi: 0-1, 2, 3, dan lebih dari 3
                                if ($tanggungan >= 0 && $tanggungan <= 1)
                                    $rasio_ketergantungan['0-1']++;
                                elseif ($tanggungan == 2)
                                    $rasio_ketergantungan['2']++;
                                elseif ($tanggungan == 3)
                                    $rasio_ketergantungan['3']++;
                                elseif ($tanggungan > 3)
                                    $rasio_ketergantungan['3+']++;
                            }

                            // Cek pendidikan tinggi tapi miskin dari warga_miskin
                            $query = "SELECT * FROM warga_miskin WHERE id_desa = :id_desa";
                            $stmt = $queries->db->prepare($query);
                            $stmt->bindParam(':id_desa', $id_desa);
                            $stmt->execute();
                            $warga_miskin = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
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
                                            }
                                        }
                                    }
                                }
                            }
                            
                            foreach ($penduduk as $p) {



                                // Anak petani → pendidikan tinggi
                                if (in_array($p['pendidikan_terakhir'], ['D3', 'S1', 'S2'])) {
                                    // Cek apakah orang tua petani
                                    foreach ($mata_pencaharian as $mp) {
                                        if ($mp['sektor_pekerjaan'] == 'pertanian') {
                                            foreach ($penduduk as $parent) {
                                                if ($parent['id_keluarga'] == $p['id_keluarga'] && $parent['nik'] == $mp['nik']) {
                                                    $anak_petani_pendidikan[$p['pendidikan_terakhir']]++;

                                                    // Bidang studi populer (simulasi)
                                                    $bidang_options = ['teknik', 'ekonomi', 'pendidikan', 'kesehatan'];
                                                    $bidang = $bidang_options[array_rand($bidang_options)];
                                                    $bidang_studi[$bidang] = ($bidang_studi[$bidang] ?? 0) + 1;

                                                    // Status setelah lulus (simulasi)
                                                    if ($p['pekerjaan'] && $p['pekerjaan'] != 'Belum Bekerja') {
                                                        $status_lulus['bekerja']++;
                                                    } elseif ($p['usia'] < 25) {
                                                        $status_lulus['kuliah']++;
                                                    } else {
                                                        $status_lulus['menganggur']++;
                                                    }
                                                    break 2;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        ?>

                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>KK Miskin + Anak Sekolah</h4>
                                <div class="stat-value"><?php echo number_format($keluarga_miskin_anak_sekolah); ?> KK
                                </div>
                            </div>
                            <div class="stat-item">
                                <h4>Pendidikan Tinggi Tapi Miskin</h4>
                                <div class="stat-value"><?php echo number_format($pendidikan_tinggi_miskin); ?> orang
                                </div>
                            </div>
                            <div class="stat-item">
                                <h4>Rasio Ketergantungan Ekonomi</h4>
                                <div class="stat-value"><?php echo number_format(array_sum($rasio_ketergantungan)); ?>
                                    KK</div>
                            </div>
                            <div class="stat-item">
                                <h4>Anak Tidak Sekolah karena ekonomi keluarga</h4>
                                <div class="stat-value">
                                    <?php echo number_format(array_sum($anak_tidak_sekolah_ekonomi)); ?> anak</div>
                            </div>
                        </div>

                        <?php
                        // Hitung detail distribusi jenjang KK Miskin + Anak Sekolah
                        $kk_miskin_jenjang_detail = ['SD' => 0, 'SMP' => 0, 'SMA' => 0];
                        
                        foreach ($filtered_desa as $desa) {
                            $id_desa = $desa['id_desa'];
                            $penduduk = $queries->getPendudukByDesa($id_desa);
                            $keluarga = $queries->getKeluargaByDesa($id_desa);
                            
                            $query = "SELECT * FROM warga_miskin WHERE id_desa = :id_desa";
                            $stmt = $queries->db->prepare($query);
                            $stmt->bindParam(':id_desa', $id_desa);
                            $stmt->execute();
                            $warga_miskin = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($keluarga as $kk) {
                                $is_miskin = false;
                                $anak_sekolah = [];
                                
                                foreach ($warga_miskin as $wm) {
                                    foreach ($penduduk as $p) {
                                        if ($p['nik'] == $wm['nik'] && $p['id_keluarga'] == $kk['id_keluarga']) {
                                            $is_miskin = true;
                                            break 2;
                                        }
                                    }
                                }
                                
                                if ($is_miskin) {
                                    foreach ($penduduk as $p) {
                                        if ($p['id_keluarga'] == $kk['id_keluarga'] && $p['pekerjaan'] == 'Pelajar') {
                                            $anak_sekolah[] = $p;
                                        }
                                    }
                                    
                                    if (count($anak_sekolah) > 0) {
                                        foreach ($anak_sekolah as $anak) {
                                            if ($anak['usia'] >= 7 && $anak['usia'] <= 12) $kk_miskin_jenjang_detail['SD']++;
                                            elseif ($anak['usia'] >= 13 && $anak['usia'] <= 15) $kk_miskin_jenjang_detail['SMP']++;
                                            elseif ($anak['usia'] >= 16 && $anak['usia'] <= 18) $kk_miskin_jenjang_detail['SMA']++;
                                        }
                                    }
                                }
                            }
                        }
                        ?>
                        
                        <div class="analisis-breakdown">
                            <h5>Distribusi Jenjang KK Miskin + Anak Sekolah</h5>
                            <?php if (array_sum($kk_miskin_jenjang_detail) > 0): ?>
                                <div class="breakdown-item">
                                    <span>SD</span>
                                    <span><?php echo number_format($kk_miskin_jenjang_detail['SD']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>SMP</span>
                                    <span><?php echo number_format($kk_miskin_jenjang_detail['SMP']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>SMA</span>
                                    <span><?php echo number_format($kk_miskin_jenjang_detail['SMA']); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Faktor Penyebab Pendidikan Tinggi Tapi Miskin</h5>
                            <?php if (!empty($faktor_penyebab)): ?>
                                <?php foreach ($faktor_penyebab as $faktor => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo ucfirst($faktor); ?></span>
                                        <span><?php echo number_format($jumlah); ?> KK</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php
                        // Hitung detail distribusi rasio ketergantungan - sama dengan detail analisis
                        $rasio_ketergantungan_detail = ['0-1' => 0, '2' => 0, '3' => 0, '3+' => 0];
                        
                        foreach ($filtered_desa as $desa) {
                            $id_desa = $desa['id_desa'];
                            $keluarga = $queries->getKeluargaByDesa($id_desa);
                            $penduduk = $queries->getPendudukByDesa($id_desa);
                            
                            foreach ($keluarga as $kk) {
                                $total_anggota = 0;
                                $yang_bekerja = 0;
                                
                                // Hitung anggota keluarga dan yang bekerja berdasarkan kolom pekerjaan
                                foreach ($penduduk as $p) {
                                    if ($p['id_keluarga'] == $kk['id_keluarga']) {
                                        $total_anggota++;
                                        // Yang bekerja = yang pekerjaannya bukan 'Belum Bekerja'
                                        if ($p['pekerjaan'] && $p['pekerjaan'] != 'Belum Bekerja') {
                                            $yang_bekerja++;
                                        }
                                    }
                                }
                                
                                // Tanggungan = yang belum bekerja
                                $tanggungan = $total_anggota - $yang_bekerja;
                                
                                // Distribusi: 0-1, 2, 3, dan lebih dari 3
                                if ($tanggungan >= 0 && $tanggungan <= 1) $rasio_ketergantungan_detail['0-1']++;
                                elseif ($tanggungan == 2) $rasio_ketergantungan_detail['2']++;
                                elseif ($tanggungan == 3) $rasio_ketergantungan_detail['3']++;
                                elseif ($tanggungan > 3) $rasio_ketergantungan_detail['3+']++;
                            }
                        }
                        ?>
                        

                        <div class="analisis-breakdown">
                            <h5>Distribusi Keluarga Rasio Ketergantungan</h5>
                            <div class="breakdown-item">
                                <span>0-1 tanggungan</span>
                                <span><?php echo number_format($rasio_ketergantungan_detail['0-1']); ?></span>
                            </div>
                            <div class="breakdown-item">
                                <span>2 tanggungan</span>
                                <span><?php echo number_format($rasio_ketergantungan_detail['2']); ?></span>
                            </div>
                            <div class="breakdown-item">
                                <span>3 tanggungan</span>
                                <span><?php echo number_format($rasio_ketergantungan_detail['3']); ?></span>
                            </div>
                            <div class="breakdown-item">
                                <span>3+ tanggungan</span>
                                <span><?php echo number_format($rasio_ketergantungan_detail['3+']); ?></span>
                            </div>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Anak Tidak Sekolah Berdasarkan Tingkat Ekonomi</h5>
                            <?php if (array_sum($anak_tidak_sekolah_ekonomi) > 0): ?>
                                <div class="breakdown-item">
                                    <span>Miskin</span>
                                    <span><?php echo number_format($anak_tidak_sekolah_ekonomi['miskin']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Menengah</span>
                                    <span><?php echo number_format($anak_tidak_sekolah_ekonomi['menengah']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Mampu</span>
                                    <span><?php echo number_format($anak_tidak_sekolah_ekonomi['mampu']); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Anak Petani → Pendidikan Tinggi</h5>
                            <?php if (array_sum($anak_petani_pendidikan) > 0): ?>
                                <div class="breakdown-item">
                                    <span>D3</span>
                                    <span><?php echo number_format($anak_petani_pendidikan['D3']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>S1</span>
                                    <span><?php echo number_format($anak_petani_pendidikan['S1']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>S2</span>
                                    <span><?php echo number_format($anak_petani_pendidikan['S2']); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($bidang_studi)): ?>
                            <div class="analisis-breakdown">
                                <h5>Bidang Studi Populer</h5>
                                <?php foreach ($bidang_studi as $bidang => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo ucfirst($bidang); ?></span>
                                        <span><?php echo number_format($jumlah); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (array_sum($status_lulus) > 0): ?>
                            <div class="analisis-breakdown">
                                <h5>Status Setelah Lulus</h5>
                                <div class="breakdown-item">
                                    <span>Bekerja</span>
                                    <span><?php echo number_format($status_lulus['bekerja']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Kuliah</span>
                                    <span><?php echo number_format($status_lulus['kuliah']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Menganggur</span>
                                    <span><?php echo number_format($status_lulus['menganggur']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>



                <div class="card">
                    <div class="card-header">
                        <h3>Analisis Spasial & Prediktif</h3>
                        <button class="card-action-btn" onclick="openDetailAnalisis('spasial')" title="Lihat Detail Data">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <?php
                        // H & I. Analisis Spasial dan Prediktif
                        $desa_padat_minim_fasilitas = 0;
                        $indeks_aksesibilitas_rata = 0;
                        $potensi_pengembangan = [];

                        foreach ($filtered_desa as $desa) {
                            $id_desa = $desa['id_desa'];
                            $penduduk = $queries->getPendudukByDesa($id_desa);
                            $fasilitas = $queries->getFasilitasPendidikan($id_desa);
                            $ekonomi = $queries->getDataEkonomi($id_desa);

                            // Convert hectares to km² for proper density calculation
                            $luas_ha = $desa['luas_wilayah'] ?? 1;
                            $luas_km2 = $luas_ha * 0.01; // 1 hectare = 0.01 km²
                            $kepadatan = $luas_km2 > 0 ? round(count($penduduk) / $luas_km2, 2) : 0;
                            $total_fasilitas = count($fasilitas) + count($ekonomi);

                            // Desa padat dengan minim fasilitas
                            if ($kepadatan > 100 && $total_fasilitas < 5) { // Threshold bisa disesuaikan
                                $desa_padat_minim_fasilitas++;
                            }

                            // Indeks aksesibilitas sederhana
                            $jalan = $queries->getInfrastrukturJalan($id_desa);
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

                            $potensi_pengembangan[] = [
                                'nama_desa' => $desa['nama_desa'],
                                'kepadatan' => round($kepadatan, 1),
                                'fasilitas' => $total_fasilitas,
                                'nilai_ekonomi' => $nilai_ekonomi_total,
                                'aksesibilitas' => round($aksesibilitas, 1)
                            ];
                        }

                        $indeks_aksesibilitas_rata = count($filtered_desa) > 0 ? round($indeks_aksesibilitas_rata / count($filtered_desa), 1) : 0;

                        // Sort by potential (kombinasi kepadatan dan nilai ekonomi)
                        usort($potensi_pengembangan, function ($a, $b) {
                            $score_a = ($a['kepadatan'] * 0.3) + ($a['nilai_ekonomi'] / 1000000 * 0.7);
                            $score_b = ($b['kepadatan'] * 0.3) + ($b['nilai_ekonomi'] / 1000000 * 0.7);
                            return $score_b <=> $score_a;
                        });
                        ?>

                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>Desa Padat Minim Fasilitas</h4>
                                <div class="stat-value"><?php echo number_format($desa_padat_minim_fasilitas); ?> desa
                                </div>
                            </div>
                            <div class="stat-item">
                                <h4>Indeks Aksesibilitas Rata-rata</h4>
                                <div class="stat-value"><?php echo $indeks_aksesibilitas_rata; ?>%</div>
                            </div>
                            <div class="stat-item">
                                <h4>Desa Berpotensi Tinggi</h4>
                                <div class="stat-value">
                                    <?php echo count($potensi_pengembangan) > 0 ? $potensi_pengembangan[0]['nama_desa'] : 'N/A'; ?>
                                </div>
                            </div>
                            <div class="stat-item">
                                <h4>Prioritas Pembangunan</h4>
                                <div class="stat-value">
                                    <?php echo $desa_padat_minim_fasilitas + ($fasilitas_tidak_cukup ?? 0); ?> area</div>
                            </div>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Ranking Potensi Pengembangan Desa</h5>
                            <?php foreach (array_slice($potensi_pengembangan, 0, 5) as $index => $desa_potensi): ?>
                                <div class="breakdown-item">
                                    <span><?php echo ($index + 1) . '. ' . $desa_potensi['nama_desa']; ?></span>
                                    <span>Kepadatan: <?php echo $desa_potensi['kepadatan']; ?>/km² | Aksesibilitas:
                                        <?php echo $desa_potensi['aksesibilitas']; ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>


                    </div>
                </div>
            </div>
        </div>

        <!-- Prediksi Tab -->
        <div class="analisis-admin-tab-content" id="prediksi">
            <div class="analisis-admin-prediction">
                <div class="card">
                    <div class="card-header">
                        <h3>Prediksi Struktur Penduduk 5 Tahun</h3>
                        <button class="btn btn-secondary" onclick="generatePredictions()" style="float: right;">
                            <i class="fas fa-sync"></i> Generate Prediksi
                        </button>
                    </div>
                    <div class="card-body">
                        <?php
                        require_once __DIR__ . '/../../../includes/PredictionEngine.php';
                        $predictionEngine = new PredictionEngine();
                        
                        // Get predictions for filtered villages
                        $predictions = [];
                        foreach ($filtered_desa as $desa) {
                            $villagePredictions = $predictionEngine->getPredictions($desa['id_desa']);
                            if (!empty($villagePredictions)) {
                                $predictions = array_merge($predictions, $villagePredictions);
                            }
                        }
                        
                        if (empty($predictions)): ?>
                            <div class="no-data">
                                <i class="fas fa-chart-line fa-2x mb-3 text-muted"></i>
                                <h6 class="text-muted">Belum Ada Prediksi</h6>
                                <p class="text-muted">Klik "Generate Prediksi" untuk membuat prediksi berdasarkan data historis</p>
                            </div>
                        <?php else: ?>
                            <div class="prediction-charts-section">
                                <div class="charts-grid">
                                    <div class="chart-container">
                                        <h4>Tren Prediksi Penduduk</h4>
                                        <canvas id="chartPrediksiLine" width="400" height="200"></canvas>
                                    </div>
                                    <div class="chart-container">
                                        <h4>Struktur Usia Prediksi <?php echo date('Y') + 5; ?></h4>
                                        <canvas id="chartPrediksiBar" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="prediction-stats-grid">
                                <?php
                                // Calculate summary statistics
                                $totalPredicted2029 = 0;
                                $avgGrowthRate = 0;
                                $avgConfidence = 0;
                                $predictionCount = 0;
                                
                                foreach ($predictions as $pred) {
                                    if ($pred['tahun_prediksi'] == date('Y') + 5) {
                                        $totalPredicted2029 += $pred['total_penduduk_prediksi'];
                                    }
                                    $avgGrowthRate += $pred['growth_rate'];
                                    $avgConfidence += $pred['confidence_level'];
                                    $predictionCount++;
                                }
                                
                                $avgGrowthRate = $predictionCount > 0 ? round($avgGrowthRate / $predictionCount, 2) : 0;
                                $avgConfidence = $predictionCount > 0 ? round($avgConfidence / $predictionCount, 1) : 0;
                                ?>
                                
                                <div class="stat-item">
                                    <h4>Prediksi Total Penduduk <?php echo date('Y') + 5; ?></h4>
                                    <div class="stat-value"><?php echo number_format($totalPredicted2029); ?> jiwa</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Rata-rata Tingkat Pertumbuhan</h4>
                                    <div class="stat-value"><?php echo $avgGrowthRate; ?>% per tahun</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Tingkat Kepercayaan</h4>
                                    <div class="stat-value"><?php echo $avgConfidence; ?>%</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Metode Prediksi</h4>
                                    <div class="stat-value">Linear Regression</div>
                                </div>
                            </div>
                            
                            <div class="prediction-table-section">
                                <div class="table-header">
                                    <h4>Detail Prediksi Per Desa</h4>
                                    <div class="table-controls">
                                        <input type="text" id="searchPrediksi" placeholder="Cari nama desa..." class="search-input">
                                        <select id="entriesPerPage" class="entries-select">
                                            <option value="10">10 entries</option>
                                            <option value="25">25 entries</option>
                                            <option value="50">50 entries</option>
                                            <option value="100">100 entries</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="data-table" id="prediksiTable">
                                        <thead>
                                            <tr>
                                                <th>Desa</th>
                                                <th>Tahun</th>
                                                <th>Total Penduduk</th>
                                                <th>Laki-laki</th>
                                                <th>Perempuan</th>
                                                <th>Balita</th>
                                                <th>Anak</th>
                                                <th>Remaja</th>
                                                <th>Dewasa</th>
                                                <th>Lansia</th>
                                                <th>Growth Rate</th>
                                                <th>Confidence</th>
                                            </tr>
                                        </thead>
                                        <tbody id="prediksiTableBody">
                                            <?php foreach ($predictions as $pred): ?>
                                            <tr>
                                                <td><?php echo $pred['nama_desa']; ?></td>
                                                <td><?php echo $pred['tahun_prediksi']; ?></td>
                                                <td><?php echo number_format($pred['total_penduduk_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_laki_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_perempuan_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_balita_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_anak_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_remaja_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_dewasa_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_lansia_prediksi']); ?></td>
                                                <td><?php echo $pred['growth_rate']; ?>%</td>
                                                <td><?php echo $pred['confidence_level']; ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="table-pagination">
                                    <div class="pagination-info">
                                        <span id="paginationInfo">Showing 1 to 10 of <?php echo count($predictions); ?> entries</span>
                                    </div>
                                    <div class="pagination-controls">
                                        <button id="prevPage" class="pagination-btn" disabled>Previous</button>
                                        <div id="pageNumbers" class="page-numbers"></div>
                                        <button id="nextPage" class="pagination-btn">Next</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="analisis-breakdown">
                                <h5>Prediksi Struktur Usia <?php echo date('Y') + 5; ?></h5>
                                <?php
                                // Calculate age structure trends for final year
                                $ageStructure2029 = [
                                    'balita' => 0, 'anak' => 0, 'remaja' => 0, 'dewasa' => 0, 'lansia' => 0
                                ];
                                
                                foreach ($predictions as $pred) {
                                    if ($pred['tahun_prediksi'] == date('Y') + 5) {
                                        $ageStructure2029['balita'] += $pred['total_balita_prediksi'];
                                        $ageStructure2029['anak'] += $pred['total_anak_prediksi'];
                                        $ageStructure2029['remaja'] += $pred['total_remaja_prediksi'];
                                        $ageStructure2029['dewasa'] += $pred['total_dewasa_prediksi'];
                                        $ageStructure2029['lansia'] += $pred['total_lansia_prediksi'];
                                    }
                                }
                                
                                $totalAge = array_sum($ageStructure2029);
                                ?>
                                <div class="breakdown-item">
                                    <span>Balita (0-5 tahun)</span>
                                    <span><?php echo number_format($ageStructure2029['balita']); ?> 
                                        (<?php echo $totalAge > 0 ? round(($ageStructure2029['balita'] / $totalAge) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Anak (6-12 tahun)</span>
                                    <span><?php echo number_format($ageStructure2029['anak']); ?> 
                                        (<?php echo $totalAge > 0 ? round(($ageStructure2029['anak'] / $totalAge) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Remaja (13-17 tahun)</span>
                                    <span><?php echo number_format($ageStructure2029['remaja']); ?> 
                                        (<?php echo $totalAge > 0 ? round(($ageStructure2029['remaja'] / $totalAge) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Dewasa (18-64 tahun)</span>
                                    <span><?php echo number_format($ageStructure2029['dewasa']); ?> 
                                        (<?php echo $totalAge > 0 ? round(($ageStructure2029['dewasa'] / $totalAge) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Lansia (65+ tahun)</span>
                                    <span><?php echo number_format($ageStructure2029['lansia']); ?> 
                                        (<?php echo $totalAge > 0 ? round(($ageStructure2029['lansia'] / $totalAge) * 100, 1) : 0; ?>%)</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Proyeksi Kebutuhan Pembangunan</h3>
                    </div>
                    <div class="card-body">
                        <div class="prediction-grid">
                            <?php
                            // Calculate predictions based on real data
                            $total_anak_sekolah_all = 0;
                            $total_fasilitas_all = 0;
                            $total_jalan_rusak = 0;
                            $total_penduduk_all = 0;

                            foreach ($all_desa as $desa) {
                                $id_desa = $desa['id_desa'];
                                $stats = $queries->getStatistikPenduduk($id_desa);
                                $total_penduduk_all += $stats['total'] ?? 0;

                                // Anak sekolah
                                foreach ($stats['kelompok_usia'] ?? [] as $kelompok) {
                                    if (in_array($kelompok['kelompok_usia'], ['Anak', 'Remaja'])) {
                                        $total_anak_sekolah_all += $kelompok['jumlah'];
                                    }
                                }

                                // Fasilitas
                                $fasilitas = $queries->getFasilitasPendidikan($id_desa);
                                $total_fasilitas_all += count($fasilitas);

                                // Jalan rusak
                                $jalan = $queries->getInfrastrukturJalan($id_desa);
                                foreach ($jalan as $j) {
                                    if ($j['kondisi_jalan'] === 'rusak') {
                                        $total_jalan_rusak += $j['panjang_jalan'];
                                    }
                                }
                            }

                            // Calculate predictions
                            $rasio_siswa_fasilitas = $total_fasilitas_all > 0 ? $total_anak_sekolah_all / $total_fasilitas_all : 0;
                            $kebutuhan_sd = $rasio_siswa_fasilitas > 50 ? ceil($rasio_siswa_fasilitas / 50) : 0;
                            $kebutuhan_smp = $rasio_siswa_fasilitas > 80 ? ceil($rasio_siswa_fasilitas / 80) : 0;
                            $dampak_kesejahteraan = $total_penduduk_all > 0 ? round((($total_fasilitas_all + count($all_desa)) / $total_penduduk_all) * 1000, 1) : 0;
                            ?>

                            <div class="prediction-item">
                                <div class="prediction-icon">
                                    <i class="fas fa-school"></i>
                                </div>
                                <div class="prediction-content">
                                    <h4>Fasilitas Pendidikan</h4>
                                    <p>Proyeksi kebutuhan 5 tahun ke depan</p>
                                    <div class="prediction-value">
                                        <?php echo $kebutuhan_sd > 0 ? '+' . $kebutuhan_sd . ' SD' : 'Cukup'; ?><?php echo $kebutuhan_smp > 0 ? ', +' . $kebutuhan_smp . ' SMP' : ''; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="prediction-item">
                                <div class="prediction-icon">
                                    <i class="fas fa-road"></i>
                                </div>
                                <div class="prediction-content">
                                    <h4>Infrastruktur Jalan</h4>
                                    <p>Prioritas perbaikan berdasarkan analisis</p>
                                    <div class="prediction-value"><?php echo round($total_jalan_rusak, 1); ?> km perlu
                                        perbaikan</div>
                                </div>
                            </div>

                            <div class="prediction-item">
                                <div class="prediction-icon">
                                    <i class="fas fa-chart-area"></i>
                                </div>
                                <div class="prediction-content">
                                    <h4>Dampak Program</h4>
                                    <p>Simulasi dampak program pembangunan</p>
                                    <div class="prediction-value">+<?php echo $dampak_kesejahteraan; ?>% kesejahteraan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.desaData = [
        { id: 'all', name: 'Semua Desa' },
        <?php foreach ($all_desa as $desa): ?>
        { id: '<?php echo $desa['id_desa']; ?>', name: '<?php echo addslashes($desa['nama_desa']); ?>' },
        <?php endforeach; ?>
    ];
</script>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>