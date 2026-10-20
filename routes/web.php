<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ComuniController;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/location/{id}', [LocationController::class, 'show'])->name('location.show');
Route::get('/api/comuni', [ComuniController::class, 'search'])->name('api.comuni');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Cart routes
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::delete('/cart/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Checkout routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
Route::get('/checkout/success/{ordine}', [CheckoutController::class, 'success'])->name('checkout.success');

// Order routes
Route::get('/order/create', [OrderController::class, 'create'])->name('order.create');
Route::get('/order/success/{numeroOrdine}/{numeroVoucher}', [OrderController::class, 'success'])
    ->name('order.success')
    ->middleware('signed');

// Voucher routes
Route::get('/voucher/{numeroOrdine}/{numeroVoucher}', [VoucherController::class, 'show'])
    ->name('voucher.show')
    ->middleware('signed');

// Ticket Validation & Check-in (Signed URLs)
Route::get('/admin/ordini/{numeroOrdine}/validate', [VoucherController::class, 'validateTicket'])
    ->name('voucher.validate')
    ->middleware('signed');

Route::post('/admin/ordini/{numeroOrdine}/confirm-checkin/{numeroVoucher}', [VoucherController::class, 'confirmCheckin'])
    ->name('voucher.confirm-checkin');

// Staff Pre-Authentication
Route::get('/staff/login', [VoucherController::class, 'showStaffLogin'])->name('staff.login');
Route::post('/staff/login', [VoucherController::class, 'processStaffLogin'])->name('staff.process-login');

/*
|-----------------------------------------
| Pagamenti (switch via .env PAYMENT_PROVIDER)
| - paypal_nvp  → PayPal Express Checkout NVP
| - nexi        → Nexi XPay
|-----------------------------------------
*/

// PayPal Payment Routes
Route::prefix('payment/paypal')->name('payment.paypal.')->group(function () {
    Route::get('/init', [App\Http\Controllers\PayPalController::class, 'init'])->name('init');
    Route::get('/return', [App\Http\Controllers\PayPalController::class, 'return'])->name('return');
    Route::post('/confirm', [App\Http\Controllers\PayPalController::class, 'confirm'])->name('confirm');
    Route::get('/cancel', [App\Http\Controllers\PayPalController::class, 'cancel'])->name('cancel');
});

// Nexi Payment Routes
Route::prefix('payment/nexi')->name('payment.nexi.')->group(function () {
    Route::get('/init', [App\Http\Controllers\NexiController::class, 'init'])->name('init');
    Route::match(['get', 'post'], '/callback', [App\Http\Controllers\NexiController::class, 'callback'])->name('callback');
    Route::match(['get', 'post'], '/notify', [App\Http\Controllers\NexiController::class, 'notify'])->name('notify');
    Route::match(['get', 'post'], '/cancel', [App\Http\Controllers\NexiController::class, 'cancel'])->name('cancel');
});

Route::get('/clear-cache', function () {
    if (!auth()->check() && request('token') !== 'rc-cache-clear') {
        abort(403, 'Accesso non autorizzato. Utilizza /clear-cache?token=rc-cache-clear o effettua l\'accesso all\'area admin.');
    }
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }

    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('location') && !\Illuminate\Support\Facades\Schema::hasColumn('location', 'ibanLocation')) {
            \Illuminate\Support\Facades\Schema::table('location', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('ibanLocation', 50)->nullable()->default('');
                $table->string('intestatarioLocation', 255)->nullable()->default('');
            });
        }
    } catch (\Throwable $e) {}
    
    if (request()->headers->get('referer')) {
        return redirect()->back();
    }
    
    return 'Cache (View, App, Config) cleared successfully!';
});

Route::get('/run-migrations', function () {
    if (!auth()->check() && request('token') !== 'rc-migrate') {
        abort(403, 'Accesso non autorizzato. Utilizza /run-migrations?token=rc-migrate.');
    }
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return 'Migrazioni eseguite con successo!<br><pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return 'Errore durante la migrazione: ' . $e->getMessage();
    }
});

Route::get('/storage-link', function () {
    if (!auth()->check() && request('token') !== 'rc-storage-link') {
        abort(403, 'Accesso non autorizzato. Utilizza /storage-link?token=rc-storage-link.');
    }
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        return 'Link simbolico storage creato con successo!<br><pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return 'Errore durante la creazione del link simbolico: ' . $e->getMessage();
    }
});

