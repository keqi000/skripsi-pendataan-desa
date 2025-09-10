<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/PredictionEngine.php';
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$predictionEngine = new PredictionEngine();

try {
    switch ($method) {
        case 'POST':
            if ($action === 'generate') {
                // Update historical data first
                $predictionEngine->updateHistoricalData();
                
                // Generate predictions
                $predictions = $predictionEngine->generatePredictions();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Prediksi berhasil dibuat',
                    'data' => $predictions
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        case 'GET':
            if ($action === 'get') {
                $villageId = $_GET['village_id'] ?? null;
                $predictions = $predictionEngine->getPredictions($villageId);
                
                echo json_encode([
                    'success' => true,
                    'data' => $predictions
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>