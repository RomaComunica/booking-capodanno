<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Voucher #{{ $numeroVoucher }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px;
            color: #000;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #000;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th {
            background-color: #5bc0de;
            color: white;
            padding: 10px;
            text-align: left;
            border: 1px solid #000;
        }
        table td {
            padding: 10px;
            border: 1px solid #000;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .voucher-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .voucher-number {
            font-size: 24px;
            font-weight: bold;
            color: #d9534f;
        }
        .print-button {
            text-align: center;
            margin: 20px 0;
        }
        @media print {
            .print-button, .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="voucher-header" style="position: relative;">
            <table style="width: 100%; border: none; margin-top: 10px;">
                <tr>
                    <td style="border: none; text-align: left; vertical-align: top;">
                        <img src="{{ asset('img/logo-rc-scuro.png') }}" alt="Roma Comunica" style="max-height: 50px; margin-bottom: 10px;">
                        <h2 style="margin-top: 0;">VOUCHER DI PRENOTAZIONE</h2>
                        <p class="voucher-number">N° {{ $voucher->numeroVoucher }}</p>
                        
                        <div style="margin-top: 20px;">
                            @if($location->image_url)
                                <img src="{{ $location->image_url }}" alt="{{ $location->nomeLocation }}" style="max-height: 120px; object-fit: contain;">
                            @elseif($partner && file_exists(public_path("partner/loghi/{$partner->idPartner}.jpg")))
                                <img src="{{ asset("partner/loghi/{$partner->idPartner}.jpg") }}" alt="{{ $partner->nomePartner }}" style="max-height: 80px;">
                            @else
                                <h1 style="margin: 0;">BOOKING ONLINE</h1>
                            @endif
                        </div>
                    </td>
                    <td style="border: none; text-align: right; vertical-align: top;">
                        @if(isset($qrCodeBase64))
                            <img src="data:image/svg+xml;base64,{{ $qrCodeBase64 }}" alt="QR Code" style="max-width: 150px; border: 1px solid #ccc; padding: 5px; border-radius: 5px;">
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <hr>

        <h3>DETTAGLI EVENTO</h3>
        <p>
            <strong>Location:</strong> {{ $location->nomeLocation }}<br>
            <strong>Indirizzo:</strong> {{ $location->indirizzoLocation }}<br>
            <strong>Data Voucher:</strong> {{ \Carbon\Carbon::parse($voucher->dataVoucher)->format('d/m/Y H:i') }}
        </p>

        <hr>

        <h3>INTESTATARIO</h3>
        <p>
            <strong>Nome:</strong> {{ trim(($voucher->nomeVoucher ?? '') . ' ' . ($voucher->cognomeVoucher ?? '')) ?: trim(($order->nomeClienteOrdine ?? '') . ' ' . ($order->cognomeClienteOrdine ?? '')) }}
        </p>

        <hr>

        <h3>BIGLIETTI ACQUISTATI</h3>
        <table>
            <thead>
                <tr>
                    <th>Data e Ora</th>
                    <th>Tipologia</th>
                    <th class="text-center">Quantità</th>
                    <th class="text-right">Prezzo</th>
                </tr>
            </thead>
            <tbody>
                @php $totaleBigliettiVoucher = 0; @endphp
                @foreach($orderLines as $line)
                @php 
                    $totaleBigliettiVoucher += ((float)$line->prezzoIngressoOrdine * (int)$line->quantitaIngressoOrdine);
                    $tipologia = $line->tipologia;
                    $isSenzaData = (bool)($line->ingressoSenzaDataOrdine || ($tipologia && $tipologia->ingressoSenzaData));
                    $etichetta = $line->etichettaIngressoOrdine ?: ($tipologia?->etichettaIngresso ?: '');
                    $dataVisualizzata = $line->data_visualizzata;
                    $orarioVisualizzato = $line->orario_visualizzato;
                @endphp
                <tr>
                    <td>
                        @if($isSenzaData)
                            {{ $etichetta ?: 'Ingresso senza data' }}
                            @if($orarioVisualizzato)
                                - {{ $orarioVisualizzato }}
                            @endif
                        @else
                            {{ $dataVisualizzata ?: 'Data non specificata' }}
                            @if($orarioVisualizzato)
                                - {{ $orarioVisualizzato }}
                            @endif
                        @endif
                    </td>
                    <td>{{ $line->nomeIngressoOrdine }}</td>
                    <td class="text-center">{{ $line->quantitaIngressoOrdine }}</td>
                    <td class="text-right">€ {{ number_format($line->prezzoIngressoOrdine, 2, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="text-right"><strong>TOTALE:</strong></td>
                    <td class="text-right"><strong>€ {{ number_format($totaleBigliettiVoucher, 2, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <hr>

        <h3 style="color: #d9534f; margin-top: 25px;">ISTRUZIONI E CONDIZIONI</h3>
        <p>
            <strong>A titolo di conferma della prenotazione è stato versato il saldo tramite {{ $order->tipoPagamento->descrizioneTipoPagamento ?? $order->tipoPagamento->nomePagamento ?? 'Pagamento' }}.</strong>
        </p>

        <div class="steps-container" style="display: flex; gap: 15px; margin: 25px 0; flex-wrap: wrap;">
            <div class="step-card" style="flex: 1; min-width: 140px; border: 2px dashed #5bc0de; border-radius: 10px; padding: 15px; text-align: center; background-color: #f9f9f9;">
                <div class="step-number" style="width: 36px; height: 36px; border-radius: 50%; background-color: #5bc0de; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; margin: 0 auto 10px auto;">1</div>
                <div style="font-weight: bold; font-size: 14px; color: #333; text-transform: uppercase;">Stampa il Voucher</div>
                <div style="font-size: 12px; color: #555; margin-top: 6px; line-height: 1.4;">Stampa la copia cartacea o salvala sul cellulare.</div>
            </div>
            <div class="step-card" style="flex: 1; min-width: 140px; border: 2px dashed #5bc0de; border-radius: 10px; padding: 15px; text-align: center; background-color: #f9f9f9;">
                <div class="step-number" style="width: 36px; height: 36px; border-radius: 50%; background-color: #5bc0de; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; margin: 0 auto 10px auto;">2</div>
                <div style="font-weight: bold; font-size: 14px; color: #333; text-transform: uppercase;">Porta il Voucher</div>
                <div style="font-size: 12px; color: #555; margin-top: 6px; line-height: 1.4;">Portalo con te all'ingresso dell'evento.</div>
            </div>
            <div class="step-card" style="flex: 1; min-width: 140px; border: 2px dashed #5bc0de; border-radius: 10px; padding: 15px; text-align: center; background-color: #f9f9f9;">
                <div class="step-number" style="width: 36px; height: 36px; border-radius: 50%; background-color: #5bc0de; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; margin: 0 auto 10px auto;">3</div>
                <div style="font-weight: bold; font-size: 14px; color: #333; text-transform: uppercase;">Consegna all'Ingresso</div>
                <div style="font-size: 12px; color: #555; margin-top: 6px; line-height: 1.4;">Mostralo all'addetto per consentire il check-in.</div>
            </div>
        </div>
        
        <h4>Note importanti</h4>
        <ul>
            <li>Il voucher è nominativo. Da presentare all'ingresso insieme ad un documento di Identità.</li>
            <li>Il presente voucher verrà sostituito con i biglietti/documento equivalente presso l'ingresso.</li>
        </ul>

        <p>
            Il voucher è valido per {{ $orderLines->sum('quantitaIngressoOrdine') }} {{ $orderLines->sum('quantitaIngressoOrdine') == 1 ? 'persona' : 'persone' }}.<br>
            <strong>Numero Ordine:</strong> {{ $order->numeroOrdine }}
        </p>

        <hr>

        <div style="font-size: 11px; color: #555; text-align: center; margin-top: 20px; background-color: #f9f9f9; padding: 15px; border-radius: 5px;">
            {!! nl2br(e(\App\Models\Setting::getVal('voucher_footer_text') ?: "Servizio booking online: Romacomunica srls\nRomacomunica non si occupa dell'organizzazione dell'evento, tutte le informazioni riportate sono state ricevute dal legittimo proprietario, responsabile nei confronti del cliente.\nVia dei Carraresi 12 - 00164 - Roma (RM) - Tel.: 06.45495920 - Cell.: 3394642099\nWebsite: capodannoromacentro.it - E-mail: info@capodannoromacentro.it - P.Iva: 15995221007")) !!}
        </div>

    </div>

    <div class="print-button no-print">
        <button onclick="window.print()" style="background-color: #5bc0de; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            🖨️ Stampa Voucher
        </button>
        <br><br>
        <a href="{{ route('home') }}" style="color: #337ab7; text-decoration: none;">← Torna alla Home</a>
    </div>

</body>
</html>
