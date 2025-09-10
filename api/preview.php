<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/queries.php';

// Clean output buffer and set headers
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$queries = new Queries();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        // Generate report data directly without including laporan.php
        $report_data = generatePreviewData($queries, $input);
        
        echo json_encode([
            'success' => true,
            'data' => $report_data
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}

function generatePreviewData($queries, $config) {
    $html_content = '';
    $selected_desa = $config['desa'] ?? 'all';
    $sections = $config['sections'] ?? [];
    
    $html_content = '<div class="preview-report">';
    
    foreach ($sections as $section) {
        switch ($section) {
            case 'perbandingan_desa':
                if (isset($config['desa1']) && isset($config['desa2'])) {
                    $html_content .= generateComparisonPreview($queries, $config['desa1'], $config['desa2']);
                }
                break;
            case 'statistik_kependudukan':
                $html_content .= generateStatistikKependudukan($queries, $selected_desa);
                break;
            case 'distribusi_ekonomi':
                $html_content .= generateDistribusiEkonomi($queries, $selected_desa);
                break;
            case 'fasilitas_pendidikan':
                $html_content .= generateFasilitasPendidikan($queries, $selected_desa);
                break;
            case 'status_infrastruktur':
                $html_content .= generateStatusInfrastruktur($queries, $selected_desa);
                break;
            case 'monitoring_real_time':
                $html_content .= generateMonitoringData($queries, $selected_desa);
                break;
            case 'analisis_kependudukan':
            case 'analisis_ekonomi':
            case 'integrasi_data':
            case 'analisis_spasial':
                $html_content .= generateAnalisisLanjutan($queries, $selected_desa, $section);
                break;
            case 'prediksi_penduduk':
                $html_content .= generatePrediksiPenduduk($queries, $selected_desa);
                break;
            case 'proyeksi_pembangunan':
                $html_content .= generateProyeksiPembangunan($queries, $selected_desa);
                break;
        }
    }
    
    $html_content .= '</div>';
    
    return ['html_content' => $html_content];
}

function generateComparisonPreview($queries, $desa1Id, $desa2Id) {
    $desa1Data = fetchDesaData($queries, $desa1Id);
    $desa2Data = fetchDesaData($queries, $desa2Id);
    
    $html = '<div class="comparison-report">';
    $html .= generateDataUmumTable($desa1Data, $desa2Data);
    $html .= generateDemografisTable($desa1Data, $desa2Data);
    $html .= generatePendidikanTable($desa1Data, $desa2Data);
    $html .= generateEkonomiTable($desa1Data, $desa2Data);
    $html .= generateInfrastrukturTable($desa1Data, $desa2Data);
    $html .= '</div>';
    
    return $html;
}

function fetchDesaData($queries, $desaId) {
    return [
        'desa' => $queries->getDesaById($desaId),
        'penduduk' => $queries->getPendudukByDesa($desaId),
        'fasilitas' => $queries->getFasilitasPendidikan($desaId),
        'ekonomi' => $queries->getDataEkonomi($desaId),
        'jalan' => $queries->getInfrastrukturJalan($desaId),
        'jembatan' => $queries->getInfrastrukturJembatan($desaId),
        'mataPencaharian' => $queries->getMataPencaharianByDesa($desaId)
    ];
}

function generateDataUmumTable($data1, $data2) {
    $totalKK1 = count(array_unique(array_column($data1['penduduk'], 'id_keluarga')));
    $totalKK2 = count(array_unique(array_column($data2['penduduk'], 'id_keluarga')));
    $rataKK1 = $totalKK1 > 0 ? round(count($data1['penduduk']) / $totalKK1, 2) : 0;
    $rataKK2 = $totalKK2 > 0 ? round(count($data2['penduduk']) / $totalKK2, 2) : 0;
    
    $laki1 = count(array_filter($data1['penduduk'], fn($p) => $p['jenis_kelamin'] === 'L'));
    $perempuan1 = count(array_filter($data1['penduduk'], fn($p) => $p['jenis_kelamin'] === 'P'));
    $rasio1 = $perempuan1 > 0 ? round($laki1 / $perempuan1, 2) : 0;
    
    $laki2 = count(array_filter($data2['penduduk'], fn($p) => $p['jenis_kelamin'] === 'L'));
    $perempuan2 = count(array_filter($data2['penduduk'], fn($p) => $p['jenis_kelamin'] === 'P'));
    $rasio2 = $perempuan2 > 0 ? round($laki2 / $perempuan2, 2) : 0;
    
    return '
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-info-circle"></i> Data Umum</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Indikator</th>
                        <th>' . htmlspecialchars($data1['desa']['nama_desa']) . '</th>
                        <th>' . htmlspecialchars($data2['desa']['nama_desa']) . '</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Luas Wilayah (km²)</td>
                        <td>' . ($data1['desa']['luas_wilayah'] ?? '0.00') . '</td>
                        <td>' . ($data2['desa']['luas_wilayah'] ?? '0.00') . '</td>
                    </tr>
                    <tr>
                        <td>Jumlah Dusun</td>
                        <td>' . ($data1['desa']['jumlah_dusun'] ?? 0) . '</td>
                        <td>' . ($data2['desa']['jumlah_dusun'] ?? 0) . '</td>
                    </tr>
                    <tr>
                        <td>Jumlah RW</td>
                        <td>' . ($data1['desa']['jumlah_rw'] ?? 0) . '</td>
                        <td>' . ($data2['desa']['jumlah_rw'] ?? 0) . '</td>
                    </tr>
                    <tr>
                        <td>Jumlah RT</td>
                        <td>' . ($data1['desa']['jumlah_rt'] ?? 0) . '</td>
                        <td>' . ($data2['desa']['jumlah_rt'] ?? 0) . '</td>
                    </tr>
                    <tr>
                        <td>Total Penduduk</td>
                        <td>' . count($data1['penduduk']) . ' jiwa</td>
                        <td>' . count($data2['penduduk']) . ' jiwa</td>
                    </tr>
                    <tr>
                        <td>Total KK</td>
                        <td>' . $totalKK1 . ' KK</td>
                        <td>' . $totalKK2 . ' KK</td>
                    </tr>
                    <tr>
                        <td>Rata-rata per KK</td>
                        <td>' . $rataKK1 . ' jiwa</td>
                        <td>' . $rataKK2 . ' jiwa</td>
                    </tr>
                    <tr>
                        <td>Rasio Jenis Kelamin</td>
                        <td>' . $rasio1 . '</td>
                        <td>' . $rasio2 . '</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>';
}

function generateDemografisTable($data1, $data2) {
    $usia0_14_1 = count(array_filter($data1['penduduk'], fn($p) => $p['usia'] <= 14));
    $usia15_64_1 = count(array_filter($data1['penduduk'], fn($p) => $p['usia'] >= 15 && $p['usia'] <= 64));
    $usia65_1 = count(array_filter($data1['penduduk'], fn($p) => $p['usia'] >= 65));
    
    $usia0_14_2 = count(array_filter($data2['penduduk'], fn($p) => $p['usia'] <= 14));
    $usia15_64_2 = count(array_filter($data2['penduduk'], fn($p) => $p['usia'] >= 15 && $p['usia'] <= 64));
    $usia65_2 = count(array_filter($data2['penduduk'], fn($p) => $p['usia'] >= 65));
    
    $total1 = count($data1['penduduk']);
    $total2 = count($data2['penduduk']);
    
    // Agama
    $agama1 = [];
    $agama2 = [];
    foreach ($data1['penduduk'] as $p) {
        if (!empty($p['agama'])) {
            $agama1[$p['agama']] = ($agama1[$p['agama']] ?? 0) + 1;
        }
    }
    foreach ($data2['penduduk'] as $p) {
        if (!empty($p['agama'])) {
            $agama2[$p['agama']] = ($agama2[$p['agama']] ?? 0) + 1;
        }
    }
    
    $allAgama = array_unique(array_merge(array_keys($agama1), array_keys($agama2)));
    $agamaRows = '';
    foreach ($allAgama as $agama) {
        $count1 = $agama1[$agama] ?? 0;
        $count2 = $agama2[$agama] ?? 0;
        $pct1 = $total1 > 0 ? round(($count1 / $total1) * 100, 1) : 0;
        $pct2 = $total2 > 0 ? round(($count2 / $total2) * 100, 1) : 0;
        
        $agamaRows .= '<tr>
            <td>' . htmlspecialchars($agama) . '</td>
            <td>' . $count1 . ' (' . $pct1 . '%)</td>
            <td>' . $count2 . ' (' . $pct2 . '%)</td>
        </tr>';
    }
    
    // Status Pernikahan
    $status1 = [];
    $status2 = [];
    foreach ($data1['penduduk'] as $p) {
        if (!empty($p['status_pernikahan'])) {
            $status1[$p['status_pernikahan']] = ($status1[$p['status_pernikahan']] ?? 0) + 1;
        }
    }
    foreach ($data2['penduduk'] as $p) {
        if (!empty($p['status_pernikahan'])) {
            $status2[$p['status_pernikahan']] = ($status2[$p['status_pernikahan']] ?? 0) + 1;
        }
    }
    
    $allStatus = array_unique(array_merge(array_keys($status1), array_keys($status2)));
    $statusRows = '';
    foreach ($allStatus as $status) {
        $count1 = $status1[$status] ?? 0;
        $count2 = $status2[$status] ?? 0;
        $pct1 = $total1 > 0 ? round(($count1 / $total1) * 100, 1) : 0;
        $pct2 = $total2 > 0 ? round(($count2 / $total2) * 100, 1) : 0;
        
        $statusRows .= '<tr>
            <td>' . htmlspecialchars($status) . '</td>
            <td>' . $count1 . ' (' . $pct1 . '%)</td>
            <td>' . $count2 . ' (' . $pct2 . '%)</td>
        </tr>';
    }
    
    return '
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-users"></i> Struktur Usia Penduduk</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Kelompok Usia</th>
                        <th>' . htmlspecialchars($data1['desa']['nama_desa']) . '</th>
                        <th>' . htmlspecialchars($data2['desa']['nama_desa']) . '</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>0-14 tahun</td>
                        <td>' . $usia0_14_1 . ' (' . ($total1 > 0 ? round(($usia0_14_1 / $total1) * 100, 1) : 0) . '%)</td>
                        <td>' . $usia0_14_2 . ' (' . ($total2 > 0 ? round(($usia0_14_2 / $total2) * 100, 1) : 0) . '%)</td>
                    </tr>
                    <tr>
                        <td>15-64 tahun</td>
                        <td>' . $usia15_64_1 . ' (' . ($total1 > 0 ? round(($usia15_64_1 / $total1) * 100, 1) : 0) . '%)</td>
                        <td>' . $usia15_64_2 . ' (' . ($total2 > 0 ? round(($usia15_64_2 / $total2) * 100, 1) : 0) . '%)</td>
                    </tr>
                    <tr>
                        <td>65+ tahun</td>
                        <td>' . $usia65_1 . ' (' . ($total1 > 0 ? round(($usia65_1 / $total1) * 100, 1) : 0) . '%)</td>
                        <td>' . $usia65_2 . ' (' . ($total2 > 0 ? round(($usia65_2 / $total2) * 100, 1) : 0) . '%)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-pray"></i> Komposisi Agama</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Agama</th>
                        <th>' . htmlspecialchars($data1['desa']['nama_desa']) . '</th>
                        <th>' . htmlspecialchars($data2['desa']['nama_desa']) . '</th>
                    </tr>
                </thead>
                <tbody>
                    ' . ($agamaRows ?: '<tr><td colspan="3">Tidak ada data agama</td></tr>') . '
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-heart"></i> Status Pernikahan</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>' . htmlspecialchars($data1['desa']['nama_desa']) . '</th>
                        <th>' . htmlspecialchars($data2['desa']['nama_desa']) . '</th>
                    </tr>
                </thead>
                <tbody>
                    ' . ($statusRows ?: '<tr><td colspan="3">Tidak ada data status pernikahan</td></tr>') . '
                </tbody>
            </table>
        </div>
    </div>';
}

function generatePendidikanTable($data1, $data2) {
    // Tingkat Pendidikan
    $pendidikan1 = [];
    $pendidikan2 = [];
    foreach ($data1['penduduk'] as $p) {
        if (!empty($p['pendidikan_terakhir'])) {
            $pendidikan1[$p['pendidikan_terakhir']] = ($pendidikan1[$p['pendidikan_terakhir']] ?? 0) + 1;
        }
    }
    foreach ($data2['penduduk'] as $p) {
        if (!empty($p['pendidikan_terakhir'])) {
            $pendidikan2[$p['pendidikan_terakhir']] = ($pendidikan2[$p['pendidikan_terakhir']] ?? 0) + 1;
        }
    }
    
    $allPendidikan = array_unique(array_merge(array_keys($pendidikan1), array_keys($pendidikan2)));
    $pendidikanRows = '';
    foreach ($allPendidikan as $tingkat) {
        $count1 = $pendidikan1[$tingkat] ?? 0;
        $count2 = $pendidikan2[$tingkat] ?? 0;
        $pct1 = count($data1['penduduk']) > 0 ? round(($count1 / count($data1['penduduk'])) * 100, 1) : 0;
        $pct2 = count($data2['penduduk']) > 0 ? round(($count2 / count($data2['penduduk'])) * 100, 1) : 0;
        
        $pendidikanRows .= '<tr>
            <td>' . htmlspecialchars($tingkat) . '</td>
            <td>' . $count1 . ' (' . $pct1 . '%)</td>
            <td>' . $count2 . ' (' . $pct2 . '%)</td>
        </tr>';
    }
    
    // Fasilitas Pendidikan
    $fasilitas1 = [];
    $fasilitas2 = [];
    foreach ($data1['fasilitas'] as $f) {
        $fasilitas1[$f['jenis_pendidikan']] = ($fasilitas1[$f['jenis_pendidikan']] ?? 0) + 1;
    }
    foreach ($data2['fasilitas'] as $f) {
        $fasilitas2[$f['jenis_pendidikan']] = ($fasilitas2[$f['jenis_pendidikan']] ?? 0) + 1;
    }
    
    $allFasilitas = array_unique(array_merge(array_keys($fasilitas1), array_keys($fasilitas2)));
    $fasilitasRows = '';
    foreach ($allFasilitas as $jenis) {
        $count1 = $fasilitas1[$jenis] ?? 0;
        $count2 = $fasilitas2[$jenis] ?? 0;
        
        $label = $jenis;
        if ($jenis === 'SD') $label = 'SD/MI';
        elseif ($jenis === 'SMP') $label = 'SMP/MTs';
        elseif ($jenis === 'SMA') $label = 'SMA/MA';
        
        $fasilitasRows .= '<tr>
            <td>' . htmlspecialchars($label) . '</td>
            <td>' . $count1 . ' unit</td>
            <td>' . $count2 . ' unit</td>
        </tr>';
    }
    
    return '
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-graduation-cap"></i> Tingkat Pendidikan Penduduk</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tingkat Pendidikan</th>
                        <th>' . htmlspecialchars($data1['desa']['nama_desa']) . '</th>
                        <th>' . htmlspecialchars($data2['desa']['nama_desa']) . '</th>
                    </tr>
                </thead>
                <tbody>
                    ' . ($pendidikanRows ?: '<tr><td colspan="3">Tidak ada data pendidikan</td></tr>') . '
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-school"></i> Fasilitas Pendidikan</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Jenis Fasilitas</th>
                        <th>' . htmlspecialchars($data1['desa']['nama_desa']) . '</th>
                        <th>' . htmlspecialchars($data2['desa']['nama_desa']) . '</th>
                    </tr>
                </thead>
                <tbody>
                    ' . ($fasilitasRows ?: '<tr><td colspan="3">Tidak ada fasilitas pendidikan</td></tr>') . '
                </tbody>
            </table>
        </div>
    </div>';
}

function generateEkonomiTable($data1, $data2) {
    $pekerjaan1 = [];
    $pekerjaan2 = [];
    foreach ($data1['mataPencaharian'] as $mp) {
        $pekerjaan1[$mp['jenis_pekerjaan']] = ($pekerjaan1[$mp['jenis_pekerjaan']] ?? 0) + 1;
    }
    foreach ($data2['mataPencaharian'] as $mp) {
        $pekerjaan2[$mp['jenis_pekerjaan']] = ($pekerjaan2[$mp['jenis_pekerjaan']] ?? 0) + 1;
    }
    
    $allPekerjaan = array_unique(array_merge(array_keys($pekerjaan1), array_keys($pekerjaan2)));
    $pekerjaanRows = '';
    foreach ($allPekerjaan as $pekerjaan) {
        $count1 = $pekerjaan1[$pekerjaan] ?? 0;
        $count2 = $pekerjaan2[$pekerjaan] ?? 0;
        $pct1 = count($data1['penduduk']) > 0 ? round(($count1 / count($data1['penduduk'])) * 100, 1) : 0;
        $pct2 = count($data2['penduduk']) > 0 ? round(($count2 / count($data2['penduduk'])) * 100, 1) : 0;
        
        $pekerjaanRows .= '<tr>
            <td>' . htmlspecialchars($pekerjaan) . '</td>
            <td>' . $count1 . ' (' . $pct1 . '%)</td>
            <td>' . $count2 . ' (' . $pct2 . '%)</td>
        </tr>';
    }
    
    // Potensi Ekonomi
    $ekonomi1 = [];
    $ekonomi2 = [];
    foreach ($data1['ekonomi'] as $e) {
        $ekonomi1[$e['jenis_data']] = ($ekonomi1[$e['jenis_data']] ?? 0) + 1;
    }
    foreach ($data2['ekonomi'] as $e) {
        $ekonomi2[$e['jenis_data']] = ($ekonomi2[$e['jenis_data']] ?? 0) + 1;
    }
    
    $allEkonomi = array_unique(array_merge(array_keys($ekonomi1), array_keys($ekonomi2)));
    $ekonomiRows = '';
    foreach ($allEkonomi as $jenis) {
        $count1 = $ekonomi1[$jenis] ?? 0;
        $count2 = $ekonomi2[$jenis] ?? 0;
        
        $label = ucfirst($jenis);
        if ($jenis === 'umkm') $label = 'UMKM';
        
        $ekonomiRows .= '<tr>
            <td>' . htmlspecialchars($label) . '</td>
            <td>' . $count1 . ' unit</td>
            <td>' . $count2 . ' unit</td>
        </tr>';
    }
    
    return '
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-briefcase"></i> Mata Pencaharian Penduduk</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Jenis Pekerjaan</th>
                        <th>' . htmlspecialchars($data1['desa']['nama_desa']) . '</th>
                        <th>' . htmlspecialchars($data2['desa']['nama_desa']) . '</th>
                    </tr>
                </thead>
                <tbody>
                    ' . ($pekerjaanRows ?: '<tr><td colspan="3">Tidak ada data pekerjaan</td></tr>') . '
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-chart-line"></i> Potensi Ekonomi Desa</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Indikator</th>
                        <th>' . htmlspecialchars($data1['desa']['nama_desa']) . '</th>
                        <th>' . htmlspecialchars($data2['desa']['nama_desa']) . '</th>
                    </tr>
                </thead>
                <tbody>
                    ' . ($ekonomiRows ?: '<tr><td colspan="3">Tidak ada data ekonomi</td></tr>') . '
                </tbody>
            </table>
        </div>
    </div>';
}

function generateInfrastrukturTable($data1, $data2) {
    $jalan1Baik = count(array_filter($data1['jalan'], fn($j) => $j['kondisi_jalan'] === 'baik'));
    $jalan1Sedang = count(array_filter($data1['jalan'], fn($j) => $j['kondisi_jalan'] === 'sedang'));
    $jalan1Rusak = count(array_filter($data1['jalan'], fn($j) => $j['kondisi_jalan'] === 'rusak'));
    
    $jalan2Baik = count(array_filter($data2['jalan'], fn($j) => $j['kondisi_jalan'] === 'baik'));
    $jalan2Sedang = count(array_filter($data2['jalan'], fn($j) => $j['kondisi_jalan'] === 'sedang'));
    $jalan2Rusak = count(array_filter($data2['jalan'], fn($j) => $j['kondisi_jalan'] === 'rusak'));
    
    $totalJalan1 = count($data1['jalan']);
    $totalJalan2 = count($data2['jalan']);
    
    // Kondisi Jembatan
    $jembatan1Baik = count(array_filter($data1['jembatan'], fn($j) => $j['kondisi_jembatan'] === 'baik'));
    $jembatan1Rusak = count(array_filter($data1['jembatan'], fn($j) => $j['kondisi_jembatan'] === 'rusak'));
    
    $jembatan2Baik = count(array_filter($data2['jembatan'], fn($j) => $j['kondisi_jembatan'] === 'baik'));
    $jembatan2Rusak = count(array_filter($data2['jembatan'], fn($j) => $j['kondisi_jembatan'] === 'rusak'));
    
    return '
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-road"></i> Kondisi Infrastruktur Jalan</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Kondisi Jalan</th>
                        <th>' . htmlspecialchars($data1['desa']['nama_desa']) . '</th>
                        <th>' . htmlspecialchars($data2['desa']['nama_desa']) . '</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Jalan Baik</td>
                        <td>' . $jalan1Baik . ' unit (' . ($totalJalan1 > 0 ? round(($jalan1Baik / $totalJalan1) * 100, 1) : 0) . '%)</td>
                        <td>' . $jalan2Baik . ' unit (' . ($totalJalan2 > 0 ? round(($jalan2Baik / $totalJalan2) * 100, 1) : 0) . '%)</td>
                    </tr>
                    <tr>
                        <td>Jalan Sedang</td>
                        <td>' . $jalan1Sedang . ' unit (' . ($totalJalan1 > 0 ? round(($jalan1Sedang / $totalJalan1) * 100, 1) : 0) . '%)</td>
                        <td>' . $jalan2Sedang . ' unit (' . ($totalJalan2 > 0 ? round(($jalan2Sedang / $totalJalan2) * 100, 1) : 0) . '%)</td>
                    </tr>
                    <tr>
                        <td>Jalan Rusak</td>
                        <td>' . $jalan1Rusak . ' unit (' . ($totalJalan1 > 0 ? round(($jalan1Rusak / $totalJalan1) * 100, 1) : 0) . '%)</td>
                        <td>' . $jalan2Rusak . ' unit (' . ($totalJalan2 > 0 ? round(($jalan2Rusak / $totalJalan2) * 100, 1) : 0) . '%)</td>
                    </tr>
                    <tr>
                        <td>Total Jalan</td>
                        <td>' . $totalJalan1 . ' unit</td>
                        <td>' . $totalJalan2 . ' unit</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-bridge-water"></i> Kondisi Infrastruktur Jembatan</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Kondisi Jembatan</th>
                        <th>' . htmlspecialchars($data1['desa']['nama_desa']) . '</th>
                        <th>' . htmlspecialchars($data2['desa']['nama_desa']) . '</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Jembatan Baik</td>
                        <td>' . $jembatan1Baik . ' unit</td>
                        <td>' . $jembatan2Baik . ' unit</td>
                    </tr>
                    <tr>
                        <td>Jembatan Rusak</td>
                        <td>' . $jembatan1Rusak . ' unit</td>
                        <td>' . $jembatan2Rusak . ' unit</td>
                    </tr>
                    <tr>
                        <td>Total Jembatan</td>
                        <td>' . count($data1['jembatan']) . ' unit</td>
                        <td>' . count($data2['jembatan']) . ' unit</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>';
}

function generateStatistikKependudukan($queries, $selected_desa) {
    $filtered_desa = $selected_desa === 'all' ? $queries->getAllDesa() : [$queries->getDesaById($selected_desa)];
    
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
    $total_kk = 0;

    foreach ($filtered_desa as $desa) {
        $id_desa = $desa['id_desa'];
        $penduduk = $queries->getPendudukByDesa($id_desa);
        $keluarga = $queries->getKeluargaByDesa($id_desa);
        $total_kk += count($keluarga);

        foreach ($penduduk as $p) {
            $total_penduduk_all++;

            // Jenis kelamin
            if ($p['jenis_kelamin'] === 'L') $total_laki++;
            else $total_perempuan++;

            // Kelompok usia
            if ($p['usia'] >= 0 && $p['usia'] <= 5) $total_balita++;
            elseif ($p['usia'] >= 6 && $p['usia'] <= 12) $total_anak++;
            elseif ($p['usia'] >= 13 && $p['usia'] <= 17) $total_remaja++;
            elseif ($p['usia'] >= 18 && $p['usia'] <= 64) $total_dewasa++;
            else $total_lansia++;

            // Agama
            $agama_stats[$p['agama']] = ($agama_stats[$p['agama']] ?? 0) + 1;

            // Pendidikan
            $pendidikan_stats[$p['pendidikan_terakhir']] = ($pendidikan_stats[$p['pendidikan_terakhir']] ?? 0) + 1;

            // Status pernikahan
            $pernikahan_stats[$p['status_pernikahan']] = ($pernikahan_stats[$p['status_pernikahan']] ?? 0) + 1;
        }
    }
    
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-users"></i> Statistik Kependudukan</h4>
        </div>
        <div class="card-body">
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Total Penduduk</h4>
                    <div class="stat-value">' . number_format($total_penduduk_all) . ' jiwa</div>
                </div>
                <div class="stat-item">
                    <h4>Laki-laki</h4>
                    <div class="stat-value">' . number_format($total_laki) . ' (' . ($total_penduduk_all > 0 ? round(($total_laki / $total_penduduk_all) * 100, 1) : 0) . '%)</div>
                </div>
                <div class="stat-item">
                    <h4>Perempuan</h4>
                    <div class="stat-value">' . number_format($total_perempuan) . ' (' . ($total_penduduk_all > 0 ? round(($total_perempuan / $total_penduduk_all) * 100, 1) : 0) . '%)</div>
                </div>
                <div class="stat-item">
                    <h4>Total KK</h4>
                    <div class="stat-value">' . number_format($total_kk) . ' KK</div>
                </div>
            </div>
            
            <div class="analisis-breakdown">
                <h5>Kelompok Usia</h5>
                <div class="breakdown-item">
                    <span>Balita (0-5 tahun)</span>
                    <span>' . number_format($total_balita) . ' (' . ($total_penduduk_all > 0 ? round(($total_balita / $total_penduduk_all) * 100, 1) : 0) . '%)</span>
                </div>
                <div class="breakdown-item">
                    <span>Anak (6-12 tahun)</span>
                    <span>' . number_format($total_anak) . ' (' . ($total_penduduk_all > 0 ? round(($total_anak / $total_penduduk_all) * 100, 1) : 0) . '%)</span>
                </div>
                <div class="breakdown-item">
                    <span>Remaja (13-17 tahun)</span>
                    <span>' . number_format($total_remaja) . ' (' . ($total_penduduk_all > 0 ? round(($total_remaja / $total_penduduk_all) * 100, 1) : 0) . '%)</span>
                </div>
                <div class="breakdown-item">
                    <span>Dewasa (18-64 tahun)</span>
                    <span>' . number_format($total_dewasa) . ' (' . ($total_penduduk_all > 0 ? round(($total_dewasa / $total_penduduk_all) * 100, 1) : 0) . '%)</span>
                </div>
                <div class="breakdown-item">
                    <span>Lansia (65+ tahun)</span>
                    <span>' . number_format($total_lansia) . ' (' . ($total_penduduk_all > 0 ? round(($total_lansia / $total_penduduk_all) * 100, 1) : 0) . '%)</span>
                </div>
            </div>
            
            <div class="analisis-breakdown">
                <h5>Agama</h5>';
    
    foreach ($agama_stats as $agama => $jumlah) {
        $html .= '<div class="breakdown-item">
            <span>' . htmlspecialchars($agama) . '</span>
            <span>' . number_format($jumlah) . ' (' . ($total_penduduk_all > 0 ? round(($jumlah / $total_penduduk_all) * 100, 1) : 0) . '%)</span>
        </div>';
    }
    
    $html .= '</div>
            
            <div class="analisis-breakdown">
                <h5>Tingkat Pendidikan</h5>';
    
    arsort($pendidikan_stats);
    foreach ($pendidikan_stats as $pendidikan => $jumlah) {
        $html .= '<div class="breakdown-item">
            <span>' . htmlspecialchars($pendidikan) . '</span>
            <span>' . number_format($jumlah) . ' (' . ($total_penduduk_all > 0 ? round(($jumlah / $total_penduduk_all) * 100, 1) : 0) . '%)</span>
        </div>';
    }
    
    $html .= '</div>
            
            <div class="analisis-breakdown">
                <h5>Status Pernikahan</h5>';
    
    foreach ($pernikahan_stats as $status => $jumlah) {
        $html .= '<div class="breakdown-item">
            <span>' . htmlspecialchars($status) . '</span>
            <span>' . number_format($jumlah) . ' (' . ($total_penduduk_all > 0 ? round(($jumlah / $total_penduduk_all) * 100, 1) : 0) . '%)</span>
        </div>';
    }
    
    return $html . '</div></div></div>';
}

function generateDistribusiEkonomi($queries, $selected_desa) {
    $filtered_desa = $selected_desa === 'all' ? $queries->getAllDesa() : [$queries->getDesaById($selected_desa)];
    
    $mata_pencaharian_stats = [];
    $umkm_stats = [];
    $total_pertanian = 0;
    $total_umkm = 0;
    $total_pasar = 0;
    $total_warga_miskin = 0;
    $bantuan_stats = [];

    foreach ($filtered_desa as $desa) {
        $id_desa = $desa['id_desa'];
        $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
        $ekonomi = $queries->getDataEkonomi($id_desa);

        foreach ($mata_pencaharian as $mp) {
            $mata_pencaharian_stats[$mp['jenis_pekerjaan']] = ($mata_pencaharian_stats[$mp['jenis_pekerjaan']] ?? 0) + 1;
        }

        foreach ($ekonomi as $e) {
            if ($e['jenis_data'] === 'pertanian') $total_pertanian++;
            elseif ($e['jenis_data'] === 'umkm') $total_umkm++;
            elseif ($e['jenis_data'] === 'pasar') $total_pasar++;
        }
        
        // Warga miskin
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
        
        // UMKM berdasarkan jenis usaha
        $umkm = $queries->getUMKM($id_desa);
        foreach ($umkm as $u) {
            $umkm_stats[$u['jenis_usaha']] = ($umkm_stats[$u['jenis_usaha']] ?? 0) + 1;
        }
    }

    $total_jenis_mata_pencaharian = count($mata_pencaharian_stats);
    
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-chart-line"></i> Distribusi Ekonomi</h4>
        </div>
        <div class="card-body">
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Total Mata Pencaharian</h4>
                    <div class="stat-value">' . number_format($total_jenis_mata_pencaharian) . ' jenis</div>
                </div>
                <div class="stat-item">
                    <h4>UMKM</h4>
                    <div class="stat-value">' . number_format($total_umkm) . ' unit</div>
                </div>
                <div class="stat-item">
                    <h4>Pertanian</h4>
                    <div class="stat-value">' . number_format($total_pertanian) . ' unit</div>
                </div>
                <div class="stat-item">
                    <h4>Pasar</h4>
                    <div class="stat-value">' . number_format($total_pasar) . ' unit</div>
                </div>
            </div>
            
            <div class="analisis-breakdown">
                <h5>Mata Pencaharian</h5>';
    
    arsort($mata_pencaharian_stats);
    $total_mp = array_sum($mata_pencaharian_stats);
    foreach ($mata_pencaharian_stats as $pekerjaan => $jumlah) {
        $html .= '<div class="breakdown-item">
            <span>' . htmlspecialchars($pekerjaan) . '</span>
            <span>' . number_format($jumlah) . ' (' . ($total_mp > 0 ? round(($jumlah / $total_mp) * 100, 1) : 0) . '%)</span>
        </div>';
    }
    
    $html .= '</div>
            
            <div class="analisis-breakdown">
                <h5>UMKM Berdasarkan Jenis Usaha</h5>';
    
    arsort($umkm_stats);
    foreach (array_slice($umkm_stats, 0, 10) as $jenis => $jumlah) {
        $html .= '<div class="breakdown-item">
            <span>' . htmlspecialchars($jenis) . '</span>
            <span>' . number_format($jumlah) . ' unit</span>
        </div>';
    }
    
    $html .= '</div>
            
            <div class="analisis-breakdown">
                <h5>Warga Miskin & Bantuan Sosial</h5>
                <div class="breakdown-item">
                    <span>Total Warga Miskin</span>
                    <span>' . number_format($total_warga_miskin) . ' orang</span>
                </div>';
    
    foreach ($bantuan_stats as $jenis => $jumlah) {
        $html .= '<div class="breakdown-item">
            <span>' . htmlspecialchars($jenis) . '</span>
            <span>' . number_format($jumlah) . ' penerima</span>
        </div>';
    }
    
    return $html . '</div></div></div>';
}

function generateFasilitasPendidikan($queries, $selected_desa) {
    $filtered_desa = $selected_desa === 'all' ? $queries->getAllDesa() : [$queries->getDesaById($selected_desa)];
    
    $fasilitas_stats = [];
    $total_kapasitas = 0;
    $total_guru = 0;
    $kapasitas_per_jenjang = [];

    foreach ($filtered_desa as $desa) {
        $id_desa = $desa['id_desa'];
        $fasilitas = $queries->getFasilitasPendidikan($id_desa);

        foreach ($fasilitas as $f) {
            $fasilitas_stats[$f['jenis_pendidikan']] = ($fasilitas_stats[$f['jenis_pendidikan']] ?? 0) + 1;
            $total_kapasitas += $f['kapasitas_siswa'] ?? 0;
            $total_guru += $f['jumlah_guru'] ?? 0;
            $kapasitas_per_jenjang[$f['jenis_pendidikan']] = ($kapasitas_per_jenjang[$f['jenis_pendidikan']] ?? 0) + ($f['kapasitas_siswa'] ?? 0);
        }
    }
    
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-school"></i> Fasilitas Pendidikan</h4>
        </div>
        <div class="card-body">
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Total Fasilitas</h4>
                    <div class="stat-value">' . number_format(array_sum($fasilitas_stats)) . ' unit</div>
                </div>
                <div class="stat-item">
                    <h4>Total Kapasitas</h4>
                    <div class="stat-value">' . number_format($total_kapasitas) . ' siswa</div>
                </div>
                <div class="stat-item">
                    <h4>Total Guru</h4>
                    <div class="stat-value">' . number_format($total_guru) . ' orang</div>
                </div>
                <div class="stat-item">
                    <h4>Rasio Guru:Siswa</h4>
                    <div class="stat-value">1:' . ($total_guru > 0 ? round($total_kapasitas / $total_guru) : 0) . '</div>
                </div>
            </div>
            
            <div class="analisis-breakdown">
                <h5>Jenis Fasilitas</h5>';
    
    foreach ($fasilitas_stats as $jenis => $jumlah) {
        $html .= '<div class="breakdown-item">
            <span>' . htmlspecialchars($jenis) . '</span>
            <span>' . number_format($jumlah) . ' unit</span>
        </div>';
    }
    
    $html .= '</div>
            
            <div class="analisis-breakdown">
                <h5>Kapasitas Per Jenjang</h5>';
    
    foreach ($kapasitas_per_jenjang as $jenis => $kapasitas) {
        $html .= '<div class="breakdown-item">
            <span>' . htmlspecialchars($jenis) . '</span>
            <span>' . number_format($kapasitas) . ' siswa</span>
        </div>';
    }
    
    return $html . '</div></div></div>';
}

function generateStatusInfrastruktur($queries, $selected_desa) {
    $filtered_desa = $selected_desa === 'all' ? $queries->getAllDesa() : [$queries->getDesaById($selected_desa)];
    
    $jalan_baik = 0;
    $jalan_sedang = 0;
    $jalan_rusak = 0;
    $total_panjang_jalan = 0;
    $jembatan_baik = 0;
    $jembatan_sedang = 0;
    $jembatan_rusak = 0;
    $total_jembatan = 0;
    $kepadatan_stats = [];
    $distribusi_fasilitas = [];

    foreach ($filtered_desa as $desa) {
        $id_desa = $desa['id_desa'];
        $jalan = $queries->getInfrastrukturJalan($id_desa);
        $jembatan = $queries->getInfrastrukturJembatan($id_desa);
        $stats = $queries->getStatistikPenduduk($id_desa);
        $fasilitas = $queries->getFasilitasPendidikan($id_desa);
        $ekonomi = $queries->getDataEkonomi($id_desa);

        foreach ($jalan as $j) {
            $total_panjang_jalan += $j['panjang_jalan'];
            if ($j['kondisi_jalan'] === 'baik') $jalan_baik++;
            elseif ($j['kondisi_jalan'] === 'sedang') $jalan_sedang++;
            else $jalan_rusak++;
        }

        foreach ($jembatan as $jmb) {
            $total_jembatan++;
            if ($jmb['kondisi_jembatan'] === 'baik') $jembatan_baik++;
            elseif ($jmb['kondisi_jembatan'] === 'sedang') $jembatan_sedang++;
            else $jembatan_rusak++;
        }
        
        // Kepadatan penduduk
        $luas_ha = $desa['luas_wilayah'] ?? 1;
        $luas_km2 = $luas_ha * 0.01;
        $kepadatan = $luas_km2 > 0 ? round(($stats['total'] ?? 0) / $luas_km2, 2) : 0;
        $kepadatan_stats[$desa['nama_desa']] = $kepadatan;

        // Distribusi fasilitas
        $total_fasilitas = count($fasilitas) + count($ekonomi);
        $distribusi_fasilitas[$desa['nama_desa']] = $total_fasilitas;
    }

    $total_jalan_unit = $jalan_baik + $jalan_sedang + $jalan_rusak;
    $rata_kepadatan = count($kepadatan_stats) > 0 ? round(array_sum($kepadatan_stats) / count($kepadatan_stats), 2) : 0;
    $total_fasilitas_umum = array_sum($distribusi_fasilitas);
    
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-road"></i> Status Infrastruktur</h4>
        </div>
        <div class="card-body">
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Total Jalan</h4>
                    <div class="stat-value">' . number_format($total_jalan_unit) . ' unit</div>
                </div>
                <div class="stat-item">
                    <h4>Panjang Jalan</h4>
                    <div class="stat-value">' . number_format($total_panjang_jalan, 1) . ' km</div>
                </div>
                <div class="stat-item">
                    <h4>Total Jembatan</h4>
                    <div class="stat-value">' . number_format($total_jembatan) . ' unit</div>
                </div>
                <div class="stat-item">
                    <h4>Kondisi Baik</h4>
                    <div class="stat-value">' . (($total_jalan_unit + $total_jembatan) > 0 ? round((($jalan_baik + $jembatan_baik) / ($total_jalan_unit + $total_jembatan)) * 100, 1) : 0) . '%</div>
                </div>
            </div>
            
            <div class="analisis-breakdown">
                <h5>Kondisi Jalan</h5>
                <div class="breakdown-item">
                    <span>Baik</span>
                    <span>' . number_format($jalan_baik) . ' (' . ($total_jalan_unit > 0 ? round(($jalan_baik / $total_jalan_unit) * 100, 1) : 0) . '%)</span>
                </div>
                <div class="breakdown-item">
                    <span>Sedang</span>
                    <span>' . number_format($jalan_sedang) . ' (' . ($total_jalan_unit > 0 ? round(($jalan_sedang / $total_jalan_unit) * 100, 1) : 0) . '%)</span>
                </div>
                <div class="breakdown-item">
                    <span>Rusak</span>
                    <span>' . number_format($jalan_rusak) . ' (' . ($total_jalan_unit > 0 ? round(($jalan_rusak / $total_jalan_unit) * 100, 1) : 0) . '%)</span>
                </div>
            </div>
            
            <div class="analisis-breakdown">
                <h5>Kondisi Jembatan</h5>
                <div class="breakdown-item">
                    <span>Baik</span>
                    <span>' . number_format($jembatan_baik) . ' (' . ($total_jembatan > 0 ? round(($jembatan_baik / $total_jembatan) * 100, 1) : 0) . '%)</span>
                </div>
                <div class="breakdown-item">
                    <span>Sedang</span>
                    <span>' . number_format($jembatan_sedang) . ' (' . ($total_jembatan > 0 ? round(($jembatan_sedang / $total_jembatan) * 100, 1) : 0) . '%)</span>
                </div>
                <div class="breakdown-item">
                    <span>Rusak</span>
                    <span>' . number_format($jembatan_rusak) . ' (' . ($total_jembatan > 0 ? round(($jembatan_rusak / $total_jembatan) * 100, 1) : 0) . '%)</span>
                </div>
            </div>
            
            <div class="analisis-breakdown">
                <h5>Analisis Spasial & Geografis</h5>
                <div class="breakdown-item">
                    <span>Rata-rata Kepadatan Penduduk</span>
                    <span>' . $rata_kepadatan . ' jiwa/km²</span>
                </div>
                <div class="breakdown-item">
                    <span>Total Fasilitas Umum</span>
                    <span>' . number_format($total_fasilitas_umum) . ' unit</span>
                </div>
                <div class="breakdown-item">
                    <span>Rata-rata Fasilitas per Desa</span>
                    <span>' . (count($filtered_desa) > 0 ? round($total_fasilitas_umum / count($filtered_desa), 1) : 0) . ' unit</span>
                </div>
            </div>
        </div>
    </div>';
    
    return $html;
}

function generateAnalisisLanjutan($queries, $selected_desa, $section) {
    $filtered_desa = $selected_desa === 'all' ? $queries->getAllDesa() : [$queries->getDesaById($selected_desa)];
    
    switch ($section) {
        case 'analisis_kependudukan':
            return generateKependudukanLanjutan($queries, $filtered_desa);
        case 'analisis_ekonomi':
            return generateEkonomiLanjutan($queries, $filtered_desa);
        case 'integrasi_data':
            return generateIntegrasiData($queries, $filtered_desa);
        case 'analisis_spasial':
            return generateSpasialPrediktif($queries, $filtered_desa);
        default:
            return '<div class="card mb-4"><div class="card-body">Section tidak ditemukan</div></div>';
    }
}

function generatePrediksiPenduduk($queries, $selected_desa) {
    $filtered_desa = $selected_desa === 'all' ? $queries->getAllDesa() : [$queries->getDesaById($selected_desa)];
    
    // Get prediction data from database
    $predictions = [];
    foreach ($filtered_desa as $desa) {
        $query = "SELECT * FROM prediksi_penduduk WHERE id_desa = :id_desa ORDER BY tahun_prediksi ASC";
        $stmt = $queries->db->prepare($query);
        $stmt->bindParam(':id_desa', $desa['id_desa']);
        $stmt->execute();
        $desa_predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($desa_predictions as $pred) {
            $pred['nama_desa'] = $desa['nama_desa'];
            $predictions[] = $pred;
        }
    }
    
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-chart-line"></i> Prediksi Struktur Penduduk 5 Tahun</h4>
        </div>
        <div class="card-body">';
    
    if (empty($predictions)) {
        $html .= '<div class="no-data">
            <i class="fas fa-chart-line fa-2x mb-3 text-muted"></i>
            <h6 class="text-muted">Belum Ada Prediksi</h6>
            <p class="text-muted">Data prediksi akan muncul setelah sistem menganalisis data historis</p>
        </div>';
    } else {
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
        
        // Prepare chart data
        $chartYears = [];
        $yearlyData = [];
        $ageData = ['balita' => 0, 'anak' => 0, 'remaja' => 0, 'dewasa' => 0, 'lansia' => 0];
        
        foreach ($predictions as $pred) {
            if (!in_array($pred['tahun_prediksi'], $chartYears)) {
                $chartYears[] = $pred['tahun_prediksi'];
            }
            if ($pred['tahun_prediksi'] == date('Y') + 5) {
                $ageData['balita'] += $pred['total_balita_prediksi'];
                $ageData['anak'] += $pred['total_anak_prediksi'];
                $ageData['remaja'] += $pred['total_remaja_prediksi'];
                $ageData['dewasa'] += $pred['total_dewasa_prediksi'];
                $ageData['lansia'] += $pred['total_lansia_prediksi'];
            }
        }
        
        foreach ($chartYears as $year) {
            $yearTotal = 0;
            foreach ($predictions as $pred) {
                if ($pred['tahun_prediksi'] == $year) {
                    $yearTotal += $pred['total_penduduk_prediksi'];
                }
            }
            $yearlyData[] = $yearTotal;
        }
        
        $html .= '<div class="prediction-charts-section">
            <div class="charts-grid">
                <div class="chart-container">
                    <h4>Tren Prediksi Penduduk</h4>
                    <div class="chart-placeholder" id="chartLineData" 
                         data-years="' . htmlspecialchars(json_encode($chartYears)) . '"
                         data-values="' . htmlspecialchars(json_encode($yearlyData)) . '">
                        <canvas id="chartPrediksiLine" width="400" height="200"></canvas>
                    </div>
                </div>
                <div class="chart-container">
                    <h4>Struktur Usia Prediksi ' . (date('Y') + 5) . '</h4>
                    <div class="chart-placeholder" id="chartBarData"
                         data-labels="' . htmlspecialchars(json_encode(["Balita", "Anak", "Remaja", "Dewasa", "Lansia"])) . '"
                         data-values="' . htmlspecialchars(json_encode(array_values($ageData))) . '">
                        <canvas id="chartPrediksiBar" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="prediction-stats-grid">
            <div class="stat-item">
                <h4>Prediksi Total Penduduk ' . (date('Y') + 5) . '</h4>
                <div class="stat-value">' . number_format($totalPredicted2029) . ' jiwa</div>
            </div>
            <div class="stat-item">
                <h4>Rata-rata Tingkat Pertumbuhan</h4>
                <div class="stat-value">' . $avgGrowthRate . '% per tahun</div>
            </div>
            <div class="stat-item">
                <h4>Tingkat Kepercayaan</h4>
                <div class="stat-value">' . $avgConfidence . '%</div>
            </div>
            <div class="stat-item">
                <h4>Metode Prediksi</h4>
                <div class="stat-value">Linear Regression</div>
            </div>
        </div>
        
        <div class="data-table-section prediction-table-section">
            <h5>Detail Prediksi Per Desa</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
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
                    <tbody>';
        
        foreach ($predictions as $pred) {
            $html .= '<tr>
                <td>' . htmlspecialchars($pred['nama_desa']) . '</td>
                <td>' . $pred['tahun_prediksi'] . '</td>
                <td>' . number_format($pred['total_penduduk_prediksi']) . '</td>
                <td>' . number_format($pred['total_laki_prediksi']) . '</td>
                <td>' . number_format($pred['total_perempuan_prediksi']) . '</td>
                <td>' . number_format($pred['total_balita_prediksi']) . '</td>
                <td>' . number_format($pred['total_anak_prediksi']) . '</td>
                <td>' . number_format($pred['total_remaja_prediksi']) . '</td>
                <td>' . number_format($pred['total_dewasa_prediksi']) . '</td>
                <td>' . number_format($pred['total_lansia_prediksi']) . '</td>
                <td>' . $pred['growth_rate'] . '%</td>
                <td>' . $pred['confidence_level'] . '%</td>
            </tr>';
        }
        
        $html .= '</tbody></table></div></div>';
        
        $totalAge = array_sum($ageData);
        
        $html .= '<div class="analisis-breakdown">
            <h5>Prediksi Struktur Usia ' . (date('Y') + 5) . '</h5>
            <div class="breakdown-item">
                <span>Balita (0-5 tahun)</span>
                <span>' . number_format($ageData['balita']) . ' 
                    (' . ($totalAge > 0 ? round(($ageData['balita'] / $totalAge) * 100, 1) : 0) . '%)</span>
            </div>
            <div class="breakdown-item">
                <span>Anak (6-12 tahun)</span>
                <span>' . number_format($ageData['anak']) . ' 
                    (' . ($totalAge > 0 ? round(($ageData['anak'] / $totalAge) * 100, 1) : 0) . '%)</span>
            </div>
            <div class="breakdown-item">
                <span>Remaja (13-17 tahun)</span>
                <span>' . number_format($ageData['remaja']) . ' 
                    (' . ($totalAge > 0 ? round(($ageData['remaja'] / $totalAge) * 100, 1) : 0) . '%)</span>
            </div>
            <div class="breakdown-item">
                <span>Dewasa (18-64 tahun)</span>
                <span>' . number_format($ageData['dewasa']) . ' 
                    (' . ($totalAge > 0 ? round(($ageData['dewasa'] / $totalAge) * 100, 1) : 0) . '%)</span>
            </div>
            <div class="breakdown-item">
                <span>Lansia (65+ tahun)</span>
                <span>' . number_format($ageData['lansia']) . ' 
                    (' . ($totalAge > 0 ? round(($ageData['lansia'] / $totalAge) * 100, 1) : 0) . '%)</span>
            </div>
        </div>';
    }
    
    return $html . '</div></div>';
}

function generateProyeksiPembangunan($queries, $selected_desa) {
    $all_desa = $queries->getAllDesa();
    
    // Calculate projections based on real data
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
    
    // Calculate projections
    $rasio_siswa_fasilitas = $total_fasilitas_all > 0 ? $total_anak_sekolah_all / $total_fasilitas_all : 0;
    $kebutuhan_sd = $rasio_siswa_fasilitas > 50 ? ceil($rasio_siswa_fasilitas / 50) : 0;
    $kebutuhan_smp = $rasio_siswa_fasilitas > 80 ? ceil($rasio_siswa_fasilitas / 80) : 0;
    $dampak_kesejahteraan = $total_penduduk_all > 0 ? round((($total_fasilitas_all + count($all_desa)) / $total_penduduk_all) * 1000, 1) : 0;
    
    return '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-tools"></i> Proyeksi Kebutuhan Pembangunan</h4>
        </div>
        <div class="card-body">
            <div class="prediction-grid">
                <div class="prediction-item">
                    <div class="prediction-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="prediction-content">
                        <h4>Fasilitas Pendidikan</h4>
                        <p>Proyeksi kebutuhan 5 tahun ke depan</p>
                        <div class="prediction-value">
                            ' . ($kebutuhan_sd > 0 ? '+' . $kebutuhan_sd . ' SD' : 'Cukup') . ($kebutuhan_smp > 0 ? ', +' . $kebutuhan_smp . ' SMP' : '') . '
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
                        <div class="prediction-value">' . round($total_jalan_rusak, 1) . ' km perlu perbaikan</div>
                    </div>
                </div>
                
                <div class="prediction-item">
                    <div class="prediction-icon">
                        <i class="fas fa-chart-area"></i>
                    </div>
                    <div class="prediction-content">
                        <h4>Dampak Program</h4>
                        <p>Simulasi dampak program pembangunan</p>
                        <div class="prediction-value">+' . $dampak_kesejahteraan . '% kesejahteraan</div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}

function generateMonitoringData($queries, $selected_desa) {
    $filtered_desa = $selected_desa === 'all' ? $queries->getAllDesa() : [$queries->getDesaById($selected_desa)];
    
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-monitor"></i> Status Data Per Desa</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Desa</th>
                            <th>Data Kependudukan</th>
                            <th>Data Ekonomi</th>
                            <th>Data Pendidikan</th>
                            <th>Data Infrastruktur</th>
                            <th>Last Update</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($filtered_desa as $index => $desa) {
        $id_desa = $desa['id_desa'];
        
        // Hitung data kependudukan
        $penduduk = $queries->getPendudukByDesa($id_desa);
        $keluarga = $queries->getKeluargaByDesa($id_desa);
        $kependudukan_count = count($penduduk) + count($keluarga);
        $kependudukan_status = $kependudukan_count > 0 ? 'Lengkap' : 'Kosong';
        
        // Hitung data ekonomi
        $ekonomi = $queries->getDataEkonomi($id_desa);
        $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
        $ekonomi_count = count($ekonomi) + count($mata_pencaharian);
        $ekonomi_status = $ekonomi_count > 0 ? 'Lengkap' : 'Kosong';
        
        // Hitung data pendidikan
        $fasilitas = $queries->getFasilitasPendidikan($id_desa);
        $pendidikan_count = count($fasilitas);
        $pendidikan_status = $pendidikan_count > 0 ? 'Lengkap' : 'Kosong';
        
        // Hitung data infrastruktur
        $jalan = $queries->getInfrastrukturJalan($id_desa);
        $jembatan = $queries->getInfrastrukturJembatan($id_desa);
        $infrastruktur_count = count($jalan) + count($jembatan);
        $infrastruktur_status = $infrastruktur_count > 0 ? 'Lengkap' : 'Kosong';
        
        // Tentukan status keseluruhan
        $total_data = $kependudukan_count + $ekonomi_count + $pendidikan_count + $infrastruktur_count;
        if ($total_data == 0) {
            $overall_status = 'Tidak Ada Data';
            $status_class = 'status-empty';
        } elseif ($kependudukan_status == 'Lengkap' && $ekonomi_status == 'Lengkap' && 
                  $pendidikan_status == 'Lengkap' && $infrastruktur_status == 'Lengkap') {
            $overall_status = 'Lengkap';
            $status_class = 'status-complete';
        } else {
            $overall_status = 'Sebagian';
            $status_class = 'status-partial';
        }
        
        // Last update (simulasi)
        $last_update = date('d/m/Y H:i', strtotime('-' . rand(1, 30) . ' days'));
        
        $html .= '<tr>
            <td>' . ($index + 1) . '</td>
            <td>' . htmlspecialchars($desa['nama_desa']) . '</td>
            <td><span class="data-status ' . ($kependudukan_status == 'Lengkap' ? 'status-ok' : 'status-empty') . '">' . $kependudukan_count . ' records</span></td>
            <td><span class="data-status ' . ($ekonomi_status == 'Lengkap' ? 'status-ok' : 'status-empty') . '">' . $ekonomi_count . ' records</span></td>
            <td><span class="data-status ' . ($pendidikan_status == 'Lengkap' ? 'status-ok' : 'status-empty') . '">' . $pendidikan_count . ' records</span></td>
            <td><span class="data-status ' . ($infrastruktur_status == 'Lengkap' ? 'status-ok' : 'status-empty') . '">' . $infrastruktur_count . ' records</span></td>
            <td>' . $last_update . '</td>
            <td><span class="status-badge ' . $status_class . '">' . $overall_status . '</span></td>
        </tr>';
    }
    
    $html .= '</tbody></table></div>
        </div>
    </div>';
    
    return $html;
}

// Fungsi untuk Analisis Kependudukan Lanjutan
function generateKependudukanLanjutan($queries, $filtered_desa) {
    $kk_perempuan_data = [];
    $produktif_data = [];
    $lansia_data = [];
    
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
            
            // KK Perempuan + Anak Sekolah
            if ($kepala_keluarga && $kepala_keluarga['jenis_kelamin'] == 'P' && count($anak_sekolah) > 0) {
                $kk_perempuan_data[] = [
                    'kepala_keluarga' => $kepala_keluarga,
                    'anak_sekolah' => $anak_sekolah,
                    'is_miskin' => $is_kk_miskin,
                    'desa' => $desa['nama_desa']
                ];
            }
            
            // KK Lansia + Tanggungan
            if ($kepala_keluarga && $kepala_keluarga['usia'] > 65 && count($anak_sekolah) > 0) {
                $lansia_data[] = [
                    'kepala_keluarga' => $kepala_keluarga,
                    'anak_sekolah' => $anak_sekolah,
                    'desa' => $desa['nama_desa']
                ];
            }
        }
        
        // Produktif Tanpa Kerja Tetap
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
                }
            }
        }
    }
    
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-users"></i> Analisis Kependudukan Lanjutan</h4>
        </div>
        <div class="card-body">
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>KK Perempuan + Anak Sekolah</h4>
                    <div class="stat-value">' . count($kk_perempuan_data) . ' KK</div>
                </div>
                <div class="stat-item">
                    <h4>Produktif Tanpa Kerja Tetap</h4>
                    <div class="stat-value">' . count($produktif_data) . ' orang</div>
                </div>
                <div class="stat-item">
                    <h4>KK Lansia + Tanggungan</h4>
                    <div class="stat-value">' . count($lansia_data) . ' KK</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <h5>Detail KK Perempuan + Anak Sekolah</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kepala Keluarga</th>
                            <th>Desa</th>
                            <th>Jumlah Anak Sekolah</th>
                            <th>Status Ekonomi</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($kk_perempuan_data)) {
        $html .= '<tr><td colspan="5">Tidak ada data KK perempuan dengan anak sekolah</td></tr>';
    } else {
        foreach ($kk_perempuan_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['kepala_keluarga']['nama_lengkap']) . '</td>
                <td>' . htmlspecialchars($data['desa']) . '</td>
                <td>' . count($data['anak_sekolah']) . ' anak</td>
                <td><span class="status-badge ' . ($data['is_miskin'] ? 'status-miskin' : 'status-mampu') . '">' . ($data['is_miskin'] ? 'Miskin' : 'Mampu') . '</span></td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table></div>
            
            <div class="data-table-section">
                <h5>Detail Produktif Tanpa Kerja Tetap</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Desa</th>
                            <th>Pendidikan</th>
                            <th>Usia</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($produktif_data)) {
        $html .= '<tr><td colspan="5">Tidak ada data penduduk produktif tanpa kerja tetap</td></tr>';
    } else {
        foreach ($produktif_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['penduduk']['nama_lengkap']) . '</td>
                <td>' . htmlspecialchars($data['desa']) . '</td>
                <td>' . htmlspecialchars($data['penduduk']['pendidikan_terakhir']) . '</td>
                <td>' . $data['penduduk']['usia'] . ' tahun</td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table></div>
            
            <div class="data-table-section">
                <h5>Detail KK Lansia + Tanggungan</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kepala Keluarga</th>
                            <th>Desa</th>
                            <th>Usia</th>
                            <th>Jumlah Tanggungan</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($lansia_data)) {
        $html .= '<tr><td colspan="5">Tidak ada data KK lansia dengan tanggungan</td></tr>';
    } else {
        foreach ($lansia_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['kepala_keluarga']['nama_lengkap']) . '</td>
                <td>' . htmlspecialchars($data['desa']) . '</td>
                <td>' . $data['kepala_keluarga']['usia'] . ' tahun</td>
                <td>' . count($data['anak_sekolah']) . ' anak</td>
            </tr>';
        }
    }
    
    return $html . '</tbody></table></div></div></div>';
}

