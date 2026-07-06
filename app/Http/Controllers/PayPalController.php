<?php

namespace App\Http\Controllers;

use App\Services\PayPalService;
use Illuminate\Http\Request;
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
    public function init()
    {
        // Get data from session
        $cart = session('cart', []);
        $customer = session('customer', []);
        $totaleAcquisto = session('totaleAcquisto', 0);

        if (empty($cart) || empty($customer)) {
            return redirect()->route('home')->with('error', 'Carrello vuoto o dati cliente mancanti');
        }

        // Calculate shipping cost
        $shippingCost = 0;
        if (isset($customer['tipoSpedizione'])) {
            $shipping = \App\Models\TipoSpedizione::find($customer['tipoSpedizione']);
            $shippingCost = $shipping ? $shipping->costoSpedizione : 0;
        }

        $totalAmount = $totaleAcquisto + $shippingCost;

        // Prepare line items for PayPal
        $items = [];
        foreach ($cart as $item) {
            $items[] = [
                'name' => $item['nomeIngresso'],
                'price' => $item['prezzoIngresso'],
                'quantity' => $item['quantita'],
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
            // Save token in session
            session(['paypal_token' => $result['token']]);
            session(['paypal_amount' => $totalAmount]);

            Log::info('PayPal checkout initiated', [
                'token' => $result['token'],
                'amount' => $totalAmount,
            ]);

            // Redirect to PayPal
            return redirect($result['redirect_url']);
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

        // Verify token matches session
        if ($token !== session('paypal_token')) {
            return redirect()->route('checkout.index')->with('error', 'Token PayPal non valido');
        }

        // Get payer details
        $details = $this->paypalService->getExpressCheckoutDetails($token);

        if (!$details['success']) {
            return redirect()->route('checkout.index')->with('error', 'Errore recupero dettagli PayPal');
        }

        // Save PayerID in session
        session(['paypal_payer_id' => $payerId]);

        Log::info('PayPal return', [
            'token' => $token,
            'payer_id' => $payerId,
            'email' => $details['email'] ?? null,
        ]);

        // Show confirmation page
        return view('payment.paypal-confirm', [
            'amount' => session('paypal_amount'),
            'cart' => session('cart'),
            'customer' => session('customer'),
            'payer' => $details,
            'location' => \App\Models\Location::find(session('idLocation')),
            'partner' => \App\Models\Partner::find(session('idPartner', 1)),
        ]);
    }

    /**
     * Confirm and complete payment
     */
    public function confirm(Request $request)
    {
        $token = session('paypal_token');
        $payerId = session('paypal_payer_id');
        $amount = session('paypal_amount');

        if (!$token || !$payerId || !$amount) {
            return redirect()->route('checkout.index')->with('error', 'Sessione PayPal scaduta');
        }

        // Complete payment
        $result = $this->paypalService->doExpressCheckoutPayment($token, $payerId, $amount);

        if ($result['success']) {
            Log::info('PayPal payment completed', [
                'transaction_id' => $result['transaction_id'],
                'amount' => $result['amount'],
                'status' => $result['status'],
            ]);

            // Save transaction ID in session for order creation
            session(['paypal_transaction_id' => $result['transaction_id']]);

            // Create order
            return redirect()->route('order.create');
        }

        // Payment failed
        Log::error('PayPal payment failed', $result);
        return redirect()->route('checkout.index')->with('error', 'Pagamento PayPal fallito: ' . $result['error']);
    }

    /**
     * Cancel payment
     */
    public function cancel()
    {
        Log::info('PayPal payment cancelled');

        // Clear PayPal session data
        session()->forget(['paypal_token', 'paypal_payer_id', 'paypal_amount']);

        return redirect()->route('checkout.index')->with('info', 'Pagamento PayPal annullato');
    }
}
