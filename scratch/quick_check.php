<?php
// Quick check using models
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== QUICK CHECK ===\n\n";

// Check using models
$location = App\Models\Location::first();
if ($location) {
    echo "Location: {$location->nomeLocation}\n";
    echo "Attiva: " . ($location->attivaLocation ? 'SI' : 'NO') . "\n";
    
    $ingressi = $location->ingressi()->where('ingressoAttivo', 1)->get();
    echo "Ingressi attivi: " . $ingressi->count() . "\n";
    
    foreach ($ingressi as $ing) {
        echo "\n  Ingresso: {$ing->nomeIngresso}\n";
        echo "  Giacenza: {$ing->giacenzaBiglietti}\n";
        
        $partners = $ing->partners;
        echo "  Partners: " . $partners->count() . "\n";
        foreach ($partners as $p) {
            echo "    - {$p->nomePartner} (ID: {$p->idPartner})\n";
        }
    }
}

// Test homepage query
echo "\n=== HOMEPAGE QUERY TEST ===\n";
$partnerId = 5;

$locations = App\Models\Location::with(['categorie', 'tipologie', 'ingressi'])
    ->where('attivaLocation', true)
    ->whereHas('ingressi', function($q) use ($partnerId) {
        $q->where('ingressoAttivo', true)
          ->whereHas('partners', function($q2) use ($partnerId) {
              $q2->where('idPartnerIngressi', $partnerId);
          });
    })
    ->get();

echo "Locations found for partner {$partnerId}: " . $locations->count() . "\n";

if ($locations->count() > 0) {
    echo "✅ SUCCESS!\n";
    foreach ($locations as $loc) {
        echo "  - {$loc->nomeLocation}\n";
    }
} else {
    echo "❌ NO LOCATIONS FOUND\n";
    echo "Checking why...\n";
    
    // Debug: check if partner 5 exists
    $partner = App\Models\Partner::find(5);
    if (!$partner) {
        echo "  Problem: Partner ID 5 doesn't exist!\n";
        echo "  Available partners:\n";
        foreach (App\Models\Partner::all() as $p) {
            echo "    - ID {$p->idPartner}: {$p->nomePartner}\n";
        }
    }
}
