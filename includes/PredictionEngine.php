<?php
require_once __DIR__ . '/../config/database.php';

class PredictionEngine {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Generate predictions for all villages for next 5 years
     */
    public function generatePredictions() {
        $villages = $this->getAllVillages();
        $predictions = [];
        
        foreach ($villages as $village) {
            $villageId = $village['id_desa'];
            $predictions[$villageId] = $this->predictVillagePopulation($villageId);
        }
        
        return $predictions;
    }
    
    /**
     * Predict population for specific village using Linear Regression
     */
    public function predictVillagePopulation($villageId) {
        $historicalData = $this->getHistoricalData($villageId);
        
        if (count($historicalData) < 3) {
            return null; // Need at least 3 data points
        }
        
        $predictions = [];
        $currentYear = date('Y');
        
        // Calculate growth rates for each demographic
        $growthRates = $this->calculateGrowthRates($historicalData);
        
        // Get latest data as baseline
        $latestData = end($historicalData);
        
        // Predict for next 5 years
        for ($year = $currentYear + 1; $year <= $currentYear + 5; $year++) {
            $yearsFromNow = $year - $currentYear;
            
            $prediction = [
                'id_desa' => $villageId,
                'tahun_prediksi' => $year,
                'total_penduduk_prediksi' => $this->applyGrowth($latestData['total_penduduk'], $growthRates['total_penduduk'], $yearsFromNow),
                'total_laki_prediksi' => $this->applyGrowth($latestData['total_laki'], $growthRates['total_laki'], $yearsFromNow),
                'total_perempuan_prediksi' => $this->applyGrowth($latestData['total_perempuan'], $growthRates['total_perempuan'], $yearsFromNow),
                'total_balita_prediksi' => $this->applyGrowth($latestData['total_balita'], $growthRates['total_balita'], $yearsFromNow),
                'total_anak_prediksi' => $this->applyGrowth($latestData['total_anak'], $growthRates['total_anak'], $yearsFromNow),
                'total_remaja_prediksi' => $this->applyGrowth($latestData['total_remaja'], $growthRates['total_remaja'], $yearsFromNow),
                'total_dewasa_prediksi' => $this->applyGrowth($latestData['total_dewasa'], $growthRates['total_dewasa'], $yearsFromNow),
                'total_lansia_prediksi' => $this->applyGrowth($latestData['total_lansia'], $growthRates['total_lansia'], $yearsFromNow),
                'total_kk_prediksi' => $this->applyGrowth($latestData['total_kk'], $growthRates['total_kk'], $yearsFromNow),
                'growth_rate' => round($growthRates['total_penduduk'] * 100, 2),
                'confidence_level' => $this->calculateConfidence($historicalData),
                'metode_prediksi' => 'linear_regression'
            ];
            
            $predictions[] = $prediction;
            
            // Save to database
            $this->savePrediction($prediction);
        }
        
        return $predictions;
    }
    
    /**
     * Calculate growth rates using linear regression
     */
    private function calculateGrowthRates($data) {
        $fields = ['total_penduduk', 'total_laki', 'total_perempuan', 'total_balita', 
                  'total_anak', 'total_remaja', 'total_dewasa', 'total_lansia', 'total_kk'];
        
        $growthRates = [];
        
        foreach ($fields as $field) {
            $growthRates[$field] = $this->linearRegression($data, $field);
        }
        
        return $growthRates;
    }
    
