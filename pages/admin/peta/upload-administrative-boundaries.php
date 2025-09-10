<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_FILES['administrative_boundaries_file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$file = $_FILES['administrative_boundaries_file'];

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error']);
    exit();
}

$allowedTypes = ['application/json', 'text/plain'];
if (!in_array($file['type'], $allowedTypes) && !str_ends_with($file['name'], '.geojson') && !str_ends_with($file['name'], '.json')) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only GeoJSON files allowed']);
    exit();
}

try {
    $queries = new Queries();
    
    // Read and parse GeoJSON
    $geojsonContent = file_get_contents($file['tmp_name']);
    $geojson = json_decode($geojsonContent, true);
    
    if (!$geojson || $geojson['type'] !== 'FeatureCollection') {
        throw new Exception('Invalid GeoJSON format. Must be a FeatureCollection');
    }
    
    $inserted = 0;
    $errors = 0;
    
    // Don't clear existing boundaries - just update/insert
    
    foreach ($geojson['features'] as $feature) {
        try {
            $properties = $feature['properties'] ?? [];
            
            // Extract data from your GeoJSON format
            $kdPropinsi = $properties['kd_propinsi'] ?? null;
            $kdDati2 = $properties['kd_dati2'] ?? null;
            $kdKecamatan = $properties['kd_kecamatan'] ?? null;
            $kdDesa = $properties['kd_desa'] ?? null;
            $kdKelurahan = $properties['kd_kelurahan'] ?? null;
            $nmKabupaten = $properties['nm_kabupaten'] ?? null;
            $nmKecamatan = $properties['nm_kecamatan'] ?? null;
            $nmDesa = $properties['nm_desa'] ?? null;
            $nmKelurahan = $properties['nm_kelurahan'] ?? null;
            
            // Determine boundary type
            $boundaryType = 'kecamatan'; // Default since your sample is kecamatan
            if ($kdDesa || $kdKelurahan) $boundaryType = 'desa';
            elseif ($kdKecamatan && !$kdDesa && !$kdKelurahan) $boundaryType = 'kecamatan';
            elseif ($kdDati2 && !$kdKecamatan) $boundaryType = 'kabupaten';
            elseif ($kdPropinsi && !$kdDati2) $boundaryType = 'provinsi';
            
            $geometryType = $feature['geometry']['type'] ?? 'MultiPolygon';
            
            // Create individual feature GeoJSON
            $featureGeoJSON = json_encode([
                'type' => 'Feature',
                'geometry' => $feature['geometry'],
                'properties' => $properties
            ]);
            
            // Get next available ID (fill gaps starting from 1)
            $stmtId = $queries->db->prepare("
                SELECT COALESCE(
                    (SELECT MIN(id + 1) 
                     FROM (SELECT 0 as id UNION SELECT id_boundary FROM administrative_boundaries) t 
                     WHERE (id + 1) NOT IN (SELECT id_boundary FROM administrative_boundaries)), 
                    1
                ) as next_id
            ");
            $stmtId->execute();
            $nextId = $stmtId->fetchColumn() ?: 1;
            
            // Insert into database with gap-filling ID
            $stmt = $queries->db->prepare("
                INSERT INTO administrative_boundaries 
                (id_boundary, kd_propinsi, kd_dati2, kd_kecamatan, kd_desa, kd_kelurahan, nm_kabupaten, nm_kecamatan, nm_desa, nm_kelurahan, boundary_type, geometry_type, geojson_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                geojson_data = VALUES(geojson_data),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([$nextId, $kdPropinsi, $kdDati2, $kdKecamatan, $kdDesa, $kdKelurahan, $nmKabupaten, $nmKecamatan, $nmDesa, $nmKelurahan, $boundaryType, $geometryType, $featureGeoJSON]);
            $inserted++;
            
        } catch (Exception $e) {
            $errors++;
            error_log("Error processing feature: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Administrative boundaries uploaded successfully',
        'inserted' => $inserted,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    error_log("Administrative boundaries upload error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error processing file: ' . $e->getMessage()
    ]);
}
?>