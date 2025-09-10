<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/queries.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Start session for authentication
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$queries = new Queries();
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($queries, $path_parts);
            break;
            
        case 'POST':
            handlePostRequest($queries);
            break;
            
        case 'PUT':
            handlePutRequest($queries, $path_parts);
            break;
            
        case 'DELETE':
            handleDeleteRequest($queries, $path_parts);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($queries, $path_parts) {
    // Get all map data
    if (end($path_parts) === 'all') {
        $all_desa = $queries->getAllDesa();
        $all_fasilitas = [];
        $all_jalan = [];
        $all_jembatan = [];

        foreach ($all_desa as $desa) {
            $id_desa = $desa['id_desa'];
            
            // Get facilities with coordinates
            $fasilitas = $queries->getFasilitasPendidikan($id_desa);
            foreach ($fasilitas as $f) {
                $f['nama_desa'] = $desa['nama_desa'];
                $f['coordinates'] = generateCoordinates($desa);
                $all_fasilitas[] = $f;
            }
            
            // Get roads with coordinates
            $jalan = $queries->getInfrastrukturJalan($id_desa);
            foreach ($jalan as $j) {
                $j['nama_desa'] = $desa['nama_desa'];
                $j['coordinates'] = generateRoadCoordinates($desa);
                $all_jalan[] = $j;
            }
            
            // Get bridges with coordinates
            $jembatan = $queries->getInfrastrukturJembatan($id_desa);
            foreach ($jembatan as $jmb) {
                $jmb['nama_desa'] = $desa['nama_desa'];
                $jmb['coordinates'] = generateCoordinates($desa);
                $all_jembatan[] = $jmb;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'desa' => $all_desa,
                'fasilitas' => $all_fasilitas,
                'jalan' => $all_jalan,
                'jembatan' => $all_jembatan
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handlePostRequest($queries) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input data']);
        return;
    }
    
    $type = $input['type'];
    $id_desa = $input['id_desa'];
    
    try {
        switch ($type) {
            case 'fasilitas':
                $result = saveFasilitas($queries, $input);
                break;
                
            case 'jalan':
                $result = saveJalan($queries, $input);
                break;
                
            case 'jembatan':
                $result = saveJembatan($queries, $input);
                break;
                
            default:
                throw new Exception('Invalid location type');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Location saved successfully',
            'data' => $result
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handlePutRequest($queries, $path_parts) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['type']) || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input data']);
        return;
    }
    
    // TODO: Implement update functionality
    echo json_encode([
        'success' => true,
        'message' => 'Update functionality will be implemented'
    ]);
}

function handleDeleteRequest($queries, $path_parts) {
    // TODO: Implement delete functionality
    echo json_encode([
        'success' => true,
        'message' => 'Delete functionality will be implemented'
    ]);
}

function saveFasilitas($queries, $data) {
    $query = "INSERT INTO fasilitas_pendidikan (
        id_desa, nama_fasilitas, jenis_pendidikan, alamat_fasilitas, 
        kapasitas_siswa, kondisi_bangunan, jumlah_guru
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $queries->db->prepare($query);
    $result = $stmt->execute([
        $data['id_desa'],
        $data['nama_fasilitas'],
        $data['jenis_pendidikan'],
        $data['alamat_fasilitas'] ?? '',
        $data['kapasitas_siswa'] ?? 0,
        $data['kondisi_bangunan'] ?? 'Baik',
        $data['jumlah_guru'] ?? 0
    ]);
    
    if (!$result) {
        throw new Exception('Failed to save facility');
    }
    
    return [
        'id' => $queries->db->lastInsertId(),
        'type' => 'fasilitas'
    ];
}

function saveJalan($queries, $data) {
    $query = "INSERT INTO infrastruktur_jalan (
        id_desa, nama_jalan, panjang_jalan, lebar_jalan, 
        kondisi_jalan, jenis_permukaan
    ) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $queries->db->prepare($query);
    $result = $stmt->execute([
        $data['id_desa'],
        $data['nama_jalan'],
        $data['panjang_jalan'],
        $data['lebar_jalan'] ?? null,
        $data['kondisi_jalan'] ?? 'baik',
        $data['jenis_permukaan'] ?? 'tanah'
    ]);
    
    if (!$result) {
        throw new Exception('Failed to save road');
    }
    
    return [
        'id' => $queries->db->lastInsertId(),
        'type' => 'jalan'
    ];
}

function saveJembatan($queries, $data) {
    $query = "INSERT INTO infrastruktur_jembatan (
        id_desa, nama_jembatan, panjang_jembatan, lebar_jembatan, 
        kondisi_jembatan, material_jembatan, kapasitas_beban
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $queries->db->prepare($query);
    $result = $stmt->execute([
        $data['id_desa'],
        $data['nama_jembatan'],
        $data['panjang_jembatan'],
        $data['lebar_jembatan'] ?? null,
        $data['kondisi_jembatan'] ?? 'baik',
        $data['material_jembatan'] ?? '',
        $data['kapasitas_beban'] ?? null
    ]);
    
    if (!$result) {
        throw new Exception('Failed to save bridge');
    }
    
    return [
        'id' => $queries->db->lastInsertId(),
        'type' => 'jembatan'
    ];
}

function generateCoordinates($desa) {
    // Generate random coordinates near village center
    $baseLat = $desa['koordinat_latitude'] ?? 0.5547; // Tibawa approximate
    $baseLng = $desa['koordinat_longitude'] ?? 123.0581;
    
    return [
        'lat' => $baseLat + (mt_rand(-500, 500) / 10000), // ±0.05 degrees
        'lng' => $baseLng + (mt_rand(-500, 500) / 10000)
    ];
}

function generateRoadCoordinates($desa) {
    // Generate road line coordinates
    $baseLat = $desa['koordinat_latitude'] ?? 0.5547;
    $baseLng = $desa['koordinat_longitude'] ?? 123.0581;
    
    $startLat = $baseLat + (mt_rand(-200, 200) / 10000);
    $startLng = $baseLng + (mt_rand(-200, 200) / 10000);
    $endLat = $startLat + (mt_rand(-100, 100) / 10000);
    $endLng = $startLng + (mt_rand(-100, 100) / 10000);
    
    return [
        'start' => ['lat' => $startLat, 'lng' => $startLng],
        'end' => ['lat' => $endLat, 'lng' => $endLng]
    ];
}
?>