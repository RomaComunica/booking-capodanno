<?php
$db = new PDO('sqlite:database/test.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Fixing Payment & Shipping Tables ===\n\n";

// Drop all related tables
$db->exec("DROP TABLE IF EXISTS cross_pagamenti_location");
$db->exec("DROP TABLE IF EXISTS cross_spedizioni_location");
$db->exec("DROP TABLE IF EXISTS tipo_pagamenti");
$db->exec("DROP TABLE IF EXISTS tipo_pagamento");
$db->exec("DROP TABLE IF EXISTS tipo_spedizione");
echo "Dropped old tables\n";

// Create tipo_pagamenti with ALL columns the app uses
$db->exec("CREATE TABLE tipo_pagamenti (
    idTipoPagamento INTEGER PRIMARY KEY AUTOINCREMENT,
    nomePagamento TEXT NOT NULL DEFAULT '',
    descrizionePagamento TEXT DEFAULT '',
    descrizioneTipoPagamento TEXT DEFAULT '',
    attivo INTEGER DEFAULT 1
)");
echo "Created tipo_pagamenti\n";

// Create tipo_spedizione with ALL columns the app uses
$db->exec("CREATE TABLE tipo_spedizione (
    idSpedizione INTEGER PRIMARY KEY AUTOINCREMENT,
    nomeSpedizione TEXT NOT NULL DEFAULT '',
    descrizioneSpedizione TEXT DEFAULT '',
    testoAggiuntivo TEXT DEFAULT '',
    attivo INTEGER DEFAULT 1
)");
echo "Created tipo_spedizione\n";

// Create cross tables
$db->exec("CREATE TABLE cross_pagamenti_location (
    idLocationPagamento INTEGER NOT NULL,
    idPagamento INTEGER NOT NULL,
    pagamentoLocationAttivo INTEGER DEFAULT 1,
    costoPagamento REAL DEFAULT 0,
    PRIMARY KEY (idLocationPagamento, idPagamento)
)");
echo "Created cross_pagamenti_location\n";

$db->exec("CREATE TABLE cross_spedizioni_location (
    idLocationSpedizione INTEGER NOT NULL,
    idSpedizione INTEGER NOT NULL,
    spedizioneLocationAttiva INTEGER DEFAULT 1,
    costoSpedizione REAL DEFAULT 0,
    PRIMARY KEY (idLocationSpedizione, idSpedizione)
)");
echo "Created cross_spedizioni_location\n";

// Insert payment methods
$db->exec("INSERT INTO tipo_pagamenti (idTipoPagamento, nomePagamento, descrizionePagamento, descrizioneTipoPagamento, attivo)
    VALUES (1, 'PayPal Express Checkout', 'Pagamento sicuro tramite PayPal', 'PayPal', 1)");
$db->exec("INSERT INTO tipo_pagamenti (idTipoPagamento, nomePagamento, descrizionePagamento, descrizioneTipoPagamento, attivo)
    VALUES (2, 'Bonifico Bancario', 'Bonifico bancario anticipato', 'Bonifico Bancario', 1)");
$db->exec("INSERT INTO tipo_pagamenti (idTipoPagamento, nomePagamento, descrizionePagamento, descrizioneTipoPagamento, attivo)
    VALUES (3, 'Contanti', 'Pagamento in contanti al ritiro', 'Contanti', 1)");
$db->exec("INSERT INTO tipo_pagamenti (idTipoPagamento, nomePagamento, descrizionePagamento, descrizioneTipoPagamento, attivo)
    VALUES (5, 'Carta di Credito (Nexi)', 'Pagamento con carta di credito tramite Nexi XPay', 'Carta di Credito (Nexi)', 1)");
echo "\nInserted 4 payment methods: PayPal, Bonifico, Contanti, Nexi\n";

// Insert shipping methods
$db->exec("INSERT INTO tipo_spedizione (idSpedizione, nomeSpedizione, descrizioneSpedizione, testoAggiuntivo, attivo)
    VALUES (1, 'Voucher via E-mail', 'Voucher via E-mail', 'Ricevi il voucher direttamente via email', 1)");
$db->exec("INSERT INTO tipo_spedizione (idSpedizione, nomeSpedizione, descrizioneSpedizione, testoAggiuntivo, attivo)
    VALUES (4, 'Ritiro in sede', 'Ritiro in sede', 'Ritiro presso Via dei Carraresi 12 - 00164 - Roma (9:30-17:30)', 1)");
echo "Inserted 2 shipping methods: Email, Ritiro in sede\n";

// Associate ALL locations
$result = $db->query("SELECT idLocation FROM location");
$locations = $result->fetchAll(PDO::FETCH_COLUMN);
echo "\nAssociating " . count($locations) . " locations...\n";

$paymentIds = [1, 2, 3, 5];
$shippingIds = [1, 4];

foreach ($locations as $locationId) {
    foreach ($paymentIds as $pid) {
        $db->exec("INSERT INTO cross_pagamenti_location VALUES ($locationId, $pid, 1, 0)");
    }
    foreach ($shippingIds as $sid) {
        $db->exec("INSERT INTO cross_spedizioni_location VALUES ($locationId, $sid, 1, 0)");
    }
}

echo "\nDone! Verifying:\n";
$count = $db->query("SELECT COUNT(*) FROM tipo_pagamenti")->fetchColumn();
echo "  Pagamenti: $count\n";
$count = $db->query("SELECT COUNT(*) FROM tipo_spedizione")->fetchColumn();
echo "  Spedizioni: $count\n";
$count = $db->query("SELECT COUNT(*) FROM cross_pagamenti_location")->fetchColumn();
echo "  Cross pagamenti: $count\n";
$count = $db->query("SELECT COUNT(*) FROM cross_spedizioni_location")->fetchColumn();
echo "  Cross spedizioni: $count\n";
