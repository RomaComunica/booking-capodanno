<?php
/**
 * Script di Migrazione da SQLite a MySQL
 * Copia i dati dal database locale SQLite (test.sqlite) al database MySQL di SiteGround.
 * 
 * ISTRUZIONI:
 * 1. Configura correttamente il file .env con i dati di MySQL di SiteGround.
 * 2. Esegui 'composer install' sul server SiteGround.
 * 3. Richiama questo file via browser (es: https://booking.capodannoromacentro.it/migrate_sqlite_to_mysql.php)
 *    oppure via terminale (ssh): php migrate_sqlite_to_mysql.php
 * 4. IMPORTANTE: Cancella questo file dal server dopo l'uso!
 */

// Disabilita limiti di tempo e memoria per database grandi
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

echo "<h2>🔄 Avvio Migrazione Database SQLite ➔ MySQL</h2>";
echo "<pre>";

// Boot di Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$sqlitePath = __DIR__ . '/database/test.sqlite';

if (!file_exists($sqlitePath)) {
    die("❌ File SQLite sorgente non trovato in: " . $sqlitePath . "\nVerifica che sia presente nella cartella /database/");
}

echo "📂 Database SQLite rilevato correttamente.\n";

// Configura connessione SQLite al volo
config(['database.connections.sqlite_source' => [
    'driver' => 'sqlite',
    'database' => $sqlitePath,
    'prefix' => '',
]]);

// Test Connessione MySQL
try {
    DB::connection()->getPdo();
    $dbName = DB::connection()->getDatabaseName();
    echo "✅ Connessione a MySQL stabilita correttamente. Database: <strong>$dbName</strong>\n";
} catch (\Exception $e) {
    die("❌ Errore connessione MySQL: " . $e->getMessage() . "\nVerifica le credenziali inserite nel file .env.");
}

// Test Connessione SQLite
try {
    $sqliteConnection = DB::connection('sqlite_source');
    $sqliteConnection->getPdo();
    echo "✅ Connessione a SQLite sorgente pronta.\n\n";
} catch (\Exception $e) {
    die("❌ Errore connessione SQLite: " . $e->getMessage());
}

// Elenco tabelle da migrare in ordine logico
$tables = [
    'users',
    'settings',
    'partner',
    'location',
    'comuni_italiani',
    'categorie_location',
    'tipologie_location',
    'tipo_biglietto',
    'cross_location_categorie',
    'cross_location_tipologie',
    'cross_partner_ingressi',
    'tipologia_ingresso',
    'tipo_pagamenti',
    'tipo_spedizione',
    'cross_pagamenti_location',
    'cross_spedizioni_location',
    'clienti',
    'ordini',
    'righe_ordine',
    'voucher',
    'entrate_uscite',
    'entrateuscite',
    'imports',
    'exports',
    'failed_import_rows'
];

// Disabilita vincoli chiavi esterne per evitare errori di inserimento sequenziale
DB::statement('SET FOREIGN_KEY_CHECKS=0;');

$successCount = 0;

foreach ($tables as $table) {
    if (!Schema::connection('sqlite_source')->hasTable($table)) {
        echo "⚠️  Tabella '$table' non presente in SQLite. Salto.\n";
        continue;
    }

    echo "📦 Migrazione tabella: <strong>$table</strong>... ";
    
    // Estrai dati da SQLite
    $rows = $sqliteConnection->table($table)->get()->map(function($row) {
        return (array) $row;
    })->toArray();
    
    if (empty($rows)) {
        echo " (Nessun dato presente, salto)\n";
        continue;
    }

    // Assicurati che la tabella esista in MySQL
    if (!Schema::hasTable($table)) {
        echo "❌ Errore: Tabella non esistente in MySQL. Assicurati di aver eseguito le migrazioni.\n";
        continue;
    }

    try {
        // Svuota tabella MySQL di destinazione
        DB::table($table)->truncate();
        
        // Inserisci i dati in blocchi per evitare limiti di parametri SQL
        $chunks = array_chunk($rows, 100);
        $totalInserted = 0;
        
        foreach ($chunks as $chunk) {
            DB::table($table)->insert($chunk);
            $totalInserted += count($chunk);
        }
        
        echo "✅ $totalInserted righe copiate con successo!\n";
        $successCount++;
        
    } catch (\Exception $e) {
        echo "❌ Errore durante la copia: " . $e->getMessage() . "\n";
    }
}

// Riabilita chiavi esterne
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "\n";
echo "==================================================\n";
echo "🎉 <strong>MIGRAZIONE COMPLETATA!</strong>\n";
echo "Tabelle copiate con successo: $successCount / " . count($tables) . "\n";
echo "==================================================\n\n";

echo "⚠️  <strong>IMPORTANTE PER LA SICUREZZA:</strong>\n";
echo "Rimuovi immediatamente questo file dal server per evitare accessi non autorizzati:\n";
echo "Comando consigliato: rm " . basename(__FILE__) . "\n";
echo "</pre>";
