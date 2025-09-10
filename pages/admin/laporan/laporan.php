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

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'save_template':
            $template_name = $_POST['template_name'];
            $template_config = $_POST['template_config'];
            
            // Find the lowest available ID
            $query = "SELECT MIN(t1.id_template + 1) AS next_id 
                     FROM laporan_template t1 
                     LEFT JOIN laporan_template t2 ON t1.id_template + 1 = t2.id_template 
                     WHERE t2.id_template IS NULL";
            $stmt = $queries->db->prepare($query);
            $stmt->execute();
            $next_id = $stmt->fetchColumn();
            
            if (!$next_id) {
                $next_id = 1;
            }
            
            // Save template with specific ID
            $query = "INSERT INTO laporan_template (id_template, nama_template, konfigurasi, created_by, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $queries->db->prepare($query);
            $result = $stmt->execute([$next_id, $template_name, $template_config, $_SESSION['user_id']]);
            
            echo json_encode(['success' => $result]);
            exit();
            
        case 'update_template':
            $template_id = $_POST['template_id'];
            $template_name = $_POST['template_name'];
            $template_config = $_POST['template_config'];
            
            // Update existing template
            $query = "UPDATE laporan_template SET nama_template = ?, konfigurasi = ?, updated_at = NOW() WHERE id_template = ? AND created_by = ?";
            $stmt = $queries->db->prepare($query);
            $result = $stmt->execute([$template_name, $template_config, $template_id, $_SESSION['user_id']]);
            
            echo json_encode(['success' => $result]);
            exit();
            
        case 'reorder_template_ids':
            // Get all templates ordered by creation date
            $query = "SELECT id_template FROM laporan_template ORDER BY created_at ASC";
            $stmt = $queries->db->prepare($query);
            $stmt->execute();
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Reorder IDs starting from 1
            $new_id = 1;
            foreach ($templates as $template) {
                $query = "UPDATE laporan_template SET id_template = ? WHERE id_template = ?";
                $stmt = $queries->db->prepare($query);
                $stmt->execute([$new_id, $template['id_template']]);
                $new_id++;
            }
            
            // Reset AUTO_INCREMENT
            $query = "ALTER TABLE laporan_template AUTO_INCREMENT = ?";
            $stmt = $queries->db->prepare($query);
            $stmt->execute([$new_id]);
            
            echo json_encode(['success' => true]);
            exit();
            
        case 'generate_report':
            $template_config = json_decode($_POST['template_config'], true);
            $report_data = generateReportData($queries, $template_config);
            
            // Save to history
            $query = "INSERT INTO laporan_riwayat (nama_laporan, konfigurasi, data_laporan, created_by, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $queries->db->prepare($query);
            $stmt->execute([$template_config['nama_laporan'], json_encode($template_config), json_encode($report_data), $_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'data' => $report_data]);
            exit();
            
        case 'preview_report':
            // Clean all output buffers first
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Type: application/json');
            
            try {
                $template_config = json_decode($_POST['template_config'], true);
                
                if (!$template_config) {
                    throw new Exception('Invalid JSON config');
                }
                
                // Validate comparison data
                if (in_array('perbandingan_desa', $template_config['sections'])) {
                    if (empty($template_config['desa1']) || empty($template_config['desa2'])) {
                        throw new Exception('Pilih kedua desa untuk perbandingan');
                    }
                    if ($template_config['desa1'] === $template_config['desa2']) {
                        throw new Exception('Pilih desa yang berbeda untuk perbandingan');
                    }
                }
                
                $report_data = generateReportData($queries, $template_config);
                
                echo json_encode(['success' => true, 'data' => $report_data]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false, 
                    'error' => $e->getMessage()
                ]);
            } catch (Error $e) {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Fatal Error: ' . $e->getMessage()
                ]);
            }
            exit();
    }
}

// Get templates and history
$templates = [];
$query = "SELECT * FROM laporan_template ORDER BY created_at DESC";
$stmt = $queries->db->prepare($query);
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$riwayat = [];
$query = "SELECT * FROM laporan_riwayat ORDER BY created_at DESC LIMIT 50";
$stmt = $queries->db->prepare($query);
$stmt->execute();
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

