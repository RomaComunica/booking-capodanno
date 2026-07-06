@php($o = $ordine)
<p>Ciao {{ $o->nomeClienteOrdine ?? 'Cliente' }},</p>
<p>la tua prenotazione è stata confermata.</p>
<p><strong>Numero Ordine:</strong> {{ $o->numeroOrdine }}<br>
<strong>Data:</strong> {{ $o->dataPagamentoOrdine ?? now()->format('Y-m-d H:i') }}</p>

<p>Puoi scaricare e stampare il tuo voucher ufficiale cliccando sul link seguente:<br>
@php
    $voucher = \App\Models\Voucher::where('numeroOrdineVoucher', $o->numeroOrdine)->first();
@endphp
@if($voucher)
    <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('voucher.show', ['numeroOrdine' => $o->numeroOrdine, 'numeroVoucher' => $voucher->numeroVoucher, 'idPartner' => $o->partner->idPartner ?? 1]) }}" style="color: #337ab7; text-decoration: none;">
        <strong>Stampa il Voucher</strong>
    </a>
@else
    <strong>(Voucher non ancora generato)</strong>
@endif
</p>
<p>Ti aspettiamo!</p>

<p>— Booking</p>