    /**
     * Linear regression to calculate growth rate
     */
    private function linearRegression($data, $field) {
        $n = count($data);
        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
        
        foreach ($data as $i => $row) {
            $x = $i + 1; // Year index
            $y = $row[$field];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        // Calculate slope (growth rate)
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        // Convert to annual growth rate
        $avgValue = $sumY / $n;
        $growthRate = $avgValue > 0 ? $slope / $avgValue : 0;
        
        return max(-0.1, min(0.1, $growthRate)); // Cap between -10% and +10%
    }
    
    /**
     * Apply growth rate for prediction
     */
    private function applyGrowth($baseValue, $growthRate, $years) {
        return max(0, round($baseValue * pow(1 + $growthRate, $years)));
    }
    
    /**
     * Calculate confidence level based on data consistency
     */
    private function calculateConfidence($data) {
        if (count($data) < 3) return 50;
        
        $variations = [];
        for ($i = 1; $i < count($data); $i++) {
            $prev = $data[$i-1]['total_penduduk'];
            $curr = $data[$i]['total_penduduk'];
            if ($prev > 0) {
                $variations[] = abs(($curr - $prev) / $prev);
            }
        }
        
        $avgVariation = array_sum($variations) / count($variations);
        $confidence = max(60, min(95, 95 - ($avgVariation * 100)));
        
        return round($confidence, 1);
    }
    
    /**
     * Get historical data for village
     */
    private function getHistoricalData($villageId) {
        $query = "SELECT * FROM data_historis WHERE id_desa = :id_desa ORDER BY tahun ASC, bulan ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $villageId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all villages
     */
    private function getAllVillages() {
        $query = "SELECT id_desa, nama_desa FROM desa ORDER BY nama_desa";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Save prediction to database
     */
    private function savePrediction($prediction) {
        $query = "INSERT INTO prediksi_penduduk 
                  (id_desa, tahun_prediksi, total_penduduk_prediksi, total_laki_prediksi, 
                   total_perempuan_prediksi, total_balita_prediksi, total_anak_prediksi, 
                   total_remaja_prediksi, total_dewasa_prediksi, total_lansia_prediksi, 
                   total_kk_prediksi, growth_rate, confidence_level, metode_prediksi)
                  VALUES 
                  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  total_penduduk_prediksi = VALUES(total_penduduk_prediksi),
                  total_laki_prediksi = VALUES(total_laki_prediksi),
                  total_perempuan_prediksi = VALUES(total_perempuan_prediksi),
                  total_balita_prediksi = VALUES(total_balita_prediksi),
                  total_anak_prediksi = VALUES(total_anak_prediksi),
                  total_remaja_prediksi = VALUES(total_remaja_prediksi),
                  total_dewasa_prediksi = VALUES(total_dewasa_prediksi),
                  total_lansia_prediksi = VALUES(total_lansia_prediksi),
                  total_kk_prediksi = VALUES(total_kk_prediksi),
                  growth_rate = VALUES(growth_rate),
                  confidence_level = VALUES(confidence_level),
                  updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->db->prepare($query);
        $params = [
            $prediction['id_desa'],
            $prediction['tahun_prediksi'],
            $prediction['total_penduduk_prediksi'],
            $prediction['total_laki_prediksi'],
            $prediction['total_perempuan_prediksi'],
            $prediction['total_balita_prediksi'],
            $prediction['total_anak_prediksi'],
            $prediction['total_remaja_prediksi'],
            $prediction['total_dewasa_prediksi'],
            $prediction['total_lansia_prediksi'],
            $prediction['total_kk_prediksi'],
            $prediction['growth_rate'],
            $prediction['confidence_level'],
            $prediction['metode_prediksi']
        ];
        return $stmt->execute($params);
    }
    
    /**
     * Get predictions for village
     */
    public function getPredictions($villageId = null) {
        if ($villageId) {
            $query = "SELECT p.*, d.nama_desa 
                      FROM prediksi_penduduk p 
                      JOIN desa d ON p.id_desa = d.id_desa 
                      WHERE p.id_desa = :id_desa 
                      ORDER BY p.tahun_prediksi ASC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_desa', $villageId);
        } else {
            $query = "SELECT p.*, d.nama_desa 
                      FROM prediksi_penduduk p 
                      JOIN desa d ON p.id_desa = d.id_desa 
                      ORDER BY d.nama_desa, p.tahun_prediksi ASC";
            $stmt = $this->db->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update historical data from current data
     */
    public function updateHistoricalData() {
        $villages = $this->getAllVillages();
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        foreach ($villages as $village) {
            $villageId = $village['id_desa'];
            
            // Get current statistics
            $stats = $this->getCurrentVillageStats($villageId);
            
            if ($stats) {
                $this->saveHistoricalData($villageId, $currentYear, $currentMonth, $stats);
            }
        }
    }
    
    /**
     * Get current village statistics - Only demographic data
     */
    private function getCurrentVillageStats($villageId) {
        // Get population data only
        $query = "SELECT 
                    COUNT(*) as total_penduduk,
                    SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as total_laki,
                    SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as total_perempuan,
                    SUM(CASE WHEN usia BETWEEN 0 AND 5 THEN 1 ELSE 0 END) as total_balita,
                    SUM(CASE WHEN usia BETWEEN 6 AND 12 THEN 1 ELSE 0 END) as total_anak,
                    SUM(CASE WHEN usia BETWEEN 13 AND 17 THEN 1 ELSE 0 END) as total_remaja,
                    SUM(CASE WHEN usia BETWEEN 18 AND 64 THEN 1 ELSE 0 END) as total_dewasa,
                    SUM(CASE WHEN usia >= 65 THEN 1 ELSE 0 END) as total_lansia
                  FROM penduduk WHERE id_desa = :id_desa";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $villageId);
        $stmt->execute();
        $popData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get KK count
        $query = "SELECT COUNT(*) as total_kk FROM keluarga WHERE id_desa = :id_desa";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $villageId);
        $stmt->execute();
        $kkData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return array_merge($popData, $kkData);
    }
    
    /**
     * Save historical data - Only demographic data
     */
    private function saveHistoricalData($villageId, $year, $month, $stats) {
        $query = "INSERT INTO data_historis 
                  (id_desa, tahun, bulan, total_penduduk, total_laki, total_perempuan, 
                   total_balita, total_anak, total_remaja, total_dewasa, total_lansia, total_kk)
                  VALUES 
                  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  total_penduduk = VALUES(total_penduduk),
                  total_laki = VALUES(total_laki),
                  total_perempuan = VALUES(total_perempuan),
                  total_balita = VALUES(total_balita),
                  total_anak = VALUES(total_anak),
                  total_remaja = VALUES(total_remaja),
                  total_dewasa = VALUES(total_dewasa),
                  total_lansia = VALUES(total_lansia),
                  total_kk = VALUES(total_kk)";
        
        $params = [
            $villageId, $year, $month,
            $stats['total_penduduk'] ?? 0,
            $stats['total_laki'] ?? 0,
            $stats['total_perempuan'] ?? 0,
            $stats['total_balita'] ?? 0,
            $stats['total_anak'] ?? 0,
            $stats['total_remaja'] ?? 0,
            $stats['total_dewasa'] ?? 0,
            $stats['total_lansia'] ?? 0,
            $stats['total_kk'] ?? 0
        ];
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }
}
?>