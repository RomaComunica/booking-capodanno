<?php
/**
 * Create minimal SQLite database for testing
 */

$dbPath = __DIR__ . '/database/database.sqlite';

if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "🗑️  Database esistente rimosso\n";
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database SQLite creato\n\n";
    
    // Users table
    echo "👤 Creando tabella users...\n";
    $pdo->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        email_verified_at DATETIME,
        password TEXT NOT NULL,
        remember_token TEXT,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    // Insert admin user (password: password)
    $pdo->exec("
    INSERT INTO users (name, email, password, created_at, updated_at)
    VALUES ('Admin', 'admin@booking.local', '\$2y\$12\$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyKuL4Oar3Oi', datetime('now'), datetime('now'))
    ");
    echo "  ✅ Admin user creato\n\n";
    
    // Location table
    echo "📍 Creando tabella location...\n";
    $pdo->exec("
    CREATE TABLE location (
        idLocation INTEGER PRIMARY KEY AUTOINCREMENT,
        nomeLocation TEXT NOT NULL,
        attivaLocation INTEGER DEFAULT 1,
        indirizzoLocation TEXT,
        telefonoLocation TEXT,
        emailLocation TEXT,
        riferimentoLocation TEXT,
        telefonoRifLocation TEXT,
        emailRifLocation TEXT,
        locandina TEXT,
        note TEXT,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    // Insert sample locations
    $pdo->exec("INSERT INTO location (nomeLocation, attivaLocation) VALUES ('Location Test 1', 1)");
    $pdo->exec("INSERT INTO location (nomeLocation, attivaLocation) VALUES ('Location Test 2', 1)");
    echo "  ✅ 2 location di test create\n\n";
    
    // Partner table
    echo "🤝 Creando tabella partner...\n";
    $pdo->exec("
    CREATE TABLE partner (
        idPartner INTEGER PRIMARY KEY AUTOINCREMENT,
        nomePartner TEXT NOT NULL,
        tipoPartner INTEGER DEFAULT 0,
        emailPartner TEXT,
        telefonoPartner TEXT,
        urlPartner TEXT,
        attivoPartner INTEGER DEFAULT 1,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    // Insert sample partners
    $pdo->exec("INSERT INTO partner (nomePartner, attivoPartner) VALUES ('Partner A', 1)");
    $pdo->exec("INSERT INTO partner (nomePartner, attivoPartner) VALUES ('Partner B', 1)");
    $pdo->exec("INSERT INTO partner (nomePartner, attivoPartner) VALUES ('Partner C', 1)");
    echo "  ✅ 3 partner di test creati\n\n";
    
    // Tipologia Ingresso table
    echo "🎫 Creando tabella tipologia_ingresso...\n";
    $pdo->exec("
    CREATE TABLE tipologia_ingresso (
        idIngressoCont INTEGER PRIMARY KEY AUTOINCREMENT,
        idIngresso INTEGER NOT NULL,
        idLocation INTEGER NOT NULL,
        nomeIngresso TEXT NOT NULL,
        prezzoIngresso REAL DEFAULT 0,
        tipoProvvigione INTEGER DEFAULT 0,
        valoreProvvigione REAL DEFAULT 0,
        tipoProvvigionePartner INTEGER DEFAULT 0,
        valoreProvvigionePartner REAL DEFAULT 0,
        giacenzaBiglietti INTEGER DEFAULT 0,
        ingressoAttivo INTEGER DEFAULT 1,
        dataIngresso DATE,
        orarioIngresso TIME,
        ingressoSenzaData INTEGER DEFAULT 0,
        etichettaIngresso TEXT,
        opzioneRimborso INTEGER DEFAULT 0,
        rimborsoValorePersona REAL,
        rimborsoDataScadenza DATETIME,
        rimborsoTesto TEXT,
        ordine INTEGER DEFAULT 0,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    // Insert sample tickets
    $pdo->exec("INSERT INTO tipologia_ingresso (idIngresso, idLocation, nomeIngresso, prezzoIngresso, giacenzaBiglietti, ingressoAttivo, ordine) 
                VALUES (1, 1, 'Biglietto Standard', 50.00, 100, 1, 10)");
    $pdo->exec("INSERT INTO tipologia_ingresso (idIngresso, idLocation, nomeIngresso, prezzoIngresso, giacenzaBiglietti, ingressoAttivo, ordine) 
                VALUES (2, 1, 'Biglietto VIP', 100.00, 50, 1, 20)");
    echo "  ✅ 2 biglietti di test creati\n\n";
    
    // Cross Partner Ingressi table
    echo "🔗 Creando tabella cross_partner_ingressi...\n";
    $pdo->exec("
    CREATE TABLE cross_partner_ingressi (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        idTipologiaIngresso INTEGER NOT NULL,
        idPartnerIngressi INTEGER NOT NULL,
        idLocation INTEGER NOT NULL,
        quantitaIngressi INTEGER DEFAULT 0,
        quantitaIngressiTerra INTEGER DEFAULT 0,
        created_at DATETIME,
        updated_at DATETIME,
        UNIQUE(idTipologiaIngresso, idPartnerIngressi)
    )");
    
    // Link partners to tickets
    $pdo->exec("INSERT INTO cross_partner_ingressi (idTipologiaIngresso, idPartnerIngressi, idLocation, quantitaIngressi, quantitaIngressiTerra) 
                VALUES (1, 1, 1, 50, 20)");
    $pdo->exec("INSERT INTO cross_partner_ingressi (idTipologiaIngresso, idPartnerIngressi, idLocation, quantitaIngressi, quantitaIngressiTerra) 
                VALUES (1, 2, 1, 30, 10)");
    echo "  ✅ Relazioni partner-biglietti create\n\n";
    
    // Ordini table
    echo "📦 Creando tabella ordini...\n";
    $pdo->exec("
    CREATE TABLE ordini (
        numeroOrdine INTEGER PRIMARY KEY AUTOINCREMENT,
        idLocationOrdine INTEGER,
        idPartnerOrdine INTEGER,
        nomeOrdine TEXT,
        cognomeOrdine TEXT,
        emailOrdine TEXT,
        telefonoOrdine TEXT,
        totaleOrdine REAL DEFAULT 0,
        statoOrdine INTEGER DEFAULT 0,
        dataOrdine DATETIME,
        dataPagamentoOrdine DATETIME,
        tipoPagamentoOrdine INTEGER,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    // Insert sample orders
    $pdo->exec("INSERT INTO ordini (idLocationOrdine, nomeOrdine, cognomeOrdine, emailOrdine, totaleOrdine, statoOrdine, dataOrdine, dataPagamentoOrdine) 
                VALUES (1, 'Mario', 'Rossi', 'mario@test.it', 150.00, 2, datetime('now', '-10 days'), datetime('now', '-10 days'))");
    $pdo->exec("INSERT INTO ordini (idLocationOrdine, nomeOrdine, cognomeOrdine, emailOrdine, totaleOrdine, statoOrdine, dataOrdine, dataPagamentoOrdine) 
                VALUES (1, 'Luigi', 'Verdi', 'luigi@test.it', 200.00, 2, datetime('now', '-5 days'), datetime('now', '-5 days'))");
    $pdo->exec("INSERT INTO ordini (idLocationOrdine, nomeOrdine, cognomeOrdine, emailOrdine, totaleOrdine, statoOrdine, dataOrdine) 
                VALUES (1, 'Anna', 'Bianchi', 'anna@test.it', 100.00, 0, datetime('now', '-1 days'))");
    echo "  ✅ 3 ordini di test creati\n\n";
    
    // Righe Ordine table
    echo "📋 Creando tabella righe_ordine...\n";
    $pdo->exec("
    CREATE TABLE righe_ordine (
        idRigaOrdine INTEGER PRIMARY KEY AUTOINCREMENT,
        numeroOrdine INTEGER,
        idIngressoOrdine INTEGER,
        nomeIngressoOrdine TEXT,
        quantitaIngressoOrdine INTEGER,
        prezzoIngressoOrdine REAL,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    $pdo->exec("INSERT INTO righe_ordine (numeroOrdine, idIngressoOrdine, nomeIngressoOrdine, quantitaIngressoOrdine, prezzoIngressoOrdine) 
                VALUES (1, 1, 'Biglietto Standard', 3, 50.00)");
    $pdo->exec("INSERT INTO righe_ordine (numeroOrdine, idIngressoOrdine, nomeIngressoOrdine, quantitaIngressoOrdine, prezzoIngressoOrdine) 
                VALUES (2, 2, 'Biglietto VIP', 2, 100.00)");
    echo "  ✅ Righe ordine create\n\n";
    
    // Migrations table
    echo "📝 Creando tabella migrations...\n";
    $pdo->exec("
    CREATE TABLE migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        migration TEXT NOT NULL,
        batch INTEGER NOT NULL
    )");
    
    $pdo->exec("INSERT INTO migrations (migration, batch) VALUES ('2024_01_01_000000_create_tables', 1)");
    echo "  ✅ Migrations table creata\n\n";
    
    echo "🎉 Database SQLite creato con successo!\n\n";
    echo "📊 Riepilogo:\n";
    echo "   - 1 utente admin\n";
    echo "   - 2 location\n";
    echo "   - 3 partner\n";
    echo "   - 2 biglietti\n";
    echo "   - 3 ordini\n\n";
    echo "🔑 Credenziali:\n";
    echo "   Email: admin@booking.local\n";
    echo "   Password: password\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
