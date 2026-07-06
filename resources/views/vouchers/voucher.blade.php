@php($o = $ordine)
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Voucher {{ $o->numeroOrdine }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    .box { border:1px solid #333; padding:16px; }
    .title { font-size:18px; font-weight:700; }
  </style>
</head>
<body>
  <div class="box">
    <div class="title">Voucher — Ordine {{ $o->numeroOrdine }}</div>
    <p>Intestatario: {{ $o->nomeClienteOrdine ?? 'Cliente' }}</p>
    <p>Location: {{ optional($o->location)->nomeLocation ?? '-' }}</p>
    <p>Partner: {{ optional($o->partner)->nomePartner ?? '-' }}</p>
    <p>Data: {{ $o->dataPagamentoOrdine ?? now()->format('Y-m-d') }}</p>
    <p>Presenta questo voucher all'ingresso.</p>

    {{-- QR Code --}}
    @php
        use App\Support\Qr;
        // Codifica il numero ordine; se vuoi, qui puoi passare un URL di verifica invece del numero semplice
        $qr = Qr::dataUri($o->numeroOrdine, 160);
    @endphp
    <div style="margin-top:24px;">
      <img src="{{ $qr }}" alt="QR" style="width:160px;height:160px">
    </div>
  </div>
</body>
</html>