// Fungsi untuk Analisis Ekonomi Lanjutan
function generateEkonomiLanjutan($queries, $filtered_desa) {
    $petani_data = [];
    $korelasi_data = [];
    $kesenjangan_data = [];
    
    foreach ($filtered_desa as $desa) {
        $id_desa = $desa['id_desa'];
        
        // Petani Lahan < 0.5 Ha
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
                'pendapatan_per_musim' => $pk['pendapatan_per_musim'] ?? 0
            ];
        }
        
        // Korelasi Pendidikan-Pekerjaan
        $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
        $penduduk = $queries->getPendudukByDesa($id_desa);
        
        $nik_pendidikan = [];
        foreach ($penduduk as $p) {
            $nik_pendidikan[$p['nik']] = $p;
        }
        
        foreach ($mata_pencaharian as $mp) {
            if (isset($nik_pendidikan[$mp['nik']])) {
                $penduduk_data = $nik_pendidikan[$mp['nik']];
                $korelasi_data[] = [
                    'penduduk' => $penduduk_data,
                    'pekerjaan' => $mp,
                    'desa' => $desa['nama_desa']
                ];
            }
        }
    }
    
    // Kesenjangan Ekonomi Antar Desa
    $all_desa = $queries->getAllDesa();
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
    
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-chart-line"></i> Analisis Ekonomi Lanjutan</h4>
        </div>
        <div class="card-body">
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Petani Lahan < 0.5 Ha</h4>
                    <div class="stat-value">' . count($petani_data) . ' orang</div>
                </div>
                <div class="stat-item">
                    <h4>Korelasi Pendidikan-Pekerjaan</h4>
                    <div class="stat-value">' . count($korelasi_data) . ' orang</div>
                </div>
                <div class="stat-item">
                    <h4>Kesenjangan Ekonomi</h4>
                    <div class="stat-value">' . count($kesenjangan_data) . ' desa</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <h5>Detail Petani Lahan < 0.5 Ha</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Petani</th>
                            <th>Desa</th>
                            <th>Luas Lahan (Ha)</th>
                            <th>Jenis Komoditas</th>
                            <th>Pendapatan/Musim</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($petani_data)) {
        $html .= '<tr><td colspan="6">Tidak ada data petani lahan kecil tanpa bantuan</td></tr>';
    } else {
        foreach ($petani_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['nama_petani']) . '</td>
                <td>' . htmlspecialchars($data['desa']) . '</td>
                <td>' . number_format($data['luas_lahan'], 2) . ' Ha</td>
                <td>' . htmlspecialchars($data['jenis_komoditas']) . '</td>
                <td>Rp ' . number_format($data['pendapatan_per_musim']) . '</td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table></div>
            
            <div class="data-table-section">
                <h5>Detail Korelasi Pendidikan-Pekerjaan</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Desa</th>
                            <th>Pendidikan</th>
                            <th>Sektor Pekerjaan</th>
                            <th>Penghasilan/Bulan</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($korelasi_data)) {
        $html .= '<tr><td colspan="6">Tidak ada data korelasi pendidikan-pekerjaan</td></tr>';
    } else {
        foreach ($korelasi_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['penduduk']['nama_lengkap']) . '</td>
                <td>' . htmlspecialchars($data['desa']) . '</td>
                <td>' . htmlspecialchars($data['penduduk']['pendidikan_terakhir']) . '</td>
                <td>' . htmlspecialchars($data['pekerjaan']['sektor_pekerjaan']) . '</td>
                <td>Rp ' . number_format($data['pekerjaan']['penghasilan_perbulan'] ?? 0) . '</td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table></div>
            
            <div class="data-table-section">
                <h5>Detail Kesenjangan Ekonomi Antar Desa</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Desa</th>
                            <th>Total Pendapatan</th>
                            <th>Jumlah Pekerja</th>
                            <th>Rata-rata Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($kesenjangan_data)) {
        $html .= '<tr><td colspan="5">Tidak ada data kesenjangan ekonomi antar desa</td></tr>';
    } else {
        foreach ($kesenjangan_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['nama_desa']) . '</td>
                <td>Rp ' . number_format($data['total_pendapatan']) . '</td>
                <td>' . $data['jumlah_pekerja'] . ' orang</td>
                <td>Rp ' . number_format($data['rata_pendapatan']) . '</td>
            </tr>';
        }
    }
    
    return $html . '</tbody></table></div></div></div>';
}

