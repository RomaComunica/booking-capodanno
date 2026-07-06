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
            Il tuo ordine <strong>#{{ $order->numeroOrdine }}</strong> è stato completato!<br><br>
            
            @if(!empty($additionalMessage))
                <div style="background-color: #f8f9fa; border-left: 4px solid #5bc0de; padding: 15px; margin: 20px 0;">
                    <em>{{ $additionalMessage }}</em>
                </div>
            @endif

            @if($order->statoOrdine != 0)
                Per stampare il tuo voucher clicca sul link seguente:<br><br>
                <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('voucher.show', ['numeroOrdine' => $order->numeroOrdine, 'numeroVoucher' => $voucher->numeroVoucher, 'idPartner' => $partner->idPartner ?? 1]) }}" style="color: #337ab7; text-decoration: none;">
                    <strong>Stampa il Voucher</strong>
                </a>
            @else
                <strong>Attenzione:</strong> Il tuo ordine è temporaneamente in attesa di pagamento.<br>
                Non appena il pagamento sarà verificato dal nostro staff, riceverai il tuo Voucher ufficiale.
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
            <tr>
                <td>
                    @if($line->ingressoSenzaDataOrdine)
                        {{ $line->etichettaIngressoOrdine }} - {{ $line->orarioIngressoOrdine ? \Carbon\Carbon::parse($line->orarioIngressoOrdine)->format('H:i') : '' }}
                    @else
                        {{ $line->dataIngressoOrdine ? \Carbon\Carbon::parse($line->dataIngressoOrdine)->format('d/m/Y') : '' }} - {{ $line->orarioIngressoOrdine ? \Carbon\Carbon::parse($line->orarioIngressoOrdine)->format('H:i') : '' }}
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
            {{ $order->tipoPagamento->descrizioneTipoPagamento ?? 'N/D' }}<br>
            @if($order->statoOrdine == 0)
                <span style="color: #f0ad4e; font-weight: bold;">In attesa di accredito.</span><br><br>
                @if($order->tipoPagamentoOrdine == 2)
                    <strong>Coordinate Bancarie per il Bonifico:</strong><br>
                    Intestatario: {{ \App\Models\Setting::getVal('intestatario_bonifico', 'Da Configurare nel Pannello Amministratore') }}<br>
                    IBAN: {{ \App\Models\Setting::getVal('iban_bonifico', 'Da Configurare nel Pannello Amministratore') }}<br>
                    Causale: Ordine #{{ $order->numeroOrdine }} - {{ $order->nomeClienteOrdine }} {{ $order->cognomeClienteOrdine }}<br>
                    <br>
                    <em>Per velocizzare l'emissione del voucher, puoi inviare copia della contabile rispondendo a questa email.</em>
                @endif
            @else
                <span class="success-text">Pagamento confermato.</span>
            @endif
        </p>

        <hr>

        <h5><strong>CONSEGNA</strong></h5>
        <p>
            {{ $order->tipoSpedizione->descrizioneSpedizione ?? 'N/D' }}<br>
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
                Per prenotare un appuntamento chiama il numero 06.45495920 - 327.3103225
            @endif
        </p>

        <hr>

        <p>Grazie per il tuo acquisto.</p>

    </div>
</body>
</html>
