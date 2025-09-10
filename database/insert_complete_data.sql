-- Insert Complete Data untuk Sistem Pendataan Desa
-- Data yang saling berkaitan dan konsisten

-- 1. Insert Desa
INSERT INTO desa (id_desa, nama_desa, nama_kecamatan, nama_kabupaten, nama_provinsi, luas_wilayah, jumlah_dusun, jumlah_rt, jumlah_rw) VALUES
(1, 'Tibawa Ilir', 'Tibawa', 'Gorontalo', 'Gorontalo', 450.50, 4, 12, 3),
(2, 'Tibawa Tengah', 'Tibawa', 'Gorontalo', 'Gorontalo', 380.25, 3, 10, 2),
(3, 'Tibawa Hulu', 'Tibawa', 'Gorontalo', 'Gorontalo', 520.75, 5, 15, 4),
(4, 'Molosipat', 'Tibawa', 'Gorontalo', 'Gorontalo', 320.00, 3, 8, 2),
(5, 'Bongo', 'Tibawa', 'Gorontalo', 'Gorontalo', 410.30, 4, 11, 3);

-- 2. Insert Keluarga (hanya kepala keluarga)
INSERT INTO keluarga (id_keluarga, id_desa, nomor_kk, nama_kepala_keluarga, alamat_keluarga, jumlah_anggota) VALUES
-- Tibawa Ilir
(1, 1, '7501010001', 'Ahmad Suharto', 'Dusun Mawar RT 01', 4),
(2, 1, '7501010002', 'Hasan Basri', 'Dusun Melati RT 02', 3),
(3, 1, '7501010003', 'Budi Santoso', 'Dusun Kenanga RT 03', 5),
-- Tibawa Tengah
(4, 2, '7501020001', 'Yusuf Ibrahim', 'Dusun Cempaka RT 01', 4),
(5, 2, '7501020002', 'Abdul Rahman', 'Dusun Anggrek RT 02', 3),
-- Tibawa Hulu
(6, 3, '7501030001', 'Sulaiman Hadi', 'Dusun Flamboyan RT 01', 4),
(7, 3, '7501030002', 'Ibrahim Yusuf', 'Dusun Sakura RT 02', 5),
-- Molosipat
(8, 4, '7501040001', 'Hamzah Ali', 'Dusun Seroja RT 01', 4),
(9, 4, '7501040002', 'Usman Hakim', 'Dusun Tulip RT 02', 3),
-- Bongo
(10, 5, '7501050001', 'Salman Farisi', 'Dusun Bougenville RT 01', 4),
(11, 5, '7501050002', 'Bilal Rabbah', 'Dusun Kamboja RT 02', 5);

-- 3. Insert Penduduk (kepala keluarga + anggota keluarga)
INSERT INTO penduduk (nik, id_desa, id_keluarga, nama_lengkap, jenis_kelamin, tanggal_lahir, usia, agama, pendidikan_terakhir, pekerjaan, status_pernikahan, alamat) VALUES
-- Tibawa Ilir - KK 1
('7501010001000001', 1, 1, 'Ahmad Suharto', 'L', '1975-05-15', 49, 'Islam', 'SMA', 'Petani', 'Menikah', 'Dusun Mawar RT 01'),
('7501010001000002', 1, 1, 'Siti Aminah', 'P', '1980-08-20', 44, 'Islam', 'SMP', 'Ibu Rumah Tangga', 'Menikah', 'Dusun Mawar RT 01'),
('7501010001000003', 1, 1, 'Budi Santoso', 'L', '2010-03-10', 14, 'Islam', 'SMP', 'Pelajar', 'Belum Menikah', 'Dusun Mawar RT 01'),
('7501010001000004', 1, 1, 'Ratna Dewi', 'P', '2018-12-05', 6, 'Islam', 'Tidak Sekolah', 'Belum Bekerja', 'Belum Menikah', 'Dusun Mawar RT 01'),
-- Tibawa Ilir - KK 2
('7501010002000001', 1, 2, 'Hasan Basri', 'L', '1970-02-28', 54, 'Islam', 'SD', 'Petani', 'Menikah', 'Dusun Melati RT 02'),
('7501010002000002', 1, 2, 'Fatimah Zahra', 'P', '1978-11-12', 46, 'Islam', 'SMA', 'Pedagang', 'Menikah', 'Dusun Melati RT 02'),
('7501010002000003', 1, 2, 'Muhammad Ali', 'L', '2008-07-18', 16, 'Islam', 'SMA', 'Pelajar', 'Belum Menikah', 'Dusun Melati RT 02'),
-- Tibawa Ilir - KK 3
('7501010003000001', 1, 3, 'Budi Santoso', 'L', '1978-03-12', 46, 'Islam', 'SMA', 'Wiraswasta', 'Menikah', 'Dusun Kenanga RT 03'),
('7501010003000002', 1, 3, 'Dewi Sari', 'P', '1982-07-25', 42, 'Islam', 'SMP', 'Ibu Rumah Tangga', 'Menikah', 'Dusun Kenanga RT 03'),
('7501010003000003', 1, 3, 'Andi Pratama', 'L', '2009-11-15', 15, 'Islam', 'SMP', 'Pelajar', 'Belum Menikah', 'Dusun Kenanga RT 03'),
('7501010003000004', 1, 3, 'Sari Indah', 'P', '2015-04-08', 9, 'Islam', 'SD', 'Pelajar', 'Belum Menikah', 'Dusun Kenanga RT 03'),
('7501010003000005', 1, 3, 'Bayu Kecil', 'L', '2020-01-20', 4, 'Islam', 'Tidak Sekolah', 'Belum Bekerja', 'Belum Menikah', 'Dusun Kenanga RT 03'),