// Fallback route to serve storage files if public/storage symlink is missing on shared hosting (e.g. SiteGround)
Route::get('/storage/{path}', function ($path) {
    $candidates = [
        storage_path('app/public/' . $path),
        public_path('img/locations/' . basename($path)),
        public_path('partner/loghi/' . basename($path)),
        public_path('storage/' . $path),
        storage_path('app/public/locations/' . basename($path)),
        storage_path('app/public/partner/loghi/' . basename($path)),
        storage_path('app/private/locations/' . basename($path)),
        storage_path('app/locations/' . basename($path)),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'webp' => 'image/webp',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                'svg'  => 'image/svg+xml',
            ];
            $contentType = $mimeTypes[$extension] ?? (mime_content_type($filePath) ?: 'application/octet-stream');

            return response()->file($filePath, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
})->where('path', '.*');

// Fallback route to serve partner logos if webroot is separated from public_path on shared hosting
Route::get('/partner/loghi/{filename}', function ($filename) {
    $base = basename($filename);
    $candidates = [
        public_path('partner/loghi/' . $base),
        storage_path('app/public/partner/loghi/' . $base),
        public_path('storage/partner/loghi/' . $base),
    ];

    // If requested as e.g. 5.jpg, also check other extensions for that partner ID
    $idPart = pathinfo($base, PATHINFO_FILENAME);
    if (is_numeric($idPart)) {
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif', 'JPG', 'JPEG', 'PNG', 'WEBP', 'GIF'] as $ext) {
            $candidates[] = public_path("partner/loghi/{$idPart}.{$ext}");
            $candidates[] = storage_path("app/public/partner/loghi/{$idPart}.{$ext}");
            $candidates[] = public_path("storage/partner/loghi/{$idPart}.{$ext}");
        }
    }

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'webp' => 'image/webp',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                'svg'  => 'image/svg+xml',
            ];
            $contentType = $mimeTypes[$extension] ?? (mime_content_type($filePath) ?: 'application/octet-stream');

            return response()->file($filePath, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }
    }

    abort(404);
})->where('filename', '.*');

