<?php

namespace App\Http\Controllers;

use App\Services\NexiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NexiController extends Controller
{
    private $nexiService;

    public function __construct(NexiService $nexiService)
    {
        $this->nexiService = $nexiService;
    }

    /**
     * Initialize Nexi payment
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

        // Generate unique transaction code
        $codTrans = $this->nexiService->generateTransactionCode();

        // Save transaction code in session
        session(['nexi_cod_trans' => $codTrans]);
        session(['nexi_amount' => $totalAmount]);

        // Set return and cancel URLs
        $returnUrl = route('payment.nexi.callback');
        $cancelUrl = route('payment.nexi.cancel');

        // Prepare payment data
        $paymentData = $this->nexiService->preparePaymentData($totalAmount, $codTrans, $returnUrl, $cancelUrl);

        Log::info('Nexi payment initiated', [
            'codTrans' => $codTrans,
            'amount' => $totalAmount,
        ]);

        // Show redirect form
        return view('payment.nexi-redirect', [
            'gatewayUrl' => $paymentData['gateway_url'],
            'alias' => $paymentData['params']['alias'],
            'importo' => $paymentData['params']['importo'],
            'divisa' => $paymentData['params']['divisa'],
            'codTrans' => $paymentData['params']['codTrans'],
            'returnUrl' => $paymentData['params']['url'],
            'cancelUrl' => $paymentData['params']['url_back'],
            'mac' => $paymentData['params']['mac'],
        ]);
    }

    /**
     * Callback from Nexi
     */
    public function callback(Request $request)
    {
        $params = $request->all();

        Log::info('Nexi callback received', $params);

        // Parse and verify callback
        $result = $this->nexiService->parseCallback($params);

        if ($result['success']) {
            // Payment successful
            Log::info('Nexi payment successful', [
                'transaction_id' => $result['transaction_id'],
                'cod_trans' => $result['cod_trans'],
                'amount' => $result['amount'],
            ]);

            // Save transaction ID in session
            session(['nexi_transaction_id' => $result['transaction_id']]);

            // Create order
            return redirect()->route('order.create');
        }

        // Payment failed
        Log::error('Nexi payment failed', $result);

        return redirect()->route('checkout.index')->with('error', 'Pagamento fallito: ' . $result['error']);
    }

    /**
     * Server-to-server notification (optional)
     */
    public function notify(Request $request)
    {
        $params = $request->all();

        Log::info('Nexi S2S notification received', $params);

        // Parse and verify
        $result = $this->nexiService->parseCallback($params);

        if ($result['success']) {
            // Update order status if needed
            // This is called asynchronously by Nexi
            Log::info('Nexi S2S notification processed', $result);

            return response('OK', 200);
        }

        Log::error('Nexi S2S notification failed', $result);

        return response('KO', 400);
    }

    /**
     * Cancel payment
     */
    public function cancel()
    {
        Log::info('Nexi payment cancelled');

        // Clear Nexi session data
        session()->forget(['nexi_cod_trans', 'nexi_amount', 'nexi_transaction_id']);

        return redirect()->route('checkout.index')->with('info', 'Pagamento Nexi annullato');
    }
}
