<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

if (Schema::hasTable('cross_pagamenti_location')) {
    echo "Table 'cross_pagamenti_location' exists.\n";
    $count = DB::table('cross_pagamenti_location')->count();
    echo "Row count: $count\n";

    if ($count == 0) {
        echo "Seeding now...\n";
        try {
            $locations = \App\Models\Location::pluck('idLocation');
            $pagamenti = \App\Models\TipoPagamento::pluck('idTipoPagamento');
            
            foreach ($locations as $locId) {
                foreach ($pagamenti as $pagId) {
                    echo "Linking Loc $locId - Pag $pagId\n";
                    DB::table('cross_pagamenti_location')->insertOrIgnore([
                        'idLocationPagamento' => $locId,
                        'idPagamento' => $pagId,
                        'pagamentoLocationAttivo' => 1,
                        'costoPagamento' => 0
                    ]);
                }
            }
            echo "Seeding completed.\n";
        } catch (\Exception $e) {
            echo "Seeding Error: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "Table 'cross_pagamenti_location' DOES NOT EXIST.\n";
}
