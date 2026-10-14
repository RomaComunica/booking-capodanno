<?php

namespace App\Http\Controllers;

use App\Models\TipologiaIngresso;
use App\Models\Location;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function add(Request $request)
    {
        \Log::info('CartController::add called', $request->all());

        // Reset previous order completion flag if starting a new cart
        session()->forget('ordinecompletato');

        // Validate basic required fields
        $request->validate([
            'idLocation' => 'required|exists:location,idLocation',
            'idPartner' => 'required',
            'totaleAcquisto' => 'required|numeric|min:0.01'
        ]);

        $cart = [];
        $location = Location::findOrFail($request->idLocation);
        
        \Log::info('Looping through fields');
        foreach ($request->all() as $key => $value) {
            if (strpos($key, 'quantitaIngresso_') === 0 && $value > 0) {
                // ... logic ...
                \Log::info("Found ticket: $key with qty: $value");
                $idIngresso = str_replace('quantitaIngresso_', '', $key);
                $qty = intval($value);
                
                // Get ticket info
                $ingresso = TipologiaIngresso::find($idIngresso);
                if (!$ingresso) {
                    \Log::error("Ticket $idIngresso not found");
                    continue;
                }
                
                // ... rest of logic ...
                // Get data from hidden fields
                $prezzo = floatval($request->input("prezzoIngresso_{$idIngresso}"));
                
                // ...
                
                // Calculate subtotal
                // ...

                $dataIngresso = $request->input("dataIngresso_{$idIngresso}") ?: ($ingresso->dataIngresso ? (\is_string($ingresso->dataIngresso) ? $ingresso->dataIngresso : $ingresso->dataIngresso->format('Y-m-d')) : null);
                $orarioIngresso = $request->input("orarioIngresso_{$idIngresso}") ?: ($ingresso->orarioIngresso ? (\is_string($ingresso->orarioIngresso) ? $ingresso->orarioIngresso : \Carbon\Carbon::parse($ingresso->orarioIngresso)->format('H:i')) : null);
                $ingressoSenzaData = $request->has("ingressoSenzaData_{$idIngresso}") ? ($request->input("ingressoSenzaData_{$idIngresso}") ? 1 : 0) : ($ingresso->ingressoSenzaData ? 1 : 0);
                $etichettaIngresso = $request->input("etichettaIngresso_{$idIngresso}") ?: $ingresso->etichettaIngresso;

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
                    'rimborso' => $request->has("rimborso_{$idIngresso}") ? 1 : 0,
                    'rimborsoValore' => $request->has("rimborso_{$idIngresso}") && $ingresso->opzioneRimborso ? floatval($ingresso->rimborsoValorePersona) : 0,
                    'subtotale' => ($prezzo * $qty) + (($request->has("rimborso_{$idIngresso}") && $ingresso->opzioneRimborso ? floatval($ingresso->rimborsoValorePersona) : 0) * $qty)
                ];
            }
        }
        
        \Log::info('Cart content:', $cart);
        
        if (empty($cart)) {
            \Log::warning('Cart empty, redirecting back');
            return redirect()->back()->with('error', 'Seleziona almeno un biglietto');
        }
        
        // Save to session
        session([
            'cart' => $cart,
            'idLocation' => $request->idLocation,
            'nomeLocation' => $location->nomeLocation,
            'idPartner' => $request->idPartner,
            'totaleAcquisto' => $request->totaleAcquisto,
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
