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
        // Get partner ID from session (set by TrackPartner middleware)
        $partnerId = session('idPartner', 5);
        
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

        // Load tickets with advanced filtering
        $ingressi = $location->ingressi()
            ->where('ingressoAttivo', true)
            ->whereHas('partners', function($q) use ($partnerId) {
                $q->where('idPartnerIngressi', $partnerId);
            })
            ->where(function($query) {
                // Filter tickets based on giorniPrimaIngresso
                // Only show tickets where: current_date + giorniPrimaIngresso <= dataIngresso
                if (DB::connection()->getDriverName() === 'sqlite') {
                    $query->whereRaw('julianday(dataIngresso) - julianday(date("now")) >= COALESCE(giorniPrimaIngresso, 0)');
                } else {
                    $query->whereRaw('DATEDIFF(dataIngresso, CURDATE()) >= COALESCE(giorniPrimaIngresso, 0)');
                }
                $query->orWhereNull('dataIngresso'); // Include tickets without date
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
