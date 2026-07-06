<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

echo "Seeding Dummy Data...\n";

try {
    // 1. Ensure Partner
    echo "Creating partner...\n";
    $partnerId = DB::table('partner')->insertGetId([
        'nomePartner' => 'Partner Test Roma',
        'emailPartner' => 'partner@test.it',
        'attivoPartner' => 1
    ]);
    echo "Partner created: $partnerId\n";

    // 2. Ensure Location
    echo "Creating location...\n";
    $loc = DB::table('location')->first();
    if (!$loc) {
        $locId = DB::table('location')->insertGetId([
            'nomeLocation' => 'Villa Miani (Demo)',
            'attivaLocation' => 1
        ]);
        echo "Location created: $locId\n";
    } else {
        $locId = $loc->idLocation;
        echo "Using existing Location: $locId\n";
    }

    // 3. Create Orders (2026)
    echo "Creating orders...\n";
    DB::table('ordini')->insert([
        'numeroOrdine' => rand(1000,9999),
        'dataOrdine' => '2026-01-15 12:00:00', // Use string explicitly
        'dataPagamentoOrdine' => '2026-01-15 12:00:00',
        'totaleOrdine' => 150.00,
        'statoOrdine' => 2,
        'idLocationOrdine' => $locId,
        'idPartnerOrdine' => $partnerId,
        'nomeOrdine' => 'Mario',
        'cognomeOrdine' => 'Rossi',
        'emailOrdine' => 'mario@rossi.it'
    ]);

    DB::table('ordini')->insert([
        'numeroOrdine' => rand(1000,9999),
        'dataOrdine' => '2026-12-31 23:00:00',
        'dataPagamentoOrdine' => '2026-12-31 23:00:00',
        'totaleOrdine' => 500.00,
        'statoOrdine' => 2,
        'idLocationOrdine' => $locId,
        'idPartnerOrdine' => $partnerId,
        'nomeOrdine' => 'Luigi',
        'cognomeOrdine' => 'Verdi',
        'emailOrdine' => 'luigi@verdi.it'
    ]);

    echo "Orders seeded.\n";

} catch (\Exception $e) {
    echo "ERROR SEEDING: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
