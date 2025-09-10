<?php
// Konfigurasi Database
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        // Deteksi environment
        if ($this->isLocalhost()) {
            // Konfigurasi Localhost
            $this->host = "localhost";
            $this->db_name = "pendataan_desa";
            $this->username = "root";
            $this->password = "";
        } else {
            // Konfigurasi Hosting
            $this->host = "localhost"; // Sesuaikan dengan hosting
            $this->db_name = "pendataan_desa";
            $this->username = ""; // Username hosting
            $this->password = ""; // Password hosting
        }
    }
    
    private function isLocalhost() {
        $localhost_ips = ['127.0.0.1', '::1', 'localhost'];
        return in_array($_SERVER['HTTP_HOST'], $localhost_ips) || 
               strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0;
    }
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Instance global database
$database = new Database();
$db = $database->getConnection();
?>