function generateReportData($queries, $config) {
    $selected_desa = $config['desa'] ?? 'all';
    $filtered_desa = $selected_desa === 'all' ? $queries->getAllDesa() : [$queries->getDesaById($selected_desa)];
    
    $html_content = '';
    
    // Check if comparison is selected
    if (in_array('perbandingan_desa', $config['sections']) && isset($config['desa1']) && isset($config['desa2'])) {
        // Generate comparison data
        $html_content = generateComparisonReport($queries, $config['desa1'], $config['desa2']);
    } else {
        // Check if any advanced analysis sections are selected
        $advanced_sections = ['analisis_kependudukan', 'analisis_ekonomi', 'integrasi_data', 'analisis_spasial'];
        $has_advanced = array_intersect($config['sections'], $advanced_sections);
        
        if (!empty($has_advanced)) {
            // Generate advanced analysis content
            $html_content = generateAdvancedAnalysisReport($queries, $config, $filtered_desa);
        } else {
            // Use regular analisis for basic sections
            ob_start();
            $_SESSION['selected_desa'] = $selected_desa;
            
            try {
                error_reporting(0);
                include __DIR__ . '/../analisis/analisis.php';
                $html_content = ob_get_clean();
            } catch (Exception $e) {
                ob_end_clean();
                $html_content = '<div class="error">Error loading basic analysis</div>';
            }
        }
    }
    
    return ['html_content' => $html_content, 'filtered_desa' => $filtered_desa];
}

function generateComparisonReport($queries, $desa1Id, $desa2Id) {
    try {
        // Fetch comparison data using the same logic as monitoring
        $desa1Data = fetchDesaComparisonData($queries, $desa1Id);
        $desa2Data = fetchDesaComparisonData($queries, $desa2Id);
        
        $html = '<div class="comparison-report">';
        
        // Data Umum
        $html .= generateDataUmumComparison($desa1Data, $desa2Data);
        
        // Demografis
        $html .= generateDemografisComparison($desa1Data, $desa2Data);
        
        // Pendidikan
        $html .= generatePendidikanComparison($desa1Data, $desa2Data);
        
        // Ekonomi
        $html .= generateEkonomiComparison($desa1Data, $desa2Data);
        
        // Infrastruktur
        $html .= generateInfrastrukturComparison($desa1Data, $desa2Data);
        
        $html .= '</div>';
        
        return $html;
        
    } catch (Exception $e) {
        return '<div class="error">Error generating comparison report: ' . $e->getMessage() . '</div>';
    }
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

function generateAdvancedAnalysisReport($queries, $config, $filtered_desa) {
    $selected_sections = $config['sections'];
    $html_content = '<div class="advanced-analysis-report">';
    
    // Analisis Kependudukan Lanjutan
    if (in_array('analisis_kependudukan', $selected_sections)) {
        $html_content .= generateKependudukanLanjutan($queries, $filtered_desa);
    }
    
    // Analisis Ekonomi Lanjutan
    if (in_array('analisis_ekonomi', $selected_sections)) {
        $html_content .= generateEkonomiLanjutan($queries, $filtered_desa);
    }
    
    // Integrasi Data
    if (in_array('integrasi_data', $selected_sections)) {
        $html_content .= generateIntegrasiData($queries, $filtered_desa);
    }
    
    // Analisis Spasial & Prediktif
    if (in_array('analisis_spasial', $selected_sections)) {
        $html_content .= generateAnalisisSpasial($queries, $filtered_desa);
    }
    
    $html_content .= '</div>';
    return $html_content;
}

function generateKependudukanLanjutan($queries, $filtered_desa) {
    $html = '<div class="card mb-4"><div class="card-header"><h4><i class="fas fa-users"></i> Analisis Kependudukan Lanjutan</h4></div><div class="card-body">';
    
    // KK Perempuan + Anak Sekolah
    $kk_perempuan_data = [];
    foreach ($filtered_desa as $desa) {
        $keluarga = $queries->getKeluargaByDesa($desa['id_desa']);
        $penduduk = $queries->getPendudukByDesa($desa['id_desa']);
        
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
            
            if ($kepala_keluarga && $kepala_keluarga['jenis_kelamin'] == 'P' && count($anak_sekolah) > 0) {
                $kk_perempuan_data[] = [
                    'kepala_keluarga' => $kepala_keluarga,
                    'anak_sekolah' => $anak_sekolah,
                    'desa' => $desa['nama_desa']
                ];
            }
        }
    }
    
    $html .= '<h5>KK Perempuan + Anak Sekolah (' . count($kk_perempuan_data) . ' KK)</h5>';
    $html .= '<div class="table-responsive"><table class="table table-bordered table-striped">';
    $html .= '<thead><tr><th>No</th><th>Nama Kepala Keluarga</th><th>Desa</th><th>Jumlah Anak Sekolah</th><th>Usia Anak</th></tr></thead><tbody>';
    
    foreach ($kk_perempuan_data as $index => $data) {
        $usia_list = array_map(function($anak) { return $anak['usia']; }, $data['anak_sekolah']);
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['kepala_keluarga']['nama_lengkap']) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['desa']) . '</td>';
        $html .= '<td>' . count($data['anak_sekolah']) . ' anak</td>';
        $html .= '<td>' . implode(', ', $usia_list) . ' tahun</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    
    // Detail Produktif Tanpa Kerja Tetap
    $produktif_data = [];
    foreach ($filtered_desa as $desa) {
        $penduduk = $queries->getPendudukByDesa($desa['id_desa']);
        $mata_pencaharian = $queries->getMataPencaharianByDesa($desa['id_desa']);
        
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
    
    $html .= '<h5 style="margin-top: 30px;">Detail Produktif Tanpa Kerja Tetap (' . count($produktif_data) . ' orang)</h5>';
    $html .= '<div class="table-responsive"><table class="table table-bordered table-striped">';
    $html .= '<thead><tr><th>No</th><th>Nama</th><th>Desa</th><th>Pendidikan</th><th>Jenis Kelamin</th><th>Usia</th><th>Status Pekerjaan</th></tr></thead><tbody>';
    
    foreach ($produktif_data as $index => $data) {
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['penduduk']['nama_lengkap']) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['desa']) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['penduduk']['pendidikan_terakhir']) . '</td>';
        $html .= '<td>' . ($data['penduduk']['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') . '</td>';
        $html .= '<td>' . $data['penduduk']['usia'] . ' tahun</td>';
        $html .= '<td>Tidak Tetap</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    $html .= '</div></div>';
    
    return $html;
}

