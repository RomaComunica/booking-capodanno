<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Voucher Conferma - Ordine #{{ $order->numeroOrdine }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        hr {
            border: 0;
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }
        h2, h3, h4 { color: #333; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th { background-color: #f5f5f5; }
        .qr-code { text-align: center; margin: 30px 0; }
        .footer {
            font-size: 11px;
            color: #555;
            text-align: center;
            margin-top: 40px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Riepilogo Ordine <strong>#{{ $order->numeroOrdine }}</strong></h2>

        <div style="margin-bottom: 20px; text-align: left;">
            @if($location->image_path && file_exists($location->image_path))
                @php
                    try {
                        $imgData = base64_encode(file_get_contents($location->image_path));
                        $imgType = strtolower(pathinfo($location->image_path, PATHINFO_EXTENSION));
                        if($imgType == 'jpg') $imgType = 'jpeg';
                        echo '<img src="data:image/' . $imgType . ';base64,' . $imgData . '" alt="' . $location->nomeLocation . '" style="max-height: 100px;">';
                    } catch(\Exception $e) {}
                @endphp
            @elseif(file_exists(public_path("images/location/{$location->idLocation}_0.jpg")))
                @php
                    try {
                        $imgData = base64_encode(file_get_contents(public_path("images/location/{$location->idLocation}_0.jpg")));
                        echo '<img src="data:image/jpeg;base64,' . $imgData . '" alt="' . $location->nomeLocation . '" style="max-height: 100px;">';
                    } catch(\Exception $e) {}
                @endphp
            @else
                <h1 style="color: #333; margin: 0;">BOOKING ONLINE</h1>
            @endif
        </div>
        <p>
            <strong>Location:</strong> {{ $location->nomeLocation }}<br>
            <strong>Indirizzo:</strong> {{ $location->indirizzoLocation }}<br>
            <strong>Data Ordine:</strong> {{ \Carbon\Carbon::parse($order->dataOrdine)->format('d/m/Y') }}<br>
            <strong>Intestatario:</strong> {{ $order->nomeClienteOrdine }} {{ $order->cognomeClienteOrdine }}<br>
            <strong>Pagamento:</strong> {{ $order->tipoPagamento->descrizioneTipoPagamento ?? 'N/D' }} 
            @if((int)$order->statoOrdine === 1)
                <span style="color: green;">(Pagato)</span>
            @else
                <span style="color: red;">(In attesa di pagamento)</span>
            @endif
        </p>

        <hr>

        <div class="qr-code">
            <h4 style="margin-bottom: 5px;">Scansiona all'ingresso:</h4>
            {{-- We output the base64 PNG data uri --}}
            <img src="{{ $qrCodeDataUri }}" alt="QR Code" style="width: 200px; height: 200px; margin: 0 auto;">
            <p style="font-size: 12px; color: #666; margin-top: 5px;">Voucher ID: {{ $voucher->numeroVoucher }}</p>
        </div>

        <hr>

        <h4>Dettaglio Ingressi</h4>
        <table>
            <thead>
                <tr>
                    <th>Data e ora</th>
                    <th>Ticket / Servizio</th>
                    <th class="text-center">Quantità</th>
                    <th class="text-right">Prezzo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderLines as $line)
                <tr>
                    <td>
                        @if($line->ingressoSenzaDataOrdine)
                            {{ $line->etichettaIngressoOrdine ?: 'Data Libera' }} 
                            {{ $line->orarioIngressoOrdine ? '- ' . \Carbon\Carbon::parse($line->orarioIngressoOrdine)->format('H:i') : '' }}
                        @else
                            {{ $line->dataIngressoOrdine ? \Carbon\Carbon::parse($line->dataIngressoOrdine)->format('d/m/Y') : 'Data non specificata' }}
                            {{ $line->orarioIngressoOrdine ? '- ' . \Carbon\Carbon::parse($line->orarioIngressoOrdine)->format('H:i') : '' }}
                        @endif
                    </td>
                    <td>{{ $line->nomeIngressoOrdine }}</td>
                    <td class="text-center">{{ $line->quantitaIngressoOrdine }}</td>
                    <td class="text-right">{{ number_format($line->prezzoIngressoOrdine, 2, ',', '.') }} €</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="text-right"><strong>Totale Ordine:</strong></td>
                    <td class="text-right"><strong>{{ number_format($order->totaleOrdine, 2, ',', '.') }} €</strong></td>
                </tr>
            </tbody>
        </table>

        <hr>

        <h4 style="color: #d9534f;">Note e Istruzioni</h4>
        <ul>
            <li>Il voucher è nominativo. Da presentare all'ingresso insieme ad un documento di Identità.</li>
            <li>Il presente voucher verrà sostituito con i biglietti/documento equivalente presso l'ingresso.</li>
            <li><strong>Porta con te il voucher (stampato o su smartphone)</strong></li>
            <li><strong>Mostra il QR code all'addetto</strong></li>
        </ul>
        
        <p style="font-size: 14px;"><strong>Valido per:</strong> {{ $orderLines->sum('quantitaIngressoOrdine') }} persone</p>

        <div class="footer">
            {!! nl2br(e(\App\Models\Setting::getVal('voucher_footer_text', "Servizio booking online: Romacomunica srls\nRomacomunica non si occupa dell'organizzazione dell'evento, tutte le informazioni riportate sono state ricevute dal legittimo proprietario, responsabile nei confronti del cliente.\nVia dei Carraresi 12 - 00164 - Roma (RM) - Tel.: 06.45495920 - Cell.: 3273103225\nWebsite: romacomunicaweb.it - E-mail: info@romacomunicaweb.it - P.Iva: 15995221007"))) !!}
        </div>

    </div>
</body>
</html>
