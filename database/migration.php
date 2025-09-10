<?php
require_once __DIR__ . '/../config/database.php';

class Migration {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function runMigration() {
        try {
            // Baca file SQL
            $sql = file_get_contents(__DIR__ . '/create_tables.sql');
            
            // Split berdasarkan statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    $this->db->exec($statement);
                    echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
                }
            }
            
            echo "\n✅ Migration completed successfully!\n";
            
        } catch (PDOException $e) {
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
        }
    }
    
    public function checkTables() {
        try {
            $query = "SHOW TABLES";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "📋 Tables in database:\n";
            foreach ($tables as $table) {
                echo "  - $table\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ Error checking tables: " . $e->getMessage() . "\n";
        }
    }
}

// Jalankan migration jika file diakses langsung
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    $migration = new Migration();
    
    echo "🚀 Starting database migration...\n\n";
    $migration->runMigration();
    
    echo "\n📋 Checking created tables...\n";
    $migration->checkTables();
}
?>