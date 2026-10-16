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
                // Get data from hidden fields (with fallback to DB price)
                $rawPrezzo = str_replace(',', '.', (string) $request->input("prezzoIngresso_{$idIngresso}", ''));
                $prezzo = floatval($rawPrezzo);
                if ($prezzo <= 0 && $ingresso->prezzoIngresso > 0) {
                    $prezzo = (float) $ingresso->prezzoIngresso;
                }
                
                // ...
                
                // Calculate subtotal
                // ...

                $dataIngresso = $request->input("dataIngresso_{$idIngresso}") ?: ($ingresso->dataIngresso ? (\is_string($ingresso->dataIngresso) ? $ingresso->dataIngresso : $ingresso->dataIngresso->format('Y-m-d')) : null);
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
