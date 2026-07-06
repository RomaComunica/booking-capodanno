<?php
/**
 * Import v6: Fully manual approach
 * 1. migrate:fresh for Laravel tables
 * 2. Drop domain tables
 * 3. Create domain tables manually with known-good SQLite DDL
 * 4. Parse INSERT VALUES from dump line by line, execute per-row
 */
ini_set('memory_limit', '1G');
set_time_limit(0);

$sqlFile = __DIR__ . '/capodanno20230203.sql';
$dbPath  = __DIR__ . '/database/test.sqlite';

echo "=== Legacy Data Import v6 ===\n\n";

echo "Step 1: migrate:fresh...\n";
echo shell_exec('php artisan migrate:fresh --force 2>&1');

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA journal_mode=WAL');
$db->exec('PRAGMA synchronous=OFF');
$db->exec('PRAGMA foreign_keys=OFF');

// Step 2: Drop and recreate domain tables with exact schema
echo "\nStep 2: Creating domain tables...\n";
$ddls = [
    'DROP TABLE IF EXISTS "voucher"',
    'DROP TABLE IF EXISTS "righe_ordine"',
    'DROP TABLE IF EXISTS "ordini"',
    'DROP TABLE IF EXISTS "cross_partner_ingressi"',
    'DROP TABLE IF EXISTS "cross_spedizioni_location"',
    'DROP TABLE IF EXISTS "cross_pagamenti_location"',
    'DROP TABLE IF EXISTS "cross_location_tipologie"',
    'DROP TABLE IF EXISTS "cross_location_categorie"',
    'DROP TABLE IF EXISTS "tipologia_ingresso"',
    'DROP TABLE IF EXISTS "location"',
    'DROP TABLE IF EXISTS "partner"',
    'DROP TABLE IF EXISTS "tipo_spedizione"',
    'DROP TABLE IF EXISTS "tipo_pagamenti"',
    'DROP TABLE IF EXISTS "tipo_biglietto"',
    'DROP TABLE IF EXISTS "tipologie_location"',
    'DROP TABLE IF EXISTS "categorie_location"',
    'DROP TABLE IF EXISTS "entrateuscite"',

    'CREATE TABLE "categorie_location" (
        "idCatLocation" INTEGER PRIMARY KEY AUTOINCREMENT,
        "nomeCatLocation" TEXT NOT NULL DEFAULT \'\'
    )',
    'CREATE TABLE "tipologie_location" (
        "idTipLocation" INTEGER PRIMARY KEY AUTOINCREMENT,
        "nomeTipLocation" TEXT NOT NULL DEFAULT \'\'
    )',
    'CREATE TABLE "tipo_biglietto" (
        "idBiglietto" INTEGER PRIMARY KEY AUTOINCREMENT,
        "nomeTipoBiglietto" TEXT NOT NULL DEFAULT \'\'
    )',
    'CREATE TABLE "tipo_pagamenti" (
        "idTipoPagamento" INTEGER PRIMARY KEY AUTOINCREMENT,
        "nomePagamento" TEXT NOT NULL DEFAULT \'\'
    )',
    'CREATE TABLE "tipo_spedizione" (
        "idSpedizione" INTEGER PRIMARY KEY AUTOINCREMENT,
        "nomeSpedizione" TEXT NOT NULL DEFAULT \'\',
        "costoSpedizione" REAL NOT NULL DEFAULT 0,
        "descrizioneSpedizione" TEXT DEFAULT NULL
    )',
    'CREATE TABLE "partner" (
        "idPartner" INTEGER PRIMARY KEY AUTOINCREMENT,
        "tipoPartner" INTEGER NOT NULL DEFAULT 0,
        "nomePartner" TEXT NOT NULL DEFAULT \'\',
        "emailPartner" TEXT NOT NULL DEFAULT \'\',
        "telefonoPartner" TEXT NOT NULL DEFAULT \'\',
        "urlPartner" TEXT NOT NULL DEFAULT \'\',
        "attivoPartner" INTEGER NOT NULL DEFAULT 0,
        "LocationPartner" INTEGER NOT NULL DEFAULT 0,
        "created_at" TEXT DEFAULT NULL,
        "updated_at" TEXT DEFAULT NULL
    )',
    'CREATE TABLE "location" (
        "idLocation" INTEGER PRIMARY KEY AUTOINCREMENT,
        "nomeLocation" TEXT NOT NULL DEFAULT \'\',
        "indirizzoLocation" TEXT NOT NULL DEFAULT \'\',
        "telefonoLocation" TEXT NOT NULL DEFAULT \'\',
        "emailLocation" TEXT NOT NULL DEFAULT \'\',
        "riferimentoLocation" TEXT NOT NULL DEFAULT \'\',
        "telefonoRifLocation" TEXT NOT NULL DEFAULT \'\',
        "emailRifLocation" TEXT NOT NULL DEFAULT \'\',
        "attivaLocation" INTEGER NOT NULL DEFAULT 1,
        "immagineLocation" TEXT DEFAULT NULL,
        "locandina" TEXT DEFAULT NULL,
        "note" TEXT DEFAULT NULL,
        "created_at" TEXT DEFAULT NULL,
        "updated_at" TEXT DEFAULT NULL
    )',
    'CREATE TABLE "tipologia_ingresso" (
        "idIngressoCont" INTEGER PRIMARY KEY AUTOINCREMENT,
        "idIngresso" INTEGER NOT NULL DEFAULT 0,
        "idLocation" INTEGER NOT NULL DEFAULT 0,
        "nomeIngresso" TEXT NOT NULL DEFAULT \'\',
        "prezzoIngresso" REAL NOT NULL DEFAULT 0,
        "tipoProvvigione" INTEGER NOT NULL DEFAULT 0,
        "valoreProvvigione" REAL NOT NULL DEFAULT 0,
        "tipoQuantitaBonus" INTEGER NOT NULL DEFAULT 0,
        "quantitaBonus" INTEGER NOT NULL DEFAULT 0,
        "valoreProvvigioneBonus" REAL NOT NULL DEFAULT 0,
        "tipoProvvigionePartner" INTEGER NOT NULL DEFAULT 0,
        "valoreProvvigionePartner" REAL NOT NULL DEFAULT 0,
        "tipoQuantitaBonusPartner" INTEGER NOT NULL DEFAULT 0,
        "quantitaBonusPartner" INTEGER NOT NULL DEFAULT 0,
        "valoreProvvigioneBonusPartner" REAL NOT NULL DEFAULT 0,
        "giacenzaBiglietti" INTEGER NOT NULL DEFAULT 0,
        "ingressoAttivo" INTEGER NOT NULL DEFAULT 1,
        "tipoBigliettoIngresso" INTEGER NOT NULL DEFAULT 0,
        "orarioIngresso" TEXT NOT NULL DEFAULT \'00:00\',
        "dataIngresso" TEXT DEFAULT NULL,
        "giorniPrimaIngresso" INTEGER NOT NULL DEFAULT 0,
        "ingressoSenzaData" INTEGER NOT NULL DEFAULT 0,
        "etichettaIngresso" TEXT NOT NULL DEFAULT \'\',
        "opzioneRimborso" INTEGER NOT NULL DEFAULT 0,
        "rimborsoValorePersona" REAL DEFAULT NULL,
        "rimborsoDataScadenza" TEXT DEFAULT NULL,
        "rimborsoTesto" TEXT DEFAULT NULL,
        "ordine" INTEGER NOT NULL DEFAULT 0,
        "created_at" TEXT DEFAULT NULL,
        "updated_at" TEXT DEFAULT NULL
    )',
    'CREATE TABLE "cross_location_categorie" (
        "idCrossCat" INTEGER PRIMARY KEY AUTOINCREMENT,
        "idLocation" INTEGER NOT NULL DEFAULT 0,
        "idCatLocation" INTEGER NOT NULL DEFAULT 0
    )',
    'CREATE TABLE "cross_location_tipologie" (
        "idCrossTip" INTEGER PRIMARY KEY AUTOINCREMENT,
        "idLocation" INTEGER NOT NULL DEFAULT 0,
        "idTipLocation" INTEGER NOT NULL DEFAULT 0
    )',
    'CREATE TABLE "cross_pagamenti_location" (
        "idCrossPagamentiLocation" INTEGER PRIMARY KEY AUTOINCREMENT,
        "idLocationPagamento" INTEGER NOT NULL,
        "idPagamento" INTEGER NOT NULL,
        "pagamentoLocationAttivo" INTEGER NOT NULL DEFAULT 0
    )',
    'CREATE TABLE "cross_spedizioni_location" (
        "idSpedizioneLocation" INTEGER PRIMARY KEY AUTOINCREMENT,
        "idLocationSpedizione" INTEGER NOT NULL,
        "idSpedizione" INTEGER NOT NULL,
        "spedizioneLocationAttiva" INTEGER NOT NULL DEFAULT 0
    )',
    'CREATE TABLE "cross_partner_ingressi" (
        "idCrossPartnerIngressi" INTEGER PRIMARY KEY AUTOINCREMENT,
        "idPartnerIngressi" INTEGER NOT NULL DEFAULT 0,
        "idTipologiaIngresso" INTEGER NOT NULL DEFAULT 0,
        "quantitaIngressi" INTEGER NOT NULL DEFAULT 0,
        "quantitaIngressiTerra" INTEGER NOT NULL DEFAULT 0,
        "idLocation" INTEGER NOT NULL DEFAULT 0
    )',
    'CREATE TABLE "ordini" (
        "idordine" INTEGER PRIMARY KEY AUTOINCREMENT,
        "numeroOrdine" INTEGER NOT NULL DEFAULT 0,
        "dataOrdine" TEXT DEFAULT NULL,
        "idLocationOrdine" INTEGER NOT NULL DEFAULT 0,
        "IPcliente" TEXT DEFAULT NULL,
        "nomeClienteOrdine" TEXT NOT NULL DEFAULT \'\',
        "cognomeClienteOrdine" TEXT NOT NULL DEFAULT \'\',
        "indirizzoClienteOrdine" TEXT NOT NULL DEFAULT \'\',
        "capClienteOrdine" TEXT NOT NULL DEFAULT \'\',
        "cittaClienteOrdine" TEXT NOT NULL DEFAULT \'\',
        "provinciaClienteOrdine" TEXT NOT NULL DEFAULT \'\',
        "telefonoClienteOrdine" TEXT NOT NULL DEFAULT \'\',
        "emailClienteOrdine" TEXT NOT NULL DEFAULT \'\',
        "noteClienteOridine" TEXT NOT NULL DEFAULT \'\',
        "totaleOrdine" REAL NOT NULL DEFAULT 0,
        "tipoPagamentoOrdine" INTEGER NOT NULL DEFAULT 0,
        "tipoSpedizioneOrdine" INTEGER NOT NULL DEFAULT 0,
        "idPartnerOrdine" INTEGER NOT NULL DEFAULT 0,
        "ordineConfermato" INTEGER NOT NULL DEFAULT 0,
        "dataPagamentoOrdine" TEXT DEFAULT NULL,
        "statoOrdine" INTEGER NOT NULL DEFAULT 0,
        "dataPagamentoOrdineFornitore" TEXT DEFAULT NULL,
        "noteOrdine" TEXT NOT NULL DEFAULT \'\',
        "dataPagamentoOrdinePartner" TEXT DEFAULT NULL,
        "created_at" TEXT DEFAULT NULL,
        "updated_at" TEXT DEFAULT NULL
    )',
    'CREATE TABLE "righe_ordine" (
        "idRigaOrdine" INTEGER PRIMARY KEY AUTOINCREMENT,
        "numeroOrdine" INTEGER NOT NULL DEFAULT 0,
        "idIngressoOrdine" INTEGER NOT NULL DEFAULT 0,
        "idClienteOrdine" INTEGER NOT NULL DEFAULT 0,
        "idPartnerOrdine" INTEGER NOT NULL DEFAULT 0,
        "quantitaIngressoOrdine" REAL NOT NULL DEFAULT 0,
        "prezzoIngressoordine" REAL DEFAULT 0,
        "nomeIngressoOrdine" TEXT NOT NULL DEFAULT \'\',
        "orarioIngressoOrdine" TEXT NOT NULL DEFAULT \'00:00\',
        "dataIngressoOrdine" TEXT DEFAULT NULL,
        "ingressoSenzaDataOrdine" INTEGER NOT NULL DEFAULT 0,
        "etichettaIngressoOrdine" TEXT NOT NULL DEFAULT \'\',
        "opzioneRimborso" INTEGER NOT NULL DEFAULT 0,
        "rimborsoValore" REAL DEFAULT NULL,
        "rimborsoData" TEXT DEFAULT NULL,
        "totaleRiga" REAL DEFAULT 0,
        "created_at" TEXT DEFAULT NULL,
        "updated_at" TEXT DEFAULT NULL
    )',
    'CREATE TABLE "voucher" (
        "idVoucher" INTEGER PRIMARY KEY AUTOINCREMENT,
        "numeroVoucher" INTEGER NOT NULL,
        "codiceVoucher" TEXT DEFAULT NULL,
        "numeroOrdineVoucher" INTEGER NOT NULL,
        "idLocationVoucher" INTEGER NOT NULL,
        "dataVoucher" TEXT DEFAULT NULL,
        "nomeVoucher" TEXT NOT NULL DEFAULT \'\',
        "cognomeVoucher" TEXT NOT NULL DEFAULT \'\',
        "telefonoVoucher" TEXT NOT NULL DEFAULT \'\',
        "emailVoucher" TEXT NOT NULL DEFAULT \'\'
    )',
    'CREATE TABLE "entrateuscite" (
        "idEntrateUscite" INTEGER PRIMARY KEY AUTOINCREMENT,
        "causale" TEXT NOT NULL DEFAULT \'\',
        "Importo" REAL NOT NULL DEFAULT 0,
        "dataCausale" TEXT DEFAULT NULL
    )',
];

