<?php
/**
 * Complete SQLite database with ALL missing tables
 */

$dbPath = __DIR__ . '/database/database.sqlite';

if (!file_exists($dbPath)) {
    die("❌ Database non trovato\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database SQLite\n\n";
    
    // Tipo Pagamenti
    echo "💳 Creando tabella tipo_pagamenti...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS tipo_pagamenti (
        idTipoPagamento INTEGER PRIMARY KEY AUTOINCREMENT,
        nomePagamento TEXT NOT NULL,
        attivoPagamento INTEGER DEFAULT 1,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    // Insert default payment types
    $pdo->exec("INSERT OR IGNORE INTO tipo_pagamenti (idTipoPagamento, nomePagamento, attivoPagamento) VALUES 
        (1, 'Contanti', 1),
        (2, 'PayPal', 1),
        (3, 'Carta di Credito', 1),
        (4, 'Bonifico', 1)");
    echo "  ✅ Tipo pagamenti creata con 4 tipi\n\n";
    
    // Tipo Spedizione
    echo "📦 Creando tabella tipo_spedizione...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS tipo_spedizione (
        idTipoSpedizione INTEGER PRIMARY KEY AUTOINCREMENT,
        nomeTipoSpedizione TEXT NOT NULL,
        costoSpedizione REAL DEFAULT 0,
        attivoSpedizione INTEGER DEFAULT 1,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    $pdo->exec("INSERT OR IGNORE INTO tipo_spedizione (idTipoSpedizione, nomeTipoSpedizione, costoSpedizione) VALUES 
        (1, 'Email', 0),
        (2, 'Corriere', 5.00),
        (3, 'Ritiro in sede', 0)");
    echo "  ✅ Tipo spedizione creata con 3 tipi\n\n";
    
    // Categoria Location
    echo "📂 Creando tabella categorie_location...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS categorie_location (
        idCatLocation INTEGER PRIMARY KEY AUTOINCREMENT,
        nomeCatLocation TEXT NOT NULL,
        attivaCatLocation INTEGER DEFAULT 1,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    $pdo->exec("INSERT OR IGNORE INTO categorie_location (nomeCatLocation) VALUES 
        ('Ristoranti'),
        ('Club'),
        ('Eventi')");
    echo "  ✅ Categorie location creata\n\n";
    
    // Tipologia Location
    echo "🏷️ Creando tabella tipologie_location...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS tipologie_location (
        idTipLocation INTEGER PRIMARY KEY AUTOINCREMENT,
        nomeTipLocation TEXT NOT NULL,
        attivaTipLocation INTEGER DEFAULT 1,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    $pdo->exec("INSERT OR IGNORE INTO tipologie_location (nomeTipLocation) VALUES 
        ('Cena'),
        ('Festa'),
        ('Cena + Festa')");
    echo "  ✅ Tipologie location creata\n\n";
    
    // Tipo Biglietto
    echo "🎫 Creando tabella tipo_biglietto...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS tipo_biglietto (
        idBiglietto INTEGER PRIMARY KEY AUTOINCREMENT,
        nomeBiglietto TEXT NOT NULL,
        attivoBiglietto INTEGER DEFAULT 1,
        created_at DATETIME,
        updated_at DATETIME
    )");
    
    $pdo->exec("INSERT OR IGNORE INTO tipo_biglietto (nomeBiglietto) VALUES 
        ('Standard'),
        ('VIP'),
        ('Premium')");
    echo "  ✅ Tipo biglietto creata\n\n";
    
    // Voucher
    echo "🎟️ Creando tabella voucher...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS voucher (
        numeroVoucher INTEGER PRIMARY KEY AUTOINCREMENT,
        numeroOrdine INTEGER,
        codiceVoucher TEXT UNIQUE,
        nomeVoucher TEXT,
        cognomeVoucher TEXT,
        emailVoucher TEXT,
        telefonoVoucher TEXT,
        dataVoucher DATETIME,
        utilizzatoVoucher INTEGER DEFAULT 0,
        dataUtilizzoVoucher DATETIME,
        created_at DATETIME,
        updated_at DATETIME
    )");
    echo "  ✅ Voucher creata\n\n";
    
    // Password Reset Tokens
    echo "🔐 Creando tabella password_reset_tokens...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS password_reset_tokens (
        email TEXT PRIMARY KEY,
        token TEXT NOT NULL,
        created_at DATETIME
    )");
    echo "  ✅ Password reset tokens creata\n\n";
    
    // Personal Access Tokens
    echo "🔑 Creando tabella personal_access_tokens...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS personal_access_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tokenable_type TEXT NOT NULL,
        tokenable_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        token TEXT NOT NULL UNIQUE,
        abilities TEXT,
        last_used_at DATETIME,
        expires_at DATETIME,
        created_at DATETIME,
        updated_at DATETIME
    )");
    echo "  ✅ Personal access tokens creata\n\n";
    
    echo "🎉 Database completato!\n\n";
    echo "📊 Tabelle create:\n";
    echo "   ✅ tipo_pagamenti (4 tipi)\n";
    echo "   ✅ tipo_spedizione (3 tipi)\n";
    echo "   ✅ categorie_location (3 categorie)\n";
    echo "   ✅ tipologie_location (3 tipologie)\n";
    echo "   ✅ tipo_biglietto (3 tipi)\n";
    echo "   ✅ voucher\n";
    echo "   ✅ password_reset_tokens\n";
    echo "   ✅ personal_access_tokens\n\n";
    
    echo "✅ Ora riprova ad accedere alla sezione Ordini!\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
