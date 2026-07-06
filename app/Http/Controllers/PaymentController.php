<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use App\Services\PayPalNvpService;
use App\Services\NexiXPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    protected $paypalService;
    protected $nexiService;

    public function __construct(PayPalNvpService $paypalService, NexiXPayService $nexiService)
    {
        $this->paypalService = $paypalService;
        $this->nexiService = $nexiService;
    }

    /**
     * Create payment based on selected method
     */
    public function create(Request $request)
    {
        $ordine = Ordine::findOrFail($request->ordine_id);

        if ($ordine->metodoPagamento === 'paypal') {
            return $this->createPayPalPayment($ordine);
        } else {
            return $this->createNexiPayment($ordine);
        }
    }

    /**
     * Create PayPal payment
     */
    private function createPayPalPayment($ordine)
    {
        $returnUrl = route('pay.return', ['ordine_id' => $ordine->idOrdine]);
        $cancelUrl = route('checkout.index');

        $result = $this->paypalService->createPayment($ordine, $returnUrl, $cancelUrl);

        if ($result['success']) {
            // Save token to order
            $ordine->update(['paymentToken' => $result['token']]);
            
            return redirect($result['redirect_url']);
        }

        return redirect()->route('checkout.index')
            ->with('error', 'Errore PayPal: ' . $result['error']);
    }

    /**
     * Create Nexi payment
     */
    private function createNexiPayment($ordine)
    {
        $returnUrl = route('nexi.esito', ['ordine_id' => $ordine->idOrdine]);
        $callbackUrl = route('nexi.callback');

        $formData = $this->nexiService->generatePaymentForm($ordine, $returnUrl, $callbackUrl);

        return view('payment.nexi-redirect', compact('formData'));
    }

    /**
     * PayPal return handler
     */
    public function return(Request $request)
    {
        $ordine = Ordine::findOrFail($request->ordine_id);
        $token = $request->token;
        $payerId = $request->PayerID;

        if (!$payerId) {
            // Payment cancelled
            $ordine->update(['statoOrdine' => 1]); // 1 = Annullato
            return redirect()->route('checkout.index')
                ->with('error', 'Pagamento annullato');
        }

        // Execute payment
        $result = $this->paypalService->executePayment($token, $payerId, $ordine->totaleOrdine);

        if ($result['success']) {
            // This update to statoOrdine=2 will trigger the email auto-send via the model hook
            $ordine->update([
                'statoOrdine' => 2, // 2 = Completato
                'transactionId' => $result['transaction_id'],
                'paymentStatus' => $result['status'],
            ]);

            return redirect()->route('checkout.success', $ordine->idOrdine);
        }

        $ordine->update(['statoOrdine' => 1]); // 1 = Annullato
        return redirect()->route('checkout.index')
            ->with('error', 'Pagamento fallito: ' . $result['error']);
    }

    /**
     * Nexi return handler (user redirect)
     */
    public function nexiReturn(Request $request)
    {
        $ordine = Ordine::findOrFail($request->ordine_id);
        
        // The actual payment verification happens in the callback
        // This is just the user redirect
        
        if ($ordine->statoOrdine === 2) { // 2 = Completato
            return redirect()->route('checkout.success', $ordine->idOrdine);
        }

        return redirect()->route('checkout.index')
            ->with('info', 'In attesa di conferma pagamento...');
    }

    /**
     * Nexi S2S callback handler
     */
    public function nexiCallback(Request $request)
    {
        Log::info('Nexi callback received', $request->all());

        $result = $this->nexiService->parsePaymentResult($request->all());

        if (!$result['success']) {
            Log::error('Nexi payment verification failed', $result);
            return response('KO', 200);
        }

        // Extract order ID from transaction code (ORD0000000123 -> 123)
        $ordineId = (int) str_replace('ORD', '', ltrim($result['transaction_id'], '0'));
        $ordine = Ordine::find($ordineId);

        if (!$ordine) {
            Log::error('Order not found for Nexi callback', ['ordine_id' => $ordineId]);
            return response('KO', 200);
        }

        // Update order status - if OK, statoOrdine=2 triggers the email auto-send via model hook
        $ordine->update([
            'statoOrdine' => $result['status'] === 'OK' ? 2 : 1, // 2 = Completato, 1 = Annullato
            'transactionId' => $result['transaction_id'],
            'authCode' => $result['auth_code'],
            'paymentStatus' => $result['status'],
        ]);

        return response('OK', 200);
    }

    /**
     * Nexi submit form (auto-post to Nexi)
     */
    public function nexiSubmit(Request $request)
    {
        $ordine = Ordine::findOrFail($request->ordine_id);
        
        $returnUrl = route('nexi.esito', ['ordine_id' => $ordine->idOrdine]);
        $callbackUrl = route('nexi.callback');

        $formData = $this->nexiService->generatePaymentForm($ordine, $returnUrl, $callbackUrl);

        return view('payment.nexi-redirect', compact('formData'));
    }
}
