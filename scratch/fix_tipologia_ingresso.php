<?php
/**
 * Add giorniPrimaIngresso column to tipologia_ingresso table
 */

$dbPath = __DIR__ . '/database/database.sqlite';

if (!file_exists($dbPath)) {
    die("❌ Database non trovato\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database SQLite\n";
    
    // Check if column exists
    $columns = $pdo->query("PRAGMA table_info(tipologia_ingresso)")->fetchAll(PDO::FETCH_ASSOC);
    $exists = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'giorniPrimaIngresso') {
            $exists = true;
            break;
        }
    }
    
    if (!$exists) {
        echo "➕ Aggiungo colonna giorniPrimaIngresso...\n";
        $pdo->exec("ALTER TABLE tipologia_ingresso ADD COLUMN giorniPrimaIngresso INTEGER DEFAULT 0");
        echo "✅ Colonna aggiunta\n";
    } else {
        echo "ℹ️ Colonna già presente\n";
    }
    
    echo "🎉 Fix completato!\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
