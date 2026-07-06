<?php
/**
 * Create cross_location_tipologie table
 */

$dbPath = __DIR__ . '/database/database.sqlite';

if (!file_exists($dbPath)) {
    die("❌ Database non trovato\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database SQLite\n\n";
    
    // Create table
    echo "🔗 Creando tabella cross_location_tipologie...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS cross_location_tipologie (
        idLocation INTEGER NOT NULL,
        idTipLocation INTEGER NOT NULL,
        PRIMARY KEY (idLocation, idTipLocation)
    )");
    echo "  ✅ Tabella creata\n\n";
    
    // Insert sample data
    echo "📥 Inserendo dati di esempio...\n";
    // Location 1 -> Tipologia 1 (Discoteca)
    $pdo->exec("INSERT OR IGNORE INTO cross_location_tipologie (idLocation, idTipLocation) VALUES (1, 1)");
    // Location 2 -> Tipologia 2 (Ristorante)
    $pdo->exec("INSERT OR IGNORE INTO cross_location_tipologie (idLocation, idTipLocation) VALUES (2, 2)");
    
    echo "  ✅ Dati inseriti\n\n";
    echo "🎉 Fix database completato!\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
