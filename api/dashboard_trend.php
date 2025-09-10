<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/queries.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$year = $_GET['year'] ?? date('Y');
$period = $_GET['period'] ?? 'jan-jun';

// Set month range
if ($period == 'jan-jun') {
    $start_month = 1;
    $end_month = 6;
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'];
} else {
    $start_month = 7;
    $end_month = 12;
    $months = ['Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
}

$queries = new Queries();

// Function to calculate trend
function calculateTrend($table, $date_field, $year, $start_month, $end_month, $queries, $condition = '') {
    $query_total = "SELECT COUNT(*) as total FROM $table WHERE 1=1 $condition";
    $stmt = $queries->db->prepare($query_total);
    $stmt->execute();
    $total_existing = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
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
    
    $percentage = 0;
    if ($total_existing == 0) {
        $percentage = $period_count > 0 ? 100 : 0;
    } else {
        $percentage = round(($period_count / $total_existing) * 100, 1);
    }
    
    error_log("TREND [$table]: Total=$total_existing, Period=$period_count, Result={$percentage}%");
    return $percentage;
}

// Calculate trends
error_log("=== DASHBOARD TREND CALC START ===");
error_log("Period: $year, Months: $start_month-$end_month");

$trend_desa = calculateTrend('desa', 'created_at', $year, $start_month, $end_month, $queries);
$trend_penduduk = calculateTrend('penduduk', 'created_at', $year, $start_month, $end_month, $queries);
$trend_pendidikan = calculateTrend('fasilitas_pendidikan', 'created_at', $year, $start_month, $end_month, $queries);
$trend_umkm = calculateTrend('umkm', 'created_at', $year, $start_month, $end_month, $queries);

// Road infrastructure trend
$trend_jalan = calculateTrend('infrastruktur_jalan', 'created_at', $year, $start_month, $end_month, $queries);

error_log("FINAL: Desa={$trend_desa}%, Penduduk={$trend_penduduk}%, Pendidikan={$trend_pendidikan}%, UMKM={$trend_umkm}%, Jalan={$trend_jalan}%");

// Get monthly data
$monthly_data = [];
for ($month = $start_month; $month <= $end_month; $month++) {
    $monthly_data[$month] = [
        'desa' => 0,
        'penduduk' => 0,
        'pendidikan' => 0,
        'umkm' => 0,
        'jalan' => 0
    ];
    
    $tables = ['desa', 'penduduk', 'fasilitas_pendidikan', 'umkm'];
    $keys = ['desa', 'penduduk', 'pendidikan', 'umkm'];
    
    error_log("Month $month data:");
    for ($i = 0; $i < count($tables); $i++) {
        $query = "SELECT COUNT(*) as count FROM {$tables[$i]} 
                 WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
        $stmt = $queries->db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $monthly_data[$month][$keys[$i]] = $count;
        error_log("  {$keys[$i]}: $count");
    }
    
    $query = "SELECT COUNT(*) as count FROM infrastruktur_jalan 
             WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
    $stmt = $queries->db->prepare($query);
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':month', $month);
    $stmt->execute();
    $jalan_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    $monthly_data[$month]['jalan'] = $jalan_count;
    error_log("  jalan: $jalan_count");
}

echo json_encode([
    'success' => true,
    'trendData' => [
        'desa' => $trend_desa,
        'penduduk' => $trend_penduduk,
        'pendidikan' => $trend_pendidikan,
        'umkm' => $trend_umkm,
        'jalan' => $trend_jalan
    ],
    'monthlyData' => $monthly_data,
    'months' => $months,
    'year' => $year,
    'period' => $period
]);
?>