<?php
/**
 * Fix tipo_spedizione table schema to match Model
 */

$dbPath = __DIR__ . '/database/database.sqlite';

if (!file_exists($dbPath)) {
    die("❌ Database non trovato\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database SQLite\n\n";
    
    // Drop existing table
    echo "🗑️ Ricreando tabella tipo_spedizione...\n";
    $pdo->exec("DROP TABLE IF EXISTS tipo_spedizione");
    
    // Create with correct column names matching Model
    // Model says parentKey = 'idSpedizione'
    $pdo->exec("
    CREATE TABLE tipo_spedizione (
        idSpedizione INTEGER PRIMARY KEY AUTOINCREMENT,
        nomeSpedizione TEXT NOT NULL,
        costoSpedizione REAL DEFAULT 0,
        attivoSpedizione INTEGER DEFAULT 1,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    // Insert initial data
    $pdo->exec("INSERT INTO tipo_spedizione (idSpedizione, nomeSpedizione, costoSpedizione) VALUES 
        (1, 'Email', 0),
        (2, 'Corriere', 5.00),
        (3, 'Ritiro in sede', 0)");
        
    echo "  ✅ Tabella tipo_spedizione corretta creata\n\n";
    
    // Check columns
    $stmt = $pdo->query("PRAGMA table_info(tipo_spedizione)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo "  - {$col['name']}\n";
    }
    
    echo "\n🎉 Fix completato! Ora Ordini dovrebbe funzionare.\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
