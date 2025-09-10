-- Add coordinates to fasilitas_pendidikan
ALTER TABLE fasilitas_pendidikan 
ADD COLUMN koordinat_latitude DECIMAL(10,8) NULL,
ADD COLUMN koordinat_longitude DECIMAL(11,8) NULL;

-- Add coordinates to infrastruktur_jalan
ALTER TABLE infrastruktur_jalan 
ADD COLUMN koordinat_start_lat DECIMAL(10,8) NULL,
ADD COLUMN koordinat_start_lng DECIMAL(11,8) NULL,
ADD COLUMN koordinat_end_lat DECIMAL(10,8) NULL,
ADD COLUMN koordinat_end_lng DECIMAL(11,8) NULL;

-- Add coordinates to infrastruktur_jembatan
ALTER TABLE infrastruktur_jembatan 
ADD COLUMN koordinat_latitude DECIMAL(10,8) NULL,
ADD COLUMN koordinat_longitude DECIMAL(11,8) NULL;

-- Update fasilitas with sample coordinates near each village
UPDATE fasilitas_pendidikan f
JOIN desa d ON f.id_desa = d.id_desa
SET f.koordinat_latitude = d.koordinat_latitude + (RAND() - 0.5) * 0.01,
    f.koordinat_longitude = d.koordinat_longitude + (RAND() - 0.5) * 0.01
WHERE d.koordinat_latitude IS NOT NULL;

-- Update jalan with sample coordinates
UPDATE infrastruktur_jalan j
JOIN desa d ON j.id_desa = d.id_desa
SET j.koordinat_start_lat = d.koordinat_latitude + (RAND() - 0.5) * 0.008,
    j.koordinat_start_lng = d.koordinat_longitude + (RAND() - 0.5) * 0.008,
    j.koordinat_end_lat = d.koordinat_latitude + (RAND() - 0.5) * 0.008,
    j.koordinat_end_lng = d.koordinat_longitude + (RAND() - 0.5) * 0.008
WHERE d.koordinat_latitude IS NOT NULL;

-- Update jembatan with sample coordinates
UPDATE infrastruktur_jembatan j
JOIN desa d ON j.id_desa = d.id_desa
SET j.koordinat_latitude = d.koordinat_latitude + (RAND() - 0.5) * 0.01,
    j.koordinat_longitude = d.koordinat_longitude + (RAND() - 0.5) * 0.01
WHERE d.koordinat_latitude IS NOT NULL;