// Fungsi untuk Integrasi Data
function generateIntegrasiData($queries, $filtered_desa) {
    $kk_miskin_data = [];
    $pendidikan_tinggi_miskin_data = [];
    $rasio_ketergantungan_data = [];
    $anak_tidak_sekolah_data = [];
    $anak_petani_count = 0;
    
    foreach ($filtered_desa as $desa) {
        $id_desa = $desa['id_desa'];
        $penduduk = $queries->getPendudukByDesa($id_desa);
        $keluarga = $queries->getKeluargaByDesa($id_desa);
        
        // Get warga miskin
        $query = "SELECT * FROM warga_miskin WHERE id_desa = :id_desa";
        $stmt = $queries->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        $warga_miskin = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($keluarga as $kk) {
            $is_kk_miskin = false;
            $anak_sekolah = [];
            $kepala_keluarga = null;
            $total_anggota = 0;
            $anggota_bekerja = 0;
            
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
                        if ($p['pekerjaan'] == 'Pelajar') {
                            $anak_sekolah[] = $p;
                        }
                        if ($p['pekerjaan'] && $p['pekerjaan'] != 'Belum Bekerja') {
                            $anggota_bekerja++;
                        }
                    }
                }
                
                if (count($anak_sekolah) > 0) {
                    $kk_miskin_data[] = [
                        'kepala_keluarga' => $kepala_keluarga,
                        'anak_sekolah' => $anak_sekolah,
                        'desa' => $desa['nama_desa']
                    ];
                }
            }
            
            // Rasio ketergantungan untuk semua KK
            if (!$kepala_keluarga) {
                foreach ($penduduk as $p) {
                    if ($p['id_keluarga'] == $kk['id_keluarga']) {
                        if (!$total_anggota) $total_anggota++;
                        if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                            $kepala_keluarga = $p;
                        }
                        if ($p['pekerjaan'] && $p['pekerjaan'] != 'Belum Bekerja') {
                            $anggota_bekerja++;
                        }
                    }
                }
            }
            
            $tanggungan = $total_anggota - $anggota_bekerja;
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
        
        // Pendidikan tinggi tapi miskin
        foreach ($warga_miskin as $wm) {
            $penduduk_miskin = null;
            foreach ($penduduk as $p) {
                if ($p['nik'] == $wm['nik']) {
                    $penduduk_miskin = $p;
                    break;
                }
            }
            
            if ($penduduk_miskin && in_array($penduduk_miskin['pendidikan_terakhir'], ['D1', 'D2', 'D3', 'S1', 'S2', 'S3'])) {
                $faktor = (!$penduduk_miskin['pekerjaan'] || $penduduk_miskin['pekerjaan'] == 'Belum Bekerja') ? 
                         'tidak bekerja' : 'ketidaksesuaian tempat kerja';
                
                $pendidikan_tinggi_miskin_data[] = [
                    'penduduk' => $penduduk_miskin,
                    'desa' => $desa['nama_desa'],
                    'faktor_penyebab' => $faktor
                ];
            }
        }
        
        // Anak tidak sekolah karena ekonomi
        foreach ($keluarga as $kk) {
            $kepala_keluarga = null;
            foreach ($penduduk as $p) {
                if ($p['id_keluarga'] == $kk['id_keluarga'] && $p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                    $kepala_keluarga = $p;
                    break;
                }
            }
            
            if ($kepala_keluarga) {
                $is_kk_miskin = false;
                foreach ($warga_miskin as $wm) {
                    if ($kepala_keluarga['nik'] == $wm['nik']) {
                        $is_kk_miskin = true;
                        break;
                    }
                }
                
                if ($is_kk_miskin) {
                    foreach ($penduduk as $p) {
                        if ($p['id_keluarga'] == $kk['id_keluarga'] && 
                            $p['usia'] > 6 && 
                            $p['pekerjaan'] != 'Pelajar' && 
                            $p['pendidikan_terakhir'] == 'Tidak Sekolah') {
                            $anak_tidak_sekolah_data[] = ['penduduk' => $p, 'desa' => $desa['nama_desa']];
                        }
                    }
                }
            }
        }
    }
    
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-link"></i> Integrasi Data Lintas Kategori</h4>
        </div>
        <div class="card-body">
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>KK Miskin + Anak Sekolah</h4>
                    <div class="stat-value">' . count($kk_miskin_data) . ' KK</div>
                </div>
                <div class="stat-item">
                    <h4>Pendidikan Tinggi Tapi Miskin</h4>
                    <div class="stat-value">' . count($pendidikan_tinggi_miskin_data) . ' orang</div>
                </div>
                <div class="stat-item">
                    <h4>Rasio Ketergantungan Ekonomi</h4>
                    <div class="stat-value">' . count($rasio_ketergantungan_data) . ' KK</div>
                </div>
                <div class="stat-item">
                    <h4>Anak Tidak Sekolah</h4>
                    <div class="stat-value">' . count($anak_tidak_sekolah_data) . ' anak</div>
                </div>
                <div class="stat-item">
                    <h4>Anak Petani → Pendidikan Tinggi</h4>
                    <div class="stat-value">' . $anak_petani_count . ' orang</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <h5>Detail KK Miskin + Anak Sekolah</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kepala Keluarga</th>
                            <th>Desa</th>
                            <th>Jumlah Anak Sekolah</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($kk_miskin_data)) {
        $html .= '<tr><td colspan="4">Tidak ada data KK miskin dengan anak sekolah</td></tr>';
    } else {
        foreach ($kk_miskin_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['kepala_keluarga']['nama_lengkap'] ?? 'Data Tidak Tersedia') . '</td>
                <td>' . htmlspecialchars($data['desa']) . '</td>
                <td>' . count($data['anak_sekolah']) . ' anak</td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table></div>
            
            <div class="data-table-section">
                <h5>Detail Pendidikan Tinggi Tapi Miskin</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Desa</th>
                            <th>Pendidikan</th>
                            <th>Pekerjaan</th>
                            <th>Faktor Penyebab</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($pendidikan_tinggi_miskin_data)) {
        $html .= '<tr><td colspan="6">Tidak ada data pendidikan tinggi tapi miskin</td></tr>';
    } else {
        foreach ($pendidikan_tinggi_miskin_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['penduduk']['nama_lengkap']) . '</td>
                <td>' . htmlspecialchars($data['desa']) . '</td>
                <td>' . htmlspecialchars($data['penduduk']['pendidikan_terakhir']) . '</td>
                <td>' . htmlspecialchars($data['penduduk']['pekerjaan'] ?: 'Belum Bekerja') . '</td>
                <td>' . htmlspecialchars($data['faktor_penyebab']) . '</td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table></div>';
    
    // Detail Rasio Ketergantungan Ekonomi
    $html .= '<div class="data-table-section">
                <h5>Detail Rasio Ketergantungan Ekonomi</h5>
                <table class="table table-bordered">
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
                    <tbody>';
    
    if (empty($rasio_ketergantungan_data)) {
        $html .= '<tr><td colspan="7">Tidak ada data rasio ketergantungan ekonomi</td></tr>';
    } else {
        foreach ($rasio_ketergantungan_data as $index => $data) {
            $t = $data['tanggungan'];
            $kategori = '';
            if ($t >= 0 && $t <= 1) $kategori = 'Rendah';
            elseif ($t == 2) $kategori = 'Sedang';
            elseif ($t == 3) $kategori = 'Tinggi';
            else $kategori = 'Sangat Tinggi';
            
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['kepala_keluarga']) . '</td>
                <td>' . htmlspecialchars($data['desa']) . '</td>
                <td>' . $data['total_anggota'] . ' orang</td>
                <td>' . $data['anggota_bekerja'] . ' orang</td>
                <td>' . $data['tanggungan'] . ' orang</td>
                <td>' . $kategori . '</td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table></div>';
    
    // Detail Anak Tidak Sekolah
    $html .= '<div class="data-table-section">
                <h5>Detail Anak Tidak Sekolah karena Ekonomi Keluarga</h5>
                <table class="table table-bordered">
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
                    <tbody>';
    
    if (empty($anak_tidak_sekolah_data)) {
        $html .= '<tr><td colspan="6">Tidak ada data anak tidak sekolah karena ekonomi keluarga</td></tr>';
    } else {
        foreach ($anak_tidak_sekolah_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['penduduk']['nama_lengkap']) . '</td>
                <td>' . htmlspecialchars($data['desa']) . '</td>
                <td>' . $data['penduduk']['usia'] . ' tahun</td>
                <td>' . ($data['penduduk']['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') . '</td>
                <td>Miskin</td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table></div>';
    
    // Detail Anak Petani → Pendidikan Tinggi
    $anak_petani_data = [];
    foreach ($filtered_desa as $desa) {
        $keluarga = $queries->getKeluargaByDesa($desa['id_desa']);
        $penduduk = $queries->getPendudukByDesa($desa['id_desa']);
        
        foreach ($keluarga as $kk) {
            $kepala_keluarga = null;
            foreach ($penduduk as $p) {
                if ($p['id_keluarga'] == $kk['id_keluarga'] && $p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                    $kepala_keluarga = $p;
                    break;
                }
            }
            
            if ($kepala_keluarga && $kepala_keluarga['pekerjaan'] == 'Petani') {
                foreach ($penduduk as $p) {
                    if ($p['id_keluarga'] == $kk['id_keluarga'] && 
                        $p['nama_lengkap'] != $kk['nama_kepala_keluarga'] && 
                        in_array($p['pendidikan_terakhir'], ['D1', 'D2', 'D3', 'S1', 'S2', 'S3'])) {
                        $anak_petani_data[] = [
                            'penduduk' => $p,
                            'desa' => $desa['nama_desa'],
                            'parent' => $kepala_keluarga
                        ];
                        $anak_petani_count++;
                    }
                }
            }
        }
    }
    
    $html .= '<div class="data-table-section">
                <h5>Detail Anak Petani → Pendidikan Tinggi</h5>
                <table class="table table-bordered">
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
                    <tbody>';
    
    if (empty($anak_petani_data)) {
        $html .= '<tr><td colspan="6">Tidak ada data anak petani dengan pendidikan tinggi</td></tr>';
    } else {
        foreach ($anak_petani_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['penduduk']['nama_lengkap']) . '</td>
                <td>' . htmlspecialchars($data['desa']) . '</td>
                <td>' . htmlspecialchars($data['penduduk']['pendidikan_terakhir']) . '</td>
                <td>' . $data['penduduk']['usia'] . ' tahun</td>
                <td>' . htmlspecialchars($data['parent']['nama_lengkap']) . '</td>
            </tr>';
        }
    }
    
    return $html . '</tbody></table></div></div></div>';
}

// Fungsi untuk Analisis Spasial & Prediktif
function generateSpasialPrediktif($queries, $filtered_desa) {
    $spasial_data = [];
    $aksesibilitas_data = [];
    $potensi_pengembangan = [];
    
    foreach ($filtered_desa as $desa) {
        $id_desa = $desa['id_desa'];
        $penduduk = $queries->getPendudukByDesa($id_desa);
        $fasilitas = $queries->getFasilitasPendidikan($id_desa);
        $ekonomi = $queries->getDataEkonomi($id_desa);
        $jalan = $queries->getInfrastrukturJalan($id_desa);
        
        // Convert hectares to km²
        $luas_ha = $desa['luas_wilayah'] ?? 1;
        $luas_km2 = $luas_ha * 0.01;
        $kepadatan = $luas_km2 > 0 ? round(count($penduduk) / $luas_km2, 2) : 0;
        $total_fasilitas = count($fasilitas);
        
        // Indeks aksesibilitas
        $jalan_baik = 0;
        $total_jalan = count($jalan);
        foreach ($jalan as $j) {
            if ($j['kondisi_jalan'] == 'baik') {
                $jalan_baik++;
            }
        }
        $aksesibilitas = $total_jalan > 0 ? ($jalan_baik / $total_jalan) * 100 : 0;
        
        // Nilai ekonomi
        $nilai_ekonomi_total = 0;
        foreach ($ekonomi as $e) {
            $nilai_ekonomi_total += $e['nilai_ekonomi'] ?? 0;
        }
        
        $spasial_data[] = [
            'nama_desa' => $desa['nama_desa'],
            'kepadatan' => $kepadatan,
            'fasilitas_pendidikan' => $total_fasilitas,
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
            'fasilitas' => $total_fasilitas,
            'nilai_ekonomi' => $nilai_ekonomi_total,
            'aksesibilitas' => round($aksesibilitas, 1)
        ];
    }
    
    // Sort by potential
    usort($potensi_pengembangan, function ($a, $b) {
        $score_a = ($a['kepadatan'] * 0.2) + ($a['nilai_ekonomi'] / 1000000 * 0.5) + ($a['aksesibilitas'] / 100 * 0.3);
        $score_b = ($b['kepadatan'] * 0.2) + ($b['nilai_ekonomi'] / 1000000 * 0.5) + ($b['aksesibilitas'] / 100 * 0.3);
        return $score_b <=> $score_a;
    });
    
    $html = '<div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-map"></i> Analisis Spasial & Prediktif</h4>
        </div>
        <div class="card-body">
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Analisis Kepadatan & Fasilitas</h4>
                    <div class="stat-value">' . count($spasial_data) . ' desa</div>
                </div>
                <div class="stat-item">
                    <h4>Indeks Aksesibilitas</h4>
                    <div class="stat-value">' . count($aksesibilitas_data) . ' desa</div>
                </div>
                <div class="stat-item">
                    <h4>Potensi Pengembangan</h4>
                    <div class="stat-value">' . (count($potensi_pengembangan) > 0 ? $potensi_pengembangan[0]['nama_desa'] : 'N/A') . '</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <h5>Detail Analisis Kepadatan & Fasilitas</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Desa</th>
                            <th>Kepadatan (jiwa/km²)</th>
                            <th>Fasilitas Pendidikan</th>
                            <th>Total Penduduk</th>
                            <th>Luas Wilayah (km²)</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($spasial_data)) {
        $html .= '<tr><td colspan="6">Tidak ada data analisis kepadatan dan fasilitas</td></tr>';
    } else {
        foreach ($spasial_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['nama_desa']) . '</td>
                <td>' . number_format($data['kepadatan'], 2) . '</td>
                <td>' . $data['fasilitas_pendidikan'] . ' unit</td>
                <td>' . $data['total_penduduk'] . ' jiwa</td>
                <td>' . $data['luas_wilayah'] . ' km²</td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table></div>
            
            <div class="data-table-section">
                <h5>Detail Indeks Aksesibilitas</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Desa</th>
                            <th>Jalan Baik</th>
                            <th>Total Jalan</th>
                            <th>Aksesibilitas (%)</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($aksesibilitas_data)) {
        $html .= '<tr><td colspan="5">Tidak ada data indeks aksesibilitas</td></tr>';
    } else {
        foreach ($aksesibilitas_data as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['nama_desa']) . '</td>
                <td>' . $data['jalan_baik'] . ' unit</td>
                <td>' . $data['total_jalan'] . ' unit</td>
                <td>' . $data['aksesibilitas'] . '%</td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table></div>
            
            <div class="data-table-section">
                <h5>Ranking Potensi Pengembangan</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Ranking</th>
                            <th>Nama Desa</th>
                            <th>Kepadatan</th>
                            <th>Total Fasilitas</th>
                            <th>Nilai Ekonomi</th>
                            <th>Aksesibilitas</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    if (empty($potensi_pengembangan)) {
        $html .= '<tr><td colspan="6">Tidak ada data potensi pengembangan desa</td></tr>';
    } else {
        foreach ($potensi_pengembangan as $index => $data) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($data['nama_desa']) . '</td>
                <td>' . number_format($data['kepadatan'], 2) . '</td>
                <td>' . $data['fasilitas'] . ' unit</td>
                <td>Rp ' . number_format($data['nilai_ekonomi']) . '</td>
                <td>' . $data['aksesibilitas'] . '%</td>
            </tr>';
        }
    }
    
    return $html . '</tbody></table></div></div></div>';
}
?>