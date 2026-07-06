<?php
/**
 * Import MySQL capodanno database schema and data to SQLite
 * Run with: php import_mysql_to_sqlite.php
 */

$sqliteDb = __DIR__ . '/database/database.sqlite';
$mysqlHost = 'localhost';
$mysqlDb = 'capodanno';
$mysqlUser = 'root';
$mysqlPass = '';

echo "🔄 Importazione Database MySQL → SQLite\n\n";

// Connect to MySQL
try {
    $mysql = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb", $mysqlUser, $mysqlPass);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connesso a MySQL\n";
} catch (PDOException $e) {
    die("❌ Errore MySQL: " . $e->getMessage() . "\n");
}

// Connect to SQLite
try {
    if (file_exists($sqliteDb)) {
        unlink($sqliteDb);
        echo "🗑️  Database SQLite esistente rimosso\n";
    }
    
    $sqlite = new PDO('sqlite:' . $sqliteDb);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlite->exec("PRAGMA foreign_keys = OFF");
    echo "✅ Connesso a SQLite\n\n";
} catch (PDOException $e) {
    die("❌ Errore SQLite: " . $e->getMessage() . "\n");
}

// Get all tables from MySQL
$tables = $mysql->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "📋 Trovate " . count($tables) . " tabelle\n\n";

foreach ($tables as $table) {
    echo "📦 Importando: $table\n";
    
    // Get CREATE TABLE statement
    $createStmt = $mysql->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $createSql = $createStmt['Create Table'];
    
    // Convert MySQL syntax to SQLite
    $createSql = preg_replace('/AUTO_INCREMENT/i', 'AUTOINCREMENT', $createSql);
    $createSql = preg_replace('/ENGINE=\w+/i', '', $createSql);
    $createSql = preg_replace('/DEFAULT CHARSET=\w+/i', '', $createSql);
    $createSql = preg_replace('/COLLATE=\w+/i', '', $createSql);
    $createSql = preg_replace('/unsigned/i', '', $createSql);
    $createSql = preg_replace('/COMMENT\s+\'[^\']*\'/i', '', $createSql);
    $createSql = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $createSql);
    $createSql = preg_replace('/CURRENT_TIMESTAMP/i', 'CURRENT_TIMESTAMP', $createSql);
    
    // Remove foreign key constraints for SQLite
    $createSql = preg_replace('/,\s*CONSTRAINT.*?FOREIGN KEY.*?\)/', '', $createSql);
    $createSql = preg_replace('/,\s*FOREIGN KEY.*?\)/', '', $createSql);
    
    // Remove KEY definitions
    $createSql = preg_replace('/,\s*KEY\s+`[^`]+`\s*\([^)]+\)/', '', $createSql);
    $createSql = preg_replace('/,\s*UNIQUE KEY\s+`[^`]+`\s*\([^)]+\)/', '', $createSql);
    
    try {
        $sqlite->exec($createSql);
        echo "  ✅ Tabella creata\n";
    } catch (PDOException $e) {
        echo "  ⚠️  Errore creazione: " . $e->getMessage() . "\n";
        continue;
    }
    
    // Copy data
    $rows = $mysql->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        $columns = array_keys($rows[0]);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $columnList = '`' . implode('`, `', $columns) . '`';
        
        $insertSql = "INSERT INTO `$table` ($columnList) VALUES ($placeholders)";
        $stmt = $sqlite->prepare($insertSql);
        
        $imported = 0;
        foreach ($rows as $row) {
            try {
                $stmt->execute(array_values($row));
                $imported++;
            } catch (PDOException $e) {
                // Skip errors silently
            }
        }
        
        echo "  📊 Importate $imported righe\n";
    }
    
    echo "\n";
}

// Create users table if not exists
echo "👤 Creando tabella users...\n";
$sqlite->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    email_verified_at DATETIME,
    password TEXT NOT NULL,
    remember_token TEXT,
    created_at DATETIME,
    updated_at DATETIME
)");

// Create admin user
$sqlite->exec("DELETE FROM users WHERE email = 'admin@booking.local'");
$sqlite->exec("
INSERT INTO users (name, email, password, created_at, updated_at)
VALUES ('Admin', 'admin@booking.local', '\$2y\$12\$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyKuL4Oar3Oi', datetime('now'), datetime('now'))
");
echo "✅ Utente admin creato (password: password)\n\n";

echo "🎉 Importazione completata!\n";
echo "\n📝 Credenziali:\n";
echo "   Email: admin@booking.local\n";
echo "   Password: password\n";
