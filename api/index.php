<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/queries.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
$path = parse_url($path, PHP_URL_PATH);
$path = str_replace('/Pendataan-desa/api/', '', $path);
$segments = explode('/', trim($path, '/'));

$queries = new Queries();

try {
    switch ($segments[0]) {
        case 'desa':
            handleDesaAPI($method, $segments, $queries);
            break;
        case 'penduduk':
            handlePendudukAPI($method, $segments, $queries);
            break;
        case 'ekonomi':
            handleEkonomiAPI($method, $segments, $queries);
            break;
        case 'pendidikan':
            handlePendidikanAPI($method, $segments, $queries);
            break;
        case 'infrastruktur':
            handleInfrastrukturAPI($method, $segments, $queries);
            break;
        case 'analisis':
            handleAnalisisAPI($method, $segments, $queries);
            break;
        case 'mata-pencaharian':
            handleMataPencaharianAPI($method, $segments, $queries);
            break;
        case 'peta':
            include __DIR__ . '/peta.php';
            break;
        case 'dashboard_trend.php':
        case 'dashboard_trend':
            include __DIR__ . '/dashboard_trend.php';
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleDesaAPI($method, $segments, $queries) {
    switch ($method) {
        case 'GET':
            if (isset($segments[1])) {
                $desa = $queries->getDesaById($segments[1]);
                echo json_encode($desa);
            } else {
                $desa = $queries->getAllDesa();
                echo json_encode($desa);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handlePendudukAPI($method, $segments, $queries) {
    switch ($method) {
        case 'GET':
            if (isset($segments[1])) {
                $penduduk = $queries->getPendudukByDesa($segments[1]);
                echo json_encode($penduduk);
            }
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $queries->insertPenduduk($data);
            echo json_encode(['success' => $result]);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleEkonomiAPI($method, $segments, $queries) {
    switch ($method) {
        case 'GET':
            if (isset($segments[1])) {
                $ekonomi = $queries->getDataEkonomi($segments[1]);
                echo json_encode($ekonomi);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handlePendidikanAPI($method, $segments, $queries) {
    switch ($method) {
        case 'GET':
            if (isset($segments[1])) {
                $pendidikan = $queries->getFasilitasPendidikan($segments[1]);
                echo json_encode($pendidikan);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleInfrastrukturAPI($method, $segments, $queries) {
    switch ($method) {
        case 'GET':
            if (isset($segments[1])) {
                $jalan = $queries->getInfrastrukturJalan($segments[1]);
                $jembatan = $queries->getInfrastrukturJembatan($segments[1]);
                echo json_encode(['jalan' => $jalan, 'jembatan' => $jembatan]);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleAnalisisAPI($method, $segments, $queries) {
    switch ($method) {
        case 'GET':
            if (isset($segments[1]) && isset($segments[2])) {
                $id_desa = $segments[1];
                $tingkat = $segments[2];
                
                if ($tingkat === 'tingkat1') {
                    $hasil = $queries->analisisTingkat1Kependudukan($id_desa);
                } else if ($tingkat === 'tingkat2') {
                    $hasil = $queries->analisisTingkat2Kependudukan($id_desa);
                }
                
                echo json_encode($hasil);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleMataPencaharianAPI($method, $segments, $queries) {
    switch ($method) {
        case 'GET':
            if (isset($segments[1])) {
                $mataPencaharian = $queries->getMataPencaharianByDesa($segments[1]);
                echo json_encode($mataPencaharian);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

?>