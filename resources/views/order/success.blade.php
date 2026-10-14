@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 800px; margin: 0 auto; padding: 0 1.5rem;">
    
    {{-- Success Icon & Title --}}
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #22C55E, #16A34A); margin-bottom: 1.5rem; box-shadow: 0 0 30px rgba(34, 197, 94, 0.3);">
            <svg width="40" height="40" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">Ordine Completato!</h1>
        <p style="color: var(--text-secondary); font-size: 1.1rem;">Il tuo ordine è stato registrato con successo</p>
    </div>

    {{-- Order Summary Panel --}}
    <div class="glass-panel" style="overflow: hidden; margin-bottom: 2rem;">
        
        {{-- Green Header --}}
        <div style="background: linear-gradient(135deg, #16A34A, #15803D); padding: 1.25rem 2rem;">
            <h2 style="font-size: 1.25rem; color: white; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">Riepilogo Ordine</h2>
        </div>

        <div style="padding: 2rem;">

            {{-- Order & Voucher Numbers --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem;">
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">Numero Ordine</p>
                    <p style="font-size: 1.75rem; font-weight: 800; color: var(--text-primary);">#{{ $numeroOrdine }}</p>
                </div>
                <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem;">
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">Numero Voucher</p>
                    <p style="font-size: 1.75rem; font-weight: 800; color: var(--accent-gold);">#{{ $numeroVoucher }}</p>
                </div>
            </div>

            {{-- Customer Info --}}
            <div style="margin-bottom: 2rem;">
                <h3 style="font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-gold); margin-bottom: 1rem;">I tuoi Dati</h3>
                <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.15rem;">Nome e Cognome</p>
                            <p style="font-weight: 600;">{{ $order->nomeClienteOrdine }} {{ $order->cognomeClienteOrdine }}</p>
                        </div>
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.15rem;">Email</p>
                            <p style="font-weight: 600;">{{ $order->emailClienteOrdine }}</p>
                        </div>
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.15rem;">Telefono</p>
                            <p style="font-weight: 600;">{{ $order->telefonoClienteOrdine }}</p>
                        </div>
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.15rem;">Data Ordine</p>
                            <p style="font-weight: 600;">{{ \Carbon\Carbon::parse($order->dataOrdine)->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Order Lines Table --}}
            <div style="margin-bottom: 2rem;">
                <h3 style="font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-gold); margin-bottom: 1rem;">Biglietti Acquistati</h3>
                    <style>
                        .desktop-only-row {
                            display: table-row;
                        }
                        .mobile-only-row {
                            display: none;
                        }
                        @media (max-width: 768px) {
                            .desktop-only-row {
                                display: none !important;
                            }
                            .mobile-only-row {
                                display: table-row !important;
                            }
                            .col-datetime {
                                display: none !important;
                            }
                            .table-responsive {
                                overflow-x: hidden !important;
                                border-radius: 8px;
                            }
                            .modern-table {
                                width: 100% !important;
                                table-layout: auto !important;
                            }
                        }
                    </style>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th class="col-datetime" style="text-align: left;">Data e Ora</th>
                                <th style="text-align: left;">Biglietto</th>
                                <th style="text-align: center;">Prezzo</th>
                                <th style="text-align: center;">Qtà</th>
                                <th style="text-align: right;">Totale</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orderLines as $line)
                            @php 
                                $tipologia = $line->tipologia;
                                $isSenzaData = (bool)($line->ingressoSenzaDataOrdine || ($tipologia && $tipologia->ingressoSenzaData));
                                $etichetta = $line->etichettaIngressoOrdine ?: ($tipologia?->etichettaIngresso ?: '');
                                $dataVisualizzata = $line->data_visualizzata;
                                $orarioVisualizzato = $line->orario_visualizzato;
                            @endphp
                            <tr class="row-active">
                                <td class="col-datetime">
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
                                <td><span class="ticket-name">{{ $line->nomeIngressoOrdine }}</span></td>
                                <td style="text-align: center;">{{ number_format($line->prezzoIngressoOrdine, 2, ',', '.') }} €</td>
                                <td style="text-align: center; font-weight: 700;">{{ $line->quantitaIngressoOrdine }}</td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a; white-space: nowrap;">{{ number_format($line->totaleRiga, 2, ',', '.') }} €</td>
                            </tr>
                            @endforeach
                            {{-- Total Row Desktop --}}
                            <tr class="row-inactive desktop-only-row">
                                <td colspan="4" style="text-align: right;">
                                    <strong>Totale Ordine</strong>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <strong>{{ number_format($order->totaleOrdine, 2, ',', '.') }} €</strong>
                                </td>
                            </tr>
                            {{-- Total Row Mobile --}}
                            <tr class="row-inactive mobile-only-row">
                                <td colspan="3" style="text-align: right;">
                                    <strong>Totale Ordine</strong>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <strong>{{ number_format($order->totaleOrdine, 2, ',', '.') }} €</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Payment & Shipping Info --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem;">
                    <h4 style="font-size: 0.9rem; color: var(--accent-gold); text-transform: uppercase; margin-bottom: 0.75rem;">Pagamento</h4>
                    <p style="color: var(--text-primary); margin-bottom: 0.5rem;">{{ $order->tipoPagamento->nomePagamento ?? 'N/D' }}</p>
                    @if($order->statoOrdine == 0)
                        <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 600; background: rgba(234, 179, 8, 0.15); color: #EAB308;">⏳ In attesa</span>
                    @else
                        <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 600; background: rgba(34, 197, 94, 0.15); color: #22C55E;">✓ Confermato</span>
                    @endif
                </div>
                <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem;">
                    <h4 style="font-size: 0.9rem; color: var(--accent-gold); text-transform: uppercase; margin-bottom: 0.75rem;">Spedizione</h4>
                    <p style="color: var(--text-primary);">{{ $order->tipoSpedizione->nomeSpedizione ?? 'N/D' }}</p>
                </div>
            </div>

            {{-- Next Steps --}}
            <div style="background: linear-gradient(135deg, rgba(255, 215, 0, 0.08), rgba(255, 215, 0, 0.02)); border: 1px solid rgba(255, 215, 0, 0.2); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                <h3 style="font-size: 1rem; color: var(--accent-gold); margin-bottom: 1rem;">📧 Prossimi Passi</h3>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem; color: var(--text-secondary); font-size: 0.95rem;">
                    <li>✓ Riceverai un'email di riepilogo all'indirizzo: <strong style="color: var(--text-primary);">{{ $order->emailClienteOrdine }}</strong></li>
                    @if($order->statoOrdine == 0)
                        <li>✓ L'email conterrà le istruzioni per effettuare il pagamento</li>
                        <li>✓ Il voucher finale ti verrà inviato in formato PDF non appena avremo verificato l'avvenuto accredito</li>
                    @else
                        <li>✓ L'email conterrà il link per scaricare il tuo voucher in formato PDF</li>
                        <li>✓ Conserva il voucher e presentalo all'ingresso dell'evento</li>
                    @endif
                </ul>
            </div>

            {{-- Action Buttons --}}
            <div class="nav-actions" style="border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <a href="{{ route('home') }}" class="btn btn-outline">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Torna alla Home
                </a>
                <a href="{{ route('home') }}" class="btn btn-primary">
                    Continua ad Acquistare
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
