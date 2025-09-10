<?php
// Konfigurasi Base URL dinamis untuk localhost dan hosting
function getBaseURL() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    
    // Deteksi localhost
    if (in_array($domainName, ['localhost', '127.0.0.1', '::1']) || strpos($domainName, 'localhost:') === 0) {
        return $protocol . $domainName . '/Pendataan-desa/';
    }
    
    // Untuk hosting
    return $protocol . $domainName . '/';
}

// Konstanta BASE_URL
define('BASE_URL', getBaseURL());

// Konfigurasi API URL
define('API_URL', BASE_URL . 'api/');

// Konfigurasi Assets
define('ASSETS_URL', BASE_URL . 'assets/');
define('CSS_URL', BASE_URL . 'assets/css/');
define('JS_URL', BASE_URL . 'assets/js/');
define('IMG_URL', BASE_URL . 'assets/img/');

// Konfigurasi Upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// Timezone
date_default_timezone_set('Asia/Makassar');
?>