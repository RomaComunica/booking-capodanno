<?php

namespace App\Services\Payments;

use App\Mail\OrderConfirmed;
use App\Services\Voucher\VoucherPdfService;
use App\Models\Ordine;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class NexiService implements PaymentProviderInterface
{
    // Per compatibilità con l'interfaccia, ritorna la nostra rotta che renderà il form Nexi
    public function createOrder(Ordine $ordine): string
    {
        return URL::route('nexi.submit', ['numeroOrdine' => $ordine->numeroOrdine]);
    }

    // Non usato in Nexi (la cattura avviene lato Nexi)
    public function captureOrder(string $token): array
    {
        $numero = $token;
        return [$numero, 'UNKNOWN'];
    }

    public function handleWebhook(array $payload): void
    {
        // non usato (usiamo la callback s2s dedicata qui sotto)
    }

    // Prepara i parametri e la firma MAC per il form verso Nexi
    public function prepareFormParams(Ordine $ordine): array
    {
        $alias = env('NEXI_ALIAS');
        $secret= env('NEXI_SECRET');
        $endpoint = rtrim(env('NEXI_ENDPOINT'), '/'); // deve puntare all'endpoint form Nexi
        $amount = (int) round(((float)($ordine->totalOrdine ?? 0)) * 100); // centesimi
        $divisa = 'EUR';
        $codTrans = $ordine->numeroOrdine;
        $ts = (string) round(microtime(true) * 1000);

        // Verifica sulla tua documentazione: formato MAC comunemente usato
        $macString = "codTrans={$codTrans}divisa={$divisa}importo={$amount}timeStamp={$ts}{$secret}";
        $mac = sha1($macString);

        return [
            'action'   => $endpoint, // es.: https://ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet
            'params'   => [
                'alias'     => $alias,
                'importo'   => $amount,
                'divisa'    => $divisa,
                'codTrans'  => $codTrans,
                'url'       => URL::route('nexi.esito'),    // redirect cliente
                'urlpost'   => URL::route('nexi.callback'), // callback server-to-server
                'timeStamp' => $ts,
                'mac'       => $mac,
                'languageId'=> 'IT',
            ],
        ];
    }

    // Ritorno cliente: aggiorniamo l'ordine se esito OK
    public function handleReturn(array $query): array
    {
        $numero = $query['codTrans'] ?? null;
        $esito  = $query['esito'] ?? 'UNKNOWN';

        if ($numero && $esito === 'OK') {
            $ordine = Ordine::where('numeroOrdine', $numero)->first();
            if ($ordine) {
                $ordine->statoOrdine = 'paid';
                $ordine->ordineConfermato = 1;
                $ordine->dataPagamentoOrdine = date('Y-m-d H:i:s');
                $ordine->save();

                // genera voucher e invia email
                $voucher = app(VoucherPdfService::class)->generateForOrder($ordine);
                $abs = storage_path('app/'.$voucher['path']);
                $to = $ordine->emailClienteOrdine ?? env('MAIL_FROM_ADDRESS');
                Mail::to($to)->send(new OrderConfirmed($ordine, $abs));
            }
        }

        return [$numero ?: 'UNKNOWN', $esito];
    }

    // Callback S2S (POST) da Nexi: valida ed aggiorna
    public function handleCallback(array $post): void
    {
        $numero = $post['codTrans'] ?? null;
        $esito  = $post['esito'] ?? 'UNKNOWN';

        if ($numero && $esito === 'OK') {
            $ordine = Ordine::where('numeroOrdine', $numero)->first();
            if ($ordine) {
                $ordine->statoOrdine = 'paid';
                $ordine->ordineConfermato = 1;
                $ordine->dataPagamentoOrdine = date('Y-m-d H:i:s');
                $ordine->save();

                // genera voucher e invia email
                $voucher = app(VoucherPdfService::class)->generateForOrder($ordine);
                $abs = storage_path('app/'.$voucher['path']);
                $to = $ordine->emailClienteOrdine ?? env('MAIL_FROM_ADDRESS');
                Mail::to($to)->send(new OrderConfirmed($ordine, $abs));
            }
        }
    }
}