-- Tibawa Tengah - KK 4
('7501020001000001', 2, 4, 'Yusuf Ibrahim', 'L', '1982-04-22', 42, 'Islam', 'S1', 'Guru', 'Menikah', 'Dusun Cempaka RT 01'),
('7501020001000002', 2, 4, 'Khadijah Sari', 'P', '1985-09-15', 39, 'Islam', 'D3', 'Perawat', 'Menikah', 'Dusun Cempaka RT 01'),
('7501020001000003', 2, 4, 'Farid Yusuf', 'L', '2012-01-30', 12, 'Islam', 'SD', 'Pelajar', 'Belum Menikah', 'Dusun Cempaka RT 01'),
('7501020001000004', 2, 4, 'Aisyah Nur', 'P', '2020-06-08', 4, 'Islam', 'Tidak Sekolah', 'Belum Bekerja', 'Belum Menikah', 'Dusun Cempaka RT 01'),
-- Tibawa Tengah - KK 5
('7501020002000001', 2, 5, 'Abdul Rahman', 'L', '1975-08-10', 49, 'Islam', 'SMP', 'Pedagang', 'Menikah', 'Dusun Anggrek RT 02'),
('7501020002000002', 2, 5, 'Aminah Siti', 'P', '1980-12-05', 44, 'Islam', 'SD', 'Ibu Rumah Tangga', 'Menikah', 'Dusun Anggrek RT 02'),
('7501020002000003', 2, 5, 'Rahman Jr', 'L', '2010-05-18', 14, 'Islam', 'SMP', 'Pelajar', 'Belum Menikah', 'Dusun Anggrek RT 02'),

-- Tibawa Hulu - KK 6
('7501030001000001', 3, 6, 'Sulaiman Hadi', 'L', '1968-12-10', 56, 'Islam', 'SMA', 'Wiraswasta', 'Menikah', 'Dusun Flamboyan RT 01'),
('7501030001000002', 3, 6, 'Maryam Siti', 'P', '1972-03-25', 52, 'Islam', 'SMP', 'Ibu Rumah Tangga', 'Menikah', 'Dusun Flamboyan RT 01'),
('7501030001000003', 3, 6, 'Hadi Putra', 'L', '2005-08-14', 19, 'Islam', 'SMA', 'Mahasiswa', 'Belum Menikah', 'Dusun Flamboyan RT 01'),
('7501030001000004', 3, 6, 'Siti Putri', 'P', '2015-11-20', 9, 'Islam', 'SD', 'Pelajar', 'Belum Menikah', 'Dusun Flamboyan RT 01'),
-- Tibawa Hulu - KK 7
('7501030002000001', 3, 7, 'Ibrahim Yusuf', 'L', '1973-06-18', 51, 'Islam', 'SMA', 'Petani', 'Menikah', 'Dusun Sakura RT 02'),
('7501030002000002', 3, 7, 'Zahra Fatma', 'P', '1978-09-22', 46, 'Islam', 'SMP', 'Ibu Rumah Tangga', 'Menikah', 'Dusun Sakura RT 02'),
('7501030002000003', 3, 7, 'Yusuf Anak', 'L', '2007-03-15', 17, 'Islam', 'SMA', 'Pelajar', 'Belum Menikah', 'Dusun Sakura RT 02'),
('7501030002000004', 3, 7, 'Fatma Anak', 'P', '2012-12-08', 12, 'Islam', 'SD', 'Pelajar', 'Belum Menikah', 'Dusun Sakura RT 02'),
('7501030002000005', 3, 7, 'Ibrahim Kecil', 'L', '2018-07-30', 6, 'Islam', 'Tidak Sekolah', 'Belum Bekerja', 'Belum Menikah', 'Dusun Sakura RT 02'),

