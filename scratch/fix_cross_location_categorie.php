<?php
/**
 * Create cross_location_categorie table
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
    echo "🔗 Creando tabella cross_location_categorie...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS cross_location_categorie (
        idLocation INTEGER NOT NULL,
        idCatLocation INTEGER NOT NULL,
        PRIMARY KEY (idLocation, idCatLocation)
    )");
    echo "  ✅ Tabella creata\n\n";
    
    // Insert sample data
    echo "📥 Inserendo dati di esempio...\n";
    // Location 1 -> Categoria 1 (Ristoranti)
    $pdo->exec("INSERT OR IGNORE INTO cross_location_categorie (idLocation, idCatLocation) VALUES (1, 1)");
    // Location 2 -> Categoria 2 (Club)
    $pdo->exec("INSERT OR IGNORE INTO cross_location_categorie (idLocation, idCatLocation) VALUES (2, 2)");
    
    echo "  ✅ Dati inseriti\n\n";
    echo "🎉 Fix database completato!\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
