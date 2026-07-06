<?php
/**
 * Recreate ordini table with complete schema
 */

$dbPath = __DIR__ . '/database/database.sqlite';

if (!file_exists($dbPath)) {
    die("❌ Database non trovato\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database SQLite\n\n";
    
    // Backup existing data
    echo "💾 Backup dati esistenti...\n";
    $existingOrders = $pdo->query("SELECT * FROM ordini")->fetchAll(PDO::FETCH_ASSOC);
    echo "  ✅ " . count($existingOrders) . " ordini salvati\n\n";
    
    // Drop and recreate table
    echo "🗑️ Ricreando tabella ordini...\n";
    $pdo->exec("DROP TABLE IF EXISTS ordini");
    
    $pdo->exec("
    CREATE TABLE ordini (
        numeroOrdine INTEGER PRIMARY KEY AUTOINCREMENT,
        idLocationOrdine INTEGER,
        idPartnerOrdine INTEGER,
        nomeOrdine TEXT,
        cognomeOrdine TEXT,
        emailOrdine TEXT,
        telefonoOrdine TEXT,
        indirizzoOrdine TEXT,
        cittaOrdine TEXT,
        capOrdine TEXT,
        provinciaOrdine TEXT,
        codiceFiscaleOrdine TEXT,
        totaleOrdine REAL DEFAULT 0,
        statoOrdine INTEGER DEFAULT 0,
        dataOrdine DATETIME,
        dataPagamentoOrdine DATETIME,
        tipoPagamentoOrdine INTEGER,
        idSpedizione INTEGER DEFAULT 1,
        noteOrdine TEXT,
        created_at DATETIME,
        updated_at DATETIME
    )");
    echo "  ✅ Tabella ordini ricreata con schema completo\n\n";
    
    // Restore data
    if (count($existingOrders) > 0) {
        echo "♻️ Ripristinando dati...\n";
        
        foreach ($existingOrders as $order) {
            $columns = [];
            $values = [];
            $placeholders = [];
            
            foreach ($order as $key => $value) {
                $columns[] = $key;
                $values[] = $value;
                $placeholders[] = '?';
            }
            
            // Add idSpedizione if not present
            if (!isset($order['idSpedizione'])) {
                $columns[] = 'idSpedizione';
                $values[] = 1;
                $placeholders[] = '?';
            }
            
            $sql = "INSERT INTO ordini (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        }
        
        echo "  ✅ " . count($existingOrders) . " ordini ripristinati\n\n";
    }
    
    // Verify schema
    echo "🔍 Verifica schema:\n";
    $columns = $pdo->query("PRAGMA table_info(ordini)")->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['numeroOrdine', 'idSpedizione', 'tipoPagamentoOrdine', 'statoOrdine'];
    foreach ($requiredColumns as $col) {
        $found = false;
        foreach ($columns as $c) {
            if ($c['name'] === $col) {
                $found = true;
                echo "  ✅ $col presente\n";
                break;
            }
        }
        if (!$found) {
            echo "  ❌ $col MANCANTE!\n";
        }
    }
    
    echo "\n🎉 Tabella ordini completata!\n";
    echo "✅ Ora pulisci la cache e riprova\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
