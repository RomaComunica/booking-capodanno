<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Location;
use App\Models\TipologiaIngresso;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;

echo "=== DEBUGGING LOCATION VISIBILITY ===\n\n";

// Check locations
$locations = Location::where('attivaLocation', 1)->get();
echo "Active Locations: " . $locations->count() . "\n";

foreach ($locations as $loc) {
    echo "\n--- Location: {$loc->nomeLocation} (ID: {$loc->idLocation}) ---\n";
    
    // Check tickets
    $tickets = $loc->ingressi()->where('ingressoAttivo', 1)->get();
    echo "Active Tickets: " . $tickets->count() . "\n";
    
    foreach ($tickets as $ticket) {
        echo "  - {$ticket->nomeIngresso} (ID: {$ticket->idIngressoCont})\n";
        echo "    Giacenza: {$ticket->giacenzaBiglietti}\n";
        echo "    Data: {$ticket->dataIngresso}\n";
        echo "    Ora: {$ticket->orarioIngresso}\n";
        
        // Check partner associations
        $partners = $ticket->partners()->get();
        echo "    Partners: " . $partners->count() . "\n";
        foreach ($partners as $p) {
            echo "      * {$p->nomePartner} (ID: {$p->idPartner})\n";
        }
        
        // Check cross_partner_ingressi directly
        $cross = DB::table('cross_partner_ingressi')
            ->where('idTipologiaIngresso', $ticket->idIngressoCont)
            ->get();
        echo "    cross_partner_ingressi records: " . $cross->count() . "\n";
        foreach ($cross as $c) {
            echo "      * Partner: {$c->idPartnerIngressi}, Location: {$c->idlocation}, Qty: {$c->quantitaIngressi}\n";
        }
    }
}

echo "\n=== TEST HOMEPAGE QUERY (Partner ID 5) ===\n";
$partnerId = 5;

$query = Location::with(['categorie', 'tipologie', 'ingressi'])
    ->where('attivaLocation', true);

// Filter locations that have at least one active ticket associated with this partner
$query->whereHas('ingressi', function($q) use ($partnerId) {
    $q->where('ingressoAttivo', true)
      ->whereHas('partners', function($q2) use ($partnerId) {
          $q2->where('idPartnerIngressi', $partnerId);
      });
});

$results = $query->get();
echo "Locations visible for partner {$partnerId}: " . $results->count() . "\n";

if ($results->count() == 0) {
    echo "\n❌ PROBLEM: No locations found!\n";
    echo "Checking why...\n\n";
    
    // Check if partner exists
    $partner = Partner::find($partnerId);
    if (!$partner) {
        echo "- Partner ID {$partnerId} does NOT exist!\n";
    } else {
        echo "- Partner ID {$partnerId} exists: {$partner->nomePartner}\n";
    }
    
    // Check cross_partner_ingressi for this partner
    $cross = DB::table('cross_partner_ingressi')
        ->where('idPartnerIngressi', $partnerId)
        ->get();
    echo "- cross_partner_ingressi records for partner {$partnerId}: " . $cross->count() . "\n";
    
    if ($cross->count() == 0) {
        echo "\n⚠️ ISSUE: No tickets associated with partner {$partnerId} in cross_partner_ingressi!\n";
        echo "You need to associate tickets with this partner.\n";
    }
} else {
    echo "\n✅ SUCCESS: Locations found!\n";
    foreach ($results as $loc) {
        echo "  - {$loc->nomeLocation}\n";
    }
}
