<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use App\Models\RigaOrdine;
use App\Models\Location;
use App\Models\Partner;
use App\Models\TipoPagamento;
use App\Models\TipoSpedizione;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        // Get partner and iframe from session/request
        $partnerId = session('idPartner', 5);
        $partner = Partner::find($partnerId);
        $iframe = $request->has('iframe') && $request->iframe == '1';
        
        // Get cart from session
        $cart = session('cart', []);
        $idLocation = session('idLocation');
        $nomeLocation = session('nomeLocation');
        $totaleAcquisto = session('totaleAcquisto', 0);
        
        if (empty($cart)) {
            return redirect()->route('home')->with('error', 'Carrello vuoto');
        }
        
        // Load location
        $location = Location::find($idLocation);
        
        // Load payment methods available for this location
        $paymentMethods = TipoPagamento::whereHas('locations', function($q) use ($idLocation) {
            $q->where('idLocationPagamento', $idLocation)
              ->where('pagamentoLocationAttivo', 1);
        })->get();

        // Fallback: if no location cross entries exist yet, load all standard payment methods
        if ($paymentMethods->isEmpty()) {
            $paymentMethods = TipoPagamento::whereIn('idTipoPagamento', [1, 2, 3, 4, 5])->get();
        }
        
        // Filter out globally disabled methods
        $paymentMethods = $paymentMethods->filter(function ($method) {
            if ($method->idTipoPagamento == 1 && !\App\Models\Setting::getVal('paypal_enabled', true)) {
                return false;
            }
            if ($method->idTipoPagamento == 2 && !\App\Models\Setting::getVal('bonifico_enabled', true)) {
                return false;
            }
            if ($method->idTipoPagamento == 3 && !\App\Models\Setting::getVal('contanti_enabled', true)) {
                return false;
            }
            if ($method->idTipoPagamento == 4 && !\App\Models\Setting::getVal('postepay_enabled', true)) {
                return false;
            }
            if ($method->idTipoPagamento == 5 && !\App\Models\Setting::getVal('nexi_enabled', true)) {
                return false;
            }
            return true;
        })->values();
        
        // Load shipping methods available for this location
        $shippingMethods = TipoSpedizione::whereHas('locations', function($q) use ($idLocation) {
            $q->where('idLocationSpedizione', $idLocation)
              ->where('spedizioneLocationAttiva', 1);
        })->get();

        // Fallback: if no location cross entries exist yet, load standard shipping methods
        if ($shippingMethods->isEmpty()) {
            $shippingMethods = TipoSpedizione::whereIn('idSpedizione', [1, 4])->get();
        }

        // Ensure Ritiro in sede (ID 4) is present if missing
        if (!$shippingMethods->contains('idSpedizione', 4)) {
            $ritiro = TipoSpedizione::find(4);
            if ($ritiro) {
                $shippingMethods->push($ritiro);
            }
        }
        
        return view('checkout.index', compact(
            'cart', 
            'location',
            'nomeLocation',
            'totaleAcquisto', 
            'partner', 
            'partnerId', 
            'iframe',
            'paymentMethods',
            'shippingMethods'
        ));
    }

    public function process(Request $request)
    {
        // DEBUG: Log all request data
        \Log::info('Checkout process called', [
            'all_data' => $request->all(),
            'has_tipoPagamento' => $request->has('tipoPagamento'),
            'has_tipoSpedizione' => $request->has('tipoSpedizione'),
            'tipoPagamento_value' => $request->input('tipoPagamento'),
            'tipoSpedizione_value' => $request->input('tipoSpedizione'),
        ]);
        
        // Validate customer data + payment/shipping selection
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cognome' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'ripetiemail' => 'required|email|same:email',
            'telefono' => 'required|string|max:50',
            'citta' => 'nullable|string|max:255',
            'tipoPagamento' => 'required|integer|exists:tipo_pagamenti,idTipoPagamento',
            'tipoSpedizione' => 'required|integer|exists:tipo_spedizione,idSpedizione',
            'privacy' => 'accepted',
        ], [
            'nome.required' => 'Il nome è obbligatorio',
            'cognome.required' => 'Il cognome è obbligatorio',
            'email.required' => 'L\'email è obbligatoria',
            'email.email' => 'Inserisci un\'email valida',
            'ripetiemail.same' => 'Le email non corrispondono',
            'telefono.required' => 'Il telefono è obbligatorio',
            'tipoPagamento.required' => 'Seleziona un metodo di pagamento',
            'tipoSpedizione.required' => 'Seleziona un metodo di spedizione',
            'privacy.accepted' => 'È obbligatorio accettare l\'informativa sulla privacy per procedere.',
        ]);

        // Enforcement: Se viene scelto il pagamento in Contanti (ID 3), il metodo di spedizione DEVE essere Ritiro in sede (non Voucher via email)
        if ((int)$validated['tipoPagamento'] === 3) {
            $shippingMethod = TipoSpedizione::find($validated['tipoSpedizione']);
            $isEmail = $shippingMethod && ($shippingMethod->idSpedizione == 1 || str_contains(strtolower($shippingMethod->nomeSpedizione . ' ' . $shippingMethod->descrizioneSpedizione), 'mail'));
            if ($isEmail) {
                return redirect()->back()->withInput()->withErrors([
                    'tipoSpedizione' => 'Per il pagamento in contanti il metodo di spedizione deve essere "Ritiro in sede". Non è possibile ricevere il voucher via email prima del pagamento.',
                ]);
            }
        }
        
        \Log::info('Validation passed', ['validated' => $validated]);
        
        // Save customer data to session
        session(['customer' => $validated]);
        
        // Redirect based on payment method
        // For Phase 4A, we'll create order directly for all methods except PayPal/Nexi
        // PayPal = 1, Nexi = 5
        if ($validated['tipoPagamento'] == 1) {
            // PayPal - will implement in Phase 4C
            return redirect()->route('payment.paypal.init');
        } elseif ($validated['tipoPagamento'] == 5) {
            // Nexi - will implement in Phase 4D
            return redirect()->route('payment.nexi.init');
        } else {
            // Other payments: create order directly
            \Log::info('Redirecting to order.create');
            return redirect()->route('order.create');
        }
    }

    public function success($ordineId)
    {
        // Temporary success page
        // In Phase 4, this will show actual order details after payment
        
        $cart = session('cart', []);
        $customer = session('customer', []);
        $totaleAcquisto = session('totaleAcquisto', 0);
        $nomeLocation = session('nomeLocation', '');
        
        return view('checkout.success', compact('cart', 'customer', 'totaleAcquisto', 'nomeLocation', 'ordineId'));
    }
}
