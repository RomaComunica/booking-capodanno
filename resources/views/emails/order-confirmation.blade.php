<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="IT" lang="IT">
<head>
    <meta http-equiv="content-encoding" content="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="content-language" content="IT" />
    <title>Acquisto biglietti</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            font-size: 14px;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        h2, h5 {
            color: #000000;
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
            border: 1px solid #ddd;
        }
        table td {
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #FFFFFF;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }
        .success-text {
            color: green;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="{{ asset('img/logo-rc-scuro.png') }}" alt="Capodanno Roma" style="max-height: 60px;">
        </div>

        @if($partner && file_exists(public_path("partner/loghi/{$partner->idPartner}.jpg")))
            <img src="{{ asset("partner/loghi/{$partner->idPartner}.jpg") }}" alt="{{ $partner->nomePartner }}" style="max-height: 80px; margin-bottom: 20px;">
        @else
            <h2>Booking online</h2>
        @endif

        <p>
            <strong>Grazie, {{ $order->nomeClienteOrdine }} {{ $order->cognomeClienteOrdine }}</strong><br><br>
            @if((int) $order->statoOrdine === 2)
                Il tuo ordine <strong>#{{ $order->numeroOrdine }}</strong> è stato completato!<br><br>
            @else
                Il tuo ordine <strong>#{{ $order->numeroOrdine }}</strong> è stato registrato ed è in attesa di pagamento.<br><br>
            @endif
            
            @if(!empty($additionalMessage))
                <div style="background-color: #f8f9fa; border-left: 4px solid #5bc0de; padding: 15px; margin: 20px 0;">
                    <em>{{ $additionalMessage }}</em>
                </div>
            @endif

            @if((int) $order->statoOrdine === 2)
                Per stampare il tuo voucher clicca sul link seguente:<br><br>
                <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('voucher.show', ['numeroOrdine' => $order->numeroOrdine, 'numeroVoucher' => $voucher->numeroVoucher, 'idPartner' => $partner->idPartner ?? 1]) }}" style="color: #337ab7; text-decoration: none;">
                    <strong>Stampa il Voucher</strong>
                </a>
            @else
                <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0;">
                    <strong>Attenzione:</strong> Il tuo ordine è temporaneamente in attesa di pagamento.<br>
                    <strong style="color: #d9534f; font-size: 15px;">Ricorda che il tuo ordine sarà effettivo solo dopo il relativo pagamento.</strong><br>
                    Non appena il pagamento sarà verificato dal nostro staff, potrai accedere al tuo Voucher ufficiale.
                </div>
            @endif
        </p>

        <hr>

        <h5><strong>RIEPILOGO ORDINE</strong></h5>
        <p>
            Hai scelto: <strong>{{ $location->nomeLocation }} - {{ $location->indirizzoLocation }}</strong><br>
            Data ordine: <strong>{{ \Carbon\Carbon::parse($order->dataOrdine)->format('d/m/Y') }}</strong><br>
            Totale: <strong>{{ number_format($order->totaleOrdine, 2, ',', '.') }} Euro</strong>
        </p>

        <table>
            <tr style="background-color: #5bc0de; color: white;">
                <th>Data e ora</th>
                <th>Biglietto</th>
                <th class="text-center">Prezzo</th>
                <th class="text-center">Quantità</th>
            </tr>
            @foreach($orderLines as $line)
            @php 
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
                        {{ $etichetta ?: 'Data aperta' }}
                        @if($orarioVisualizzato)
                            - {{ $orarioVisualizzato }}
                        @endif
                    @else
                        {{ $dataVisualizzata ?: '-' }}
                        @if($orarioVisualizzato)
                            - {{ $orarioVisualizzato }}
                        @endif
                    @endif
                </td>
                <td>{{ $line->nomeIngressoOrdine }}</td>
                <td class="text-center">{{ number_format($line->prezzoIngressoOrdine, 2, ',', '.') }} €</td>
                <td class="text-center">{{ $line->quantitaIngressoOrdine }}</td>
            </tr>
            @endforeach
        </table>

        <hr>

        <h5><strong>I TUOI DATI</strong></h5>
        <p>
            {{ $order->nomeClienteOrdine }}<br>
            {{ $order->cognomeClienteOrdine }}<br>
        </p>

        <hr>

        <h5><strong>PAGAMENTO</strong></h5>
        <p>
            {{ $order->tipoPagamento->descrizioneTipoPagamento ?? $order->tipoPagamento->nomePagamento ?? 'N/D' }}<br>
            @if($order->statoOrdine == 0)
                <span style="color: #f0ad4e; font-weight: bold;">In attesa di accredito.</span><br><br>
                @if($order->tipoPagamentoOrdine == 2)
                    <strong>Coordinate Bancarie per il Bonifico:</strong><br>
                    Intestatario: {{ \App\Models\Setting::getVal('intestatario_bonifico', '') }}<br>
                    IBAN: {{ \App\Models\Setting::getVal('iban_bonifico', '') }}<br>
                    Causale: Ordine #{{ $order->numeroOrdine }} - {{ $order->nomeClienteOrdine }} {{ $order->cognomeClienteOrdine }}<br>
                    <br>
                    <em>Per velocizzare l'emissione del voucher, puoi inviare copia della contabile rispondendo a questa email.</em>
                @elseif($order->tipoPagamentoOrdine == 4)
                    <strong>Dati per la Ricarica PostePay:</strong><br>
                    Intestatario Carta: {{ \App\Models\Setting::getVal('intestatario_postepay', '') }}<br>
                    Numero Carta: {{ \App\Models\Setting::getVal('numero_carta_postepay', '') }}<br>
                    Codice Fiscale: {{ \App\Models\Setting::getVal('codice_fiscale_postepay', '') }}<br>
                    Causale / Riferimento: Ordine #{{ $order->numeroOrdine }} - {{ $order->nomeClienteOrdine }} {{ $order->cognomeClienteOrdine }}<br>
                    <br>
                    <em>Per velocizzare l'emissione del voucher, puoi inviare copia della ricevuta di ricarica rispondendo a questa email.</em>
                @elseif($order->tipoPagamentoOrdine == 3)
                    <strong>Pagamento in Contanti al Ritiro:</strong><br>
                    Il pagamento verrà corrisposto in contanti al momento del ritiro dei biglietti presso la nostra sede.<br>
                    <strong>Indirizzo sede:</strong> Via dei CARRARESI 12 - 00164 Roma (orario 9:30-17:30)<br>
                    <br>
                    <em>Verrai contattato dal nostro staff per concordare data e ora del ritiro, oppure puoi chiamare il numero 06.45495920 - 327.3103225.</em>
                @endif
            @else
                <span class="success-text">Pagamento confermato.</span>
            @endif
        </p>

        <hr>

        <h5><strong>CONSEGNA</strong></h5>
        <p>
            {{ $order->tipoSpedizione->descrizioneSpedizione ?? $order->tipoSpedizione->nomeSpedizione ?? 'N/D' }}<br>
            @if($order->tipoSpedizioneOrdine == 1)
                @if($order->statoOrdine == 0)
                    Il voucher sarà inviato alla verifica dell'accredito.
                @else
                    Il voucher è accessibile dal link in alto.
                @endif
            @elseif($order->tipoSpedizioneOrdine == 2)
                Verrai contattato dal nostro Staff per fissare data e ora di ritiro.
            @elseif($order->tipoSpedizioneOrdine == 3)
                Verrai contattato dal nostro Staff per organizzare la consegna.
            @elseif($order->tipoSpedizioneOrdine == 4)
                Ritiro presso la nostra sede in Via dei CARRARESI 12 - 00164 Roma (9:30-17:30). Per fissare l'appuntamento chiama il numero 06.45495920 - 327.3103225.
            @endif
        </p>

        <hr>

        <p>Grazie per il tuo acquisto.</p>

    </div>
</body>
</html>
