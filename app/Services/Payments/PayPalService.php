<?php
namespace App\Services\Payments;

use App\Models\Ordine;
use Illuminate\Support\Facades\URL;

class PayPalService implements PaymentProviderInterface
{
    public function createOrder(Ordine $ordine): string
    {
        // TODO: integra srmklive/paypal
        return URL::route('paypal.approve', ['token' => 'FAKE-TOKEN-'.$ordine->numeroOrdine]);
    }

    public function captureOrder(string $token): array
    {
        $numeroOrdine = str_replace('FAKE-TOKEN-', '', $token);
        $ordine = Ordine::where('numeroOrdine', $numeroOrdine)->firstOrFail();
        $ordine->statoOrdine = 'paid';
        $ordine->ordineConfermato = 1;
        $ordine->dataPagamentoOrdine = date('Y-m-d H:i:s');
        $ordine->save();
        return [$numeroOrdine, 'COMPLETED'];
    }

    public function handleWebhook(array $payload): void
    {
        // TODO: verifica firma e gestisci eventi PayPal
    }
}
