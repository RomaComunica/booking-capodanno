<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use App\Models\RigaOrdine;
use App\Models\Voucher;
use App\Models\TipologiaIngresso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function create(Request $request)
    {
        \Log::info('OrderController::create called', ['tx' => $request->query('tx')]);

        $tx = $request->query('tx');

        // If this transaction token was already completed, redirect straight to its signed success URL
        if ($tx && \Illuminate\Support\Facades\Cache::has('checkout_done_' . $tx)) {
            \Log::info('Order already completed for tx, redirecting to cached success URL', ['tx' => $tx]);
            return redirect()->to(\Illuminate\Support\Facades\Cache::get('checkout_done_' . $tx));
        }

        // Restore session from cache if partitioned iframe cookie was lost on redirect
        if ($tx && (empty(session('cart')) || empty(session('customer')))) {
            $cached = \Illuminate\Support\Facades\Cache::get('checkout_tx_' . $tx);
            if (is_array($cached)) {
                session($cached);
                \Log::info('Restored session data from cache in OrderController::create', ['tx' => $tx]);
            }
        }
        
        // Check if order already completed (prevent duplicates)
        if (session('ordinecompletato') == 1) {
            \Log::warning('Order already completed, redirecting to home');
            return redirect()->route('home')->with('error', 'Ordine già completato');
        }
        
        // Get data from session
        $cart = session('cart', []);
        $customer = session('customer', []);
        $idLocation = session('idLocation');
        $partnerId = session('idPartner', 5);
        
        \Log::info('Session data loaded', [
            'cart_count' => count($cart),
            'has_customer' => !empty($customer),
            'idLocation' => $idLocation,
            'partnerId' => $partnerId,
        ]);
        
        if (empty($cart) || empty($customer)) {
            \Log::error('Missing cart or customer data', [
                'cart_empty' => empty($cart),
                'customer_empty' => empty($customer),
            ]);
            return redirect()->route('home')->with('error', 'Dati mancanti');
        }
        
        DB::beginTransaction();
        
        try {
            \Log::info('Starting order creation transaction');
            
            // Generate order number (sequential)
            $numeroOrdine = $this->generateOrderNumber();
            \Log::info('Generated order number', ['numeroOrdine' => $numeroOrdine]);
            
            // Generate voucher number (sequential)
            $numeroVoucher = $this->generateVoucherNumber();
            \Log::info('Generated voucher number', ['numeroVoucher' => $numeroVoucher]);
            
            // Calculate total with shipping and payment surcharge
            $totaleOrdine = $this->calculateTotal($customer['tipoSpedizione'], $customer['tipoPagamento'], $cart);
            \Log::info('Calculated total', ['totaleOrdine' => $totaleOrdine]);
            
            // Create order
            try {
                \Log::info('About to create order', [
                    'numeroOrdine' => $numeroOrdine,
                    'customer' => $customer,
                    'idLocation' => $idLocation,
                    'partnerId' => $partnerId,
                    'totaleOrdine' => $totaleOrdine,
                ]);
                
                $order = $this->createOrder($numeroOrdine, $customer, $idLocation, $partnerId, $totaleOrdine);
                \Log::info('Order created successfully', ['order_id' => $order->idordine]);
            } catch (\Exception $e) {
                \Log::error('Failed to create order', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
            
            // Create order lines
            try {
                \Log::info('About to create order lines', ['cart' => $cart]);
                $this->createOrderLines($numeroOrdine, $cart, $partnerId);
                \Log::info('Order lines created - SUCCESS');
            } catch (\Exception $e) {
                \Log::error('Failed to create order lines', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
            
            \Log::info('=== CHECKPOINT: Before voucher creation ===');
            
            // Create voucher
            try {
                \Log::info('About to create voucher', [
                    'numeroVoucher' => $numeroVoucher,
                    'numeroOrdine' => $numeroOrdine,
                ]);
                $this->createVoucher($numeroVoucher, $numeroOrdine, $customer, $idLocation);
                \Log::info('Voucher created - SUCCESS');
            } catch (\Exception $e) {
                \Log::error('Failed to create voucher', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
            
            // Decrement stock
            try {
                \Log::info('About to decrement stock');
                $this->decrementStock($cart, $idLocation);
                \Log::info('Stock decremented');
            } catch (\Exception $e) {
                \Log::error('Failed to decrement stock', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
            
            DB::commit();
            
            // Send confirmation email for ALL methods so users get bank details or confirmations.
            try {
                $voucher = Voucher::where('numeroVoucher', $numeroVoucher)->first();
                \Mail::to($customer['email'])->send(new \App\Mail\OrderConfirmation($order, $voucher));
            } catch (\Exception $e) {
                // Log email error but don't fail the order
                \Log::error('Failed to send order confirmation email: ' . $e->getMessage());
            }
            
            $successUrl = \Illuminate\Support\Facades\URL::signedRoute('order.success', [
                'numeroOrdine' => $numeroOrdine,
                'numeroVoucher' => $numeroVoucher
            ]);

            // Cache the success URL for this transaction to prevent duplicates on page reload
            $cacheKeys = array_filter([$tx, session('nexi_cod_trans'), session('paypal_token')]);
            foreach ($cacheKeys as $ck) {
                \Illuminate\Support\Facades\Cache::put('checkout_done_' . $ck, $successUrl, now()->addHours(6));
                \Illuminate\Support\Facades\Cache::forget('checkout_tx_' . $ck);
            }

            // Mark order as completed in session
            session(['ordinecompletato' => 1]);
            
            // Clear cart, customer data, and payment session variables
            session()->forget(['cart', 'customer', 'totaleAcquisto', 'paypal_transaction_id', 'nexi_transaction_id', 'nexi_cod_trans', 'paypal_token']);
            
            // Redirect to success page (signed URL)
            return redirect()->to($successUrl);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Order creation failed in main catch block', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('checkout.index')
                ->with('error', 'Errore nella creazione dell\'ordine: ' . $e->getMessage());
        }
    }
    
    private function generateOrderNumber()
    {
        $lastOrder = Ordine::orderBy('numeroOrdine', 'desc')->first();
        return $lastOrder ? (int)$lastOrder->numeroOrdine + 1 : 100;
    }
    
    private function generateVoucherNumber()
    {
        $lastVoucher = Voucher::orderBy('numeroVoucher', 'desc')->first();
        return $lastVoucher ? (int)$lastVoucher->numeroVoucher + 1 : 100;
    }
    
    private function calculateTotal($tipoSpedizione, $tipoPagamento = null, $cart = [])
    {
        $subtotal = session('totaleAcquisto', 0);
        
        // Get shipping cost from database
        $shipping = DB::table('cross_spedizioni_location')
            ->where('idSpedizione', $tipoSpedizione)
            ->where('idLocationSpedizione', session('idLocation'))
            ->first();
        
        $shippingCost = $shipping ? (float)$shipping->costoSpedizione : 0.0;
        
        // Surcharge for PayPal (+2€ per ticket)
        $paypalFee = 0.0;
        if ((int)$tipoPagamento === 1 && !empty($cart)) {
            $totalTickets = (int)array_sum(array_column($cart, 'quantita'));
            $paypalFee = 2.00 * $totalTickets;
        }
        
        return $subtotal + $shippingCost + $paypalFee;
    }
    
    private function createOrder($numeroOrdine, $customer, $idLocation, $partnerId, $totaleOrdine)
    {
        $statoOrdine = 0; // 0 = In corso/in attesa
        $transazioneFornitore = null;

        if (session()->has('paypal_transaction_id')) {
            $statoOrdine = 2; // 2 = Completato
            $transazioneFornitore = session('paypal_transaction_id');
        } elseif (session()->has('nexi_transaction_id')) {
            $statoOrdine = 2; // 2 = Completato
            $transazioneFornitore = session('nexi_transaction_id');
        }

        return Ordine::create([
            'numeroOrdine' => $numeroOrdine,
            'dataOrdine' => now(),
            'idLocationOrdine' => $idLocation,
            'IPcliente' => request()->ip(),
            'nomeClienteOrdine' => $customer['nome'],
            'cognomeClienteOrdine' => $customer['cognome'],
            'emailClienteOrdine' => $customer['email'],
            'telefonoClienteOrdine' => $customer['telefono'],
            'indirizzoClienteOrdine' => '',
            'capClienteOrdine' => '',
            'cittaClienteOrdine' => $customer['citta'] ?? '',
            'provinciaClienteOrdine' => '',
            'totaleOrdine' => $totaleOrdine,
            'tipoPagamentoOrdine' => $customer['tipoPagamento'],
            'tipoSpedizioneOrdine' => $customer['tipoSpedizione'],
            'idPartnerOrdine' => $partnerId,
            'ordineConfermato' => 1,
            'dataPagamentoOrdine' => now(),
            'statoOrdine' => $statoOrdine,
            'dataPagamentoOrdineFornitore' => $transazioneFornitore,
        ]);
    }
    
    private function createOrderLines($numeroOrdine, $cart, $partnerId)
    {
        foreach ($cart as $item) {
            RigaOrdine::create([
                'numeroOrdine' => $numeroOrdine,
                'idIngressoOrdine' => $item['idIngresso'],
                'idPartnerOrdine' => $partnerId,
                'quantitaIngressoOrdine' => $item['quantita'],
                'prezzoIngressoOrdine' => $item['prezzoIngresso'],
                'nomeIngressoOrdine' => $item['nomeIngresso'],
                'orarioIngressoOrdine' => $item['orarioIngresso'] ?? null,
                'dataIngressoOrdine' => $item['dataIngresso'] ?? null,
                'ingressoSenzaDataOrdine' => $item['ingressoSenzaData'] ?? $item['senzaData'] ?? 0,
                'etichettaIngressoOrdine' => $item['etichettaIngresso'] ?? $item['etichetta'] ?? '',
                'opzioneRimborso' => $item['rimborso'] ?? 0,
                'rimborsoValore' => $item['rimborsoValore'] ?? null,
                'rimborsoData' => $item['rimborsoData'] ?? null,
                'totaleRiga' => $item['subtotale'],
            ]);
        }
    }
    
    private function createVoucher($numeroVoucher, $numeroOrdine, $customer, $idLocation)
    {
        // Generate unique voucher code
        $codiceVoucher = 'VCH-' . strtoupper(substr(md5($numeroVoucher . time()), 0, 10));
        
        Voucher::create([
            'numeroVoucher' => $numeroVoucher,
            'codiceVoucher' => $codiceVoucher,
            'numeroOrdineVoucher' => $numeroOrdine,
            'idLocationVoucher' => $idLocation,
            'dataVoucher' => now(),
            'nomeVoucher' => $customer['nome'],
            'cognomeVoucher' => $customer['cognome'],
            'telefonoVoucher' => $customer['telefono'],
            'emailVoucher' => $customer['email'],
        ]);
    }
    
    private function decrementStock($cart, $idLocation)
    {
        foreach ($cart as $item) {
            DB::table('tipologia_ingresso')
                ->where('idIngressoCont', $item['idIngresso'])
                ->where('idLocation', $idLocation)
                ->decrement('giacenzaBiglietti', $item['quantita']);
        }
    }
    
    public function success($numeroOrdine, $numeroVoucher)
    {
        // Load order details
        $order = Ordine::where('numeroOrdine', $numeroOrdine)->first();
        
        if (!$order) {
            return redirect()->route('home')->with('error', 'Ordine non trovato');
        }
        
        $orderLines = RigaOrdine::where('numeroOrdine', $numeroOrdine)->get();
        $voucher = Voucher::where('numeroVoucher', $numeroVoucher)->first();
        
        // Load location and partner for layout
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
