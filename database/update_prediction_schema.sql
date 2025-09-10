-- Update database schema for demographic-only predictions
-- Run this script to update existing database

-- Remove non-demographic columns from data_historis table
ALTER TABLE data_historis 
DROP COLUMN IF EXISTS total_fasilitas_pendidikan,
DROP COLUMN IF EXISTS total_umkm,
DROP COLUMN IF EXISTS total_infrastruktur;

-- Add comment to clarify table purpose
ALTER TABLE data_historis COMMENT = 'Historical demographic data for population predictions';

-- Add comment to prediksi_penduduk table
ALTER TABLE prediksi_penduduk COMMENT = 'Population predictions based on demographic trends';