-- Molosipat - KK 8
('7501040001000001', 4, 8, 'Hamzah Ali', 'L', '1976-07-08', 48, 'Islam', 'SMP', 'Nelayan', 'Menikah', 'Dusun Seroja RT 01'),
('7501040001000002', 4, 8, 'Zainab Fatma', 'P', '1981-10-18', 43, 'Islam', 'SD', 'Ibu Rumah Tangga', 'Menikah', 'Dusun Seroja RT 01'),
('7501040001000003', 4, 8, 'Ali Hamzah', 'L', '2009-04-12', 15, 'Islam', 'SMP', 'Pelajar', 'Belum Menikah', 'Dusun Seroja RT 01'),
('7501040001000004', 4, 8, 'Fatma Zain', 'P', '2017-09-03', 7, 'Islam', 'SD', 'Pelajar', 'Belum Menikah', 'Dusun Seroja RT 01'),
-- Molosipat - KK 9
('7501040002000001', 4, 9, 'Usman Hakim', 'L', '1974-11-25', 50, 'Islam', 'SD', 'Nelayan', 'Menikah', 'Dusun Tulip RT 02'),
('7501040002000002', 4, 9, 'Halimah Tus', 'P', '1979-02-14', 45, 'Islam', 'SD', 'Ibu Rumah Tangga', 'Menikah', 'Dusun Tulip RT 02'),
('7501040002000003', 4, 9, 'Hakim Usman', 'L', '2011-08-07', 13, 'Islam', 'SMP', 'Pelajar', 'Belum Menikah', 'Dusun Tulip RT 02'),

-- Bongo - KK 10
('7501050001000001', 5, 10, 'Salman Farisi', 'L', '1973-01-15', 51, 'Islam', 'SMA', 'Petani', 'Menikah', 'Dusun Bougenville RT 01'),
('7501050001000002', 5, 10, 'Ruqayyah Binti', 'P', '1977-06-28', 47, 'Islam', 'SMP', 'Pedagang', 'Menikah', 'Dusun Bougenville RT 01'),
('7501050001000003', 5, 10, 'Farisi Salman', 'L', '2008-11-12', 16, 'Islam', 'SMA', 'Pelajar', 'Belum Menikah', 'Dusun Bougenville RT 01'),
('7501050001000004', 5, 10, 'Binti Ruqay', 'P', '2019-03-17', 5, 'Islam', 'Tidak Sekolah', 'Belum Bekerja', 'Belum Menikah', 'Dusun Bougenville RT 01'),
-- Bongo - KK 11
('7501050002000001', 5, 11, 'Bilal Rabbah', 'L', '1970-09-20', 54, 'Islam', 'SMP', 'Petani', 'Menikah', 'Dusun Kamboja RT 02'),
('7501050002000002', 5, 11, 'Ummu Salamah', 'P', '1975-04-18', 49, 'Islam', 'SD', 'Ibu Rumah Tangga', 'Menikah', 'Dusun Kamboja RT 02'),
('7501050002000003', 5, 11, 'Rabbah Bilal', 'L', '2006-12-10', 18, 'Islam', 'SMA', 'Pelajar', 'Belum Menikah', 'Dusun Kamboja RT 02'),
('7501050002000004', 5, 11, 'Salamah Ummu', 'P', '2013-07-25', 11, 'Islam', 'SD', 'Pelajar', 'Belum Menikah', 'Dusun Kamboja RT 02'),
('7501050002000005', 5, 11, 'Bilal Kecil', 'L', '2017-02-08', 7, 'Islam', 'SD', 'Pelajar', 'Belum Menikah', 'Dusun Kamboja RT 02');

-- 4. Insert Fasilitas Pendidikan
INSERT INTO fasilitas_pendidikan (id_desa, nama_fasilitas, jenis_pendidikan, alamat_fasilitas, kapasitas_siswa, kondisi_bangunan, jumlah_guru) VALUES
-- Tibawa Ilir (7 fasilitas)
(1, 'PAUD Mawar', 'PAUD', 'Dusun Mawar', 30, 'Baik', 2),
(1, 'TK Melati', 'TK', 'Dusun Melati', 40, 'Baik', 3),
(1, 'SDN 1 Tibawa Ilir', 'SD', 'Dusun Mawar', 120, 'Baik', 8),
(1, 'SDN 2 Tibawa Ilir', 'SD', 'Dusun Melati', 100, 'Sedang', 6),
(1, 'SMPN 1 Tibawa Ilir', 'SMP', 'Dusun Kenanga', 150, 'Baik', 12),
(1, 'SMAN 1 Tibawa Ilir', 'SMA', 'Dusun Kenanga', 180, 'Baik', 15),
(1, 'SMK Pertanian', 'SMK', 'Dusun Mawar', 120, 'Sedang', 10),

-- Tibawa Tengah (5 fasilitas)
(2, 'PAUD Cempaka', 'PAUD', 'Dusun Cempaka', 25, 'Baik', 2),
(2, 'TK Anggrek', 'TK', 'Dusun Anggrek', 35, 'Baik', 2),
(2, 'SDN Tibawa Tengah', 'SD', 'Dusun Cempaka', 100, 'Baik', 7),
(2, 'SMPN Tibawa Tengah', 'SMP', 'Dusun Anggrek', 120, 'Sedang', 9),
(2, 'SMAN Tibawa Tengah', 'SMA', 'Dusun Dahlia', 140, 'Baik', 11),

