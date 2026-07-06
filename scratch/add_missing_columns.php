<?php

try {
    $db = new PDO('sqlite:database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Adding missing columns to ordini table ===\n\n";

    $columnsToAdd = [
        'IPcliente' => 'VARCHAR(50)',
        'indirizzoClienteOrdine' => 'VARCHAR(255)',
        'capClienteOrdine' => 'VARCHAR(10)',
        'cittaClienteOrdine' => 'VARCHAR(100)',
        'provinciaClienteOrdine' => 'VARCHAR(2)',
        'ordineConfermato' => 'INTEGER DEFAULT 0',
        'dataPagamentoOrdineFornitore' => 'DATETIME',
    ];

    foreach ($columnsToAdd as $column => $type) {
        try {
            $sql = "ALTER TABLE ordini ADD COLUMN $column $type";
            $db->exec($sql);
            echo "✅ Added: $column ($type)\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate column name') !== false) {
                echo "⚠️  Already exists: $column\n";
            } else {
                throw $e;
            }
        }
    }

    echo "\n✅ All columns added successfully!\n";

    // Test insert again
    echo "\n=== Testing INSERT again ===\n\n";
    
    $sql = "INSERT INTO ordini (
        numeroOrdine, dataOrdine, idLocationOrdine, IPcliente,
        nomeClienteOrdine, cognomeClienteOrdine, emailClienteOrdine, telefonoClienteOrdine,
        indirizzoClienteOrdine, capClienteOrdine, cittaClienteOrdine, provinciaClienteOrdine,
        totaleOrdine, tipoPagamentoOrdine, tipoSpedizioneOrdine, idPartnerOrdine,
        ordineConfermato, dataPagamentoOrdine, statoOrdine, dataPagamentoOrdineFornitore
    ) VALUES (
        999, datetime('now'), 1, '127.0.0.1',
        'Test', 'Test', 'test@test.com', '1234567890',
        '', '', '', '',
        100.0, 2, 1, 1,
        1, datetime('now'), 2, NULL
    )";

    $db->exec($sql);
    echo "✅ Test INSERT successful!\n";
    
    $db->exec("DELETE FROM ordini WHERE numeroOrdine = 999");
    echo "✅ Test record deleted\n";
    
    echo "\n🎉 Database is ready for order creation!\n";

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