// Admin utility to inspect and recover any Nexi transaction that succeeded on Nexi but failed before order creation
Route::match(['get', 'post'], '/recover-nexi-orders', function (\Illuminate\Http\Request $request) {
    if (!auth()->check() && $request->input('token') !== 'rc-recover-nexi') {
        abort(403, 'Accesso non autorizzato. Usa /recover-nexi-orders?token=rc-recover-nexi');
    }

    $message = null;

    // Handle 1-click order creation from recovered payload
    if ($request->isMethod('post') && $request->filled('payload_b64')) {
        $decoded = json_decode(base64_decode($request->input('payload_b64')), true);
        if (is_array($decoded) && !empty($decoded['cart']) && !empty($decoded['customer'])) {
            $codTrans = $decoded['nexi_cod_trans'] ?? ('REC_' . time());
            $txId = $decoded['nexi_transaction_id'] ?? $codTrans;
            \Illuminate\Support\Facades\Cache::forget('checkout_done_' . $codTrans);
            \Illuminate\Support\Facades\Cache::put('checkout_tx_' . $codTrans, $decoded, now()->addHours(24));

            $res = app(\App\Http\Controllers\OrderController::class)->finalizeOrderFromSessionOrCache($codTrans, [
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
        if (\Illuminate\Support\Facades\Schema::hasTable('cache')) {
            $rows = \Illuminate\Support\Facades\DB::table('cache')
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

    // 2. Parse storage/logs/laravel.log (last 800 KB) to reconstruct recent checkout attempts
    $logPath = storage_path('logs/laravel.log');
    $logEntries = [];
    if (file_exists($logPath)) {
        $size = filesize($logPath);
        $fp = fopen($logPath, 'r');
        if ($fp) {
            $readBytes = min($size, 800 * 1024);
            if ($size > $readBytes) {
                fseek($fp, $size - $readBytes);
            }
            $content = fread($fp, $readBytes);
            fclose($fp);

            $lines = explode("\n", $content);
            $lastCart = null;
            $lastCustomer = null;
            $lastLocation = null;
            $lastPartner = 5;

            foreach ($lines as $line) {
                if (str_contains($line, 'CartController::add called') && preg_match('/\{.*\}$/', $line, $m)) {
                    $data = json_decode($m[0], true);
                    if (is_array($data)) {
                        $lastLocation = $data['idLocation'] ?? $lastLocation;
                        $lastPartner = $data['idPartner'] ?? $lastPartner;
                    }
                } elseif (str_contains($line, 'Cart content:') && preg_match('/\[.*\]$/', $line, $m)) {
                    $c = json_decode($m[0], true);
                    if (is_array($c)) {
                        $lastCart = $c;
                    }
                } elseif (str_contains($line, 'Validation passed') && preg_match('/\{.*\}$/', $line, $m)) {
                    $v = json_decode($m[0], true);
                    if (!empty($v['validated'])) {
                        $lastCustomer = $v['validated'];
                    }
                } elseif (str_contains($line, 'About to create order ') && preg_match('/\{.*\}$/', $line, $m)) {
                    $o = json_decode($m[0], true);
                    if (!empty($o['customer'])) {
                        $lastCustomer = $o['customer'];
                        $lastLocation = $o['idLocation'] ?? $lastLocation;
                        $lastPartner = $o['partnerId'] ?? $lastPartner;
                    }
                } elseif (str_contains($line, 'Nexi payment initiated') && preg_match('/\{.*\}$/', $line, $m)) {
                    $nx = json_decode($m[0], true);
                    $codTrans = $nx['codTrans'] ?? ('LOG_' . uniqid());
                    $timeMatch = [];
                    preg_match('/^\[([^\]]+)\]/', $line, $timeMatch);
                    if ($lastCustomer && $lastCart && empty($candidates[$codTrans])) {
                        $candidates[$codTrans] = [
                            'source' => 'Log Laravel (' . ($timeMatch[1] ?? '') . ')',
                            'cart' => $nx['cart'] ?? $lastCart,
                            'customer' => $lastCustomer,
                            'idLocation' => $nx['idLocation'] ?? $lastLocation,
                            'idPartner' => $lastPartner ?: 5,
                            'totaleAcquisto' => $nx['amount'] ?? 0,
                            'nexi_cod_trans' => $codTrans,
                            'nexi_amount' => $nx['amount'] ?? 0,
                            'created_at' => $timeMatch[1] ?? '',
                        ];
                    }
                } elseif ((str_contains($line, 'Nexi callback received') || str_contains($line, 'Failed to create order') || str_contains($line, 'Nexi payment failed')) && preg_match('/^\[([^\]]+)\].*$/', $line, $m)) {
                    $logEntries[] = $m[0];
                }
            }
        }
    }

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Recupero Ordini Nexi</title>';
    $html .= '<style>body{font-family:sans-serif;background:#f4f6f9;padding:24px;color:#222} .card{background:#fff;border-radius:8px;padding:20px;margin-bottom:16px;box-shadow:0 2px 6px rgba(0,0,0,0.08)} table{width:100%;border-collapse:collapse;margin-top:10px} th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;font-size:14px} .btn{background:#16a34a;color:#fff;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;font-weight:bold} .badge-ok{background:#dcfce7;color:#166534;padding:4px 8px;border-radius:4px;font-weight:bold} .badge-warn{background:#fef3c7;color:#92400e;padding:4px 8px;border-radius:4px;font-weight:bold}</style></head><body>';
    $html .= '<h2>Diagnostica e Recupero Pagamenti Nexi</h2>';

    if ($message) {
        $html .= '<div class="card" style="border-left:5px solid #16a34a;font-weight:bold;">' . htmlspecialchars($message) . '</div>';
    }

    $html .= '<div class="card"><h3>Tentativi di Checkout / Transazioni Trovate (' . count($candidates) . ')</h3>';
    if (empty($candidates)) {
        $html .= '<p>Nessuna sessione in cache o nel log recente.</p>';
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
            $html .= '<td><form method="POST" action="/recover-nexi-orders?token=rc-recover-nexi" onsubmit="return confirm(\'Confermi la creazione dell\\\'ordine e l\\\'invio del voucher via email?\');">' . csrf_field() . '<input type="hidden" name="payload_b64" value="' . htmlspecialchars($b64) . '"><button type="submit" class="btn">Crea Ordine + Invia Email</button></form></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    }
    $html .= '</div>';

    if (!empty($logEntries)) {
        $html .= '<div class="card"><h3>Ultimi eventi Nexi / Ordini nel Log</h3><pre style="background:#1e293b;color:#f8fafc;padding:12px;border-radius:6px;overflow-x:auto;font-size:12px;">' . htmlspecialchars(implode("\n", array_slice($logEntries, -25))) . '</pre></div>';
    }

    $html .= '</body></html>';
    return response($html);
});



