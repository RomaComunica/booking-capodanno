<?php

try {
    $db = new PDO('sqlite:database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Checking ALL ordini columns ===\n\n";

    // Get current columns
    $result = $db->query("PRAGMA table_info(ordini)");
    $columns = [];
    foreach ($result as $row) {
        $columns[] = $row['name'];
    }

    echo "Existing columns:\n";
    print_r($columns);

    echo "\n\n=== Columns used in OrderController::createOrder ===\n\n";

    $usedColumns = [
        'numeroOrdine',
        'dataOrdine',
        'idLocationOrdine',
        'IPcliente',
        'nomeClienteOrdine',
        'cognomeClienteOrdine',
        'emailClienteOrdine',
        'telefonoClienteOrdine',
        'indirizzoClienteOrdine',
        'capClienteOrdine',
        'cittaClienteOrdine',
        'provinciaClienteOrdine',
        'totaleOrdine',
        'tipoPagamentoOrdine',
        'tipoSpedizioneOrdine',
        'idPartnerOrdine',
        'ordineConfermato',
        'dataPagamentoOrdine',
        'statoOrdine',
        'dataPagamentoOrdineFornitore',
    ];

    echo "Checking each column:\n\n";
    foreach ($usedColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ $col\n";
        } else {
            echo "❌ MISSING: $col\n";
        }
    }

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