-- Tibawa Hulu (8 fasilitas)
(3, 'PAUD Flamboyan', 'PAUD', 'Dusun Flamboyan', 35, 'Baik', 3),
(3, 'TK Sakura', 'TK', 'Dusun Sakura', 45, 'Baik', 3),
(3, 'SDN 1 Tibawa Hulu', 'SD', 'Dusun Flamboyan', 140, 'Baik', 9),
(3, 'SDN 2 Tibawa Hulu', 'SD', 'Dusun Sakura', 120, 'Baik', 8),
(3, 'SMPN 1 Tibawa Hulu', 'SMP', 'Dusun Teratai', 160, 'Baik', 13),
(3, 'SMPN 2 Tibawa Hulu', 'SMP', 'Dusun Flamboyan', 140, 'Sedang', 11),
(3, 'SMAN Tibawa Hulu', 'SMA', 'Dusun Teratai', 200, 'Baik', 16),
(3, 'SMK Teknologi', 'SMK', 'Dusun Sakura', 150, 'Baik', 12),

-- Molosipat (4 fasilitas)
(4, 'PAUD Seroja', 'PAUD', 'Dusun Seroja', 20, 'Sedang', 2),
(4, 'SDN Molosipat', 'SD', 'Dusun Seroja', 80, 'Baik', 6),
(4, 'SMPN Molosipat', 'SMP', 'Dusun Tulip', 100, 'Sedang', 8),
(4, 'SMAN Molosipat', 'SMA', 'Dusun Mawar', 120, 'Baik', 10),

-- Bongo (6 fasilitas)
(5, 'PAUD Bougenville', 'PAUD', 'Dusun Bougenville', 30, 'Baik', 2),
(5, 'TK Kamboja', 'TK', 'Dusun Kamboja', 40, 'Baik', 3),
(5, 'SDN 1 Bongo', 'SD', 'Dusun Bougenville', 110, 'Baik', 7),
(5, 'SDN 2 Bongo', 'SD', 'Dusun Lily', 90, 'Sedang', 6),
(5, 'SMPN Bongo', 'SMP', 'Dusun Kamboja', 130, 'Baik', 10),
(5, 'SMAN Bongo', 'SMA', 'Dusun Lily', 160, 'Baik', 13);

-- 5. Insert UMKM
INSERT INTO umkm (id_desa, nik_pemilik, nama_usaha, jenis_usaha, modal_usaha, omzet_perbulan, jumlah_karyawan, alamat_usaha, status_izin) VALUES
-- Tibawa Ilir (25 UMKM)
(1, '7501010001000001', 'Warung Mawar', 'Kuliner', 5000000, 3000000, 2, 'Dusun Mawar RT 01', 'Ada'),
(1, '7501010002000002', 'Toko Kelontong Melati', 'Perdagangan', 8000000, 4500000, 1, 'Dusun Melati RT 02', 'Ada'),
(1, '7501010001000002', 'Jahit Siti', 'Jasa', 3000000, 2000000, 0, 'Dusun Mawar RT 01', 'Tidak Ada'),

-- Tibawa Tengah (17 UMKM)
(2, '7501020001000001', 'Bengkel Motor Yusuf', 'Jasa', 15000000, 6000000, 2, 'Dusun Cempaka RT 01', 'Ada'),
(2, '7501020001000002', 'Salon Khadijah', 'Jasa', 7000000, 3500000, 1, 'Dusun Cempaka RT 01', 'Ada'),

-- Tibawa Hulu (28 UMKM)
(3, '7501030001000001', 'Toko Bangunan Sulaiman', 'Perdagangan', 25000000, 12000000, 3, 'Dusun Flamboyan RT 01', 'Ada'),
(3, '7501030001000002', 'Catering Maryam', 'Kuliner', 10000000, 8000000, 4, 'Dusun Flamboyan RT 01', 'Ada'),

-- Molosipat (15 UMKM)
(4, '7501040001000001', 'Jual Ikan Hamzah', 'Perdagangan', 4000000, 2500000, 1, 'Dusun Seroja RT 01', 'Tidak Ada'),
(4, '7501040001000002', 'Warung Zainab', 'Kuliner', 3500000, 2000000, 0, 'Dusun Seroja RT 01', 'Tidak Ada'),

-- Bongo (19 UMKM)
(5, '7501050001000001', 'Tani Salman', 'Pertanian', 12000000, 5000000, 2, 'Dusun Bougenville RT 01', 'Ada'),
(5, '7501050001000002', 'Kue Ruqayyah', 'Kuliner', 6000000, 4000000, 1, 'Dusun Bougenville RT 01', 'Ada');

