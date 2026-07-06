<?php
use \App\Models\Ordine;
\ = Ordine::with('righe', 'location', 'partner')->first();
if (\) {
    \ = \->voucher;
    \ = \Endroid\QrCode\Builder\Builder::create()
        ->writer(new \Endroid\QrCode\Writer\SvgWriter())
        ->data(\->numeroVoucher)
        ->build();
    \ = \->getString();
    
    \ = view('voucher.pdf', [
        'order' => \,
        'voucher' => \,
        'orderLines' => \->righe,
        'location' => \->location,
        'partner' => \->partner,
        'qrCodeSvg' => \
    ])->render();
    
    \ = new \Dompdf\Dompdf();
    \->loadHtml(\);
    \->render();
    echo 'PDF rendered successfully. Size: ' . strlen(\->output()) . ' bytes';
} else {
    echo 'No order found.';
}

