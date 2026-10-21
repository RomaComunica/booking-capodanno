<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\CategoriaLocation;
use App\Models\TipologiaLocation;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Get partner ID from session (set by TrackPartner middleware)
        // Default is 5 = CRC
        $partnerId = session('idPartner', 5);
        
        // Get partner info for display
        $partner = \App\Models\Partner::find($partnerId);
        
        // Check if iframe mode
        $iframe = $request->has('iframe') && $request->iframe == '1';

        // Build query for locations
        $query = Location::with(['categorie', 'tipologie', 'ingressi'])
            ->where('attivaLocation', true);

        // Filter locations that have at least one active ticket associated with this partner
        $query->whereHas('ingressi', function($q) use ($partnerId) {
            $q->where('ingressoAttivo', true)
              ->whereHas('partners', function($partnerQuery) use ($partnerId) {
                  $partnerQuery->where('idPartnerIngressi', $partnerId);
              });
        });

        // Filter by category
        if ($request->has('categoria') && $request->categoria) {
            $query->whereHas('categorie', function($q) use ($request) {
                $q->where('cross_location_categorie.idCatLocation', $request->categoria);
            });
        }

        // Filter by type
        if ($request->has('tipologia') && $request->tipologia) {
            $query->whereHas('tipologie', function($q) use ($request) {
                $q->where('cross_location_tipologie.idTipLocation', $request->tipologia);
            });
        }

        // Search by name
        if ($request->filled('search')) {
            $searchTerm = trim((string) $request->search);
            $query->where('nomeLocation', 'LIKE', '%' . $searchTerm . '%');

            // If search matches a single location (or exact name match), redirect straight to its tickets page
            $exactMatch = (clone $query)
                ->whereRaw('LOWER(TRIM(nomeLocation)) = ?', [strtolower($searchTerm)])
                ->first();

            if ($exactMatch) {
                return redirect()->route('location.show', array_filter([
                    'id' => $exactMatch->idLocation,
                    'iframe' => $iframe ? '1' : null,
                ]));
            }

            $matchingLocations = (clone $query)->limit(2)->get();
            if ($matchingLocations->count() === 1) {
                return redirect()->route('location.show', array_filter([
                    'id' => $matchingLocations->first()->idLocation,
                    'iframe' => $iframe ? '1' : null,
                ]));
            }
        }

        $locations = $query->paginate(12);
        $categorie = CategoriaLocation::all();
        $tipologie = TipologiaLocation::all();
        $availableLocations = Location::where('attivaLocation', true)
            ->whereHas('ingressi', function($q) use ($partnerId) {
                $q->where('ingressoAttivo', true)
                  ->whereHas('partners', function($partnerQuery) use ($partnerId) {
                      $partnerQuery->where('idPartnerIngressi', $partnerId);
                  });
            })
            ->orderBy('nomeLocation')
            ->get();

        return view('home', compact('locations', 'categorie', 'tipologie', 'partner', 'partnerId', 'iframe', 'availableLocations'));
    }
}
