<?php
/**
 * Add missing columns to ordini table
 */

$dbPath = __DIR__ . '/database/database.sqlite';

if (!file_exists($dbPath)) {
    die("❌ Database non trovato\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database SQLite\n\n";
    
    // Check if column exists
    $columns = $pdo->query("PRAGMA table_info(ordini)")->fetchAll(PDO::FETCH_ASSOC);
    $hasIdSpedizione = false;
    
    foreach ($columns as $col) {
        if ($col['name'] === 'idSpedizione') {
            $hasIdSpedizione = true;
            break;
        }
    }
    
    if (!$hasIdSpedizione) {
        echo "➕ Aggiungendo colonna idSpedizione a tabella ordini...\n";
        $pdo->exec("ALTER TABLE ordini ADD COLUMN idSpedizione INTEGER DEFAULT 1");
        echo "  ✅ Colonna idSpedizione aggiunta\n\n";
    } else {
        echo "✅ Colonna idSpedizione già presente\n\n";
    }
    
    // Add other potentially missing columns
    $columnsToAdd = [
        'indirizzoOrdine' => 'TEXT',
        'cittaOrdine' => 'TEXT',
        'capOrdine' => 'TEXT',
        'provinciaOrdine' => 'TEXT',
        'noteOrdine' => 'TEXT',
        'codiceFiscaleOrdine' => 'TEXT',
    ];
    
    foreach ($columnsToAdd as $columnName => $columnType) {
        $hasColumn = false;
        foreach ($columns as $col) {
            if ($col['name'] === $columnName) {
                $hasColumn = true;
                break;
            }
        }
        
        if (!$hasColumn) {
            echo "➕ Aggiungendo colonna $columnName...\n";
            $pdo->exec("ALTER TABLE ordini ADD COLUMN $columnName $columnType");
            echo "  ✅ Colonna $columnName aggiunta\n";
        }
    }
    
    echo "\n🎉 Tabella ordini aggiornata!\n";
    echo "✅ Ora riprova ad accedere alla sezione Ordini\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
