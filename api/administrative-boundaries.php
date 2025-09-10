<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/queries.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $queries = new Queries();
    
    // Get all administrative boundaries
    $stmt = $queries->db->prepare("
        SELECT id_boundary, boundary_type, kd_kecamatan, nm_kabupaten, nm_kecamatan, nm_desa, nm_kelurahan, geojson_data, created_at
        FROM administrative_boundaries 
        ORDER BY 
            CASE boundary_type 
                WHEN 'provinsi' THEN 1 
                WHEN 'kabupaten' THEN 2 
                WHEN 'kecamatan' THEN 3 
                WHEN 'desa' THEN 4 
            END,
            nm_kabupaten, nm_kecamatan, nm_desa, nm_kelurahan
    ");
    
    $stmt->execute();
    $boundaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($boundaries);
    
} catch (Exception $e) {
    error_log("Administrative boundaries API error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Failed to fetch administrative boundaries',
        'message' => $e->getMessage()
    ]);
}
?>