<?php

try {
    $db = new PDO('sqlite:database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Testing INSERT with all fields ===\n\n";

    // Try to insert with all fields from createOrder
    $sql = "INSERT INTO ordini (
        numeroOrdine,
        dataOrdine,
        idLocationOrdine,
        IPcliente,
        nomeClienteOrdine,
        cognomeClienteOrdine,
        emailClienteOrdine,
        telefonoClienteOrdine,
        indirizzoClienteOrdine,
        capClienteOrdine,
        cittaClienteOrdine,
        provinciaClienteOrdine,
        totaleOrdine,
        tipoPagamentoOrdine,
        tipoSpedizioneOrdine,
        idPartnerOrdine,
        ordineConfermato,
        dataPagamentoOrdine,
        statoOrdine,
        dataPagamentoOrdineFornitore
    ) VALUES (
        999,
        datetime('now'),
        1,
        '127.0.0.1',
        'Test',
        'Test',
        'test@test.com',
        '1234567890',
        '',
        '',
        '',
        '',
        100.0,
        2,
        1,
        1,
        1,
        datetime('now'),
        2,
        NULL
    )";

    $db->exec($sql);
    echo "✅ INSERT successful!\n";
    
    // Delete test record
    $db->exec("DELETE FROM ordini WHERE numeroOrdine = 999");
    echo "✅ Test record deleted\n";

} catch (PDOException $e) {
    echo "\n❌ INSERT failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    // Show which column is missing
    if (strpos($e->getMessage(), 'has no column named') !== false) {
        preg_match('/has no column named (.+)/', $e->getMessage(), $matches);
        if (isset($matches[1])) {
            echo "Missing column: " . $matches[1] . "\n";
        }
    }
}
