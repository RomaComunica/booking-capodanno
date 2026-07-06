<?php

namespace App\Services\Payments;

use App\Models\Ordine;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Services\Voucher\VoucherPdfService;
use App\Mail\OrderConfirmed;


class PayPalNvpService implements PaymentProviderInterface
{
    protected Client $http;
    protected string $endpoint;
    protected string $user;
    protected string $pwd;
    protected string $sig;

    public function __construct()
    {
        $mode = env('PAYPAL_MODE', 'sandbox'); // sandbox|live
        $this->endpoint = $mode === 'live'
            ? 'https://api-3t.paypal.com/nvp'
            : 'https://api-3t.sandbox.paypal.com/nvp';

        $this->user = env('PAYPAL_NVP_USERNAME');
        $this->pwd  = env('PAYPAL_NVP_PASSWORD');
        $this->sig  = env('PAYPAL_NVP_SIGNATURE');

        $this->http = new Client(['timeout' => 20]);
    }

    protected function nvp(string $method, array $params): array
    {
        $base = [
            'METHOD'    => $method,
            'VERSION'   => '124',
            'USER'      => $this->user,
            'PWD'       => $this->pwd,
            'SIGNATURE' => $this->sig,
        ];

        $res = $this->http->post($this->endpoint, [
            'form_params' => array_merge($base, $params),
        ]);

        parse_str((string) $res->getBody(), $out);
        return $out;
    }

    public function createOrder(Ordine $ordine): string
    {
        $amount = number_format((float)($ordine->totalOrdine ?? 0), 2, '.', '');
        $return = URL::route('pay.return');
        $cancel = URL::route('booking.index');

        $resp = $this->nvp('SetExpressCheckout', [
            'PAYMENTREQUEST_0_AMT'          => $amount,
            'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
            'RETURNURL'                     => $return,
            'CANCELURL'                     => $cancel,
            'PAYMENTREQUEST_0_INVNUM'       => $ordine->numeroOrdine,
            'PAYMENTREQUEST_0_DESC'         => 'Ordine '.$ordine->numeroOrdine,
        ]);

        if (($resp['ACK'] ?? '') !== 'Success') {
            throw new \RuntimeException('PayPal SetExpressCheckout failed: '.json_encode($resp));
        }

        $token = $resp['TOKEN'];
        $base = (env('PAYPAL_MODE', 'sandbox') === 'live')
            ? 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='
            : 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';

        return $base.$token;
    }

    public function captureOrder(string $token): array
    {
        $payerId = request()->query('PayerID'); // PayPal NVP lo passa alla RETURNURL
        if (!$payerId) {
            throw new \InvalidArgumentException('PayerID mancante nella return URL');
        }

        // Recupero dettagli per avere INVNUM (nostro numeroOrdine) e importo
        $details = $this->nvp('GetExpressCheckoutDetails', ['TOKEN' => $token]);
        $numero = $details['PAYMENTREQUEST_0_INVNUM'] ?? null;
        $amount = $details['PAYMENTREQUEST_0_AMT'] ?? null;
        if (!$numero || !$amount) {
            throw new \RuntimeException('Impossibile determinare numeroOrdine/amount.');
        }

        // Cattura
        $pay = $this->nvp('DoExpressCheckoutPayment', [
            'TOKEN'                         => $token,
            'PAYERID'                       => $payerId,
            'PAYMENTREQUEST_0_AMT'          => $amount,
            'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
            'PAYMENTREQUEST_0_PAYMENTACTION'=> 'Sale',
        ]);

        if (($pay['ACK'] ?? '') !== 'Success') {
            throw new \RuntimeException('DoExpressCheckoutPayment failed: '.json_encode($pay));
        }

        // Aggiorna ordine
        $ordine = Ordine::where('numeroOrdine', $numero)->firstOrFail();
        $ordine->statoOrdine = 'paid';
        $ordine->ordineConfermato = 1;
        $ordine->dataPagamentoOrdine = date('Y-m-d H:i:s');
        $ordine->save();
        // genera voucher e manda email
        $voucher = app(VoucherPdfService::class)->generateForOrder($ordine);
        $abs = storage_path('app/'.$voucher['path']);
        $to = $ordine->emailClienteOrdine ?? env('MAIL_FROM_ADDRESS'); // adatta il campo email
        Mail::to($to)->send(new OrderConfirmed($ordine, $abs));


        return [$numero, 'COMPLETED'];
    }

    public function handleWebhook(array $payload): void
    {
        // NVP non usa webhook (c’è IPN, opzionale). Lasciato vuoto.
    }
}
