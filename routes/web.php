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
    Route::post('/callback', [App\Http\Controllers\NexiController::class, 'callback'])->name('callback');
    Route::post('/notify', [App\Http\Controllers\NexiController::class, 'notify'])->name('notify');
    Route::get('/cancel', [App\Http\Controllers\NexiController::class, 'cancel'])->name('cancel');
});

Route::get('/clear-cache', function () {
    if (!auth()->check() && request('token') !== 'rc-cache-clear') {
        abort(403, 'Accesso non autorizzato. Utilizza /clear-cache?token=rc-cache-clear o effettua l\'accesso all\'area admin.');
    }
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    
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
        public_path('storage/' . $path),
        storage_path('app/public/locations/' . basename($path)),
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

