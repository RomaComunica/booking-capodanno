<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use App\Models\RigaOrdine;
use App\Models\Voucher;
use App\Models\TipologiaIngresso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class OrderController extends Controller
{
    public function create(Request $request)
    {
        $tx = $request->query('tx');
        Log::info('OrderController::create called', ['tx' => $tx]);

        // If this transaction token was already completed, redirect straight to its signed success URL
        if ($tx && Cache::has('checkout_done_' . $tx)) {
            Log::info('Order already completed for tx, redirecting to cached success URL', ['tx' => $tx]);
            return redirect()->to(Cache::get('checkout_done_' . $tx));
        }

        // Restore session from cache if partitioned iframe cookie was lost on redirect
        if ($tx && (empty(session('cart')) || empty(session('customer')))) {
            $cached = Cache::get('checkout_tx_' . $tx);
            if (is_array($cached)) {
                session($cached);
                Log::info('Restored session data from cache in OrderController::create', ['tx' => $tx]);
            }
        }

        // Check if order already completed in current session (prevent duplicates)
        if (!$tx && session('ordinecompletato') == 1) {
            Log::warning('Order already completed, redirecting to home');
            return redirect()->route('home')->with('error', 'Ordine già completato');
        }

        $result = $this->finalizeOrderFromSessionOrCache($tx);

        if ($result['success']) {
            return redirect()->to($result['success_url']);
        }

        if (($result['error_code'] ?? '') === 'missing_data') {
            return redirect()->route('home')->with('error', 'Dati mancanti');
        }

        return redirect()->route('checkout.index')
            ->with('error', 'Errore nella creazione dell\'ordine: ' . ($result['error'] ?? 'Errore imprevisto'));
    }

    /**
     * Finalize order creation from session or cached transaction data.
     * Safe to call from both browser callback and Server-to-Server notification (idempotent per $tx).
     */
    public function finalizeOrderFromSessionOrCache(?string $tx = null, array $overrideData = []): array
    {
        if ($tx && Cache::has('checkout_done_' . $tx)) {
            return [
                'success' => true,
                'already_done' => true,
                'success_url' => Cache::get('checkout_done_' . $tx),
            ];
        }

        // Merge cached data if available
        $cached = ($tx && Cache::has('checkout_tx_' . $tx)) ? Cache::get('checkout_tx_' . $tx) : [];
        if (!is_array($cached)) {
            $cached = [];
        }

        $state = array_merge([
            'cart' => session('cart', []),
            'customer' => session('customer', []),
            'idLocation' => session('idLocation'),
            'idPartner' => session('idPartner', 5),
            'totaleAcquisto' => session('totaleAcquisto', 0),
            'paypal_transaction_id' => session('paypal_transaction_id'),
            'nexi_transaction_id' => session('nexi_transaction_id'),
            'nexi_cod_trans' => session('nexi_cod_trans'),
            'paypal_token' => session('paypal_token'),
        ], $cached, $overrideData);

        $cart = $state['cart'] ?? [];
        $customer = $state['customer'] ?? [];
        $idLocation = $state['idLocation'] ?? null;
        $partnerId = $state['idPartner'] ?? 5;

        Log::info('OrderController::finalizeOrderFromSessionOrCache loaded data', [
            'tx' => $tx,
            'cart_count' => is_array($cart) ? count($cart) : 0,
            'has_customer' => !empty($customer),
            'idLocation' => $idLocation,
            'partnerId' => $partnerId,
        ]);

        if (empty($cart) || empty($customer)) {
            Log::error('Missing cart or customer data in finalizeOrderFromSessionOrCache', [
                'tx' => $tx,
                'cart_empty' => empty($cart),
                'customer_empty' => empty($customer),
            ]);
            return [
                'success' => false,
                'error_code' => 'missing_data',
                'error' => 'Dati carrello o cliente mancanti',
            ];
        }

        DB::beginTransaction();

        try {
            // Double-check cache inside transaction to avoid race condition between S2S notify and browser callback
            if ($tx && Cache::has('checkout_done_' . $tx)) {
                DB::rollBack();
                return [
                    'success' => true,
                    'already_done' => true,
                    'success_url' => Cache::get('checkout_done_' . $tx),
                ];
            }

            $numeroOrdine = $this->generateOrderNumber();
            $numeroVoucher = $this->generateVoucherNumber();

            $totaleOrdine = $this->calculateTotal(
                $customer['tipoSpedizione'] ?? null,
                $customer['tipoPagamento'] ?? null,
                $cart,
                $state['totaleAcquisto'] ?? null,
                $idLocation
            );

            $order = $this->createOrder($numeroOrdine, $customer, $idLocation, $partnerId, $totaleOrdine, $state);
            Log::info('Order created successfully', [
                'numeroOrdine' => $numeroOrdine,
                'order_id' => $order->idordine ?? $numeroOrdine,
            ]);

            $this->createOrderLines($numeroOrdine, $cart, $partnerId);
            Log::info('Order lines created - SUCCESS', ['numeroOrdine' => $numeroOrdine]);

            $this->createVoucher($numeroVoucher, $numeroOrdine, $customer, $idLocation);
            Log::info('Voucher created - SUCCESS', ['numeroVoucher' => $numeroVoucher]);

            $this->decrementStock($cart, $idLocation);
            Log::info('Stock decremented', ['numeroOrdine' => $numeroOrdine]);

            DB::commit();

            // Send confirmation email for ALL methods so users get bank details or confirmations.
            try {
                $order->load(['righe', 'location', 'partner']);
                $voucher = Voucher::where('numeroVoucher', $numeroVoucher)->first();
                if ($voucher && !empty($customer['email'])) {
                    Mail::to($customer['email'])->send(new \App\Mail\OrderConfirmation($order, $voucher));
                    Log::info('Order confirmation email sent', [
                        'numeroOrdine' => $numeroOrdine,
                        'email' => $customer['email'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send order confirmation email: ' . $e->getMessage(), [
                    'numeroOrdine' => $numeroOrdine,
                ]);
            }

            $successUrl = URL::signedRoute('order.success', [
                'numeroOrdine' => $numeroOrdine,
                'numeroVoucher' => $numeroVoucher,
            ]);

            // Cache the success URL for this transaction to prevent duplicates on page reload or S2S + browser callback
            $cacheKeys = array_unique(array_filter([
                $tx,
                $state['nexi_cod_trans'] ?? null,
                $state['paypal_token'] ?? null,
            ]));
            foreach ($cacheKeys as $ck) {
                Cache::put('checkout_done_' . $ck, $successUrl, now()->addHours(48));
                Cache::forget('checkout_tx_' . $ck);
            }

            // Mark order as completed in session
            session(['ordinecompletato' => 1]);

            // Clear cart, customer data, and payment session variables
            session()->forget([
                'cart',
                'customer',
                'totaleAcquisto',
                'paypal_transaction_id',
                'nexi_transaction_id',
                'nexi_cod_trans',
                'paypal_token',
            ]);

            return [
                'success' => true,
                'numeroOrdine' => $numeroOrdine,
                'numeroVoucher' => $numeroVoucher,
                'success_url' => $successUrl,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Order creation failed in finalizeOrderFromSessionOrCache', [
                'tx' => $tx,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function generateOrderNumber()
    {
        $lastOrder = Ordine::orderBy('numeroOrdine', 'desc')->first();
        return $lastOrder ? (int) $lastOrder->numeroOrdine + 1 : 100;
    }

    private function generateVoucherNumber()
    {
        $lastVoucher = Voucher::orderBy('numeroVoucher', 'desc')->first();
        return $lastVoucher ? (int) $lastVoucher->numeroVoucher + 1 : 100;
    }

    private function calculateTotal($tipoSpedizione, $tipoPagamento = null, $cart = [], $subtotalOverride = null, $idLocationOverride = null)
    {
        $subtotal = $subtotalOverride !== null ? (float) $subtotalOverride : (float) session('totaleAcquisto', 0);
        if ($subtotal <= 0 && !empty($cart)) {
            $subtotal = (float) collect($cart)->sum(fn ($item) => (float) ($item['subtotale'] ?? ((float) ($item['prezzoIngresso'] ?? 0) * (int) ($item['quantita'] ?? 1))));
        }

        $idLocation = $idLocationOverride ?? session('idLocation');

        // Get shipping cost from database
        $shipping = DB::table('cross_spedizioni_location')
            ->where('idSpedizione', $tipoSpedizione)
            ->where('idLocationSpedizione', $idLocation)
            ->first();

        $shippingCost = $shipping && isset($shipping->costoSpedizione) ? (float) $shipping->costoSpedizione : 0.0;

        // Surcharge for PayPal (+2€ per ticket)
        $paypalFee = 0.0;
        if ((int) $tipoPagamento === 1 && !empty($cart)) {
            $totalTickets = (int) array_sum(array_column($cart, 'quantita'));
            $paypalFee = 2.00 * $totalTickets;
        }

        return $subtotal + $shippingCost + $paypalFee;
    }

    private function createOrder($numeroOrdine, $customer, $idLocation, $partnerId, $totaleOrdine, array $state = [])
    {
        $statoOrdine = 0; // 0 = In corso/in attesa
        $noteOrdine = '';

        $paypalTx = $state['paypal_transaction_id'] ?? session('paypal_transaction_id');
        $nexiTx = $state['nexi_transaction_id'] ?? session('nexi_transaction_id');
        $nexiCodTrans = $state['nexi_cod_trans'] ?? session('nexi_cod_trans');

        if (!empty($paypalTx) || (int) ($customer['tipoPagamento'] ?? 0) === 1) {
            if (!empty($paypalTx)) {
                $statoOrdine = 2; // 2 = Completato
                $noteOrdine = 'Transazione PayPal: ' . $paypalTx;
            }
        } elseif (!empty($nexiTx) || (int) ($customer['tipoPagamento'] ?? 0) === 5) {
            if (!empty($nexiTx) || !empty($nexiCodTrans)) {
                $statoOrdine = 2; // 2 = Completato
                $noteOrdine = 'Pagamento Nexi (Aut: ' . ($nexiTx ?: '-') . ($nexiCodTrans ? ' / CodTrans: ' . $nexiCodTrans : '') . ')';
            }
        }

        // IMPORTANT: dataPagamentoOrdineFornitore is a DATETIME column in MySQL (legacy schema).
        // Never insert an alphanumeric transaction ID into dataPagamentoOrdineFornitore!
        return Ordine::create([
            'numeroOrdine' => $numeroOrdine,
            'dataOrdine' => now(),
            'idLocationOrdine' => $idLocation,
            'IPcliente' => request()->ip() ?? '127.0.0.1',
            'nomeClienteOrdine' => (string) ($customer['nome'] ?? ''),
            'cognomeClienteOrdine' => (string) ($customer['cognome'] ?? ''),
            'emailClienteOrdine' => (string) ($customer['email'] ?? ''),
            'telefonoClienteOrdine' => (string) ($customer['telefono'] ?? ''),
            'indirizzoClienteOrdine' => (string) ($customer['indirizzo'] ?? ''),
            'capClienteOrdine' => (string) ($customer['cap'] ?? ''),
            'cittaClienteOrdine' => (string) ($customer['citta'] ?? ''),
            'provinciaClienteOrdine' => (string) ($customer['provincia'] ?? ''),
            'totaleOrdine' => $totaleOrdine,
            'tipoPagamentoOrdine' => (int) ($customer['tipoPagamento'] ?? 0),
            'tipoSpedizioneOrdine' => (int) ($customer['tipoSpedizione'] ?? 1),
            'idPartnerOrdine' => (int) ($partnerId ?: 5),
            'ordineConfermato' => 1,
            'dataPagamentoOrdine' => now(),
            'statoOrdine' => $statoOrdine,
            'dataPagamentoOrdineFornitore' => null,
            'noteOrdine' => $noteOrdine,
        ]);
    }

    private function createOrderLines($numeroOrdine, $cart, $partnerId)
    {
        foreach ($cart as $item) {
            RigaOrdine::create([
                'numeroOrdine' => $numeroOrdine,
                'idIngressoOrdine' => (int) ($item['idIngresso'] ?? 0),
                'idPartnerOrdine' => (int) ($partnerId ?: 5),
                'quantitaIngressoOrdine' => (int) ($item['quantita'] ?? 1),
                'prezzoIngressoOrdine' => (float) ($item['prezzoIngresso'] ?? 0),
                'nomeIngressoOrdine' => (string) ($item['nomeIngresso'] ?? ''),
                'orarioIngressoOrdine' => !empty($item['orarioIngresso']) ? (string) $item['orarioIngresso'] : '00:00',
                'dataIngressoOrdine' => !empty($item['dataIngresso']) ? $item['dataIngresso'] : null,
                'ingressoSenzaDataOrdine' => (int) ($item['ingressoSenzaData'] ?? $item['senzaData'] ?? 0),
                'etichettaIngressoOrdine' => (string) ($item['etichettaIngresso'] ?? $item['etichetta'] ?? ''),
                'opzioneRimborso' => (int) ($item['rimborso'] ?? 0),
                'rimborsoValore' => isset($item['rimborsoValore']) ? (float) $item['rimborsoValore'] : null,
                'rimborsoData' => !empty($item['rimborsoData']) ? $item['rimborsoData'] : null,
                'totaleRiga' => (float) ($item['subtotale'] ?? ((float) ($item['prezzoIngresso'] ?? 0) * (int) ($item['quantita'] ?? 1))),
            ]);
        }
    }

    private function createVoucher($numeroVoucher, $numeroOrdine, $customer, $idLocation)
    {
        $codiceVoucher = 'VCH-' . strtoupper(substr(md5($numeroVoucher . time()), 0, 10));

        Voucher::create([
            'numeroVoucher' => $numeroVoucher,
            'codiceVoucher' => $codiceVoucher,
            'numeroOrdineVoucher' => $numeroOrdine,
            'idLocationVoucher' => $idLocation,
            'dataVoucher' => now(),
            'nomeVoucher' => (string) ($customer['nome'] ?? ''),
            'cognomeVoucher' => (string) ($customer['cognome'] ?? ''),
            'telefonoVoucher' => (string) ($customer['telefono'] ?? ''),
            'emailVoucher' => (string) ($customer['email'] ?? ''),
        ]);
    }

    private function decrementStock($cart, $idLocation)
    {
        foreach ($cart as $item) {
            DB::table('tipologia_ingresso')
                ->where('idIngressoCont', $item['idIngresso'])
                ->where('idLocation', $idLocation)
                ->decrement('giacenzaBiglietti', (int) ($item['quantita'] ?? 1));
        }
    }

    public function success($numeroOrdine, $numeroVoucher)
    {
        $order = Ordine::where('numeroOrdine', $numeroOrdine)->first();

        if (!$order) {
            return redirect()->route('home')->with('error', 'Ordine non trovato');
        }

        $orderLines = RigaOrdine::where('numeroOrdine', $numeroOrdine)->get();
        $voucher = Voucher::where('numeroVoucher', $numeroVoucher)->first();

        $location = $order->location;
        $partner = $order->partner;

        return view('order.success', compact(
            'order',
            'orderLines',
            'voucher',
            'numeroOrdine',
            'numeroVoucher',
            'location',
            'partner'
        ));
    }
}
