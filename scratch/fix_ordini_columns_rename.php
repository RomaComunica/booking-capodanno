<?php
/**
 * Fix Ordini Columns - Rename to match ASP compatibility
 */

$dbPath = __DIR__ . '/database/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database SQLite\n";
    
    // Check if we need to rename columns
    $cols = $pdo->query("PRAGMA table_info(ordini)")->fetchAll(PDO::FETCH_ASSOC);
    $hasOldNames = false;
    foreach ($cols as $c) {
        if ($c['name'] === 'emailOrdine') {
            $hasOldNames = true;
            break;
        }
    }
    
    if ($hasOldNames) {
        echo "🔧 Rinomino colonne per compatibilità...\n";
        
        $pdo->beginTransaction();
        
        // SQLite doesn't support multiple RENAME COLUMN in one statement easily in all versions, 
        // but recent versions do. strict transaction.
        
        $renames = [
            'emailOrdine' => 'emailClienteOrdine',
            'nomeOrdine' => 'nomeClienteOrdine',
            'cognomeOrdine' => 'cognomeClienteOrdine',
            'telefonoOrdine' => 'telefonoClienteOrdine',
            'indirizzoOrdine' => 'indirizzoClienteOrdine',
            'cittaOrdine' => 'cittaClienteOrdine',
            'capOrdine' => 'capClienteOrdine',
            'provinciaOrdine' => 'provinciaClienteOrdine',
        ];
        
        foreach ($renames as $old => $new) {
            // Check if column exists before renaming
            $exists = false;
            foreach ($cols as $c) {
                if ($c['name'] === $old) $exists = true;
            }
            
            if ($exists) {
                $pdo->exec("ALTER TABLE ordini RENAME COLUMN $old TO $new");
                echo "  ✅ $old -> $new\n";
            }
        }
        
        $pdo->commit();
        echo "✅ Colonne rinominate\n";
        
        // Recreate View
        echo "🔄 Ricreo la vista clienti_view...\n";
        $pdo->exec("DROP VIEW IF EXISTS clienti_view");
        $sql = "
        CREATE VIEW clienti_view AS
        SELECT 
            emailClienteOrdine as email,
            MAX(nomeClienteOrdine) as nome,
            MAX(cognomeClienteOrdine) as cognome,
            MAX(telefonoClienteOrdine) as telefono,
            COUNT(numeroOrdine) as numero_ordini,
            SUM(totaleOrdine) as totale_speso,
            MAX(dataOrdine) as ultimo_ordine
        FROM ordini
        WHERE emailClienteOrdine IS NOT NULL AND emailClienteOrdine != ''
        GROUP BY emailClienteOrdine
        ";
        $pdo->exec($sql);
        echo "✅ Vista ricreata\n";
        
    } else {
        echo "ℹ️ Colonne già corrette\n";
    }
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
