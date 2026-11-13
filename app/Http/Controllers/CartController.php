<?php

namespace App\Http\Controllers;

use App\Models\TipologiaIngresso;
use App\Models\Location;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function add(Request $request)
    {
        \Log::info('CartController::add called', [
            'idLocation' => $request->input('idLocation'),
            'idPartner' => $request->input('idPartner'),
        ]);

        // Reset previous order completion flag if starting a new cart
        session()->forget('ordinecompletato');

        // Validate basic required fields
        $request->validate([
            'idLocation' => 'required|exists:location,idLocation',
            'idPartner' => 'required',
            'totaleAcquisto' => 'nullable|numeric|min:0'
        ]);

        $cart = [];
        $serverTotale = 0.0;
        $location = Location::findOrFail($request->idLocation);
        
        foreach ($request->all() as $key => $value) {
            if (strpos($key, 'quantitaIngresso_') === 0 && (int) $value > 0) {
                $idIngresso = (int) str_replace('quantitaIngresso_', '', $key);
                $qty = (int) $value;
                
                // Get ticket info directly from DB (never trust client-submitted price)
                $ingresso = TipologiaIngresso::where('idIngressoCont', $idIngresso)
                    ->where('idLocation', $location->idLocation)
                    ->first();

                if (!$ingresso || !$ingresso->ingressoAttivo) {
                    \Log::warning("Ticket $idIngresso not found or inactive for location {$location->idLocation}");
                    continue;
                }

                if ($qty > (int) $ingresso->giacenzaBiglietti) {
                    return redirect()->back()->with('error', "Quantità richiesta non disponibile per {$ingresso->nomeIngresso}.");
                }
                
                $prezzo = (float) $ingresso->prezzoIngresso;

                $dataIngresso = $ingresso->dataIngresso
                    ? (\is_string($ingresso->dataIngresso) ? $ingresso->dataIngresso : $ingresso->dataIngresso->format('Y-m-d'))
                    : $request->input("dataIngresso_{$idIngresso}");

                $rawOrario = $ingresso->getAttributes()['orarioIngresso'] ?? $ingresso->orarioIngresso;
                $orarioIngresso = null;
                if (!empty($rawOrario)) {
                    if ($rawOrario instanceof \DateTimeInterface) {
                        $orarioIngresso = $rawOrario->format('H:i');
                    } else {
                        $clean = trim((string)$rawOrario);
                        if (preg_match('/(\d{1,2})[:.](\d{2})/', $clean, $m)) {
                            $orarioIngresso = sprintf('%02d:%02d', $m[1], $m[2]);
                        }
                    }
                }
                if (empty($orarioIngresso) || $orarioIngresso === '00:00') {
                    $reqOrario = $request->input("orarioIngresso_{$idIngresso}");
                    if (!empty($reqOrario) && preg_match('/(\d{1,2})[:.](\d{2})/', trim((string)$reqOrario), $m)) {
                        $orarioIngresso = sprintf('%02d:%02d', $m[1], $m[2]);
                    }
                }
                $ingressoSenzaData = $ingresso->ingressoSenzaData ? 1 : 0;
                $etichettaIngresso = $ingresso->etichettaIngresso;

                $rimborsoValido = $request->has("rimborso_{$idIngresso}")
                    && $ingresso->opzioneRimborso
                    && (!$ingresso->rimborsoDataScadenza || now()->lessThanOrEqualTo($ingresso->rimborsoDataScadenza));
                $rimborsoValore = $rimborsoValido ? (float) $ingresso->rimborsoValorePersona : 0.0;
                $subtotale = round(($prezzo * $qty) + ($rimborsoValore * $qty), 2);
                $serverTotale += $subtotale;

                $cart[] = [
                    'idIngresso' => $idIngresso,
                    'nomeIngresso' => $ingresso->nomeIngresso,
                    'prezzoIngresso' => $prezzo,
                    'quantita' => $qty,
                    'dataIngresso' => $dataIngresso,
                    'orarioIngresso' => $orarioIngresso,
                    'ingressoSenzaData' => $ingressoSenzaData,
                    'senzaData' => $ingressoSenzaData,
                    'etichettaIngresso' => $etichettaIngresso,
                    'etichetta' => $etichettaIngresso,
                    'rimborso' => $rimborsoValido ? 1 : 0,
                    'rimborsoValore' => $rimborsoValore,
                    'subtotale' => $subtotale,
                ];
            }
        }
        
        if (empty($cart) || $serverTotale <= 0) {
            \Log::warning('Cart empty, redirecting back');
            return redirect()->back()->with('error', 'Seleziona almeno un biglietto');
        }
        
        // Save to session using server-calculated total
        session([
            'cart' => $cart,
            'idLocation' => $location->idLocation,
            'nomeLocation' => $location->nomeLocation,
            'idPartner' => $request->idPartner,
            'totaleAcquisto' => round($serverTotale, 2),
            'iframe' => $request->has('iframe') && $request->iframe == '1'
        ]);
        
        return redirect()->route('checkout.index', ['iframe' => session('iframe') ? '1' : null]);
    }

    public function index()
    {
        $cart = session()->get('cart', []);
        $total = collect($cart)->sum(function($item) {
            return $item['prezzoIngresso'] * $item['quantita'];
        });

        return view('cart.index', compact('cart', 'total'));
    }

    public function remove($id)
    {
        $cart = session()->get('cart', []);
        unset($cart[$id]);
        session()->put('cart', $cart);

        return redirect()->back()->with('success', 'Rimosso dal carrello');
    }

    public function clear()
    {
        session()->forget('cart');
        return redirect()->back()->with('success', 'Carrello svuotato');
    }
}