-- 6. Insert Infrastruktur Jalan
INSERT INTO infrastruktur_jalan (id_desa, nama_jalan, panjang_jalan, lebar_jalan, kondisi_jalan, jenis_permukaan) VALUES
-- Tibawa Ilir (10 jalan)
(1, 'Jalan Utama Mawar', 2.5, 4.0, 'baik', 'aspal'),
(1, 'Jalan Melati Raya', 1.8, 3.5, 'baik', 'aspal'),
(1, 'Jalan Kenanga', 1.2, 3.0, 'sedang', 'beton'),
(1, 'Jalan Desa 1', 0.8, 2.5, 'sedang', 'kerikil'),
(1, 'Jalan Desa 2', 0.6, 2.0, 'rusak', 'tanah'),

-- Tibawa Tengah (8 jalan)
(2, 'Jalan Cempaka Utama', 2.0, 4.0, 'baik', 'aspal'),
(2, 'Jalan Anggrek', 1.5, 3.0, 'sedang', 'beton'),
(2, 'Jalan Dahlia', 1.0, 2.5, 'sedang', 'kerikil'),
(2, 'Jalan Kampung 1', 0.7, 2.0, 'rusak', 'tanah'),

-- Tibawa Hulu (12 jalan)
(3, 'Jalan Flamboyan Raya', 3.0, 4.5, 'baik', 'aspal'),
(3, 'Jalan Sakura Indah', 2.2, 4.0, 'baik', 'aspal'),
(3, 'Jalan Teratai', 1.8, 3.5, 'baik', 'beton'),
(3, 'Jalan Desa Hulu 1', 1.0, 3.0, 'sedang', 'kerikil'),
(3, 'Jalan Desa Hulu 2', 0.8, 2.5, 'sedang', 'kerikil'),
(3, 'Jalan Kampung Hulu', 0.5, 2.0, 'rusak', 'tanah'),

-- Molosipat (7 jalan)
(4, 'Jalan Seroja', 1.5, 3.0, 'baik', 'aspal'),
(4, 'Jalan Tulip', 1.2, 2.5, 'sedang', 'beton'),
(4, 'Jalan Pantai', 2.0, 3.5, 'baik', 'aspal'),
(4, 'Jalan Kampung Nelayan', 0.8, 2.0, 'rusak', 'kerikil'),

-- Bongo (9 jalan)
(5, 'Jalan Bougenville Utama', 2.5, 4.0, 'baik', 'aspal'),
(5, 'Jalan Kamboja', 1.8, 3.5, 'baik', 'beton'),
(5, 'Jalan Lily', 1.5, 3.0, 'sedang', 'beton'),
(5, 'Jalan Desa Bongo 1', 1.0, 2.5, 'sedang', 'kerikil'),
(5, 'Jalan Desa Bongo 2', 0.7, 2.0, 'rusak', 'tanah');

-- 7. Insert Data Historis (2019-2024)
INSERT INTO data_historis (id_desa, tahun, bulan, total_penduduk, total_laki, total_perempuan, total_balita, total_anak, total_remaja, total_dewasa, total_lansia, total_kk, total_fasilitas_pendidikan, total_umkm, total_infrastruktur) VALUES
-- Tibawa Ilir
(1, 2019, 12, 1250, 625, 625, 125, 200, 150, 650, 125, 312, 5, 15, 8),
(1, 2020, 12, 1275, 638, 637, 128, 204, 153, 663, 127, 319, 5, 17, 8),
(1, 2021, 12, 1302, 651, 651, 130, 208, 156, 676, 132, 326, 6, 19, 9),
(1, 2022, 12, 1328, 664, 664, 133, 212, 159, 689, 135, 332, 6, 21, 9),
(1, 2023, 12, 1355, 678, 677, 136, 217, 163, 703, 136, 339, 6, 23, 10),
(1, 2024, 12, 1383, 692, 691, 138, 221, 166, 717, 141, 346, 7, 25, 10),

-- Tibawa Tengah
(2, 2019, 12, 980, 490, 490, 98, 157, 118, 510, 97, 245, 4, 12, 6),
(2, 2020, 12, 1000, 500, 500, 100, 160, 120, 520, 100, 250, 4, 13, 6),
(2, 2021, 12, 1020, 510, 510, 102, 163, 122, 530, 103, 255, 4, 14, 7),
(2, 2022, 12, 1041, 521, 520, 104, 167, 125, 541, 104, 260, 5, 15, 7),
(2, 2023, 12, 1062, 531, 531, 106, 170, 127, 552, 107, 266, 5, 16, 7),
(2, 2024, 12, 1084, 542, 542, 108, 173, 130, 564, 109, 271, 5, 17, 8),

