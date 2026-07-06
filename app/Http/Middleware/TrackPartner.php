<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPartner
{
    /**
     * Handle an incoming request.
     *
     * Questo middleware gestisce il tracking del partner attraverso le sessioni.
     * Legge il parametro idPartner dalla query string e lo salva in sessione.
     * Se non è presente, usa il valore dalla sessione o default a 5 (gestore principale).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Supporto per entrambi i parametri: 'partner' (nuovo standard) e 'idPartner' (legacy)
        $partnerId = $request->query('partner') ?? $request->query('idPartner');
        
        if ($partnerId && is_numeric($partnerId)) {
            session(['partner_id' => $partnerId]); // Nuovo standard sessione
            session(['idPartner' => $partnerId]);  // Legacy support
        }
        
        // Tracking Location (opzionale, per pre-selezione)
        if ($request->has('location')) {
            $locationId = $request->query('location');
            if (is_numeric($locationId)) {
                session(['location_id' => $locationId]);
            }
        }
        
        // Default partner se non esiste (5 = CRC)
        if (!session()->has('partner_id')) {
            session(['partner_id' => 5]);
            session(['idPartner' => 5]);
        }

        return $next($request);
    }
}