function generateEkonomiLanjutan($queries, $filtered_desa) {
    $html = '<div class="card mb-4"><div class="card-header"><h4><i class="fas fa-seedling"></i> Analisis Ekonomi Lanjutan</h4></div><div class="card-body">';
    
    // Petani Lahan < 0.5 Ha Tanpa Bantuan
    $petani_data = [];
    foreach ($filtered_desa as $desa) {
        $query = "SELECT p.*, de.id_desa, pd.nama_lengkap 
                 FROM pertanian p 
                 JOIN data_ekonomi de ON p.id_ekonomi = de.id_ekonomi 
                 LEFT JOIN penduduk pd ON pd.nik = p.nik_petani
                 WHERE de.id_desa = :id_desa AND p.luas_lahan < 0.5 AND p.bantuan_pertanian = 'tidak_ada'";
        $stmt = $queries->db->prepare($query);
        $stmt->bindParam(':id_desa', $desa['id_desa']);
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
    }
    
    $html .= '<h5>Petani Lahan < 0.5 Ha Tanpa Bantuan (' . count($petani_data) . ' petani)</h5>';
    $html .= '<div class="table-responsive"><table class="table table-bordered table-striped">';
    $html .= '<thead><tr><th>No</th><th>Nama Petani</th><th>Desa</th><th>Luas Lahan (Ha)</th><th>Jenis Komoditas</th><th>Pendapatan/Musim</th></tr></thead><tbody>';
    
    foreach ($petani_data as $index => $data) {
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['nama_petani']) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['desa']) . '</td>';
        $html .= '<td>' . number_format($data['luas_lahan'], 2) . ' Ha</td>';
        $html .= '<td>' . htmlspecialchars($data['jenis_komoditas']) . '</td>';
        $html .= '<td>Rp ' . number_format($data['pendapatan_per_musim']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    $html .= '</div></div>';
    
    return $html;
}

