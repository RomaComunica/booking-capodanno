<?php
try {
    $dbPath = __DIR__ . '/database/database_v2.sqlite';
    echo "Reconstructing database at $dbPath\n";
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = OFF");

    // 1. Load Laravel base schema
    $laravelSql = file_get_contents(__DIR__ . '/reconstruct_schema.sql');
    if ($laravelSql) {
        // Split by double newline or semicolon? The file has ";\n\n"
        $statements = explode(";\n\n", $laravelSql);
        foreach ($statements as $stmt) {
            if (trim($stmt)) {
                $pdo->exec($stmt);
            }
        }
        echo "Laravel schema loaded.\n";
    }

    // 2. Application Schema
    echo "Creating Application tables...\n";

    // Categorie Location
    $pdo->exec("CREATE TABLE IF NOT EXISTS categorie_location (
        idCatLocation INTEGER PRIMARY KEY AUTOINCREMENT, 
        nomeCatLocation TEXT
    )");

    // Tipologie Location
    $pdo->exec("CREATE TABLE IF NOT EXISTS tipologie_location (
        idTipLocation INTEGER PRIMARY KEY AUTOINCREMENT, 
        nomeTipLocation TEXT
    )");

    // Location
    $pdo->exec("CREATE TABLE IF NOT EXISTS location (
        idLocation INTEGER PRIMARY KEY AUTOINCREMENT, 
        nomeLocation TEXT, 
        indirizzoLocation TEXT, 
        telefonoLocation TEXT, 
        emailLocation TEXT, 
        riferimentoLocation TEXT, 
        telefonoRifLocation TEXT, 
        emailRifLocation TEXT, 
        attivaLocation INTEGER DEFAULT 1,
        slugLocation TEXT,
        descrizioneLocation TEXT,
        capienzaLocation INTEGER,
        immagineLocation TEXT
    )");
    
    // Cross Location Categorie
    $pdo->exec("CREATE TABLE IF NOT EXISTS cross_location_categorie (
        idLocation INTEGER, 
        idCatLocation INTEGER, 
        PRIMARY KEY (idLocation, idCatLocation)
    )");

    // Cross Location Tipologie
    $pdo->exec("CREATE TABLE IF NOT EXISTS cross_location_tipologie (
        idLocation INTEGER, 
        idTipLocation INTEGER, 
        PRIMARY KEY (idLocation, idTipLocation)
    )");

    // Tipo Pagamenti (if not in reconstruction file, but it was)
    $pdo->exec("CREATE TABLE IF NOT EXISTS tipo_pagamenti (
        idTipoPagamento INTEGER PRIMARY KEY AUTOINCREMENT, 
        descrizioneTipoPagamento TEXT, 
        attivo INTEGER DEFAULT 1
    )");

    // Tipo Spedizione
    $pdo->exec("CREATE TABLE IF NOT EXISTS tipo_spedizione (
        idSpedizione INTEGER PRIMARY KEY AUTOINCREMENT, 
        descrizioneSpedizione TEXT, 
        costoSpedizione DECIMAL(10,2) DEFAULT 0, 
        attivo INTEGER DEFAULT 1
    )");


    // Cross Pagamenti Location (THE FIX)
    $pdo->exec("CREATE TABLE IF NOT EXISTS cross_pagamenti_location (
        idLocationPagamento INTEGER, 
        idPagamento INTEGER, 
        pagamentoLocationAttivo INTEGER DEFAULT 1, 
        costoPagamento DECIMAL(10,2), 
        PRIMARY KEY (idLocationPagamento, idPagamento)
    )");

    // Cross Spedizioni Location
    $pdo->exec("CREATE TABLE IF NOT EXISTS cross_spedizioni_location (
        idLocationSpedizione INTEGER, 
        idSpedizione INTEGER, 
        spedizioneLocationAttiva INTEGER DEFAULT 1,
        costoSpedizione DECIMAL(10,2), 
        PRIMARY KEY (idLocationSpedizione, idSpedizione)
    )");

    // Tipologia Ingresso
    $pdo->exec("DROP TABLE IF EXISTS tipologia_ingresso");
    $pdo->exec("CREATE TABLE tipologia_ingresso (
        idIngressoCont INTEGER PRIMARY KEY AUTOINCREMENT, 
        idIngresso INTEGER, 
        idLocation INTEGER, 
        nomeIngresso TEXT, 
        prezzoIngresso DECIMAL(10,2) DEFAULT 0, 
        ordine INTEGER DEFAULT 0, 
        giorniPrimaIngresso INTEGER DEFAULT 0,
        giacenzaBiglietti INTEGER,
        descrizioneIngresso TEXT,
        tipoProvvigione INTEGER DEFAULT 0,
        valoreProvvigione DECIMAL(10,2) DEFAULT 0,
        valoreProvvigioneBonus DECIMAL(10,2) DEFAULT 0,
        tipoProvvigionePartner INTEGER DEFAULT 0,
        valoreProvvigionePartner DECIMAL(10,2) DEFAULT 0,
        valoreProvvigioneBonusPartner DECIMAL(10,2) DEFAULT 0,
        ingressoAttivo INTEGER DEFAULT 1,
        ingressoSenzaData INTEGER DEFAULT 0,
        dataIngresso DATETIME NULL,
        orarioIngresso TEXT NULL,
        etichettaIngresso TEXT NULL,
        opzioneRimborso INTEGER DEFAULT 0,
        rimborsoValorePersona DECIMAL(10,2) DEFAULT 0,
        rimborsoDataScadenza DATETIME NULL,
        rimborsoTesto TEXT NULL
    )");

    // Partner
    $pdo->exec("CREATE TABLE IF NOT EXISTS partner (
        idPartner INTEGER PRIMARY KEY AUTOINCREMENT, 
        nomePartner TEXT, 
        tipoPartner TEXT, 
        emailPartner TEXT, 
        telefonoPartner TEXT, 
        urlPartner TEXT, 
        attivoPartner INTEGER DEFAULT 1, 
        codicePartner TEXT,
        passwordPartner TEXT
    )");

    // Cross Partner Ingressi
    $pdo->exec("DROP TABLE IF EXISTS cross_partner_ingressi");
    $pdo->exec("CREATE TABLE cross_partner_ingressi (
        idPartnerIngressi INTEGER, 
        idTipologiaIngresso INTEGER, 
        quantitaIngressi INTEGER DEFAULT 0, 
        quantitaIngressiTerra INTEGER DEFAULT 0,
        idLocation INTEGER, 
        created_at DATETIME,
        updated_at DATETIME,
        PRIMARY KEY (idPartnerIngressi, idTipologiaIngresso)
    )");
    
    // Ordini
    $pdo->exec("CREATE TABLE IF NOT EXISTS ordini (
        numeroOrdine INTEGER PRIMARY KEY, 
        dataOrdine DATETIME, 
        idLocationOrdine INTEGER, 
        IPcliente TEXT, 
        nomeClienteOrdine TEXT, 
        cognomeClienteOrdine TEXT, 
        emailClienteOrdine TEXT, 
        telefonoClienteOrdine TEXT, 
        indirizzoClienteOrdine TEXT, 
        capClienteOrdine TEXT, 
        cittaClienteOrdine TEXT, 
        provinciaClienteOrdine TEXT, 
        totaleOrdine DECIMAL(10,2), 
        tipoPagamentoOrdine INTEGER, 
        tipoSpedizioneOrdine INTEGER, 
        idPartnerOrdine INTEGER, 
        ordineConfermato INTEGER DEFAULT 0, 
        dataPagamentoOrdine DATETIME, 
        statoOrdine INTEGER DEFAULT 0, 
        dataPagamentoOrdineFornitore DATETIME,
        noteOrdine TEXT,
        dataPagamentoOrdinePartner DATETIME
    )");

    // Righe Ordine
    $pdo->exec("CREATE TABLE IF NOT EXISTS righe_ordine (
        idRigaOrdine INTEGER PRIMARY KEY AUTOINCREMENT, 
        numeroOrdine INTEGER, 
        idIngressoOrdine INTEGER, 
        idPartnerOrdine INTEGER, 
        quantitaIngressoOrdine INTEGER, 
        prezzoIngressoOrdine DECIMAL(10,2), 
        nomeIngressoOrdine TEXT, 
        orarioIngressoOrdine TEXT, 
        dataIngressoOrdine DATETIME, 
        ingressoSenzaDataOrdine INTEGER DEFAULT 0, 
        etichettaIngressoOrdine TEXT, 
        opzioneRimborso INTEGER DEFAULT 0, 
        rimborsoValore DECIMAL(10,2), 
        rimborsoData DATETIME, 
        totaleRiga DECIMAL(10,2)
    )");

    // Voucher
    $pdo->exec("CREATE TABLE IF NOT EXISTS voucher (
        numeroVoucher INTEGER PRIMARY KEY, 
        codiceVoucher TEXT, 
        numeroOrdineVoucher INTEGER, 
        idLocationVoucher INTEGER, 
        dataVoucher DATETIME, 
        nomeVoucher TEXT, 
        cognomeVoucher TEXT, 
        telefonoVoucher TEXT, 
        emailVoucher TEXT,
        utilizzatoVoucher INTEGER DEFAULT 0
    )");
    
    // Cliente and ClientiView (Optional for now)
    
    echo "Application tables created.\n";
    
    // 3. SEEDING
    echo "Seeding basic data...\n";
    
    // Payments
    $pdo->exec("DELETE FROM tipo_pagamenti");
    $pdo->exec("INSERT INTO tipo_pagamenti (idTipoPagamento, descrizioneTipoPagamento) VALUES (1, 'PayPal'), (2, 'Bonifico Bancario'), (3, 'Contanti'), (5, 'Carta di Credito (Nexi)')");
    
    // Shipping
    $pdo->exec("DELETE FROM tipo_spedizione");
    $pdo->exec("INSERT INTO tipo_spedizione (idSpedizione, descrizioneSpedizione, costoSpedizione) VALUES (1, 'Email', 0.00), (2, 'Posta', 5.00)");
    
    // Location (Dummy)
    $pdo->exec("DELETE FROM location");
    $pdo->exec("INSERT INTO location (idLocation, nomeLocation, attivaLocation) VALUES (1, 'Location Test', 1)");
    
    // Tipologia Ingresso (Dummy)
    $pdo->exec("DELETE FROM tipologia_ingresso");
    $pdo->exec("INSERT INTO tipologia_ingresso (idIngressoCont, idLocation, nomeIngresso, prezzoIngresso, giacenzaBiglietti, tipoProvvigione, valoreProvvigione, tipoProvvigionePartner, valoreProvvigionePartner) VALUES (1, 1, 'Ingresso Standard', 50.00, 100, 0, 10.00, 0, 5.00)");

    // Partner (Dummy)
    $pdo->exec("DELETE FROM partner");
    $pdo->exec("INSERT INTO partner (idPartner, nomePartner, tipoPartner, codicePartner, passwordPartner) VALUES (1, 'Partner Test', 'diretto', 'TEST001', 'password')");

    // Cross Pagamenti Location
    $pdo->exec("DELETE FROM cross_pagamenti_location");
    // Link Location 1 to Payment 1, 2, 3, 5
    $pdo->exec("INSERT INTO cross_pagamenti_location (idLocationPagamento, idPagamento, pagamentoLocationAttivo) VALUES (1, 1, 1), (1, 2, 1), (1, 3, 1), (1, 5, 1)");

    // Cross Spedizioni Location
    $pdo->exec("DELETE FROM cross_spedizioni_location");
    $pdo->exec("INSERT INTO cross_spedizioni_location (idLocationSpedizione, idSpedizione, spedizioneLocationAttiva) VALUES (1, 1, 1), (1, 2, 1)");

    echo "Data seeded.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