-- Tibawa Hulu
(3, 2019, 12, 1450, 725, 725, 145, 232, 174, 754, 145, 363, 6, 18, 10),
(3, 2020, 12, 1479, 740, 739, 148, 237, 177, 769, 148, 370, 6, 20, 10),
(3, 2021, 12, 1509, 755, 754, 151, 242, 181, 785, 150, 377, 7, 22, 11),
(3, 2022, 12, 1540, 770, 770, 154, 246, 185, 801, 154, 385, 7, 24, 11),
(3, 2023, 12, 1571, 786, 785, 157, 251, 188, 818, 157, 393, 7, 26, 12),
(3, 2024, 12, 1603, 802, 801, 160, 256, 192, 835, 160, 401, 8, 28, 12),

-- Molosipat
(4, 2019, 12, 850, 425, 425, 85, 136, 102, 442, 85, 213, 3, 10, 5),
(4, 2020, 12, 868, 434, 434, 87, 139, 104, 451, 87, 217, 3, 11, 5),
(4, 2021, 12, 886, 443, 443, 89, 142, 106, 460, 89, 222, 4, 12, 6),
(4, 2022, 12, 905, 453, 452, 91, 145, 109, 470, 90, 226, 4, 13, 6),
(4, 2023, 12, 924, 462, 462, 92, 148, 111, 480, 93, 231, 4, 14, 6),
(4, 2024, 12, 943, 472, 471, 94, 151, 113, 491, 94, 236, 4, 15, 7),

-- Bongo
(5, 2019, 12, 1150, 575, 575, 115, 184, 138, 598, 115, 288, 5, 14, 7),
(5, 2020, 12, 1173, 587, 586, 117, 188, 141, 610, 117, 293, 5, 15, 7),
(5, 2021, 12, 1197, 599, 598, 120, 191, 143, 622, 121, 299, 5, 16, 8),
(5, 2022, 12, 1221, 611, 610, 122, 195, 146, 635, 123, 305, 6, 17, 8),
(5, 2023, 12, 1246, 623, 623, 125, 199, 149, 648, 125, 312, 6, 18, 8),
(5, 2024, 12, 1271, 636, 635, 127, 203, 152, 661, 128, 318, 6, 19, 9);

-- 8. Insert Mata Pencaharian
INSERT INTO mata_pencaharian (nik, id_desa, jenis_pekerjaan, sektor_pekerjaan, penghasilan_perbulan, status_pekerjaan) VALUES
('7501010001000001', 1, 'Petani', 'pertanian', 2500000, 'tetap'),
('7501010002000002', 1, 'Pedagang', 'jasa', 3000000, 'tidak_tetap'),
('7501020001000001', 2, 'Guru', 'jasa', 4500000, 'tetap'),
('7501020001000002', 2, 'Perawat', 'jasa', 3800000, 'tetap'),
('7501030001000001', 3, 'Wiraswasta', 'jasa', 5000000, 'tidak_tetap'),
('7501040001000001', 4, 'Nelayan', 'pertanian', 2200000, 'tidak_tetap'),
('7501050001000001', 5, 'Petani', 'pertanian', 2800000, 'tetap'),
('7501050001000002', 5, 'Pedagang', 'jasa', 2500000, 'tidak_tetap');

-- 9. Insert Warga Miskin (hanya kepala keluarga)
INSERT INTO warga_miskin (nik, id_desa, jenis_bantuan, nominal_bantuan, periode_bantuan, status_penerima) VALUES
('7501010002000001', 1, 'PKH', 750000, '2024', 'aktif'),
('7501020002000001', 2, 'BPNT', 200000, '2024', 'aktif'),
('7501040002000001', 4, 'PKH', 750000, '2024', 'aktif'),
('7501050002000001', 5, 'BPNT', 200000, '2024', 'aktif');

