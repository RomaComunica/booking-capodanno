<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "Aligning Schema with Legacy Code...\n";

// 1. Rename/Recreate 'righe_ordine' to match StatisticsService expectations
Schema::dropIfExists('righe_ordini'); // Drop the wrong one
Schema::dropIfExists('righe_ordine'); // Drop existing if any

echo "Creating 'righe_ordine' with legacy columns...\n";
Schema::create('righe_ordine', function (Blueprint $table) {
    $table->increments('idRigaOrdine');
    $table->string('numeroOrdine', 50)->index();
    
    // Legacy column names based on StatisticsService usage
    $table->integer('idIngressoOrdine')->nullable(); // Was idTipologiaIngresso
    $table->string('nomeIngresso', 255)->nullable();
    $table->integer('quantitaIngressoOrdine')->default(0); // Was quantitaIngressi
    $table->decimal('prezzoIngressoOrdine', 10, 2)->default(0); // Was prezzoIngresso
    $table->decimal('totaleRiga', 10, 2)->default(0);
});

// 2. Re-seed correct dummy data for righe_ordine
$ordini = DB::table('ordini')->get();
if ($ordini->isNotEmpty()) {
    echo "Reseeding righe_ordine for existing orders...\n";
    foreach ($ordini as $ordine) {
        DB::table('righe_ordine')->insert([
            'numeroOrdine' => $ordine->numeroOrdine,
            'idIngressoOrdine' => 1, // Demo ID
            'nomeIngresso' => 'Ingresso Test',
            'quantitaIngressoOrdine' => 2,
            'prezzoIngressoOrdine' => 75.00,
            'totaleRiga' => 150.00
        ]);
    }
}

// 3. Verify tipologia_ingresso idIngresso match
// StatisticsService joins on: tipologia_ingresso.idIngresso
// My schema has: idIngressoCont (PK) and idIngresso (integer).
// Need to ensure idIngresso is populated unique/correctly.
$tip = DB::table('tipologia_ingresso')->first();
if (!$tip) {
    echo "Seeding tipologia_ingresso...\n";
    DB::table('tipologia_ingresso')->insert([
        'idIngresso' => 1,
        'idLocation' => 1,
        'nomeIngresso' => 'Cena di Gala',
        'prezzoIngresso' => 75.00,
        'tipoProvvigione' => 0, // Fixed
        'valoreProvvigione' => 5.00,
        'ingressoAttivo' => 1
    ]);
}

echo "DONE. Schema Aligned.\n";
