<?php

/**
 * SiteGround Database Fix and Cache Clear Script
 * 
 * Instructions:
 * 1. Upload this file to the "public" folder of your server.
 * 2. Visit: https://www.yourdomain.it/sg_fix_db.php
 * 3. IMPORTANT: Delete this file after use!
 */

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "<div style='font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>";
echo "<h1 style='color: #2563eb;'>🛠️ SiteGround Database Repair Script</h1>";

try {
    // 1. Check & Fix "tavolo" column in "ordini" table
    echo "<h3>1. Verifica colonna 'tavolo' in tabella 'ordini'</h3>";
    if (Schema::hasColumn('ordini', 'tavolo')) {
        echo "<p style='color: green;'>La colonna <strong>tavolo</strong> esiste già nella tabella <strong>ordini</strong>.</p>";
    } else {
        echo "<p style='color: orange;'>La colonna <strong>tavolo</strong> non esiste. La sto aggiungendo...</p>";
        Schema::table('ordini', function ($table) {
            $table->string('tavolo', 100)->nullable()->after('noteOrdine');
        });
        echo "<p style='color: green; font-weight: bold;'>Colonna <strong>tavolo</strong> aggiunta con successo!</p>";
    }

    // 2. Check & Fix "utilizzato" and "data_utilizzo" columns in "voucher" table
    echo "<h3>2. Verifica colonne check-in in tabella 'voucher'</h3>";
    $hasUtilizzato = Schema::hasColumn('voucher', 'utilizzato');
    $hasDataUtilizzo = Schema::hasColumn('voucher', 'data_utilizzo');
    
    if ($hasUtilizzato && $hasDataUtilizzo) {
        echo "<p style='color: green;'>Le colonne <strong>utilizzato</strong> e <strong>data_utilizzo</strong> esistono già nella tabella <strong>voucher</strong>.</p>";
    } else {
        echo "<p style='color: orange;'>Colonne mancanti nella tabella <strong>voucher</strong>. Le sto aggiungendo...</p>";
        Schema::table('voucher', function ($table) use ($hasUtilizzato, $hasDataUtilizzo) {
            if (!$hasUtilizzato) {
                $table->boolean('utilizzato')->default(0);
            }
            if (!$hasDataUtilizzo) {
                $table->dateTime('data_utilizzo')->nullable();
            }
        });
        echo "<p style='color: green; font-weight: bold;'>Colonne check-in aggiunte con successo alla tabella <strong>voucher</strong>!</p>";
    }

    // 3. Sync migrations table
    echo "<h3>3. Sincronizzazione tabella 'migrations'</h3>";
    $migrations = [
        '2026_06_12_100000_add_checkin_fields_to_voucher_table' => 1,
        '2026_06_12_110000_add_tavolo_to_ordini_table' => 2
    ];

    foreach ($migrations as $migrationName => $batch) {
        $exists = DB::table('migrations')->where('migration', $migrationName)->exists();
        if ($exists) {
            echo "<p style='color: green;'>La migrazione <code>$migrationName</code> è già presente nella tabella delle migrazioni.</p>";
        } else {
            echo "<p style='color: orange;'>La migrazione <code>$migrationName</code> non è registrata. La sto inserendo...</p>";
            DB::table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => $batch
            ]);
            echo "<p style='color: green; font-weight: bold;'>Migrazione <code>$migrationName</code> registrata!</p>";
        }
    }

    // 4. Clear and rebuild optimize cache
    echo "<h3>4. Pulizia Cache e Ottimizzazione</h3>";
    Artisan::call('optimize:clear');
    echo "<pre style='background: #f4f4f4; padding: 10px;'>" . Artisan::output() . "</pre>";
    
    Artisan::call('optimize');
    echo "<pre style='background: #f4f4f4; padding: 10px;'>" . Artisan::output() . "</pre>";

    echo "<h2 style='color: #16a34a;'>✅ Ripristino database e cache completato!</h2>";
    echo "<p style='color: #dc2626; font-weight: bold;'>⚠️ ATTENZIONE: Elimina questo file (sg_fix_db.php) dal server immediatamente per motivi di sicurezza.</p>";

} catch (\Exception $e) {
    echo "<h2 style='color: #dc2626;'>❌ Si è verificato un errore durante l'esecuzione:</h2>";
    echo "<pre style='background: #fee2e2; padding: 10px; color: #991b1b;'>" . $e->getMessage() . "</pre>";
}

echo "</div>";