-- 10. Insert Data Ekonomi
INSERT INTO data_ekonomi (id_desa, jenis_data, nama_usaha_atau_pekerjaan, deskripsi, jumlah_pelaku, hasil_produksi, nilai_ekonomi, lokasi) VALUES
-- Tibawa Ilir
(1, 'pertanian', 'Kelompok Tani Mawar', 'Budidaya padi dan jagung', 45, 'Padi 120 ton/tahun', 300000000, 'Dusun Mawar'),
(1, 'umkm', 'Kelompok UMKM Melati', 'Usaha kuliner dan kerajinan', 25, 'Makanan olahan', 150000000, 'Dusun Melati'),
(1, 'pasar', 'Pasar Tibawa Ilir', 'Pasar tradisional', 30, 'Perdagangan umum', 200000000, 'Pusat Desa'),
-- Tibawa Tengah
(2, 'pertanian', 'Kelompok Tani Cempaka', 'Budidaya sayuran', 35, 'Sayuran 80 ton/tahun', 240000000, 'Dusun Cempaka'),
(2, 'umkm', 'UMKM Anggrek', 'Jasa dan perdagangan', 17, 'Jasa bengkel', 120000000, 'Dusun Anggrek'),
-- Tibawa Hulu
(3, 'pertanian', 'Kelompok Tani Flamboyan', 'Budidaya buah-buahan', 55, 'Buah 150 ton/tahun', 450000000, 'Dusun Flamboyan'),
(3, 'umkm', 'UMKM Sakura', 'Industri kecil', 28, 'Produk olahan', 280000000, 'Dusun Sakura'),
(3, 'pasar', 'Pasar Tibawa Hulu', 'Pasar modern', 40, 'Perdagangan retail', 350000000, 'Pusat Desa'),
-- Molosipat
(4, 'pertanian', 'Kelompok Nelayan Seroja', 'Penangkapan ikan', 25, 'Ikan 60 ton/tahun', 180000000, 'Pantai Seroja'),
(4, 'umkm', 'UMKM Tulip', 'Pengolahan ikan', 15, 'Ikan olahan', 90000000, 'Dusun Tulip'),
-- Bongo
(5, 'pertanian', 'Kelompok Tani Bougenville', 'Budidaya campuran', 40, 'Padi dan sayur', 320000000, 'Dusun Bougenville'),
(5, 'umkm', 'UMKM Kamboja', 'Kuliner tradisional', 19, 'Makanan khas', 140000000, 'Dusun Kamboja');

-- 11. Insert Infrastruktur Jembatan
INSERT INTO infrastruktur_jembatan (id_desa, nama_jembatan, panjang_jembatan, lebar_jembatan, kondisi_jembatan, material_jembatan, kapasitas_beban) VALUES
-- Tibawa Ilir
(1, 'Jembatan Mawar', 25.0, 4.0, 'baik', 'Beton Bertulang', 10.0),
(1, 'Jembatan Melati', 18.0, 3.5, 'sedang', 'Beton', 8.0),
-- Tibawa Tengah
(2, 'Jembatan Cempaka', 20.0, 4.0, 'baik', 'Beton Bertulang', 10.0),
-- Tibawa Hulu
(3, 'Jembatan Flamboyan', 30.0, 5.0, 'baik', 'Beton Bertulang', 15.0),
(3, 'Jembatan Sakura', 22.0, 4.0, 'baik', 'Beton', 10.0),
-- Molosipat
(4, 'Jembatan Seroja', 15.0, 3.0, 'sedang', 'Kayu', 5.0),
-- Bongo
(5, 'Jembatan Bougenville', 28.0, 4.5, 'baik', 'Beton Bertulang', 12.0),
(5, 'Jembatan Lily', 16.0, 3.5, 'sedang', 'Beton', 8.0);

-- 12. Insert Pasar
INSERT INTO pasar (id_desa, nama_pasar, jenis_pasar, jumlah_pedagang, hari_operasional, alamat_pasar, kondisi_fasilitas) VALUES
(1, 'Pasar Tibawa Ilir', 'tradisional', 30, 'Senin, Rabu, Jumat', 'Pusat Desa Tibawa Ilir', 'Baik'),
(2, 'Pasar Minggu Cempaka', 'tradisional', 20, 'Minggu', 'Dusun Cempaka', 'Sedang'),
(3, 'Pasar Tibawa Hulu', 'modern', 40, 'Setiap Hari', 'Pusat Desa Tibawa Hulu', 'Baik'),
(4, 'Pasar Ikan Molosipat', 'tradisional', 15, 'Selasa, Kamis, Sabtu', 'Pantai Molosipat', 'Sedang'),
(5, 'Pasar Bongo', 'tradisional', 25, 'Rabu, Sabtu', 'Pusat Desa Bongo', 'Baik');

-- 13. Insert Pelaku Ekonomi
INSERT INTO pelaku_ekonomi (id_ekonomi, nik, peran, status_aktif, tanggal_bergabung) VALUES
(1, '7501010001000001', 'pemilik', 'aktif', '2020-01-15'),
(1, '7501010002000002', 'anggota', 'aktif', '2020-03-20'),
(2, '7501010001000002', 'pemilik', 'aktif', '2019-06-10'),
(4, '7501020001000001', 'pemilik', 'aktif', '2021-02-14'),
(4, '7501020001000002', 'anggota', 'aktif', '2021-04-18'),
(6, '7501030001000001', 'pemilik', 'aktif', '2019-08-22'),
(6, '7501030001000002', 'anggota', 'aktif', '2020-01-30'),
(9, '7501040001000001', 'pemilik', 'aktif', '2020-05-12'),
(9, '7501040001000002', 'anggota', 'aktif', '2020-07-25'),
(11, '7501050001000001', 'pemilik', 'aktif', '2019-11-08'),
(11, '7501050001000002', 'anggota', 'aktif', '2020-02-16');

