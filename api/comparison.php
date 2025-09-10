<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/queries.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$queries = new Queries();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['desa1']) && isset($_GET['desa2'])) {
    $desa1Id = $_GET['desa1'];
    $desa2Id = $_GET['desa2'];
    
    try {
        // Fetch data for both desa
        $desa1Data = fetchDesaComparisonData($queries, $desa1Id);
        $desa2Data = fetchDesaComparisonData($queries, $desa2Id);
        
        $comparisonData = generateComparisonData($desa1Data, $desa2Data);
        
        echo json_encode([
            'success' => true,
            'data' => $comparisonData
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
        'error' => 'Invalid request parameters'
    ]);
}

function fetchDesaComparisonData($queries, $desaId) {
    $desa = $queries->getDesaById($desaId);
    $penduduk = $queries->getPendudukByDesa($desaId);
    $fasilitas = $queries->getFasilitasPendidikan($desaId);
    $ekonomi = $queries->getDataEkonomi($desaId);
    $jalan = $queries->getInfrastrukturJalan($desaId);
    $jembatan = $queries->getInfrastrukturJembatan($desaId);
    $mataPencaharian = $queries->getMataPencaharianByDesa($desaId);
    
    return [
        'desa' => $desa,
        'penduduk' => $penduduk,
        'fasilitas' => $fasilitas,
        'ekonomi' => $ekonomi,
        'jalan' => $jalan,
        'jembatan' => $jembatan,
        'mataPencaharian' => $mataPencaharian
    ];
}

function generateComparisonData($data1, $data2) {
    $html = '';
    
    // Data Umum
    $html .= generateDataUmumComparison($data1, $data2);
    
    // Demografis
    $html .= generateDemografisComparison($data1, $data2);
    
    // Pendidikan
    $html .= generatePendidikanComparison($data1, $data2);
    
    // Ekonomi
    $html .= generateEkonomiComparison($data1, $data2);
    
    // Infrastruktur
    $html .= generateInfrastrukturComparison($data1, $data2);
    
    return $html;
}

function generateDataUmumComparison($data1, $data2) {
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

function generateDemografisComparison($data1, $data2) {
    // Struktur Usia
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

function generatePendidikanComparison($data1, $data2) {
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

function generateEkonomiComparison($data1, $data2) {
    // Mata Pencaharian
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

function generateInfrastrukturComparison($data1, $data2) {
    // Kondisi Jalan
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
?>