function generateIntegrasiData($queries, $filtered_desa) {
    $html = '<div class="card mb-4"><div class="card-header"><h4><i class="fas fa-link"></i> Integrasi Data Lintas Kategori</h4></div><div class="card-body">';
    
    // KK Miskin + Anak Sekolah
    $kk_miskin_data = [];
    foreach ($filtered_desa as $desa) {
        $keluarga = $queries->getKeluargaByDesa($desa['id_desa']);
        $penduduk = $queries->getPendudukByDesa($desa['id_desa']);
        
        $query = "SELECT * FROM warga_miskin WHERE id_desa = :id_desa";
        $stmt = $queries->db->prepare($query);
        $stmt->bindParam(':id_desa', $desa['id_desa']);
        $stmt->execute();
        $warga_miskin = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($keluarga as $kk) {
            $is_kk_miskin = false;
            $anak_sekolah = [];
            $kepala_keluarga = null;
            
            foreach ($warga_miskin as $wm) {
                foreach ($penduduk as $p) {
                    if ($p['nik'] == $wm['nik'] && $p['id_keluarga'] == $kk['id_keluarga']) {
                        $is_kk_miskin = true;
                        break 2;
                    }
                }
            }
            
            if ($is_kk_miskin) {
                foreach ($penduduk as $p) {
                    if ($p['id_keluarga'] == $kk['id_keluarga']) {
                        if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                            $kepala_keluarga = $p;
                        }
                        if ($p['pekerjaan'] == 'Pelajar') {
                            $anak_sekolah[] = $p;
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
        }
    }
    
    $html .= '<div class="detail-section"><div class="section-header"><h3><i class="fas fa-users"></i> Detail KK Miskin + Anak Sekolah</h3></div><div class="section-content">';
    $html .= '<div class="analisis-stats-grid"><div class="stat-item"><h4>KK Miskin + Anak Sekolah</h4><div class="stat-value">' . count($kk_miskin_data) . ' KK</div></div></div>';
    $html .= '<div class="data-table-section"><div class="table-header"><h4>Daftar KK Miskin + Anak Sekolah</h4></div>';
    $html .= '<div class="table-responsive"><table class="data-table"><thead><tr><th>No</th><th>Nama Kepala Keluarga</th><th>Desa</th><th>Jumlah Anak Sekolah</th><th>Anak Sekolah</th></tr></thead><tbody>';
    
    foreach ($kk_miskin_data as $index => $data) {
        $anak_list = array_map(function($anak) { 
            return $anak['nama_lengkap'] . ' (' . $anak['usia'] . ' tahun)';
        }, $data['anak_sekolah']);
        
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['kepala_keluarga']['nama_lengkap'] ?? 'Data Tidak Tersedia') . '</td>';
        $html .= '<td>' . htmlspecialchars($data['desa']) . '</td>';
        $html .= '<td>' . count($data['anak_sekolah']) . ' anak</td>';
        $html .= '<td>' . implode(', ', $anak_list) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div></div></div></div>';
    
    // Detail Rasio Ketergantungan Ekonomi
    $rasio_data = [];
    foreach ($filtered_desa as $desa) {
        $keluarga = $queries->getKeluargaByDesa($desa['id_desa']);
        $penduduk = $queries->getPendudukByDesa($desa['id_desa']);
        
        foreach ($keluarga as $kk) {
            $yang_bekerja = 0;
            $total_anggota = 0;
            $kepala_keluarga = null;
            
            foreach ($penduduk as $p) {
                if ($p['id_keluarga'] == $kk['id_keluarga']) {
                    $total_anggota++;
                    if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                        $kepala_keluarga = $p;
                    }
                    if ($p['pekerjaan'] && $p['pekerjaan'] != 'Belum Bekerja') {
                        $yang_bekerja++;
                    }
                }
            }
            
            $tanggungan = $total_anggota - $yang_bekerja;
            
            if ($kepala_keluarga) {
                $rasio_data[] = [
                    'kepala_keluarga' => $kepala_keluarga['nama_lengkap'],
                    'desa' => $desa['nama_desa'],
                    'total_anggota' => $total_anggota,
                    'anggota_bekerja' => $yang_bekerja,
                    'tanggungan' => $tanggungan
                ];
            }
        }
    }
    
    $html .= '<div class="detail-section"><div class="section-header"><h3><i class="fas fa-balance-scale"></i> Detail Rasio Ketergantungan Ekonomi</h3></div><div class="section-content">';
    $html .= '<div class="analisis-stats-grid"><div class="stat-item"><h4>Rasio Ketergantungan Ekonomi</h4><div class="stat-value">' . count($rasio_data) . ' KK</div></div></div>';
    $html .= '<div class="data-table-section"><div class="table-header"><h4>Daftar Rasio Ketergantungan</h4></div>';
    $html .= '<div class="table-responsive"><table class="data-table"><thead><tr><th>No</th><th>Kepala Keluarga</th><th>Desa</th><th>Total Anggota</th><th>Yang Bekerja</th><th>Tanggungan</th><th>Kategori</th></tr></thead><tbody>';
    
    foreach ($rasio_data as $index => $data) {
        $t = $data['tanggungan'];
        $kategori = '';
        if ($t >= 0 && $t <= 1) $kategori = 'Rendah';
        elseif ($t == 2) $kategori = 'Sedang';
        elseif ($t == 3) $kategori = 'Tinggi';
        else $kategori = 'Sangat Tinggi';
        
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['kepala_keluarga']) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['desa']) . '</td>';
        $html .= '<td>' . $data['total_anggota'] . ' orang</td>';
        $html .= '<td>' . $data['anggota_bekerja'] . ' orang</td>';
        $html .= '<td>' . $data['tanggungan'] . ' orang</td>';
        $html .= '<td>' . $kategori . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div></div></div></div>';
    
    // Detail Pendidikan Tinggi Tapi Miskin
    $pendidikan_tinggi_miskin_data = [];
    foreach ($filtered_desa as $desa) {
        $penduduk = $queries->getPendudukByDesa($desa['id_desa']);
        $keluarga = $queries->getKeluargaByDesa($desa['id_desa']);
        
        $query = "SELECT * FROM warga_miskin WHERE id_desa = :id_desa";
        $stmt = $queries->db->prepare($query);
        $stmt->bindParam(':id_desa', $desa['id_desa']);
        $stmt->execute();
        $warga_miskin = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($warga_miskin as $wm) {
            $penduduk_miskin = null;
            foreach ($penduduk as $p) {
                if ($p['nik'] == $wm['nik']) {
                    $penduduk_miskin = $p;
                    break;
                }
            }
            
            if ($penduduk_miskin) {
                $is_kepala_keluarga = false;
                foreach ($keluarga as $kk) {
                    if ($kk['nama_kepala_keluarga'] == $penduduk_miskin['nama_lengkap'] && 
                        $penduduk_miskin['id_keluarga'] == $kk['id_keluarga']) {
                        $is_kepala_keluarga = true;
                        break;
                    }
                }
                
                if ($is_kepala_keluarga && in_array($penduduk_miskin['pendidikan_terakhir'], ['D1', 'D2', 'D3', 'S1', 'S2', 'S3'])) {
                    $faktor = (!$penduduk_miskin['pekerjaan'] || $penduduk_miskin['pekerjaan'] == 'Belum Bekerja') ? 
                             'tidak bekerja' : 'ketidaksesuaian tempat kerja';
                    
                    $pendidikan_tinggi_miskin_data[] = [
                        'penduduk' => $penduduk_miskin,
                        'desa' => $desa['nama_desa'],
                        'faktor_penyebab' => $faktor
                    ];
                }
            }
        }
    }
    
    $html .= '<div class="detail-section"><div class="section-header"><h3><i class="fas fa-user-graduate"></i> Detail Pendidikan Tinggi Tapi Miskin</h3></div><div class="section-content">';
    $html .= '<div class="analisis-stats-grid"><div class="stat-item"><h4>Pendidikan Tinggi Tapi Miskin</h4><div class="stat-value">' . count($pendidikan_tinggi_miskin_data) . ' orang</div></div></div>';
    $html .= '<div class="data-table-section"><div class="table-header"><h4>Daftar Pendidikan Tinggi Tapi Miskin</h4></div>';
    $html .= '<div class="table-responsive"><table class="data-table"><thead><tr><th>No</th><th>Nama</th><th>Desa</th><th>Pendidikan</th><th>Pekerjaan</th><th>Usia</th><th>Faktor Penyebab</th></tr></thead><tbody>';
    
    if (empty($pendidikan_tinggi_miskin_data)) {
        $html .= '<tr><td colspan="7">Tidak ada data pendidikan tinggi tapi miskin</td></tr>';
    } else {
        foreach ($pendidikan_tinggi_miskin_data as $index => $data) {
            $html .= '<tr>';
            $html .= '<td>' . ($index + 1) . '</td>';
            $html .= '<td>' . htmlspecialchars($data['penduduk']['nama_lengkap']) . '</td>';
            $html .= '<td>' . htmlspecialchars($data['desa']) . '</td>';
            $html .= '<td>' . htmlspecialchars($data['penduduk']['pendidikan_terakhir']) . '</td>';
            $html .= '<td>' . htmlspecialchars($data['penduduk']['pekerjaan'] ?: 'Belum Bekerja') . '</td>';
            $html .= '<td>' . $data['penduduk']['usia'] . ' tahun</td>';
            $html .= '<td>' . htmlspecialchars($data['faktor_penyebab']) . '</td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '</tbody></table></div></div></div></div>';
    
    // Detail Anak Tidak Sekolah
    $anak_tidak_sekolah_data = [];
    foreach ($filtered_desa as $desa) {
        $keluarga = $queries->getKeluargaByDesa($desa['id_desa']);
        $penduduk = $queries->getPendudukByDesa($desa['id_desa']);
        
        $query = "SELECT * FROM warga_miskin WHERE id_desa = :id_desa";
        $stmt = $queries->db->prepare($query);
        $stmt->bindParam(':id_desa', $desa['id_desa']);
        $stmt->execute();
        $warga_miskin = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
                            $anak_tidak_sekolah_data[] = ['penduduk' => $p, 'desa' => $desa['nama_desa'], 'status' => 'miskin'];
                        }
                    }
                }
            }
        }
    }
    
    $html .= '<div class="detail-section"><div class="section-header"><h3><i class="fas fa-child"></i> Detail Anak Tidak Sekolah karena Ekonomi Keluarga</h3></div><div class="section-content">';
    $html .= '<div class="analisis-stats-grid"><div class="stat-item"><h4>Anak Tidak Sekolah karena Ekonomi Keluarga</h4><div class="stat-value">' . count($anak_tidak_sekolah_data) . ' anak</div></div></div>';
    $html .= '<div class="data-table-section"><div class="table-header"><h4>Daftar Anak Tidak Sekolah</h4></div>';
    $html .= '<div class="table-responsive"><table class="data-table"><thead><tr><th>No</th><th>Nama</th><th>Desa</th><th>Usia</th><th>Jenis Kelamin</th><th>Status Ekonomi Keluarga</th></tr></thead><tbody>';
    
    if (empty($anak_tidak_sekolah_data)) {
        $html .= '<tr><td colspan="6">Tidak ada data anak tidak sekolah karena ekonomi keluarga</td></tr>';
    } else {
        foreach ($anak_tidak_sekolah_data as $index => $data) {
            $html .= '<tr>';
            $html .= '<td>' . ($index + 1) . '</td>';
            $html .= '<td>' . htmlspecialchars($data['penduduk']['nama_lengkap']) . '</td>';
            $html .= '<td>' . htmlspecialchars($data['desa']) . '</td>';
            $html .= '<td>' . $data['penduduk']['usia'] . ' tahun</td>';
            $html .= '<td>' . ($data['penduduk']['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') . '</td>';
            $html .= '<td>Miskin</td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '</tbody></table></div></div></div></div>';
    
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
                    }
                }
            }
        }
    }
    
    $html .= '<div class="detail-section"><div class="section-header"><h3><i class="fas fa-seedling"></i> Detail Anak Petani → Pendidikan Tinggi</h3></div><div class="section-content">';
    $html .= '<div class="analisis-stats-grid"><div class="stat-item"><h4>Anak Petani → Pendidikan Tinggi</h4><div class="stat-value">' . count($anak_petani_data) . ' orang</div></div></div>';
    $html .= '<div class="data-table-section"><div class="table-header"><h4>Daftar Anak Petani Pendidikan Tinggi</h4></div>';
    $html .= '<div class="table-responsive"><table class="data-table"><thead><tr><th>No</th><th>Nama</th><th>Desa</th><th>Pendidikan</th><th>Usia</th><th>Nama Orang Tua</th></tr></thead><tbody>';
    
    if (empty($anak_petani_data)) {
        $html .= '<tr><td colspan="6">Tidak ada data anak petani dengan pendidikan tinggi</td></tr>';
    } else {
        foreach ($anak_petani_data as $index => $data) {
            $html .= '<tr>';
            $html .= '<td>' . ($index + 1) . '</td>';
            $html .= '<td>' . htmlspecialchars($data['penduduk']['nama_lengkap']) . '</td>';
            $html .= '<td>' . htmlspecialchars($data['desa']) . '</td>';
            $html .= '<td>' . htmlspecialchars($data['penduduk']['pendidikan_terakhir']) . '</td>';
            $html .= '<td>' . $data['penduduk']['usia'] . ' tahun</td>';
            $html .= '<td>' . htmlspecialchars($data['parent']['nama_lengkap']) . '</td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '</tbody></table></div></div></div></div>';
    $html .= '</div></div>';
    
    return $html;
}

function generateAnalisisSpasial($queries, $filtered_desa) {
    $html = '<div class="card mb-4"><div class="card-header"><h4><i class="fas fa-map"></i> Analisis Spasial & Prediktif</h4></div><div class="card-body">';
    
    // Potensi Pengembangan Desa
    $potensi_data = [];
    foreach ($filtered_desa as $desa) {
        $penduduk = $queries->getPendudukByDesa($desa['id_desa']);
        $fasilitas = $queries->getFasilitasPendidikan($desa['id_desa']);
        $ekonomi = $queries->getDataEkonomi($desa['id_desa']);
        $jalan = $queries->getInfrastrukturJalan($desa['id_desa']);
        
        $luas_km2 = ($desa['luas_wilayah'] ?? 1) * 0.01;
        $kepadatan = $luas_km2 > 0 ? round(count($penduduk) / $luas_km2, 2) : 0;
        
        $jalan_baik = count(array_filter($jalan, fn($j) => $j['kondisi_jalan'] == 'baik'));
        $total_jalan = count($jalan);
        $aksesibilitas = $total_jalan > 0 ? round(($jalan_baik / $total_jalan) * 100, 1) : 0;
        
        $nilai_ekonomi = 0;
        foreach ($ekonomi as $e) {
            $nilai_ekonomi += $e['nilai_ekonomi'] ?? 0;
        }
        
        $potensi_data[] = [
            'nama_desa' => $desa['nama_desa'],
            'kepadatan' => $kepadatan,
            'fasilitas' => count($fasilitas),
            'nilai_ekonomi' => $nilai_ekonomi,
            'aksesibilitas' => $aksesibilitas,
            'total_penduduk' => count($penduduk)
        ];
    }
    
    // Sort by potential score
    usort($potensi_data, function ($a, $b) {
        $score_a = ($a['kepadatan'] * 0.2) + ($a['nilai_ekonomi'] / 1000000 * 0.5) + ($a['aksesibilitas'] / 100 * 0.3);
        $score_b = ($b['kepadatan'] * 0.2) + ($b['nilai_ekonomi'] / 1000000 * 0.5) + ($b['aksesibilitas'] / 100 * 0.3);
        return $score_b <=> $score_a;
    });
    
    $html .= '<h5>Ranking Potensi Pengembangan Desa</h5>';
    $html .= '<div class="table-responsive"><table class="table table-bordered table-striped">';
    $html .= '<thead><tr><th>Ranking</th><th>Nama Desa</th><th>Kepadatan (jiwa/km²)</th><th>Total Fasilitas</th><th>Nilai Ekonomi</th><th>Aksesibilitas (%)</th></tr></thead><tbody>';
    
    foreach ($potensi_data as $index => $data) {
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($data['nama_desa']) . '</td>';
        $html .= '<td>' . number_format($data['kepadatan'], 2) . '</td>';
        $html .= '<td>' . $data['fasilitas'] . ' unit</td>';
        $html .= '<td>Rp ' . number_format($data['nilai_ekonomi']) . '</td>';
        $html .= '<td>' . $data['aksesibilitas'] . '%</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    $html .= '</div></div>';
    
    return $html;
}

// Check if this is AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $page_title = 'Laporan';
    $current_page = 'laporan';
    $page_css = ['laporan.css'];
    $page_js = ['laporan.js'];
    
    ob_start();
}
?>

<div class="laporan-admin-container">
    <div class="laporan-admin-header">
        <div class="laporan-admin-title">
            <h2>Manajemen Laporan</h2>
            <p>Generate dan kelola laporan berdasarkan data analisis</p>
        </div>
    </div>

    <div class="laporan-admin-tabs">
        <div class="laporan-admin-tab-nav">
            <button class="laporan-admin-tab-btn active" onclick="switchLaporanTab('template')">
                Template Laporan
            </button>
            <button class="laporan-admin-tab-btn" onclick="switchLaporanTab('generate')">
                Generate Laporan Custom
            </button>
            <button class="laporan-admin-tab-btn" onclick="switchLaporanTab('riwayat')">
                Riwayat Laporan
            </button>
        </div>
    </div>

    <div class="laporan-admin-content">
        <!-- Template Tab -->
        <div class="laporan-admin-tab-content active" id="template">
            <div class="card">
                <div class="card-header">
                    <h3>Template Laporan Tersimpan</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($templates)): ?>
                        <div class="no-data">
                            <i class="fas fa-file-alt fa-3x mb-3 text-muted"></i>
                            <h5 class="text-muted">Belum Ada Template</h5>
                            <p class="text-muted">Buat template laporan di tab "Generate Laporan Custom"</p>
                        </div>
                    <?php else: ?>
                        <div class="template-grid">
                            <?php foreach ($templates as $template): ?>
                                <div class="template-card">
                                    <div class="template-header">
                                        <h4><?php echo htmlspecialchars($template['nama_template']); ?></h4>
                                        <div class="template-actions">
                                            <button class="btn-use" onclick="useTemplate(<?php echo $template['id_template']; ?>)">
                                                <i class="fas fa-play"></i> Gunakan
                                            </button>
                                            <button class="btn-delete" onclick="deleteTemplate(<?php echo $template['id_template']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="template-info">
                                        <p><strong>Dibuat:</strong> <?php echo date('d/m/Y H:i', strtotime($template['created_at'])); ?></p>
                                        <div class="template-sections">
                                            <?php 
                                            $config = json_decode($template['konfigurasi'], true);
                                            foreach ($config['sections'] ?? [] as $section):
                                            ?>
                                                <span class="section-tag"><?php echo ucwords(str_replace('_', ' ', $section)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Generate Tab -->
        <div class="laporan-admin-tab-content" id="generate">
            <div class="card">
                <div class="card-header">
                    <h3>Generate Laporan Custom</h3>
                </div>
                <div class="card-body">
                    <form id="reportForm">
                        <div class="form-section">
                            <h4>Informasi Laporan</h4>
                            <div class="form-group">
                                <label>Nama Laporan</label>
                                <input type="text" id="reportName" class="form-control" placeholder="Masukkan nama laporan" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Pilih Desa</label>
                                    <select id="reportDesa" class="form-control">
                                        <option value="all">Semua Desa</option>
                                        <?php foreach ($all_desa as $desa): ?>
                                            <option value="<?php echo $desa['id_desa']; ?>"><?php echo $desa['nama_desa']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tahun Data</label>
                                    <select id="reportYear" class="form-control">
                                        <?php for ($year = date('Y'); $year >= 2020; $year--): ?>
                                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h4>Pilih Data yang Akan Disertakan</h4>
                            <div class="section-grid">
                                <div class="section-category">
                                    <h5>Analisis Dasar</h5>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="statistik_kependudukan">
                                        <span>Statistik Kependudukan</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="distribusi_ekonomi">
                                        <span>Distribusi Ekonomi</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="fasilitas_pendidikan">
                                        <span>Fasilitas Pendidikan</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="status_infrastruktur">
                                        <span>Status Infrastruktur</span>
                                    </label>
                                </div>

                                <div class="section-category">
                                    <h5>Analisis Lanjutan</h5>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="analisis_kependudukan">
                                        <span>Analisis Kependudukan Lanjutan</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="analisis_ekonomi">
                                        <span>Analisis Ekonomi Lanjutan</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="integrasi_data">
                                        <span>Integrasi Data Lintas Kategori</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="analisis_spasial">
                                        <span>Analisis Spasial & Prediktif</span>
                                    </label>
                                </div>

                                <div class="section-category">
                                    <h5>Prediksi & Proyeksi</h5>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="prediksi_penduduk">
                                        <span>Prediksi Struktur Penduduk</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="proyeksi_pembangunan">
                                        <span>Proyeksi Kebutuhan Pembangunan</span>
                                    </label>
                                </div>

                                <div class="section-category">
                                    <h5>Perbandingan & Monitoring</h5>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="perbandingan_desa" onchange="toggleComparisonForm(this)">
                                        <span>Perbandingan Antar Desa</span>
                                    </label>
                                    <div id="comparisonForm" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Desa Pertama</label>
                                                <select id="comparisonDesa1" class="form-control">
                                                    <option value="">-- Pilih Desa --</option>
                                                    <?php foreach ($all_desa as $desa): ?>
                                                        <option value="<?php echo $desa['id_desa']; ?>"><?php echo $desa['nama_desa']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Desa Kedua</label>
                                                <select id="comparisonDesa2" class="form-control">
                                                    <option value="">-- Pilih Desa --</option>
                                                    <?php foreach ($all_desa as $desa): ?>
                                                        <option value="<?php echo $desa['id_desa']; ?>"><?php echo $desa['nama_desa']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="sections" value="monitoring_real_time">
                                        <span>Status Data Per Desa</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-info" onclick="showPreview()">
                                <i class="fas fa-eye"></i> Preview Laporan
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="saveAsTemplate()">
                                <i class="fas fa-save"></i> Simpan sebagai Template
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearFormAndState()" title="Bersihkan form dan hapus data tersimpan">
                                <i class="fas fa-eraser"></i> Reset Form
                            </button>
                            <button type="button" class="btn btn-primary" onclick="generateReport()">
                                <i class="fas fa-file-pdf"></i> Generate & Export PDF
                            </button>
                        </div>
                    </form>
                    
                    <!-- Preview Section -->
                    <div id="previewSection" style="display: none;">
                        <div class="preview-header">
                            <h4>Preview Laporan</h4>
                            <button class="btn btn-secondary" onclick="hidePreview()">
                                <i class="fas fa-times"></i> Tutup Preview
                            </button>
                        </div>
                        <div id="previewContent" class="preview-content">
                            <!-- Preview content will be loaded here -->
                        </div>
                    </div>
                    

                </div>
            </div>
        </div>

        <!-- Riwayat Tab -->
        <div class="laporan-admin-tab-content" id="riwayat">
            <div class="card">
                <div class="card-header">
                    <h3>Riwayat Laporan</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($riwayat)): ?>
                        <div class="no-data">
                            <i class="fas fa-history fa-3x mb-3 text-muted"></i>
                            <h5 class="text-muted">Belum Ada Riwayat</h5>
                            <p class="text-muted">Riwayat laporan yang telah dibuat akan muncul di sini</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nama Laporan</th>
                                        <th>Desa</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($riwayat as $item): ?>
                                        <?php $config = json_decode($item['konfigurasi'], true); ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['nama_laporan']); ?></td>
                                            <td>
                                                <?php 
                                                if ($config['desa'] === 'all') {
                                                    echo 'Semua Desa';
                                                } else {
                                                    foreach ($all_desa as $desa) {
                                                        if ($desa['id_desa'] == $config['desa']) {
                                                            echo $desa['nama_desa'];
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></td>
                                            <td><span class="badge badge-success">Selesai</span></td>
                                            <td>
                                                <button class="btn-view" onclick="viewReport(<?php echo $item['id_riwayat']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-download" onclick="downloadReport(<?php echo $item['id_riwayat']; ?>)">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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