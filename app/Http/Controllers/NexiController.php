<?php

namespace App\Http\Controllers;

use App\Services\NexiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NexiController extends Controller
{
    private $nexiService;

    public function __construct(NexiService $nexiService)
    {
        $this->nexiService = $nexiService;
    }

    /**
     * Initialize Nexi payment (or open admin recovery panel when ?token=rc-recover-nexi is passed)
     */
    public function init(Request $request)
    {
        if ($request->boolean('recover') && auth()->check()) {
            return $this->renderRecoveryPanel($request);
        }

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
        if ($request->boolean('recover') && auth()->check()) {
            return $this->renderRecoveryPanel($request);
        }

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

    /**
     * Admin diagnostic & 1-click recovery panel accessible via:
     * /payment/nexi/init?token=rc-recover-nexi
     */
    public function renderRecoveryPanel(Request $request)
    {
        try {
            Artisan::call('route:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        } catch (\Throwable $e) {}

        $message = null;

        // Handle 1-click order creation from recovered payload (via GET or POST)
        if ($request->filled('payload_b64')) {
            $decoded = json_decode(base64_decode($request->input('payload_b64')), true);
            if (is_array($decoded) && !empty($decoded['cart']) && !empty($decoded['customer'])) {
                $codTrans = $decoded['nexi_cod_trans'] ?? ('REC_' . time());
                $txId = $decoded['nexi_transaction_id'] ?? $codTrans;
                Cache::forget('checkout_done_' . $codTrans);
                Cache::put('checkout_tx_' . $codTrans, $decoded, now()->addHours(24));

                $res = app(OrderController::class)->finalizeOrderFromSessionOrCache($codTrans, [
                    'nexi_transaction_id' => $txId,
                    'nexi_cod_trans' => $codTrans,
                ]);

                if (!empty($res['success'])) {
                    $message = "✅ Ordine #{$res['numeroOrdine']} (Voucher #{$res['numeroVoucher']}) creato con successo e email di conferma inviata a " . ($decoded['customer']['email'] ?? '');
                } else {
                    $message = "❌ Errore creazione ordine: " . ($res['error'] ?? 'Errore sconosciuto');
                }
            }
        }

        $candidates = [];

        // 1. Check Cache table if using database cache driver
        try {
            if (Schema::hasTable('cache')) {
                $rows = DB::table('cache')
                    ->where('key', 'like', '%checkout_tx_%')
                    ->orderByDesc('expiration')
                    ->limit(30)
                    ->get();

                foreach ($rows as $row) {
                    $keyName = preg_replace('/^.*checkout_tx_/', '', $row->key);
                    $val = @unserialize($row->value);
                    if (is_array($val) && !empty($val['customer'])) {
                        $candidates[$keyName] = array_merge($val, [
                            'source' => 'Database Cache (' . $keyName . ')',
                            'nexi_cod_trans' => $val['nexi_cod_trans'] ?? $keyName,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {}

        // 2. Parse storage/logs/*.log (up to last 3 MB) to reconstruct recent checkout attempts
        $logFiles = glob(storage_path('logs/*.log')) ?: [];
        usort($logFiles, fn ($a, $b) => @filemtime($a) <=> @filemtime($b));
        $logFiles = array_slice($logFiles, -3);

        $logEntries = [];
        $lastCart = null;
        $lastCustomer = null;
        $lastLocation = null;
        $lastPartner = 5;
        $lastTotale = 0;

        foreach ($logFiles as $logPath) {
            if (!file_exists($logPath)) {
                continue;
            }
            $size = filesize($logPath);
            $fp = @fopen($logPath, 'r');
            if (!$fp) {
                continue;
            }
            $readBytes = min($size, 3 * 1024 * 1024);
            if ($size > $readBytes) {
                fseek($fp, $size - $readBytes);
            }
            $content = fread($fp, $readBytes);
            fclose($fp);

            $lines = explode("\n", (string) $content);

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $timeMatch = [];
                preg_match('/^\[([^\]]+)\]/', $line, $timeMatch);
                $timestamp = $timeMatch[1] ?? '';

                if (str_contains($line, 'CartController::add called')) {
                    $marker = 'CartController::add called';
                    $pos = strpos($line, '{', strpos($line, $marker));
                    if ($pos !== false) {
                        $data = json_decode(substr($line, $pos), true);
                        if (is_array($data)) {
                            $lastLocation = $data['idLocation'] ?? $lastLocation;
                            $lastPartner = $data['idPartner'] ?? $lastPartner;
                            $lastTotale = $data['totaleAcquisto'] ?? $lastTotale;
                        }
                    }
                    $logEntries[] = $line;
                } elseif (str_contains($line, 'Cart content:')) {
                    $marker = 'Cart content:';
                    $pos = strpos($line, '[', strpos($line, $marker));
                    if ($pos !== false) {
                        $c = json_decode(substr($line, $pos), true);
                        if (is_array($c)) {
                            $lastCart = $c;
                        }
                    }
                    $logEntries[] = $line;
                } elseif (str_contains($line, 'Checkout process called')) {
                    $pos = strpos($line, '{');
                    if ($pos !== false) {
                        $cp = json_decode(substr($line, $pos), true);
                        if (!empty($cp['all_data']) && is_array($cp['all_data'])) {
                            $lastCustomer = $cp['all_data'];
                        }
                    }
                    $logEntries[] = $line;
                } elseif (str_contains($line, 'Validation passed')) {
                    $pos = strpos($line, '{');
                    if ($pos !== false) {
                        $v = json_decode(substr($line, $pos), true);
                        if (!empty($v['validated']) && is_array($v['validated'])) {
                            $lastCustomer = $v['validated'];
                        }
                    }
                } elseif (str_contains($line, 'About to create order ')) {
                    $pos = strpos($line, '{');
                    if ($pos !== false) {
                        $o = json_decode(substr($line, $pos), true);
                        if (!empty($o['customer'])) {
                            $lastCustomer = $o['customer'];
                            $lastLocation = $o['idLocation'] ?? $lastLocation;
                            $lastPartner = $o['partnerId'] ?? $lastPartner;
                            $lastTotale = $o['totaleOrdine'] ?? $lastTotale;
                        }
                    }
                    $logEntries[] = $line;
                } elseif (str_contains($line, 'About to create order lines')) {
                    $pos = strpos($line, '{');
                    if ($pos !== false) {
                        $ol = json_decode(substr($line, $pos), true);
                        if (!empty($ol['cart']) && is_array($ol['cart'])) {
                            $lastCart = $ol['cart'];
                        }
                    }
                } elseif (str_contains($line, 'Nexi payment initiated') || str_contains($line, 'Nexi payment prepared')) {
                    $pos = strpos($line, '{');
                    if ($pos !== false) {
                        $nx = json_decode(substr($line, $pos), true);
                        $codTrans = $nx['codTrans'] ?? ('LOG_' . uniqid());
                        if ($lastCustomer && $lastCart) {
                            $candidates[$codTrans] = [
                                'source' => 'Log Laravel (' . $timestamp . ')',
                                'cart' => $nx['cart'] ?? $lastCart,
                                'customer' => $lastCustomer,
                                'idLocation' => $nx['idLocation'] ?? $lastLocation,
                                'idPartner' => $lastPartner ?: 5,
                                'totaleAcquisto' => $nx['amount'] ?? $lastTotale,
                                'nexi_cod_trans' => $codTrans,
                                'nexi_amount' => $nx['amount'] ?? $lastTotale,
                                'created_at' => $timestamp,
                            ];
                        }
                    }
                    $logEntries[] = $line;
                } elseif (
                    str_contains($line, 'Nexi callback') ||
                    str_contains($line, 'Nexi S2S') ||
                    str_contains($line, 'Nexi payment') ||
                    str_contains($line, 'Nexi MAC') ||
                    str_contains($line, 'Failed to create order') ||
                    str_contains($line, 'Order creation failed')
                ) {
                    $logEntries[] = $line;
                }
            }
        }

        // Also include the last checkout attempt if it was tipoPagamento == 5 even if Nexi payment initiated wasn't matched
        if ($lastCustomer && $lastCart && (int) ($lastCustomer['tipoPagamento'] ?? 0) === 5 && empty($candidates)) {
            $candidates['LAST_CHECKOUT'] = [
                'source' => 'Ultimo Checkout nel Log',
                'cart' => $lastCart,
                'customer' => $lastCustomer,
                'idLocation' => $lastLocation,
                'idPartner' => $lastPartner ?: 5,
                'totaleAcquisto' => $lastTotale,
                'nexi_cod_trans' => 'REC_' . date('YmdHis'),
                'nexi_amount' => $lastTotale,
            ];
        }

        $actionUrl = route('payment.nexi.callback', ['token' => 'rc-recover-nexi']);

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Recupero Ordini Nexi</title>';
        $html .= '<style>body{font-family:sans-serif;background:#f4f6f9;padding:24px;color:#222} .card{background:#fff;border-radius:8px;padding:20px;margin-bottom:16px;box-shadow:0 2px 6px rgba(0,0,0,0.08)} table{width:100%;border-collapse:collapse;margin-top:10px} th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;font-size:14px} .btn{background:#16a34a;color:#fff;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;font-weight:bold;text-decoration:none;display:inline-block} .badge-ok{background:#dcfce7;color:#166534;padding:4px 8px;border-radius:4px;font-weight:bold} .badge-warn{background:#fef3c7;color:#92400e;padding:4px 8px;border-radius:4px;font-weight:bold}</style></head><body>';
        $html .= '<h2>Diagnostica e Recupero Pagamenti Nexi</h2>';

        if ($message) {
            $html .= '<div class="card" style="border-left:5px solid #16a34a;font-weight:bold;">' . htmlspecialchars($message) . '</div>';
        }

        $html .= '<div class="card"><h3>Tentativi di Checkout / Transazioni Trovate (' . count($candidates) . ')</h3>';
        if (empty($candidates)) {
            $html .= '<p>Nessuna sessione Nexi trovata in cache o nel log recente.</p>';
        } else {
            $html .= '<table><thead><tr><th>Origine / Data</th><th>CodTrans</th><th>Cliente</th><th>Location / Biglietti</th><th>Totale</th><th>Stato nel DB</th><th>Azione</th></tr></thead><tbody>';
            foreach (array_reverse($candidates, true) as $cod => $item) {
                $cust = $item['customer'] ?? [];
                $email = $cust['email'] ?? '';
                $existingOrder = $email ? \App\Models\Ordine::where('emailClienteOrdine', $email)->orderByDesc('numeroOrdine')->first() : null;
                $loc = !empty($item['idLocation']) ? \App\Models\Location::find($item['idLocation']) : null;
                $ticketsDesc = collect($item['cart'] ?? [])->map(fn ($t) => ($t['quantita'] ?? 1) . 'x ' . ($t['nomeIngresso'] ?? '') . ' (€' . ($t['prezzoIngresso'] ?? 0) . ')')->implode('<br>');
                $b64 = base64_encode(json_encode($item));

                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($item['source'] ?? '') . '</td>';
                $html .= '<td><code>' . htmlspecialchars((string) $cod) . '</code></td>';
                $html .= '<td><strong>' . htmlspecialchars(($cust['nome'] ?? '') . ' ' . ($cust['cognome'] ?? '')) . '</strong><br>' . htmlspecialchars($email) . '<br>' . htmlspecialchars($cust['telefono'] ?? '') . '</td>';
                $html .= '<td><strong>' . htmlspecialchars($loc->nomeLocation ?? ('ID ' . ($item['idLocation'] ?? ''))) . '</strong><br>' . $ticketsDesc . '</td>';
                $html .= '<td><strong>€ ' . number_format((float) ($item['nexi_amount'] ?? $item['totaleAcquisto'] ?? 0), 2, ',', '.') . '</strong></td>';
                if ($existingOrder) {
                    $html .= '<td><span class="badge-ok">Presente: Ordine #' . $existingOrder->numeroOrdine . ' (' . $existingOrder->dataOrdine . ')</span></td>';
                } else {
                    $html .= '<td><span class="badge-warn">NON PRESENTE NEL DB</span></td>';
                }
                $html .= '<td><form method="POST" action="' . htmlspecialchars($actionUrl) . '" onsubmit="return confirm(\'Confermi la creazione dell\\\'ordine e l\\\'invio del voucher via email?\');">' . csrf_field() . '<input type="hidden" name="token" value="rc-recover-nexi"><input type="hidden" name="payload_b64" value="' . htmlspecialchars($b64) . '"><button type="submit" class="btn">Crea Ordine + Invia Email</button></form></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        $html .= '</div>';

        $html .= '<div class="card"><h3>Ultimi eventi Nexi / Checkout nel Log (' . count($logEntries) . ')</h3><pre style="background:#1e293b;color:#f8fafc;padding:12px;border-radius:6px;overflow-x:auto;font-size:12px;max-height:400px;">' . htmlspecialchars(implode("\n\n", array_slice($logEntries, -35))) . '</pre></div>';

        $html .= '</body></html>';
        return response($html);
    }
}