-- 14. Insert Pertanian
INSERT INTO pertanian (id_ekonomi, nik_petani, id_desa, luas_lahan, jenis_komoditas, jenis_lahan, status_kepemilikan, produksi_per_musim, satuan_produksi, pendapatan_per_musim, musim_tanam, bantuan_pertanian, jenis_bantuan, teknologi_pertanian, irigasi) VALUES
(1, '7501010001000001', 1, 0.75, 'Padi', 'sawah', 'milik_sendiri', 4.5, 'ton', 18000000, 'musim_hujan', 'ada', 'Pupuk Bersubsidi', 'semi_modern', 'teknis'),
(1, '7501010002000002', 1, 0.50, 'Jagung', 'ladang', 'milik_sendiri', 2.0, 'ton', 8000000, 'musim_kemarau', 'tidak_ada', NULL, 'tradisional', 'tadah_hujan'),
(4, '7501020001000001', 2, 0.30, 'Bayam', 'ladang', 'sewa', 1.5, 'ton', 6000000, 'sepanjang_tahun', 'ada', 'Bibit Unggul', 'modern', 'semi_teknis'),
(4, '7501020001000002', 2, 0.25, 'Kangkung', 'ladang', 'milik_sendiri', 1.2, 'ton', 4800000, 'sepanjang_tahun', 'tidak_ada', NULL, 'tradisional', 'sederhana'),
(6, '7501030001000001', 3, 1.20, 'Rambutan', 'kebun', 'milik_sendiri', 8.0, 'ton', 32000000, 'musim_hujan', 'ada', 'Pupuk Organik', 'semi_modern', 'tadah_hujan'),
(6, '7501030001000002', 3, 0.80, 'Mangga', 'kebun', 'milik_sendiri', 5.0, 'ton', 25000000, 'musim_kemarau', 'tidak_ada', NULL, 'tradisional', 'tadah_hujan'),
(9, '7501040001000001', 4, 0.15, 'Ikan Bandeng', 'tambak', 'sewa', 2.0, 'ton', 20000000, 'sepanjang_tahun', 'ada', 'Pakan Ikan', 'semi_modern', 'teknis'),
(11, '7501050001000001', 5, 0.60, 'Padi', 'sawah', 'milik_sendiri', 3.6, 'ton', 14400000, 'musim_hujan', 'ada', 'Pupuk Bersubsidi', 'semi_modern', 'teknis'),
(11, '7501050001000002', 5, 0.40, 'Pisang', 'kebun', 'milik_sendiri', 200.0, 'ikat', 6000000, 'sepanjang_tahun', 'tidak_ada', NULL, 'tradisional', 'tadah_hujan');

-- 15. Update UMKM dengan id_ekonomi
UPDATE umkm SET id_ekonomi = 2 WHERE id_desa = 1 AND nama_usaha = 'Warung Mawar';
UPDATE umkm SET id_ekonomi = 2 WHERE id_desa = 1 AND nama_usaha = 'Toko Kelontong Melati';
UPDATE umkm SET id_ekonomi = 5 WHERE id_desa = 2 AND nama_usaha = 'Bengkel Motor Yusuf';
UPDATE umkm SET id_ekonomi = 5 WHERE id_desa = 2 AND nama_usaha = 'Salon Khadijah';
UPDATE umkm SET id_ekonomi = 7 WHERE id_desa = 3 AND nama_usaha = 'Toko Bangunan Sulaiman';
UPDATE umkm SET id_ekonomi = 7 WHERE id_desa = 3 AND nama_usaha = 'Catering Maryam';
UPDATE umkm SET id_ekonomi = 10 WHERE id_desa = 4 AND nama_usaha = 'Jual Ikan Hamzah';
UPDATE umkm SET id_ekonomi = 10 WHERE id_desa = 4 AND nama_usaha = 'Warung Zainab';
UPDATE umkm SET id_ekonomi = 12 WHERE id_desa = 5 AND nama_usaha = 'Tani Salman';
UPDATE umkm SET id_ekonomi = 12 WHERE id_desa = 5 AND nama_usaha = 'Kue Ruqayyah';

-- 16. Insert User Admin dan Operator
INSERT INTO user (id_desa, username, email, password, nama_lengkap, role, status_aktif) VALUES 
(NULL, 'admin', 'admin@tibawa.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Kecamatan', 'admin_kecamatan', 'aktif'),
(1, 'operator1', 'operator1@tibawa.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator Tibawa Ilir', 'operator_desa', 'aktif'),
(2, 'operator2', 'operator2@tibawa.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator Tibawa Tengah', 'operator_desa', 'aktif'),
(3, 'operator3', 'operator3@tibawa.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator Tibawa Hulu', 'operator_desa', 'aktif'),
(4, 'operator4', 'operator4@tibawa.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator Molosipat', 'operator_desa', 'aktif'),
(5, 'operator5', 'operator5@tibawa.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator Bongo', 'operator_desa', 'aktif')
ON DUPLICATE KEY UPDATE email = VALUES(email);