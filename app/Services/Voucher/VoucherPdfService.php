<?php

namespace App\Services\Voucher;

use App\Models\Ordine;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class VoucherPdfService
{
    public function generateForOrder(Ordine $ordine): array
    {
        $html = View::make('vouchers.voucher', ['ordine' => $ordine])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfPath = 'vouchers/'.$ordine->numeroOrdine.'.pdf';
        Storage::put($pdfPath, $dompdf->output());

        return [
            'path' => $pdfPath,
            'url'  => route('checkout.thankyou', ['numeroOrdine' => $ordine->numeroOrdine]),
        ];
    }
}
