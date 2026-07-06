<?php
/**
 * Script per creare manualmente la tabella cross_partner_ingressi
 * Eseguire con: php create_cross_partner_table.php
 */

$dbPath = __DIR__ . '/database/database.sqlite';

if (!file_exists($dbPath)) {
    die("Database SQLite non trovato: $dbPath\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connesso al database SQLite\n";
    
    // Drop table if exists
    $pdo->exec("DROP TABLE IF EXISTS cross_partner_ingressi");
    echo "Tabella esistente rimossa (se presente)\n";
    
    // Create table
    $sql = "
    CREATE TABLE cross_partner_ingressi (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        idTipologiaIngresso INTEGER NOT NULL,
        idPartnerIngressi INTEGER NOT NULL,
        idLocation INTEGER NOT NULL,
        quantitaIngressi INTEGER DEFAULT 0,
        quantitaIngressiTerra INTEGER DEFAULT 0,
        created_at DATETIME,
        updated_at DATETIME,
        UNIQUE(idTipologiaIngresso, idPartnerIngressi)
    )";
    
    $pdo->exec($sql);
    echo "✅ Tabella cross_partner_ingressi creata con successo!\n";
    
    // Verify
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='cross_partner_ingressi'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ Verifica: Tabella esiste nel database\n";
        
        // Show structure
        $stmt = $pdo->query("PRAGMA table_info(cross_partner_ingressi)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nStruttura tabella:\n";
        foreach ($columns as $col) {
            echo "  - {$col['name']} ({$col['type']})\n";
        }
    } else {
        echo "❌ Errore: Tabella non trovata dopo creazione\n";
    }
    
    // Insert migration record
    $pdo->exec("INSERT INTO migrations (migration, batch) VALUES ('2026_02_17_122933_create_cross_partner_ingressi_table', 1)");
    echo "\n✅ Record migration aggiunto\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Completato! La tabella è pronta per l'uso.\n";
