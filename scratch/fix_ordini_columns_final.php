<?php
/**
 * Fix Ordini Columns - FINAL ATTEMPT
 * Drop View -> Rename Columns -> Recreate View
 */

$dbPath = __DIR__ . '/database/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database SQLite\n";
    
    // 1. Drop existing view to avoid dependencies
    $pdo->exec("DROP VIEW IF EXISTS clienti_view");
    echo "🗑️  Vista rimossa\n";

    // 2. Get current columns
    $cols = $pdo->query("PRAGMA table_info(ordini)")->fetchAll(PDO::FETCH_ASSOC);
    $existingCols = array_column($cols, 'name');
    
    // Map of Old -> New names
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
        if (in_array($old, $existingCols)) {
            echo "🔄 Rinomino $old -> $new... ";
            try {
                $pdo->exec("ALTER TABLE ordini RENAME COLUMN $old TO $new");
                echo "✅ OK\n";
            } catch (PDOException $e) {
                echo "❌ Errore ignoto: " . $e->getMessage() . "\n";
            }
        } else {
            echo "ℹ️ Colonna $old non trovata (probabilmente già rinominata)\n";
        }
    }
    
    // 3. Recreate View
    echo "🔄 Ricreo la vista clienti_view...\n";
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
    
    echo "🎉 Fix completato!\n";
    
} catch (PDOException $e) {
    echo "❌ Errore Fatale: " . $e->getMessage() . "\n";
    exit(1);
}
