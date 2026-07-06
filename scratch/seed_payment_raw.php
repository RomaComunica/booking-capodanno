<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Seeding cross_pagamenti_location RAW...\n";
    
    $locIds = DB::table('location')->pluck('idLocation');
    $pagIds = DB::table('tipo_pagamenti')->pluck('idTipoPagamento');
    
    echo "Found " . count($locIds) . " locations and " . count($pagIds) . " payments.\n";

    foreach ($locIds as $locId) {
        foreach ($pagIds as $pagId) {
            echo "Linking Loc $locId - Pag $pagId\n";
            DB::table('cross_pagamenti_location')->insertOrIgnore([
                'idLocationPagamento' => $locId,
                'idPagamento' => $pagId,
                'pagamentoLocationAttivo' => 1,
                'costoPagamento' => 0
            ]);
        }
    }
    echo "DONE seeding.\n";
    
    $count = DB::table('cross_pagamenti_location')->count();
    echo "Final count: $count\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
