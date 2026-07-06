<?php

try {
    $dbPath = __DIR__ . '/database/test.sqlite';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Dropping Existing Tables ===\n\n";

    // Drop existing tables to recreate them fresh
    $db->exec("DROP TABLE IF EXISTS cross_pagamenti_location");
    echo "✓ Dropped cross_pagamenti_location\n";
    
    $db->exec("DROP TABLE IF EXISTS cross_spedizioni_location");
    echo "✓ Dropped cross_spedizioni_location\n";
    
    $db->exec("DROP TABLE IF EXISTS tipo_pagamenti");
    echo "✓ Dropped tipo_pagamenti\n";
    
    $db->exec("DROP TABLE IF EXISTS tipo_spedizione");
    echo "✓ Dropped tipo_spedizione\n";

    echo "\n=== Creating Fresh Tables ===\n\n";

    // Create tipo_pagamenti table
    $db->exec("CREATE TABLE tipo_pagamenti (
        idTipoPagamento INTEGER PRIMARY KEY,
        nomePagamento TEXT NOT NULL,
        descrizionePagamento TEXT
    )");
    echo "✓ Created tipo_pagamenti\n";

    // Create tipo_spedizione table
    $db->exec("CREATE TABLE tipo_spedizione (
        idSpedizione INTEGER PRIMARY KEY,
        nomeSpedizione TEXT NOT NULL,
        descrizioneSpedizione TEXT
    )");
    echo "✓ Created tipo_spedizione\n";

    // Create cross_pagamenti_location table
    $db->exec("CREATE TABLE cross_pagamenti_location (
        idLocationPagamento INTEGER NOT NULL,
        idPagamento INTEGER NOT NULL,
        pagamentoLocationAttivo INTEGER DEFAULT 1,
        costoPagamento REAL DEFAULT 0,
        PRIMARY KEY (idLocationPagamento, idPagamento)
    )");
    echo "✓ Created cross_pagamenti_location\n";

    // Create cross_spedizioni_location table
    $db->exec("CREATE TABLE cross_spedizioni_location (
        idLocationSpedizione INTEGER NOT NULL,
        idSpedizione INTEGER NOT NULL,
        spedizioneLocationAttiva INTEGER DEFAULT 1,
        costoSpedizione REAL DEFAULT 0,
        PRIMARY KEY (idLocationSpedizione, idSpedizione)
    )");
    echo "✓ Created cross_spedizioni_location\n";

    echo "\n=== Inserting Payment Methods ===\n\n";

    // Insert payment methods (NO Postepay)
    $db->exec("INSERT INTO tipo_pagamenti VALUES (1, 'PayPal Express Checkout', 'Pagamento sicuro tramite PayPal')");
    $db->exec("INSERT INTO tipo_pagamenti VALUES (2, 'Bonifico Bancario', 'Bonifico bancario anticipato')");
    $db->exec("INSERT INTO tipo_pagamenti VALUES (3, 'Contanti', 'Pagamento in contanti al ritiro')");
    $db->exec("INSERT INTO tipo_pagamenti VALUES (5, 'Carta di Credito (Nexi)', 'Pagamento con carta di credito tramite Nexi XPay')");
    
    echo "✓ PayPal Express Checkout\n";
    echo "✓ Bonifico Bancario\n";
    echo "✓ Contanti\n";
    echo "✓ Carta di Credito (Nexi)\n";

    echo "\n=== Inserting Shipping Methods ===\n\n";

    // Insert shipping methods (NO home delivery)
    $db->exec("INSERT INTO tipo_spedizione VALUES (1, 'Voucher via E-mail', 'Ricevi il voucher direttamente via email')");
    $db->exec("INSERT INTO tipo_spedizione VALUES (4, 'Ritiro in sede', 'Ritiro presso Via dei CARRARESI 12 - 00164 - Roma (9:30-17:30)')");
    
    echo "✓ Voucher via E-mail\n";
    echo "✓ Ritiro in sede\n";

    echo "\n=== Associating with Locations ===\n\n";

    // Get all locations
    $result = $db->query("SELECT idLocation FROM location");
    $locations = $result ? $result->fetchAll(PDO::FETCH_COLUMN) : [];
    
    echo "Found " . count($locations) . " locations\n\n";

    if (count($locations) > 0) {
        $paymentIds = [1, 2, 3, 5]; // PayPal, Bonifico, Contanti, Nexi
        $shippingIds = [1, 4]; // Email, Ritiro in sede

        foreach ($locations as $locationId) {
            // Associate payment methods (all free)
            foreach ($paymentIds as $pid) {
                $db->exec("INSERT INTO cross_pagamenti_location VALUES ($locationId, $pid, 1, 0)");
            }

            // Associate shipping methods
            $db->exec("INSERT INTO cross_spedizioni_location VALUES ($locationId, 1, 1, 0)");    // Email - Free
            $db->exec("INSERT INTO cross_spedizioni_location VALUES ($locationId, 4, 1, 0)");    // Ritiro - Free
            
            echo "✓ Location ID $locationId configured\n";
        }
    }

    echo "\n=== Summary ===\n\n";
    echo "✅ 4 tables created\n";
    echo "✅ 4 payment methods added\n";
    echo "✅ 2 shipping methods added\n";
    echo "✅ " . count($locations) . " locations configured\n";
    echo "\n🎉 Database ready for testing!\n";

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
