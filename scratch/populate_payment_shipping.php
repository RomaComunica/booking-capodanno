<?php

try {
    $db = new PDO('sqlite:database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Creating missing tables and populating data...\n\n";

    // Create tipo_pagamenti table
    $db->exec("CREATE TABLE IF NOT EXISTS tipo_pagamenti (
        idTipoPagamento INTEGER PRIMARY KEY,
        nomePagamento TEXT NOT NULL,
        descrizionePagamento TEXT
    )");
    echo "✓ Table tipo_pagamenti created/verified\n";

    // Create tipo_spedizione table
    $db->exec("CREATE TABLE IF NOT EXISTS tipo_spedizione (
        idSpedizione INTEGER PRIMARY KEY,
        nomeSpedizione TEXT NOT NULL,
        descrizioneSpedizione TEXT
    )");
    echo "✓ Table tipo_spedizione created/verified\n";

    // Create cross_pagamenti_location table
    $db->exec("CREATE TABLE IF NOT EXISTS cross_pagamenti_location (
        idLocationPagamento INTEGER NOT NULL,
        idPagamento INTEGER NOT NULL,
        pagamentoLocationAttivo INTEGER DEFAULT 1,
        costoPagamento REAL DEFAULT 0,
        PRIMARY KEY (idLocationPagamento, idPagamento)
    )");
    echo "✓ Table cross_pagamenti_location created/verified\n";

    // Create cross_spedizioni_location table
    $db->exec("CREATE TABLE IF NOT EXISTS cross_spedizioni_location (
        idLocationSpedizione INTEGER NOT NULL,
        idSpedizione INTEGER NOT NULL,
        spedizioneLocationAttiva INTEGER DEFAULT 1,
        costoSpedizione REAL DEFAULT 0,
        PRIMARY KEY (idLocationSpedizione, idSpedizione)
    )");
    echo "✓ Table cross_spedizioni_location created/verified\n\n";

    // Insert payment methods
    $paymentMethods = [
        [1, 'PayPal Express Checkout', 'Pagamento sicuro tramite PayPal'],
        [2, 'Bonifico Bancario', 'Bonifico bancario anticipato'],
        [3, 'Contanti', 'Pagamento in contanti al ritiro'],
        [4, 'Ricarica Postepay', 'Ricarica su carta Postepay'],
        [5, 'Carta di Credito (Nexi)', 'Pagamento con carta di credito tramite Nexi XPay'],
    ];

    $stmt = $db->prepare("INSERT OR REPLACE INTO tipo_pagamenti (idTipoPagamento, nomePagamento, descrizionePagamento) VALUES (?, ?, ?)");
    foreach ($paymentMethods as [$id, $nome, $desc]) {
        $stmt->execute([$id, $nome, $desc]);
        echo "✓ Payment method: $nome\n";
    }

    echo "\n";

    // Insert shipping methods
    $shippingMethods = [
        [1, 'Voucher via E-mail', 'Ricevi il voucher direttamente via email'],
        [2, 'Consegna a domicilio dentro GRA', 'Consegna a domicilio dentro il Grande Raccordo Anulare'],
        [3, 'Consegna a domicilio fuori GRA', 'Consegna a domicilio fuori dal Grande Raccordo Anulare'],
        [4, 'Ritiro in ufficio', 'Ritiro presso Via dei CARRARESI 12 - 00164 - Roma (9:30-17:30)'],
    ];

    $stmt = $db->prepare("INSERT OR REPLACE INTO tipo_spedizione (idSpedizione, nomeSpedizione, descrizioneSpedizione) VALUES (?, ?, ?)");
    foreach ($shippingMethods as [$id, $nome, $desc]) {
        $stmt->execute([$id, $nome, $desc]);
        echo "✓ Shipping method: $nome\n";
    }

    echo "\n";

    // Get all locations
    $result = $db->query("SELECT idLocation FROM location");
    if (!$result) {
        echo "Error querying locations\n";
        exit(1);
    }
    
    $locations = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($locations) . " locations\n\n";

    if (count($locations) == 0) {
        echo "No locations found. Skipping associations.\n";
        exit(0);
    }

    // Associate all payment methods with all locations
    $stmtPayment = $db->prepare("INSERT OR REPLACE INTO cross_pagamenti_location (idLocationPagamento, idPagamento, pagamentoLocationAttivo, costoPagamento) VALUES (?, ?, 1, 0)");
    $stmtShipping = $db->prepare("INSERT OR REPLACE INTO cross_spedizioni_location (idLocationSpedizione, idSpedizione, spedizioneLocationAttiva, costoSpedizione) VALUES (?, ?, 1, ?)");

    foreach ($locations as $locationId) {
        foreach ($paymentMethods as [$paymentId, $nome, $desc]) {
            $stmtPayment->execute([$locationId, $paymentId]);
        }

        foreach ($shippingMethods as [$shippingId, $nome, $desc]) {
            $cost = 0;
            if ($shippingId == 2) $cost = 10; // Inside GRA
            if ($shippingId == 3) $cost = 15; // Outside GRA
            $stmtShipping->execute([$locationId, $shippingId, $cost]);
        }
        
        echo "✓ Location ID $locationId: associated with all payment/shipping methods\n";
    }

    echo "\n✅ Done! All tables created and data populated successfully!\n";

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
