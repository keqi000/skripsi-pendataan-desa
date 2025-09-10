-- Drop old tables and create unified boundary table
DROP TABLE IF EXISTS administrative_boundaries;
DROP TABLE IF EXISTS boundary_coordinates;

-- Create unified boundary table matching your GeoJSON format
CREATE TABLE administrative_boundaries (
    id_boundary INT PRIMARY KEY,
    kd_propinsi VARCHAR(10),
    kd_dati2 VARCHAR(10), 
    kd_kecamatan VARCHAR(10),
    kd_desa VARCHAR(10),
    kd_kelurahan VARCHAR(10),
    nm_propinsi VARCHAR(100),
    nm_kabupaten VARCHAR(100),
    nm_kecamatan VARCHAR(100),
    nm_desa VARCHAR(100),
    nm_kelurahan VARCHAR(100),
    boundary_type ENUM('provinsi', 'kabupaten', 'kecamatan', 'desa') NOT NULL,
    geometry_type ENUM('Polygon', 'MultiPolygon') DEFAULT 'MultiPolygon',
    geojson_data LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kecamatan (kd_kecamatan),
    INDEX idx_desa (kd_desa),
    INDEX idx_type (boundary_type)
);