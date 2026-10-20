<?php

namespace App\Http\Controllers;

use App\Services\NexiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
    public function init(Request $request)
    {
        // Restore from cache token if session was lost in iframe redirect
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

        $totalAmount = $totaleAcquisto + $shippingCost;

        // Generate unique transaction code
        $codTrans = $this->nexiService->generateTransactionCode();

        // Save transaction code in session
        session([
            'nexi_cod_trans' => $codTrans,
            'nexi_amount' => $totalAmount,
        ]);

        $txData = [
            'cart' => $cart,
            'customer' => $customer,
            'idLocation' => $idLocation,
            'nomeLocation' => session('nomeLocation'),
            'idPartner' => session('idPartner', 5),
            'totaleAcquisto' => $totaleAcquisto,
            'iframe' => session('iframe', false),
            'nexi_cod_trans' => $codTrans,
            'nexi_amount' => $totalAmount,
            'created_at' => now()->toDateTimeString(),
        ];

        // Persist checkout session data in Cache keyed by codTrans (48h) so cross-site / _top return or S2S notify never loses session
        Cache::put('checkout_tx_' . $codTrans, $txData, now()->addHours(48));

        // Keep an index of recent Nexi transactions for diagnostics / recovery
        $recentList = Cache::get('nexi_recent_cod_trans', []);
        if (!is_array($recentList)) {
            $recentList = [];
        }
        array_unshift($recentList, $codTrans);
        Cache::put('nexi_recent_cod_trans', array_slice(array_unique($recentList), 0, 50), now()->addDays(7));

        // Set return, cancel, and Server-to-Server notify URLs
        $returnUrl = route('payment.nexi.callback');
        $cancelUrl = route('payment.nexi.cancel', ['codTrans' => $codTrans]);
        $notifyUrl = route('payment.nexi.notify');

        // Prepare payment data
        $paymentData = $this->nexiService->preparePaymentData($totalAmount, $codTrans, $returnUrl, $cancelUrl, $customer, $notifyUrl);

        Log::info('Nexi payment initiated', [
            'codTrans' => $codTrans,
            'amount' => $totalAmount,
            'customer_email' => $customer['email'] ?? null,
            'idLocation' => $idLocation,
            'cart' => $cart,
        ]);

        // Show redirect form
        return view('payment.nexi-redirect', [
            'gatewayUrl' => $paymentData['gateway_url'],
            'params' => $paymentData['params'],
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
     * Callback from Nexi (supports both GET browser redirect and POST)
     */
    public function callback(Request $request)
    {
        $params = $request->all();
        $codTrans = $params['codTrans'] ?? session('nexi_cod_trans');

        Log::info('Nexi callback received', [
            'method' => $request->method(),
            'params' => $params,
        ]);

        // Restore session from cache if partitioned iframe cookie was not sent on top-level return
        if ($codTrans && (empty(session('cart')) || empty(session('customer')))) {
            $cached = Cache::get('checkout_tx_' . $codTrans);
            if (is_array($cached)) {
                session($cached);
                Log::info('Restored checkout session from cache for Nexi callback', ['codTrans' => $codTrans]);
            }
        }

        // If this transaction was already processed into an order (e.g. via S2S notify), redirect straight to its success URL
        if ($codTrans && Cache::has('checkout_done_' . $codTrans)) {
            return redirect()->to(Cache::get('checkout_done_' . $codTrans));
        }

        // Parse and verify callback
        $result = $this->nexiService->parseCallback($params);

        if ($result['success']) {
            $txId = $result['transaction_id'] ?: $codTrans;

            Log::info('Nexi payment successful', [
                'transaction_id' => $txId,
                'cod_trans' => $result['cod_trans'],
                'amount' => $result['amount'],
            ]);

            session([
                'nexi_transaction_id' => $txId,
                'nexi_cod_trans' => $codTrans,
            ]);
            session()->forget('ordinecompletato');

            if ($codTrans && Cache::has('checkout_tx_' . $codTrans)) {
                $cached = Cache::get('checkout_tx_' . $codTrans);
                $cached['nexi_transaction_id'] = $txId;
                Cache::put('checkout_tx_' . $codTrans, $cached, now()->addHours(48));
            }

            // Finalize order directly (and send confirmation email) without needing an extra redirect hop
            $orderResult = app(OrderController::class)->finalizeOrderFromSessionOrCache($codTrans, [
                'nexi_transaction_id' => $txId,
                'nexi_cod_trans' => $codTrans,
            ]);

            if (!empty($orderResult['success']) && !empty($orderResult['success_url'])) {
                return redirect()->to($orderResult['success_url']);
            }

            // Fallback redirect if needed
            return redirect()->route('order.create', ['tx' => $codTrans]);
        }

        if (!empty($result['cancelled'])) {
            return $this->cancel($request);
        }

        // Payment failed
        Log::error('Nexi payment failed', $result);

        return redirect()->route('checkout.index')->with('error', 'Pagamento non riuscito: ' . ($result['error'] ?? 'Riprova o scegli un altro metodo di pagamento.'));
    }

    /**
     * Server-to-server notification from Nexi (urlpost)
     * Creates the order immediately if not yet created, so even if the user closes the browser on Nexi's receipt screen,
     * the order and confirmation email are never lost.
     */
    public function notify(Request $request)
    {
        $params = $request->all();
        $codTrans = $params['codTrans'] ?? null;

        Log::info('Nexi S2S notification received', $params);

        $result = $this->nexiService->parseCallback($params);

        if ($result['success']) {
            $txId = $result['transaction_id'] ?: $codTrans;

            if ($codTrans && !Cache::has('checkout_done_' . $codTrans) && Cache::has('checkout_tx_' . $codTrans)) {
                $orderResult = app(OrderController::class)->finalizeOrderFromSessionOrCache($codTrans, [
                    'nexi_transaction_id' => $txId,
                    'nexi_cod_trans' => $codTrans,
                ]);
                Log::info('Nexi S2S notification finalized order', [
                    'codTrans' => $codTrans,
                    'orderResult' => $orderResult,
                ]);
            }

            return response('OK', 200);
        }

        Log::error('Nexi S2S notification failed', $result);

        return response('KO', 400);
    }

    /**
     * Cancel payment
     */
    public function cancel(Request $request)
    {
        $codTrans = $request->query('codTrans') ?? session('nexi_cod_trans');

        Log::info('Nexi payment cancelled', ['codTrans' => $codTrans]);

        if ($codTrans && (empty(session('cart')) || empty(session('customer')))) {
            $cached = Cache::get('checkout_tx_' . $codTrans);
            if (is_array($cached)) {
                session($cached);
            }
        }

        // Clear Nexi transaction data but keep cart & customer so user can retry
        session()->forget(['nexi_cod_trans', 'nexi_amount', 'nexi_transaction_id']);

        return redirect()->route('checkout.index')->with('info', 'Pagamento con carta annullato. Puoi riprovare o scegliere un altro metodo di pagamento.');
    }
}
