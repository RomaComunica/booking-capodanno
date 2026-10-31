<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\CategoriaLocation;
use App\Models\TipologiaLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Get partner ID from query string first (iframe-safe when 3rd-party cookies are blocked), fallback to session
        // Default is 5 = CRC
        $reqPartner = $request->query('partner') ?? $request->query('idPartner');
        $partnerId = ($reqPartner && is_numeric($reqPartner))
            ? (int) $reqPartner
            : (int) session('idPartner', 5);
        
        // Get partner info for display
        $partner = \App\Models\Partner::find($partnerId);
        
        // Check if iframe mode
        $iframe = $request->has('iframe') && $request->iframe == '1';

        // If a specific location ID is passed in query string (e.g. ?partner=X&location=Y), redirect directly to it
        $locationParam = $request->query('location') ?? $request->query('idLocation');
        if ($locationParam && is_numeric($locationParam) && (int) $locationParam > 0) {
            return redirect()->route('location.show', array_filter([
                'id' => (int) $locationParam,
                'idPartner' => $partnerId,
                'iframe' => $iframe ? '1' : null,
            ]));
        }

        $partnerTicketFilter = function ($q) use ($partnerId) {
            $q->where('ingressoAttivo', true)
              ->where(function ($subQuery) use ($partnerId) {
                  $subQuery->whereHas('partners', function ($partnerQuery) use ($partnerId) {
                      $partnerQuery->where('idPartnerIngressi', $partnerId);
                  })->orWhereExists(function ($sub) use ($partnerId) {
                      $sub->select(DB::raw(1))
                          ->from('cross_partner_ingressi')
                          ->where('cross_partner_ingressi.idPartnerIngressi', $partnerId)
                          ->whereColumn('cross_partner_ingressi.idLocation', 'tipologia_ingresso.idLocation')
                          ->whereColumn('cross_partner_ingressi.idTipologiaIngresso', 'tipologia_ingresso.idIngresso');
                  })->orWhere(function ($unassignedQuery) use ($partnerId) {
                      $unassignedQuery->whereNotExists(function ($noDirect) {
                          $noDirect->select(DB::raw(1))
                              ->from('cross_partner_ingressi')
                              ->whereColumn('cross_partner_ingressi.idTipologiaIngresso', 'tipologia_ingresso.idIngressoCont');
                      })->where(function ($locFallback) use ($partnerId) {
                          $locFallback->whereExists(function ($locHasPartner) use ($partnerId) {
                              $locHasPartner->select(DB::raw(1))
                                  ->from('cross_partner_ingressi')
                                  ->whereColumn('cross_partner_ingressi.idLocation', 'tipologia_ingresso.idLocation')
                                  ->where('cross_partner_ingressi.idPartnerIngressi', $partnerId);
                          })->orWhereNotExists(function ($locHasNoPartners) {
                              $locHasNoPartners->select(DB::raw(1))
                                  ->from('cross_partner_ingressi')
                                  ->whereColumn('cross_partner_ingressi.idLocation', 'tipologia_ingresso.idLocation');
                          });
                      });
                  });
              });
        };

        // Build query for locations
        $query = Location::with(['categorie', 'tipologie', 'ingressi'])
            ->where('attivaLocation', true);

        // Filter locations that have at least one active ticket associated with this partner
        $query->whereHas('ingressi', $partnerTicketFilter);

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
                    'idPartner' => $partnerId,
                    'iframe' => $iframe ? '1' : null,
                ]));
            }

            $matchingLocations = (clone $query)->limit(2)->get();
            if ($matchingLocations->count() === 1) {
                return redirect()->route('location.show', array_filter([
                    'id' => $matchingLocations->first()->idLocation,
                    'idPartner' => $partnerId,
                    'iframe' => $iframe ? '1' : null,
                ]));
            }
        }

        $locations = $query->paginate(12);
        $categorie = CategoriaLocation::all();
        $tipologie = TipologiaLocation::all();
        $availableLocations = Location::where('attivaLocation', true)
            ->whereHas('ingressi', $partnerTicketFilter)
            ->orderBy('nomeLocation')
            ->get();

        return view('home', compact('locations', 'categorie', 'tipologie', 'partner', 'partnerId', 'iframe', 'availableLocations'));
    }
}
