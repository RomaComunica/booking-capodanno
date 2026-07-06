<?php

/**
 * SiteGround Laravel Deployment Script
 * 
 * Istruzioni:
 * 1. Carica questo file nella cartella "public" (o public_html se hai modificato i percorsi) del tuo server.
 * 2. Visita l'URL: https://www.tuosito.it/sg_deploy.php
 * 3. Per lanciare le migrazioni (se servono), visita: https://www.tuosito.it/sg_deploy.php?migrate=1
 * 4. IMPORTANTE: Cancella questo file dopo l'uso!
 */

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Artisan;

echo "<div style='font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>";
echo "<h1 style='color: #2563eb;'>🚀 SiteGround Deploy Script</h1>";

try {
    echo "<h3>1. Pulizia Cache</h3>";
    Artisan::call('optimize:clear');
    echo "<pre style='background: #f4f4f4; padding: 10px;'>" . Artisan::output() . "</pre>";
    
    echo "<h3>2. Ottimizzazione (Config, Route, Views)</h3>";
    Artisan::call('optimize');
    echo "<pre style='background: #f4f4f4; padding: 10px;'>" . Artisan::output() . "</pre>";

    echo "<h3>3. Creazione Storage Link</h3>";
    Artisan::call('storage:link');
    echo "<pre style='background: #f4f4f4; padding: 10px;'>" . Artisan::output() . "</pre>";
    
    if (isset($_GET['migrate']) && $_GET['migrate'] == '1') {
        echo "<h3>4. Esecuzione Migrations</h3>";
        Artisan::call('migrate', ['--force' => true]);
        echo "<pre style='background: #f4f4f4; padding: 10px;'>" . Artisan::output() . "</pre>";
    } else {
        echo "<p style='color: #666;'><i>Nota: Migrations saltate. Aggiungi <code>?migrate=1</code> all'URL per eseguirle.</i></p>";
    }
    
    echo "<h2 style='color: #16a34a;'>✅ Tutte le operazioni completate con successo!</h2>";
    echo "<p style='color: #dc2626; font-weight: bold;'>⚠️ ATTENZIONE: Elimina il file sg_deploy.php dal server per motivi di sicurezza.</p>";

} catch (\Exception $e) {
    echo "<h2 style='color: #dc2626;'>❌ Errore durante l'esecuzione:</h2>";
    echo "<pre style='background: #fee2e2; padding: 10px; color: #991b1b;'>" . $e->getMessage() . "</pre>";
}

echo "</div>";
