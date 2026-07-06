<?php
/**
 * Create Clienti View manually
 */

$dbPath = __DIR__ . '/database/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database SQLite\n";
    
    // Drop view if exists
    $pdo->exec("DROP VIEW IF EXISTS clienti_view");
    echo "🗑️  Vista precedente rimossa (se esistente)\n";
    
    // Create view
    $sql = "
    CREATE VIEW clienti_view AS
    SELECT 
        emailClienteOrdine as email,
        MAX(nomeClienteOrdine) as nome,
        MAX(cognomeClienteOrdine) as cognome,
        MAX(telefonoClienteOrdine) as telefono,
        COUNT(idordine) as numero_ordini,
        SUM(totaleOrdine) as totale_speso,
        MAX(dataOrdine) as ultimo_ordine
    FROM ordini
    WHERE emailClienteOrdine IS NOT NULL AND emailClienteOrdine != ''
    GROUP BY emailClienteOrdine
    ";
    
    $pdo->exec($sql);
    echo "✅ Vista `clienti_view` creata correttamente\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
