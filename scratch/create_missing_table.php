<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

try {
    echo "Creating cross_pagamenti_location table...\n";
    
    if (!Schema::hasTable('cross_pagamenti_location')) {
        Schema::create('cross_pagamenti_location', function (Blueprint $table) {
            $table->integer('idLocationPagamento');
            $table->integer('idPagamento');
            $table->boolean('pagamentoLocationAttivo')->default(1);
            $table->decimal('costoPagamento', 10, 2)->nullable();
            $table->primary(['idLocationPagamento', 'idPagamento']);
        });
        echo "Table created successfully.\n";
    } else {
        echo "Table already exists.\n";
    }

    echo "Seeding default data...\n";
    // Link all payment methods to all locations
    $locations = \App\Models\Location::all();
    $pagamenti = \App\Models\TipoPagamento::all();

    foreach ($locations as $location) {
        foreach ($pagamenti as $pagamento) {
            DB::table('cross_pagamenti_location')->insertOrIgnore([
                'idLocationPagamento' => $location->idLocation,
                'idPagamento' => $pagamento->idTipoPagamento,
                'pagamentoLocationAttivo' => 1,
                'costoPagamento' => 0
            ]);
        }
    }
    echo "Data seeded.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
