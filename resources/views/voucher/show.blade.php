<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Voucher #{{ $numeroVoucher }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 12px;
            color: #000;
            font-size: 13px;
            line-height: 1.35;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .container {
            max-width: 780px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 16px 20px;
        }
        h1, h2, h3, h4 {
            color: #000;
            margin: 6px 0;
        }
        h2 {
            font-size: 20px;
        }
        h3 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        p {
            margin: 5px 0;
        }
        hr {
            border: 0;
            border-top: 1px solid #ccc;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }
        table th {
            background-color: #5bc0de;
            color: white;
            padding: 7px 10px;
            text-align: left;
            border: 1px solid #000;
            font-size: 13px;
        }
        table td {
            padding: 7px 10px;
            border: 1px solid #000;
            font-size: 13px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .voucher-header {
            margin-bottom: 10px;
        }
        .voucher-number {
            font-size: 20px;
            font-weight: bold;
            color: #d9534f;
            margin: 4px 0;
        }
        .info-grid {
            display: flex;
            gap: 20px;
            align-items: stretch;
            margin: 8px 0;
        }
        .info-box {
            flex: 1;
            background-color: #fafafa;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 14px;
        }
        .print-button {
            text-align: center;
            margin: 16px 0;
        }
        @media print {
            body {
                margin: 0;
                font-size: 12px;
            }
            .container {
                max-width: 100%;
                width: 100%;
                padding: 14px 18px;
                page-break-inside: avoid;
            }
            .print-button, .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="voucher-header" style="position: relative;">
            <table style="width: 100%; border: none; margin: 0;">
                <tr>
                    <td style="border: none; text-align: left; vertical-align: top; padding: 0;">
                        <img src="{{ asset('img/logo-rc-scuro.png') }}" alt="Roma Comunica" style="max-height: 42px; margin-bottom: 6px;">
                        <h2 style="margin: 0;">VOUCHER DI PRENOTAZIONE</h2>
                        <p class="voucher-number">N° {{ $voucher->numeroVoucher }}</p>
                        
                        <div style="margin-top: 8px;">
                            @if($location->image_url)
                                <img src="{{ $location->image_url }}" alt="{{ $location->nomeLocation }}" style="max-height: 85px; object-fit: contain;">
                            @elseif($partner && $partner->logo_url)
                                <img src="{{ $partner->logo_url }}" alt="{{ $partner->nomePartner }}" style="max-height: 65px;">
                            @elseif($partner && file_exists(public_path("partner/loghi/{$partner->idPartner}.jpg")))
                                <img src="{{ asset("partner/loghi/{$partner->idPartner}.jpg") }}" alt="{{ $partner->nomePartner }}" style="max-height: 65px;">
                            @else
                                <h1 style="margin: 0; font-size: 20px;">BOOKING ONLINE</h1>
                            @endif
                        </div>
                    </td>
                    <td style="border: none; text-align: right; vertical-align: top; padding: 0;">
                        @if(isset($qrCodeBase64))
                            <img src="data:image/svg+xml;base64,{{ $qrCodeBase64 }}" alt="QR Code" style="max-width: 130px; border: 1px solid #ccc; padding: 4px; border-radius: 5px;">
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <hr>

        {{-- Dettagli Evento ed Intestatario affiancati nella stessa riga --}}
        <div class="info-grid">
            <div class="info-box">
                <h3 style="margin-top: 0;">DETTAGLI EVENTO</h3>
                <p style="margin-bottom: 0;">
                    <strong>Location:</strong> {{ $location->nomeLocation }}<br>
                    <strong>Indirizzo:</strong> {{ $location->indirizzoLocation }}<br>
                    <strong>Data Voucher:</strong> {{ \Carbon\Carbon::parse($voucher->dataVoucher)->format('d/m/Y H:i') }}
                </p>
            </div>
            <div class="info-box">
                <h3 style="margin-top: 0;">INTESTATARIO</h3>
                <p style="margin-bottom: 0;">
                    <strong>Nome:</strong> {{ trim(($voucher->nomeVoucher ?? '') . ' ' . ($voucher->cognomeVoucher ?? '')) ?: trim(($order->nomeClienteOrdine ?? '') . ' ' . ($order->cognomeClienteOrdine ?? '')) }}<br>
                    <strong>Numero Ordine:</strong> #{{ $order->numeroOrdine }}<br>
                    <strong>Validità:</strong> {{ $orderLines->sum('quantitaIngressoOrdine') }} {{ $orderLines->sum('quantitaIngressoOrdine') == 1 ? 'persona' : 'persone' }}
                </p>
            </div>
        </div>

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
                    if (!$tipologia && isset($location) && $location) {
                        $tipologia = \App\Models\TipologiaIngresso::where('idLocation', $location->idLocation)
                            ->where('nomeIngresso', $line->nomeIngressoOrdine)
                            ->first();
                    }
                    $isSenzaData = (bool)($line->ingressoSenzaDataOrdine || ($tipologia && $tipologia->ingressoSenzaData));
                    $etichetta = $line->etichettaIngressoOrdine ?: ($tipologia?->etichettaIngresso ?: '');
                    $dataVisualizzata = $line->data_visualizzata ?: ($tipologia?->dataIngresso ? \Carbon\Carbon::parse($tipologia->dataIngresso)->format('d/m/Y') : null);
                    $orarioVisualizzato = $line->orario_visualizzato;
                    if ((empty($orarioVisualizzato) || $orarioVisualizzato === '00:00') && $tipologia) {
                        $rawTipOrario = $tipologia->getAttributes()['orarioIngresso'] ?? $tipologia->orarioIngresso;
                        if (!empty($rawTipOrario) && preg_match('/(\d{1,2})[:.](\d{2})/', (string)$rawTipOrario, $m)) {
                            $parsedTime = sprintf('%02d:%02d', $m[1], $m[2]);
                            if ($parsedTime !== '00:00') {
                                $orarioVisualizzato = $parsedTime;
                            }
                        }
                    }
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

        <h3 style="color: #d9534f; margin-top: 10px;">ISTRUZIONI E CONDIZIONI</h3>
        <p>
            <strong>A titolo di conferma della prenotazione è stato versato il saldo tramite {{ $order->tipoPagamento->descrizioneTipoPagamento ?? $order->tipoPagamento->nomePagamento ?? 'Pagamento' }}.</strong>
        </p>

        <div class="steps-container" style="display: flex; gap: 12px; margin: 12px 0;">
            <div class="step-card" style="flex: 1; border: 2px dashed #5bc0de; border-radius: 8px; padding: 10px; text-align: center; background-color: #f9f9f9;">
                <div class="step-number" style="width: 28px; height: 28px; border-radius: 50%; background-color: #5bc0de; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; margin: 0 auto 6px auto;">1</div>
                <div style="font-weight: bold; font-size: 12px; color: #333; text-transform: uppercase;">Stampa il Voucher</div>
                <div style="font-size: 11px; color: #555; margin-top: 4px; line-height: 1.3;">Stampa la copia cartacea o salvala sul cellulare.</div>
            </div>
            <div class="step-card" style="flex: 1; border: 2px dashed #5bc0de; border-radius: 8px; padding: 10px; text-align: center; background-color: #f9f9f9;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background-color: #5bc0de; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; margin: 0 auto 6px auto;">2</div>
                <div style="font-weight: bold; font-size: 12px; color: #333; text-transform: uppercase;">Porta il Voucher</div>
                <div style="font-size: 11px; color: #555; margin-top: 4px; line-height: 1.3;">Portalo con te all'ingresso dell'evento.</div>
            </div>
            <div class="step-card" style="flex: 1; border: 2px dashed #5bc0de; border-radius: 8px; padding: 10px; text-align: center; background-color: #f9f9f9;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background-color: #5bc0de; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; margin: 0 auto 6px auto;">3</div>
                <div style="font-weight: bold; font-size: 12px; color: #333; text-transform: uppercase;">Consegna all'Ingresso</div>
                <div style="font-size: 11px; color: #555; margin-top: 4px; line-height: 1.3;">Mostralo all'addetto per consentire il check-in.</div>
            </div>
        </div>
        
        <h4 style="margin-bottom: 4px;">Note importanti</h4>
        <ul style="margin: 4px 0 8px 0; padding-left: 20px;">
            <li>Il voucher è nominativo. Da presentare all'ingresso insieme ad un documento di Identità.</li>
            <li>Il presente voucher verrà sostituito con i biglietti/documento equivalente presso l'ingresso.</li>
        </ul>

        <hr>

        <div style="font-size: 10.5px; color: #555; text-align: center; margin-top: 8px; background-color: #f9f9f9; padding: 10px; border-radius: 5px;">
            {!! nl2br(e(\App\Models\Setting::getVal('voucher_footer_text') ?: "Servizio booking online: Romacomunica srls\nRomacomunica non si occupa dell'organizzazione dell'evento, tutte le informazioni riportate sono state ricevute dal legittimo proprietario, responsabile nei confronti del cliente.\nVia dei Carraresi 12 - 00164 - Roma (RM) - Tel.: 06.45495920 - Cell.: 3394642099\nWebsite: capodannoromacentro.it - E-mail: info@capodannoromacentro.it - P.Iva: 15995221007")) !!}
        </div>

    </div>

    <div class="print-button no-print">
        <button onclick="window.print()" style="background-color: #5bc0de; color: white; padding: 12px 26px; border: none; border-radius: 5px; cursor: pointer; font-size: 15px;">
            🖨️ Stampa Voucher
        </button>
        <br><br>
        <a href="{{ route('home') }}" style="color: #337ab7; text-decoration: none;">← Torna alla Home</a>
    </div>

</body>
</html>