foreach ($ddls as $ddl) {
    try {
        $db->exec($ddl);
    } catch (Exception $e) {
        echo "  DDL Error: " . substr($e->getMessage(), 0, 100) . "\n";
    }
}
echo "  Tables created.\n";

// Step 3: Parse dump and execute INSERTs
echo "\nStep 3: Importing data...\n\n";

$lines = file($sqlFile, FILE_IGNORE_NEW_LINES);
$stats = [];
$errorCount = 0;
$collecting = false;
$currentSql = '';
$currentTable = '';
$tableRename = ['vouchers' => 'voucher'];

$db->beginTransaction();

foreach ($lines as $line) {
    $trimmed = trim($line);
    if (empty($trimmed)) continue;
    if (strpos($trimmed, '/*!') === 0) continue;
    if (strpos($trimmed, '--') === 0) continue;
    
    // Only process INSERT statements
    if (strpos($trimmed, 'INSERT INTO') === 0) {
        if (preg_match('/INSERT INTO `([^`]+)`/', $trimmed, $m)) {
            $mysqlTable = $m[1];
            $sqliteTable = $tableRename[$mysqlTable] ?? $mysqlTable;
            $currentTable = $sqliteTable;
            
            if (!isset($stats[$currentTable])) {
                $stats[$currentTable] = 0;
                echo "  $mysqlTable -> $sqliteTable\n";
            }
        }
        
        // Convert MySQL to SQLite
        $converted = str_replace('`', '"', $trimmed);
        // Rename table
        foreach ($tableRename as $from => $to) {
            $converted = str_replace('"' . $from . '"', '"' . $to . '"', $converted);
        }
        // Convert MySQL backslash escapes to SQLite double-quote escapes
        $converted = str_replace("\\'", "''", $converted);
        $converted = str_replace('\\"', '"', $converted);
        $converted = str_replace("'0000-00-00'", "NULL", $converted);
        $converted = str_replace("'0000-00-00 00:00:00'", "NULL", $converted);
        
        $currentSql = $converted;
        $collecting = true;
        
        if (substr($trimmed, -1) === ';') {
            execSql($db, $currentSql, $currentTable, $stats, $errorCount);
            $collecting = false;
            $currentSql = '';
        }
        continue;
    }
    
    // Continuation of INSERT statement
    if ($collecting) {
        $part = str_replace('`', '"', $line);
        // Convert MySQL backslash escapes to SQLite double-quote escapes
        $part = str_replace("\\'", "''", $part);
        $part = str_replace('\\"', '"', $part);
        $part = str_replace("'0000-00-00'", "NULL", $part);
        $part = str_replace("'0000-00-00 00:00:00'", "NULL", $part);
        $currentSql .= "\n" . $part;
        
        if (substr($trimmed, -1) === ';') {
            execSql($db, $currentSql, $currentTable, $stats, $errorCount);
            $collecting = false;
            $currentSql = '';
        }
    }
}

$db->commit();

// Summary
echo "\n=== Summary ===\n";
$total = 0;
foreach ($stats as $t => $c) {
    echo "  " . str_pad($t, 30) . "$c\n";
    $total += $c;
}
echo "  " . str_repeat('-', 40) . "\n";
echo "  " . str_pad("TOTAL", 30) . "$total\n";
echo "  Errors: $errorCount\n\nDone!\n";

// Cleanup
foreach (['check_counts.php','check_tables.php','debug_import.php','schema_dump.txt','import_results.txt','import_log.txt'] as $f) {
    @unlink(__DIR__ . "/$f");
}

function execSql($db, $sql, $table, &$stats, &$errorCount) {
    try {
        $n = $db->exec($sql);
        $stats[$table] = ($stats[$table] ?? 0) + max(0, (int)$n);
    } catch (Exception $e) {
        $errorCount++;
        static $shown = [];
        if (!isset($shown[$table])) $shown[$table] = 0;
        if ($shown[$table] < 2) {
            echo "    ERR: " . substr($e->getMessage(), 0, 160) . "\n";
            $shown[$table]++;
        }
    }
}
