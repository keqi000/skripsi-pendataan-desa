# Sistem Informasi Pendataan Desa Terintegrasi

Sistem informasi analisis dan pelaporan data desa berbasis web untuk mendukung kebijakan pemerintah pusat di Kecamatan Tibawa, Kabupaten Gorontalo.

## 🚀 Fitur Utama

### Untuk Operator Desa:
- ✅ Manajemen data umum desa
- ✅ Pendataan kependudukan lengkap
- ✅ Data ekonomi (UMKM, mata pencaharian, bantuan sosial)
- ✅ Data pendidikan dan fasilitas
- ✅ Data infrastruktur (jalan, jembatan)
- ✅ Analisis data tingkat 1 dan 2
- ✅ Pembuatan laporan otomatis

### Untuk Admin Kecamatan:
- ✅ Monitoring data semua desa
- ✅ Analisis data lintas desa
- ✅ Visualisasi peta dan geografis
- ✅ Laporan agregat kecamatan
- ✅ Dashboard real-time

## 🛠️ Teknologi

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+
- **Server**: Apache (XAMPP)
- **Styling**: Custom CSS dengan color palette: #112D4E, #3F72AF, #DBE2EF, #F9F7F7

## 📋 Persyaratan Sistem

- PHP 8.0 atau lebih tinggi
- MySQL 8.0 atau lebih tinggi
- Apache Web Server
- Browser modern (Chrome, Firefox, Safari, Edge)

## 🔧 Instalasi

### 1. Clone atau Download Project
```bash
git clone [repository-url]
# atau download dan extract ke folder xampp/htdocs/
```

### 2. Setup Database
1. Buka phpMyAdmin (http://localhost/phpmyadmin)
2. Buat database baru dengan nama `pendataan_desa`
3. Jalankan migration:
   ```bash
   php database/migration.php
   ```
   Atau import manual file `database/create_tables.sql`

### 3. Konfigurasi Database
Edit file `config/database.php` jika diperlukan:
```php
// Untuk localhost (default)
$this->host = "localhost";
$this->db_name = "pendataan_desa";
$this->username = "root";
$this->password = "";
```

### 4. Setup Web Server
1. Pastikan Apache dan MySQL running di XAMPP
2. Akses aplikasi melalui: `http://localhost/Pendataan-desa`

### 5. Login Default
- **Username**: `admin`
- **Password**: `password`
- **Role**: Admin Kecamatan

## 📁 Struktur Project

```
Pendataan-desa/
├── .htaccess                 # URL rewriting & security
├── index.php                 # Entry point
├── README.md                 # Dokumentasi
│
├── config/                   # Konfigurasi
│   ├── config.php           # Base URL & constants
│   └── database.php         # Database connection
│
├── database/                 # Database files
│   ├── create_tables.sql    # Database schema
│   ├── migration.php        # Migration runner
│   └── queries.php          # Database queries
│
├── api/                      # REST API endpoints
│   └── index.php            # API router
│
├── assets/                   # Static assets
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── img/                 # Images
│
├── layout/                   # Layout templates
│   └── main.php             # Main layout
│
├── pages/                    # Application pages
│   ├── auth/                # Authentication
│   ├── dashboard/           # Dashboard
│   ├── data-umum/          # Data umum desa
│   ├── kependudukan/       # Data kependudukan
│   ├── ekonomi/            # Data ekonomi
│   ├── pendidikan/         # Data pendidikan
│   ├── infrastruktur/      # Data infrastruktur
│   ├── analisis/           # Analisis data
│   ├── laporan/            # Laporan
│   ├── monitoring/         # Monitoring (admin)
│   └── peta/               # Peta geografis
│
└── uploads/                  # File uploads
```

## 🎨 Design System

### Color Palette
- **Primary**: #112D4E (Dark Blue)
- **Secondary**: #3F72AF (Blue)
- **Light**: #DBE2EF (Light Blue)
- **Lightest**: #F9F7F7 (Off White)

### Features
- ✅ Responsive design (Desktop, Tablet, Mobile)
- ✅ Dark mode support
- ✅ Page & content loaders
- ✅ Real-time filtering
- ✅ Dynamic base URL (localhost/hosting)
- ✅ Namespace isolation per page

## 🔐 Keamanan

- Password hashing dengan PHP password_hash()
- SQL injection protection dengan PDO prepared statements
- XSS protection dengan htmlspecialchars()
- CSRF protection (session-based)
- File upload validation
- Access control berdasarkan role

## 📊 Database Schema

Sistem menggunakan 14 tabel utama:
- `desa` - Data umum desa
- `penduduk` - Data penduduk
- `keluarga` - Data keluarga
- `fasilitas_pendidikan` - Fasilitas pendidikan
- `data_ekonomi` - Data ekonomi umum
- `mata_pencaharian` - Mata pencaharian penduduk
- `umkm` - Data UMKM
- `infrastruktur_jalan` - Data jalan
- `infrastruktur_jembatan` - Data jembatan
- `warga_miskin` - Data bantuan sosial
- `pasar` - Data pasar
- `user` - Data pengguna
- `laporan` - Data laporan
- `analisis_data` - Hasil analisis

## 🚀 Deployment

### Localhost
1. Pastikan XAMPP running
2. Akses: `http://localhost/Pendataan-desa`

### Hosting
1. Upload semua file ke public_html
2. Update konfigurasi database di `config/database.php`
3. Sistem akan otomatis detect hosting environment

## 🔄 API Endpoints

```
GET  /api/desa              # List semua desa
GET  /api/desa/{id}         # Detail desa
GET  /api/penduduk/{desa}   # Data penduduk per desa
POST /api/penduduk          # Tambah penduduk
GET  /api/ekonomi/{desa}    # Data ekonomi per desa
GET  /api/analisis/{desa}/tingkat1  # Analisis tingkat 1
GET  /api/analisis/{desa}/tingkat2  # Analisis tingkat 2
```

## 🤝 Kontribusi

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 License

Distributed under the MIT License. See `LICENSE` for more information.

## 📞 Kontak

**Developer**: [Your Name]
**Email**: [your.email@example.com]
**Project Link**: [repository-url]

---

**Sistem Informasi Pendataan Desa Terintegrasi**  
*Mendukung Kebijakan Pemerintah Pusat di Kecamatan Tibawa*