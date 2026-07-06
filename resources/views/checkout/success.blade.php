@extends('layouts.app')

@section('content')
<div class="container main-content">
    
    <div class="max-w-3xl mx-auto">
        
        {{-- Success Message --}}
        <div class="alert alert-success text-center mb-4" style="flex-direction: column; padding: 2.5rem; justify-content: center;">
            <svg width="64" height="64" fill="none" class="mx-auto" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--success); margin-bottom:1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h1 class="page-title" style="font-size: 2rem;">Dati Ricevuti!</h1>
            <p class="text-secondary mt-2">I tuoi dati sono stati salvati correttamente.</p>
            <p class="text-secondary text-sm mt-1">Ordine temporaneo: <strong>{{ $ordineId }}</strong></p>
        </div>

        {{-- Info Box --}}
        <div class="alert alert-info mb-4" style="flex-direction: column; align-items: flex-start;">
            <h2 class="section-heading mb-2">ℹ️ Nota Implementazione</h2>
            <p class="mb-2">Questa è una pagina temporanea per <strong>Step 3</strong>.</p>
            <p class="mb-2">In <strong>Phase 4 - Payment Integration</strong> verrà implementato:</p>
            <ul style="margin-left: 1.5rem; list-style-type: disc;" class="text-secondary">
                <li>Selezione metodo spedizione</li>
                <li>Selezione metodo pagamento</li>
                <li>Integrazione PayPal NVP</li>
                <li>Integrazione Nexi XPay</li>
                <li>Creazione ordine nel database</li>
                <li>Generazione e invio voucher PDF</li>
            </ul>
        </div>

        {{-- Order Summary --}}
        <div class="glass-panel summary-box mb-4">
            <h2 class="section-heading mb-4 border-bottom pb-2">Riepilogo Dati Salvati</h2>
            
            {{-- Customer Data --}}
            <div class="mb-4">
                <h3 class="font-bold text-gold mb-2 uppercase tracking-wide text-sm">Dati Cliente</h3>
                <div class="form-grid" style="gap: 1rem;">
                    <div>
                        <span class="text-secondary">Nome:</span>
                        <span class="font-bold ml-2 text-primary">{{ $customer['nome'] ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-secondary">Cognome:</span>
                        <span class="font-bold ml-2 text-primary">{{ $customer['cognome'] ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-secondary">Email:</span>
                        <span class="font-bold ml-2 text-primary">{{ $customer['email'] ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-secondary">Telefono:</span>
                        <span class="font-bold ml-2 text-primary">{{ $customer['telefono'] ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <hr class="divider my-2">

            {{-- Order Items --}}
            <div class="mb-4">
                <h3 class="font-bold text-gold mb-2 uppercase tracking-wide text-sm">Biglietti Selezionati</h3>
                <p class="text-secondary mb-3">Location: <strong class="text-primary">{{ $nomeLocation }}</strong></p>
                
                <div class="items-list">
                    @foreach($cart as $item)
                    <div class="item-card mb-2 p-3 rounded bg-highlight">
                        <div class="flex-between">
                            <div>
                                <h4 class="font-bold">{{ $item['nomeIngresso'] }}</h4>
                                <p class="text-secondary text-sm mt-1">
                                    @if($item['ingressoSenzaData'])
                                        {{ $item['etichettaIngresso'] }} - {{ $item['orarioIngresso'] ? \Carbon\Carbon::parse($item['orarioIngresso'])->format('H:i') : '' }}
                                    @else
                                        {{ $item['dataIngresso'] ? \Carbon\Carbon::parse($item['dataIngresso'])->format('d/m/y') : '' }} - {{ $item['orarioIngresso'] ? \Carbon\Carbon::parse($item['orarioIngresso'])->format('H:i') : '' }}
                                    @endif
                                </p>
                                <p class="text-secondary text-sm mt-1">
                                    Quantità: {{ $item['quantita'] }} × {{ number_format($item['prezzoIngresso'], 2, ',', '.') }} €
                                    @if($item['rimborso'])
                                        <span class="text-gold ml-1">(+ Rimborso {{ number_format($item['rimborsoValore'] * $item['quantita'], 2, ',', '.') }} €)</span>
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gold" style="font-size: 1.1rem;">{{ number_format($item['subtotale'], 2, ',', '.') }} €</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <hr class="divider my-2">

            {{-- Total --}}
            <div class="summary-total mt-4">
                <h3 class="total-label uppercase">Totale</h3>
                <h3 class="total-amount text-gold" style="font-size: 2rem;">{{ number_format($totaleAcquisto, 2, ',', '.') }} €</h3>
            </div>
        </div>

        {{-- Actions --}}
        <div class="text-center mt-4 pb-4">
            <a href="{{ route('home') }}" class="btn btn-primary btn-block" style="max-width: 300px; margin: 0 auto;">
                Torna alla Homepage
            </a>
        </div>

    </div>

</div>
@endsection
