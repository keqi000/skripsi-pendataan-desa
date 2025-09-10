-- Database: pendataan_desa
-- Sistem Informasi Pendataan Desa Terintegrasi

-- Tabel DESA
CREATE TABLE desa (
    id_desa INT PRIMARY KEY AUTO_INCREMENT,
    nama_desa VARCHAR(100) NOT NULL,
    nama_kecamatan VARCHAR(100) NOT NULL,
    nama_kabupaten VARCHAR(100) NOT NULL,
    nama_provinsi VARCHAR(100) NOT NULL,
    luas_wilayah DECIMAL(10,2),
    jumlah_dusun INT DEFAULT 0,
    jumlah_rt INT DEFAULT 0,
    jumlah_rw INT DEFAULT 0,
    koordinat_latitude DECIMAL(10,8),
    koordinat_longitude DECIMAL(11,8),
    peta_fisik VARCHAR(255),
    peta_administratif VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_desa_created (nama_desa, created_at)
);

-- Tabel KELUARGA
CREATE TABLE keluarga (
    id_keluarga INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NOT NULL,
    nomor_kk VARCHAR(20) NOT NULL,
    nama_kepala_keluarga VARCHAR(100) NOT NULL,
    alamat_keluarga TEXT,
    jumlah_anggota INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_kk_created (nomor_kk, created_at),
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel PENDUDUK
CREATE TABLE penduduk (
    nik VARCHAR(16) NOT NULL,
    id_desa INT NOT NULL,
    id_keluarga INT,
    nama_lengkap VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    tanggal_lahir DATE NOT NULL,
    usia INT,
    agama ENUM('Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu') NOT NULL,
    pendidikan_terakhir ENUM('Tidak Sekolah', 'SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3') DEFAULT 'Tidak Sekolah',
    pekerjaan VARCHAR(100),
    status_pernikahan ENUM('Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati') DEFAULT 'Belum Menikah',
    status_migrasi ENUM('Menetap', 'Pendatang', 'Pindah') DEFAULT 'Menetap',
    alamat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (nik, created_at),
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE,
    FOREIGN KEY (id_keluarga) REFERENCES keluarga(id_keluarga) ON DELETE SET NULL
);

-- Tabel FASILITAS_PENDIDIKAN
CREATE TABLE fasilitas_pendidikan (
    id_fasilitas INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NOT NULL,
    nama_fasilitas VARCHAR(100) NOT NULL,
    jenis_pendidikan ENUM('PAUD', 'TK', 'SD', 'SMP', 'SMA', 'SMK') NOT NULL,
    alamat_fasilitas TEXT,
    kapasitas_siswa INT DEFAULT 0,
    kondisi_bangunan ENUM('Baik', 'Sedang', 'Rusak') DEFAULT 'Baik',
    jumlah_guru INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fasilitas_created (nama_fasilitas, created_at),
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel DATA_EKONOMI
CREATE TABLE data_ekonomi (
    id_ekonomi INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NOT NULL,
    jenis_data ENUM('mata_pencaharian', 'umkm', 'pertanian', 'pasar', 'bantuan_sosial') NOT NULL,
    nama_usaha_atau_pekerjaan VARCHAR(100),
    deskripsi TEXT,
    jumlah_pelaku INT DEFAULT 0,
    hasil_produksi VARCHAR(100),
    nilai_ekonomi DECIMAL(15,2),
    lokasi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel MATA_PENCAHARIAN
CREATE TABLE mata_pencaharian (
    id_pencaharian INT PRIMARY KEY AUTO_INCREMENT,
    nik VARCHAR(16) NOT NULL,
    id_desa INT NOT NULL,
    jenis_pekerjaan VARCHAR(100) NOT NULL,
    sektor_pekerjaan ENUM('pertanian', 'industri', 'jasa', 'lainnya') DEFAULT 'lainnya',
    penghasilan_perbulan DECIMAL(12,2),
    status_pekerjaan ENUM('tetap', 'tidak_tetap') DEFAULT 'tidak_tetap',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE,
    UNIQUE KEY unique_nik_created (nik, created_at)
);

-- Tabel UMKM
CREATE TABLE umkm (
    id_umkm INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NOT NULL,
    id_ekonomi INT NULL,
    nik_pemilik VARCHAR(16) NOT NULL,
    nama_usaha VARCHAR(100) NOT NULL,
    jenis_usaha VARCHAR(100),
    modal_usaha DECIMAL(15,2),
    omzet_perbulan DECIMAL(15,2),
    jumlah_karyawan INT DEFAULT 0,
    alamat_usaha TEXT,
    status_izin ENUM('Ada', 'Tidak Ada') DEFAULT 'Tidak Ada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_umkm_created (nama_usaha, created_at),
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE,
    FOREIGN KEY (id_ekonomi) REFERENCES data_ekonomi(id_ekonomi) ON DELETE SET NULL
);

-- Tabel INFRASTRUKTUR_JALAN
CREATE TABLE infrastruktur_jalan (
    id_jalan INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NOT NULL,
    nama_jalan VARCHAR(100) NOT NULL,
    panjang_jalan DECIMAL(8,2) NOT NULL,
    lebar_jalan DECIMAL(5,2),
    kondisi_jalan ENUM('baik', 'sedang', 'rusak') DEFAULT 'baik',
    jenis_permukaan ENUM('aspal', 'beton', 'tanah', 'kerikil') DEFAULT 'tanah',
    foto_jalan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_jalan_created (nama_jalan, created_at),
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel INFRASTRUKTUR_JEMBATAN
CREATE TABLE infrastruktur_jembatan (
    id_jembatan INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NOT NULL,
    nama_jembatan VARCHAR(100) NOT NULL,
    panjang_jembatan DECIMAL(8,2) NOT NULL,
    lebar_jembatan DECIMAL(5,2),
    kondisi_jembatan ENUM('baik', 'sedang', 'rusak') DEFAULT 'baik',
    material_jembatan VARCHAR(50),
    kapasitas_beban DECIMAL(8,2),
    foto_jembatan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel WARGA_MISKIN
CREATE TABLE warga_miskin (
    id_warga_miskin INT PRIMARY KEY AUTO_INCREMENT,
    nik VARCHAR(16) NOT NULL,
    id_desa INT NOT NULL,
    jenis_bantuan VARCHAR(100),
    nominal_bantuan DECIMAL(12,2),
    periode_bantuan VARCHAR(50),
    status_penerima ENUM('aktif', 'non_aktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel PASAR
CREATE TABLE pasar (
    id_pasar INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NOT NULL,
    nama_pasar VARCHAR(100) NOT NULL,
    jenis_pasar ENUM('tradisional', 'modern') DEFAULT 'tradisional',
    jumlah_pedagang INT DEFAULT 0,
    hari_operasional VARCHAR(100),
    alamat_pasar TEXT,
    kondisi_fasilitas ENUM('Baik', 'Sedang', 'Rusak') DEFAULT 'Baik',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_pasar_created (nama_pasar, created_at),
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel USER
CREATE TABLE user (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('operator_desa', 'admin_kecamatan') NOT NULL,
    status_aktif ENUM('aktif', 'non_aktif') DEFAULT 'aktif',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE SET NULL
);

-- Tabel LAPORAN
CREATE TABLE laporan (
    id_laporan INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    id_desa INT NOT NULL,
    judul_laporan VARCHAR(200) NOT NULL,
    jenis_laporan ENUM('bulanan', 'triwulan', 'tahunan', 'khusus') NOT NULL,
    periode_laporan VARCHAR(50),
    konten_laporan JSON,
    file_laporan VARCHAR(255),
    status_laporan ENUM('draft', 'final', 'terkirim') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel ANALISIS_DATA
CREATE TABLE analisis_data (
    id_analisis INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NOT NULL,
    id_user INT NOT NULL,
    jenis_analisis ENUM('tingkat_1', 'tingkat_2') NOT NULL,
    kategori_data ENUM('kependudukan', 'ekonomi', 'pendidikan', 'infrastruktur') NOT NULL,
    parameter_analisis JSON,
    hasil_analisis JSON,
    visualisasi_data JSON,
    tanggal_analisis DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE
);

-- Tabel PELAKU_EKONOMI (Penghubung data_ekonomi, umkm, dan penduduk)
CREATE TABLE pelaku_ekonomi (
    id_pelaku INT PRIMARY KEY AUTO_INCREMENT,
    id_ekonomi INT NOT NULL,
    nik VARCHAR(16) NOT NULL,
    id_umkm INT NULL,
    peran ENUM('pemilik', 'karyawan', 'anggota') NOT NULL,
    status_aktif ENUM('aktif', 'non_aktif') DEFAULT 'aktif',
    tanggal_bergabung DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ekonomi) REFERENCES data_ekonomi(id_ekonomi) ON DELETE CASCADE,
    FOREIGN KEY (id_umkm) REFERENCES umkm(id_umkm) ON DELETE SET NULL
);

-- Tabel PERTANIAN (Detail data pertanian)
CREATE TABLE pertanian (
    id_pertanian INT PRIMARY KEY AUTO_INCREMENT,
    id_ekonomi INT NOT NULL,
    nik_petani VARCHAR(16) NOT NULL,
    id_desa INT NOT NULL,
    luas_lahan DECIMAL(8,2) NOT NULL,
    jenis_komoditas VARCHAR(100) NOT NULL,
    jenis_lahan ENUM('sawah', 'ladang', 'kebun', 'tambak') NOT NULL,
    status_kepemilikan ENUM('milik_sendiri', 'sewa', 'bagi_hasil', 'hibah') DEFAULT 'milik_sendiri',
    produksi_per_musim DECIMAL(10,2),
    satuan_produksi ENUM('ton', 'kuintal', 'kg', 'ikat', 'buah') DEFAULT 'kg',
    pendapatan_per_musim DECIMAL(15,2),
    musim_tanam ENUM('musim_hujan', 'musim_kemarau', 'sepanjang_tahun') DEFAULT 'sepanjang_tahun',
    bantuan_pertanian ENUM('ada', 'tidak_ada') DEFAULT 'tidak_ada',
    jenis_bantuan VARCHAR(100),
    teknologi_pertanian ENUM('tradisional', 'semi_modern', 'modern') DEFAULT 'tradisional',
    irigasi ENUM('teknis', 'semi_teknis', 'sederhana', 'tadah_hujan') DEFAULT 'tadah_hujan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ekonomi) REFERENCES data_ekonomi(id_ekonomi) ON DELETE CASCADE,
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel DATA_HISTORIS (untuk prediksi demografis)
CREATE TABLE data_historis (
    id_historis INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NOT NULL,
    tahun YEAR NOT NULL,
    bulan TINYINT NOT NULL,
    total_penduduk INT DEFAULT 0,
    total_laki INT DEFAULT 0,
    total_perempuan INT DEFAULT 0,
    total_balita INT DEFAULT 0,
    total_anak INT DEFAULT 0,
    total_remaja INT DEFAULT 0,
    total_dewasa INT DEFAULT 0,
    total_lansia INT DEFAULT 0,
    total_kk INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_desa_periode (id_desa, tahun, bulan),
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel PREDIKSI_PENDUDUK (hasil prediksi)
CREATE TABLE prediksi_penduduk (
    id_prediksi INT PRIMARY KEY AUTO_INCREMENT,
    id_desa INT NOT NULL,
    tahun_prediksi YEAR NOT NULL,
    total_penduduk_prediksi INT DEFAULT 0,
    total_laki_prediksi INT DEFAULT 0,
    total_perempuan_prediksi INT DEFAULT 0,
    total_balita_prediksi INT DEFAULT 0,
    total_anak_prediksi INT DEFAULT 0,
    total_remaja_prediksi INT DEFAULT 0,
    total_dewasa_prediksi INT DEFAULT 0,
    total_lansia_prediksi INT DEFAULT 0,
    total_kk_prediksi INT DEFAULT 0,
    growth_rate DECIMAL(5,2) DEFAULT 0,
    confidence_level DECIMAL(5,2) DEFAULT 0,
    metode_prediksi ENUM('linear_regression', 'exponential_smoothing', 'arima') DEFAULT 'linear_regression',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_desa_tahun_prediksi (id_desa, tahun_prediksi),
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Tabel untuk menyimpan template laporan
CREATE TABLE laporan_template (
    id_template INT AUTO_INCREMENT PRIMARY KEY,
    nama_template VARCHAR(255) NOT NULL,
    konfigurasi TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES user(id_user) ON DELETE CASCADE
);

-- Tabel untuk menyimpan riwayat laporan yang telah dibuat
CREATE TABLE laporan_riwayat (
    id_riwayat INT AUTO_INCREMENT PRIMARY KEY,
    nama_laporan VARCHAR(255) NOT NULL,
    konfigurasi TEXT NOT NULL,
    data_laporan LONGTEXT NOT NULL,
    file_path VARCHAR(500) NULL,
    status ENUM('draft', 'completed', 'failed') DEFAULT 'completed',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES user(id_user) ON DELETE CASCADE
);

-- Insert user admin default
INSERT INTO user (username, email, password, nama_lengkap, role) 
VALUES ('admin', 'admin@tibawa.go.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Kecamatan', 'admin_kecamatan');