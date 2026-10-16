<?php

namespace App\Http\Controllers;

use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    private $paypalService;

    public function __construct(PayPalService $paypalService)
    {
        $this->paypalService = $paypalService;
    }

    /**
     * Initialize PayPal payment
     */
    public function init(Request $request)
    {
        if ((empty(session('cart')) || empty(session('customer'))) && $request->filled('tx')) {
            $cached = Cache::get('checkout_tx_' . $request->query('tx'));
            if (is_array($cached)) {
                session($cached);
            }
        }

        // Get data from session
        $cart = session('cart', []);
        $customer = session('customer', []);
        $totaleAcquisto = (float) session('totaleAcquisto', 0);
        $idLocation = session('idLocation');

        if (empty($cart) || empty($customer)) {
            return redirect()->route('home')->with('error', 'Carrello vuoto o dati cliente mancanti');
        }

        // Calculate shipping cost (matching OrderController::calculateTotal)
        $shippingCost = 0.0;
        if (isset($customer['tipoSpedizione'])) {
            $shippingPivot = DB::table('cross_spedizioni_location')
                ->where('idSpedizione', $customer['tipoSpedizione'])
                ->where('idLocationSpedizione', $idLocation)
                ->first();

            if ($shippingPivot && isset($shippingPivot->costoSpedizione)) {
                $shippingCost = (float) $shippingPivot->costoSpedizione;
            } else {
                $shipping = \App\Models\TipoSpedizione::find($customer['tipoSpedizione']);
                $shippingCost = $shipping && isset($shipping->costoSpedizione) ? (float) $shipping->costoSpedizione : 0.0;
            }
        }

        // Calculate PayPal fee (+2€ per ticket)
        $totalTickets = (int) array_sum(array_column($cart, 'quantita'));
        $paypalFee = 2.00 * $totalTickets;

        $totalAmount = $totaleAcquisto + $shippingCost + $paypalFee;

        // Prepare line items for PayPal (use unit subtotal so rimborso option is included and sums match)
        $items = [];
        foreach ($cart as $item) {
            $qty = max(1, (int) ($item['quantita'] ?? 1));
            $unitPrice = isset($item['subtotale']) ? ((float) $item['subtotale'] / $qty) : (float) $item['prezzoIngresso'];
            $items[] = [
                'name' => $item['nomeIngresso'],
                'price' => $unitPrice,
                'quantity' => $qty,
            ];
        }

        // Add PayPal fee line item if > 0
        if ($paypalFee > 0) {
            $items[] = [
                'name' => 'Costi di gestione PayPal (+2€/biglietto)',
                'price' => $paypalFee,
                'quantity' => 1,
            ];
        }

        // Add shipping as line item if > 0
        if ($shippingCost > 0) {
            $items[] = [
                'name' => 'Spedizione',
                'price' => $shippingCost,
                'quantity' => 1,
            ];
        }

        // Set return and cancel URLs
        $returnUrl = route('payment.paypal.return');
        $cancelUrl = route('payment.paypal.cancel');

        // Call PayPal SetExpressCheckout
        $result = $this->paypalService->setExpressCheckout($totalAmount, $returnUrl, $cancelUrl, $items);

        if ($result['success']) {
            $token = $result['token'];

            // Save token in session
            session([
                'paypal_token' => $token,
                'paypal_amount' => $totalAmount,
            ]);

            // Persist checkout session in Cache keyed by PayPal token for cross-site / iframe return
            Cache::put('checkout_tx_' . $token, [
                'cart' => $cart,
                'customer' => $customer,
                'idLocation' => $idLocation,
                'nomeLocation' => session('nomeLocation'),
                'idPartner' => session('idPartner', 5),
                'totaleAcquisto' => $totaleAcquisto,
                'iframe' => session('iframe', false),
                'paypal_token' => $token,
                'paypal_amount' => $totalAmount,
            ], now()->addHours(6));

            Log::info('PayPal checkout initiated', [
                'token' => $token,
                'amount' => $totalAmount,
            ]);

            // Redirect to PayPal (breaking out of iframe to _top so PayPal X-Frame-Options does not block it)
            $redirectUrl = $result['redirect_url'];
            return response(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reindirizzamento a PayPal...</title></head>' .
                '<body style="background:#121212;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center;">' .
                '<div><h3>Reindirizzamento a PayPal...</h3><p>Attendere prego...</p>' .
                '<p><a href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '" target="_top" style="display:inline-block;margin-top:12px;padding:10px 22px;background:#FFD700;color:#121212;text-decoration:none;border-radius:30px;font-weight:bold;">Procedi su PayPal &rarr;</a></p></div>' .
                '<script>try { window.top.location.href = ' . json_encode($redirectUrl) . '; } catch(e) { window.location.href = ' . json_encode($redirectUrl) . '; }</script>' .
                '</body></html>'
            );
        }

        // Error
        Log::error('PayPal init failed', $result);
        return redirect()->route('checkout.index')->with('error', 'Errore PayPal: ' . $result['error']);
    }

    /**
     * Return from PayPal
     */
    public function return(Request $request)
    {
        $token = $request->get('token');
        $payerId = $request->get('PayerID');

        if (!$token || !$payerId) {
            return redirect()->route('checkout.index')->with('error', 'Parametri PayPal mancanti');
        }

        // Restore session from cache if partitioned iframe cookie was not sent on top-level return
        if (empty(session('cart')) || empty(session('customer')) || !session('paypal_token')) {
            $cached = Cache::get('checkout_tx_' . $token);
            if (is_array($cached)) {
                session($cached);
            }
        }

        // Verify token matches session or cache
        if ($token !== session('paypal_token') && !Cache::has('checkout_tx_' . $token)) {
            return redirect()->route('checkout.index')->with('error', 'Token PayPal non valido');
        }

        // Get payer details
        $details = $this->paypalService->getExpressCheckoutDetails($token);

        if (!$details['success']) {
            return redirect()->route('checkout.index')->with('error', 'Errore recupero dettagli PayPal');
        }

        // Save PayerID in session and cache
        session([
            'paypal_token' => $token,
            'paypal_payer_id' => $payerId,
        ]);

        if (Cache::has('checkout_tx_' . $token)) {
            $cached = Cache::get('checkout_tx_' . $token);
            $cached['paypal_payer_id'] = $payerId;
            Cache::put('checkout_tx_' . $token, $cached, now()->addHours(6));
        }

        Log::info('PayPal return', [
            'token' => $token,
            'payer_id' => $payerId,
            'email' => $details['email'] ?? null,
        ]);

        // Show confirmation page
        return view('payment.paypal-confirm', [
            'token' => $token,
            'payerId' => $payerId,
            'amount' => session('paypal_amount'),
            'cart' => session('cart'),
            'customer' => session('customer'),
            'payer' => $details,
            'location' => \App\Models\Location::find(session('idLocation')),
            'partner' => \App\Models\Partner::find(session('idPartner', 5)),
        ]);
    }

    /**
     * Confirm and complete payment
     */
    public function confirm(Request $request)
    {
        $token = $request->input('token') ?: session('paypal_token');
        $payerId = $request->input('payer_id') ?: session('paypal_payer_id');

        if ($token && (empty(session('cart')) || empty(session('customer')) || !session('paypal_amount'))) {
            $cached = Cache::get('checkout_tx_' . $token);
            if (is_array($cached)) {
                session($cached);
            }
        }

        $amount = session('paypal_amount');
        if (!$payerId) {
            $payerId = session('paypal_payer_id');
        }

        if (!$token || !$payerId || !$amount) {
            return redirect()->route('checkout.index')->with('error', 'Sessione PayPal scaduta');
        }

        if (Cache::has('checkout_done_' . $token)) {
            return redirect()->to(Cache::get('checkout_done_' . $token));
        }

        // Complete payment
        $result = $this->paypalService->doExpressCheckoutPayment($token, $payerId, $amount);

        if ($result['success']) {
            Log::info('PayPal payment completed', [
                'transaction_id' => $result['transaction_id'],
                'amount' => $result['amount'],
                'status' => $result['status'],
            ]);

            // Save transaction ID in session and cache for order creation
            session(['paypal_transaction_id' => $result['transaction_id']]);
            session()->forget('ordinecompletato');

            if (Cache::has('checkout_tx_' . $token)) {
                $cached = Cache::get('checkout_tx_' . $token);
                $cached['paypal_transaction_id'] = $result['transaction_id'];
                Cache::put('checkout_tx_' . $token, $cached, now()->addHours(6));
            }

            // Create order
            return redirect()->route('order.create', ['tx' => $token]);
        }

        // Payment failed
        Log::error('PayPal payment failed', $result);
        return redirect()->route('checkout.index')->with('error', 'Pagamento PayPal fallito: ' . $result['error']);
    }

    /**
     * Cancel payment
     */
    public function cancel(Request $request)
    {
        $token = $request->query('token') ?? session('paypal_token');
        Log::info('PayPal payment cancelled', ['token' => $token]);

        if ($token && (empty(session('cart')) || empty(session('customer')))) {
            $cached = Cache::get('checkout_tx_' . $token);
            if (is_array($cached)) {
                session($cached);
            }
        }

        // Clear PayPal session data
        session()->forget(['paypal_token', 'paypal_payer_id', 'paypal_amount']);

        return redirect()->route('checkout.index')->with('info', 'Pagamento PayPal annullato');
    }
}

