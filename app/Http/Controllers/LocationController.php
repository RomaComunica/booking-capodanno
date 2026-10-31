<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function show(Request $request, $id)
    {
        // Get partner ID from query string first (iframe-safe when 3rd-party cookies are blocked), fallback to session
        $reqPartner = $request->query('partner') ?? $request->query('idPartner');
        $partnerId = ($reqPartner && is_numeric($reqPartner))
            ? (int) $reqPartner
            : (int) session('idPartner', 5);
        
        // Get partner info for display
        $partner = Partner::find($partnerId);
        
        // Check if iframe mode
        $iframe = $request->has('iframe') && $request->iframe == '1';

        // Load location with basic info
        $location = Location::with(['categorie', 'tipologie'])
            ->findOrFail($id);

        if (!$location->attivaLocation) {
            $ingressi = collect();
            return view('location.show', compact('location', 'ingressi', 'partner', 'partnerId', 'iframe'));
        }

        // Load active tickets for this location and partner
        // Supports:
        // 1. Direct association via idIngressoCont (Filament)
        // 2. Legacy association via idLocation + idIngresso
        // 3. Fallback to location-level partner association if ticket was created without explicit rows in cross_partner_ingressi
        $ingressi = $location->ingressi()
            ->where('ingressoAttivo', true)
            ->where(function ($query) use ($partnerId) {
                $query->whereHas('partners', function ($q) use ($partnerId) {
                    $q->where('idPartnerIngressi', $partnerId);
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
            })
            ->orderBy('ordine', 'asc')
            ->orderBy('dataIngresso', 'asc')
            ->orderBy('prezzoIngresso', 'asc')
            ->get()
            ->map(function($ingresso) {
                // Calculate if refund option is available
                $ingresso->mostraRimborso = $ingresso->opzioneRimborso 
                    && $ingresso->rimborsoValorePersona > 0
                    && $ingresso->rimborsoDataScadenza 
                    && $ingresso->rimborsoDataScadenza > now();
                
                return $ingresso;
            });

        return view('location.show', compact('location', 'ingressi', 'partner', 'partnerId', 'iframe'));
    }
}
