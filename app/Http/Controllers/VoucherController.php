<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use App\Models\Voucher;
use App\Models\RigaOrdine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class VoucherController extends Controller
{
    public function show($numeroOrdine, $numeroVoucher, Request $request)
    {
        // Get partner from request or default
        $partnerId = $request->get('idPartner', 1);
        
        // Load order
        $order = Ordine::where('numeroOrdine', $numeroOrdine)->first();
        
        if (!$order) {
            abort(404, 'Ordine non trovato');
        }
        
        // Load voucher
        $voucher = Voucher::where('numeroVoucher', $numeroVoucher)
            ->where('numeroOrdineVoucher', $numeroOrdine)
            ->first();
        
        if (!$voucher) {
            abort(404, 'Voucher non trovato');
        }
        
        // Load order lines
        $orderLines = RigaOrdine::where('numeroOrdine', $numeroOrdine)->get();
        
        // Load location and partner
        $location = $order->location;
        $partner = $order->partner;
        // Generate QR Code containing the signed validation URL
        $qrCodeData = \Illuminate\Support\Facades\URL::signedRoute('voucher.validate', [
            'numeroOrdine' => $order->numeroOrdine,
            'voucher' => $voucher->numeroVoucher
        ]);
        
        $qrCode = new QrCode(data: $qrCodeData, size: 150, margin: 5);
        $writer = new SvgWriter();
        
        $result = $writer->write($qrCode);
        $qrCodeBase64 = base64_encode($result->getString());
        
        return view('voucher.show', compact('order', 'voucher', 'numeroVoucher', 'orderLines', 'location', 'partner', 'partnerId', 'qrCodeBase64'));
    }

    public function validateTicket($numeroOrdine, Request $request)
    {
        $numeroVoucher = $request->get('voucher');

        $order = Ordine::where('numeroOrdine', $numeroOrdine)->first();
        if (!$order) {
            abort(404, 'Ordine non trovato');
        }

        $voucher = Voucher::where('numeroVoucher', $numeroVoucher)
            ->where('numeroOrdineVoucher', $numeroOrdine)
            ->first();
        if (!$voucher) {
            abort(404, 'Voucher non trovato');
        }

        $orderLines = RigaOrdine::where('numeroOrdine', $numeroOrdine)->get();
        $location = $order->location;

        // Check if device is authenticated as staff
        $isStaff = $this->isValidStaffCookie($request);

        return view('voucher.validate', compact('order', 'voucher', 'orderLines', 'location', 'isStaff'));
    }

    public function confirmCheckin($numeroOrdine, $numeroVoucher, Request $request)
    {
        // Security check: must be staff
        if (!$this->isValidStaffCookie($request)) {
            abort(403, 'Azione non autorizzata. Questo dispositivo non è registrato come Staff.');
        }

        $voucher = Voucher::where('numeroVoucher', $numeroVoucher)
            ->where('numeroOrdineVoucher', $numeroOrdine)
            ->first();

        if (!$voucher) {
            abort(404, 'Voucher non trovato');
        }

        // Mark as used
        $voucher->update([
            'utilizzato' => true,
            'data_utilizzo' => now(),
        ]);

        return redirect()->back()->with('success', 'Ingresso registrato con successo!');
    }

    public function showStaffLogin(Request $request)
    {
        $redirectUrl = $request->get('redirect');
        return view('voucher.staff_login', compact('redirectUrl'));
    }

    public function processStaffLogin(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $throttleKey = 'staff-login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return redirect()->back()->withInput()->withErrors([
                'password' => "Troppi tentativi falliti. Riprova tra {$seconds} secondi.",
            ]);
        }

        $staffPassword = (string) \App\Models\Setting::getVal('password_staff', 'staff2026');

        if (hash_equals($staffPassword, (string) $request->input('password'))) {
            RateLimiter::clear($throttleKey);

            $cookie = cookie(
                'staff_device',
                $this->getStaffCookieToken(),
                60 * 24 * 30,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            );

            $redirectUrl = $this->sanitizeInternalRedirect($request->input('redirect_url'), $request);
            if ($redirectUrl) {
                return redirect()->to($redirectUrl)->withCookie($cookie)->with('success', 'Dispositivo Staff abilitato con successo!');
            }

            return redirect()->route('home')->withCookie($cookie)->with('success', 'Dispositivo Staff abilitato con successo!');
        }

        RateLimiter::hit($throttleKey, 60);

        return redirect()->back()->withInput()->withErrors(['password' => 'Password non corretta.']);
    }

    private function getStaffCookieToken(): string
    {
        return hash_hmac('sha256', 'staff_authenticated', (string) config('app.key'));
    }

    private function isValidStaffCookie(Request $request): bool
    {
        $cookieVal = $request->cookie('staff_device');
        if (!is_string($cookieVal) || $cookieVal === '') {
            return false;
        }

        return hash_equals($this->getStaffCookieToken(), $cookieVal);
    }

    private function sanitizeInternalRedirect(?string $url, Request $request): ?string
    {
        if (!$url) {
            return null;
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        $parsed = parse_url($url);
        if (is_array($parsed) && ($parsed['host'] ?? null) === $request->getHost()) {
            return $url;
        }

        return null;
    }
}
