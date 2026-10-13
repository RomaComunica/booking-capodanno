@extends('layouts.app')

@section('meta_title', $location->nomeLocation . ' - Capodanno ' . date('Y'))
@section('meta_description', Str::limit(strip_tags($location->descrizioneLocation), 160))
@section('og_image', asset('loghi/' . $location->idLocation . '.jpg'))

@section('content')
<!-- Schema.org Event Markup -->
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Event",
  "name": "{{ $location->nomeLocation }}",
  "description": "{!! addslashes(strip_tags($location->descrizioneLocation)) !!}",
  "startDate": "{{ date('Y') }}-12-31T20:00:00+01:00",
  "endDate": "{{ date('Y')+1 }}-01-01T06:00:00+01:00",
  "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
  "eventStatus": "https://schema.org/EventScheduled",
  "location": {
    "@@type": "Place",
    "name": "{{ $location->nomeLocation }}",
    "address": {
      "@@type": "PostalAddress",
      "addressLocality": "{{ $location->cittaLocation ?? '' }}",
      "addressRegion": "{{ $location->provinciaLocation ?? '' }}",
      "addressCountry": "IT"
    }
  },
  "image": [
    "{{ asset('loghi/' . $location->idLocation . '.jpg') }}"
  ],
  "offers": {
    "@@type": "AggregateOffer",
    "priceCurrency": "EUR",
    "lowPrice": "{{ $location->ingressi->min('prezzoIngresso') ?? 0 }}",
    "highPrice": "{{ $location->ingressi->max('prezzoIngresso') ?? 0 }}",
    "offerCount": "{{ $location->ingressi->count() ?? 1 }}",
    "availability": "https://schema.org/InStock",
    "validFrom": "{{ date('Y-m-d\TH:i:sP') }}"
  }
}
</script>
<div class="container main-content">
    
    @if(!$iframe)
    {{-- Header with Partner Logo --}}
    <div class="page-header flex-between mb-2">
        <div class="header-titles">
            @if($partner && file_exists(public_path("partner/loghi/{$partnerId}.jpg")))
                <img src="{{ asset("partner/loghi/{$partnerId}.jpg") }}" alt="{{ $partner->nomePartner ?? 'Partner' }}" class="partner-logo">
            @else
                <img src="{{ asset('partner/loghi/default.jpg') }}" alt="Booking" class="partner-logo default-logo" onerror="this.style.display='none'">
            @endif
            <h1 class="page-title">ACQUISTA ONLINE</h1>
            <p class="page-subtitle">Servizio di Booking online in 4 passi.</p>
        </div>
        <div class="header-payments desktop-only">
            <img src="{{ asset('img/carte_di_credito.png') }}" alt="Metodi di pagamento" class="payment-icons" onerror="this.style.display='none'">
        </div>
    </div>
    @else
    {{-- Simplified header for iframe mode --}}
    <div class="page-header mb-2">
        <h1 class="page-title">ACQUISTA ONLINE</h1>
        <p class="page-subtitle">Servizio di Booking online in 4 passi.</p>
    </div>
    @endif

    <hr class="divider">

    {{-- Step Indicator --}}
    <div class="step-indicator mb-3">
        <img src="{{ asset('img/step_2.jpg') }}" alt="Step 2" class="step-img desktop-only" onerror="this.style.display='none'">
        <img src="{{ asset('img/step_2m.jpg') }}" alt="Step 2" class="step-img mobile-only" onerror="this.style.display='none'">
    </div>

    {{-- Main Panel --}}
    <div class="glass-panel detail-panel animate-fade-in">
        <div class="detail-pad">
            
            @if($ingressi->count() > 0)
            
            <form id="formTickets" method="POST" action="{{ route('cart.add') }}">
                @csrf
                
                <h2 class="panel-heading">
                    Scegli Quantità - {{ $location->nomeLocation }}
                </h2>

                {{-- Tickets Table --}}
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th class="text-left">Data e ora</th>
                                <th class="text-left">Biglietto</th>
                                <th class="text-center">Prezzo</th>
                                <th class="text-center">Qtà</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ingressi as $ingresso)
                            <tr class="{{ $ingresso->ingressoAttivo ? 'row-active' : 'row-inactive' }}">
                                {{-- Data e Ora --}}
                                <td>
                                    @if($ingresso->ingressoSenzaData)
                                        {{ $ingresso->etichettaIngresso }} - {{ $ingresso->orarioIngresso ? \Carbon\Carbon::parse($ingresso->orarioIngresso)->format('H:i') : '' }}
                                    @else
                                        {{ $ingresso->dataIngresso ? $ingresso->dataIngresso->format('d/m/y') : '' }} - {{ $ingresso->orarioIngresso ? \Carbon\Carbon::parse($ingresso->orarioIngresso)->format('H:i') : '' }}
                                    @endif
                                </td>

                                {{-- Nome Biglietto --}}
                                <td>
                                    <div class="ticket-name">{{ $ingresso->nomeIngresso }}</div>
                                </td>

                                {{-- Prezzo --}}
                                <td class="text-center font-bold text-dark">
                                    {{ number_format($ingresso->prezzoIngresso, 2, ',', '.') }} €
                                </td>

                                {{-- Quantità --}}
                                <td class="text-center">
                                    {{-- Hidden fields for this ticket --}}
                                    <input type="hidden" name="dataIngresso_{{ $ingresso->idIngressoCont }}" value="{{ $ingresso->dataIngresso }}">
                                    <input type="hidden" name="orarioIngresso_{{ $ingresso->idIngressoCont }}" value="{{ $ingresso->orarioIngresso }}">
                                    <input type="hidden" name="prezzoIngresso_{{ $ingresso->idIngressoCont }}" id="prezzoIngresso_{{ $ingresso->idIngressoCont }}" value="{{ $ingresso->prezzoIngresso }}">
                                    <input type="hidden" name="idIngresso_{{ $ingresso->idIngressoCont }}" value="{{ $ingresso->idIngressoCont }}">
                                    <input type="hidden" name="ingressoSenzaData_{{ $ingresso->idIngressoCont }}" value="{{ $ingresso->ingressoSenzaData ? '1' : '0' }}">
                                    <input type="hidden" name="etichettaIngresso_{{ $ingresso->idIngressoCont }}" value="{{ $ingresso->etichettaIngresso }}">

                                    @if($ingresso->giacenzaBiglietti > 0)
                                        <input 
                                            type="number" 
                                            name="quantitaIngresso_{{ $ingresso->idIngressoCont }}" 
                                            id="quantitaIngresso_{{ $ingresso->idIngressoCont }}"
                                            class="form-input qty-input mx-auto"
                                            placeholder="0"
                                            min="0"
                                            max="{{ $ingresso->giacenzaBiglietti }}"
                                            data-id="{{ $ingresso->idIngressoCont }}"
                                            data-prezzo="{{ $ingresso->prezzoIngresso }}"
                                            data-max="{{ $ingresso->giacenzaBiglietti }}"
                                        >
                                    @else
                                        <input type="hidden" name="quantitaIngresso_{{ $ingresso->idIngressoCont }}">
                                        <span class="badge badge-danger">Esaurito</span>
                                    @endif
                                </td>
                            </tr>

                            {{-- Refund Option Row --}}
                            @if($ingresso->mostraRimborso)
                            <tr class="row-refund">
                                <td colspan="4">
                                    <div class="refund-wrapper">
                                        <label class="custom-checkbox">
                                            <input 
                                                type="checkbox" 
                                                name="rimborso_{{ $ingresso->idIngressoCont }}" 
                                                id="rimborso_{{ $ingresso->idIngressoCont }}"
                                                value="1"
                                                data-prezzo="{{ $ingresso->rimborsoValorePersona }}"
                                                data-id="{{ $ingresso->idIngressoCont }}"
                                                class="rimborso-checkbox"
                                            >
                                            <span class="checkmark"></span>
                                        </label>
                                        <label for="rimborso_{{ $ingresso->idIngressoCont }}" class="refund-label">
                                            <strong>Opzione rimborso:</strong> costo {{ number_format($ingresso->rimborsoValorePersona, 2, ',', '.') }} € per persona.
                                            Valida fino alle {{ $ingresso->rimborsoDataScadenza->format('H:i') }} del {{ $ingresso->rimborsoDataScadenza->format('d/m/Y') }}.
                                            @if($ingresso->rimborsoTesto)
                                                <br><span class="text-sm mt-1">{{ $ingresso->rimborsoTesto }}</span>
                                            @endif
                                        </label>
                                        <input type="hidden" name="rimborsoData_{{ $ingresso->idIngressoCont }}" value="{{ $ingresso->rimborsoDataScadenza }}">
                                        <input type="hidden" name="rimborsoValore_{{ $ingresso->idIngressoCont }}" value="{{ $ingresso->rimborsoValorePersona }}">
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Total --}}
                <div class="total-bar mt-4">
                    <h4 id="totale" class="total-price"></h4>
                </div>

                {{-- Hidden fields --}}
                <input type="hidden" name="totaleAcquisto" id="totaleAcquisto">
                <input type="hidden" name="idLocation" value="{{ $location->idLocation }}">
                <input type="hidden" name="idPartner" value="{{ $partnerId }}">
                @if($iframe)
                    <input type="hidden" name="iframe" value="1">
                @endif

                {{-- Navigation Buttons --}}
                <div class="nav-actions mt-4">
                    <a href="{{ route('home', ['idPartner' => $partnerId, 'iframe' => $iframe ? '1' : null]) }}" 
                       class="btn btn-outline">
                        Indietro
                    </a>
                    
                    <button 
                        type="submit" 
                        class="btn btn-primary"
                        onclick="return validateForm();">
                        Prosegui
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>

            </form>

            @else
            
            {{-- No tickets available --}}
            <div class="empty-state text-center">
                <svg width="64" height="64" fill="none" class="mx-auto" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--text-secondary); margin-bottom:1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="empty-text mb-3">Disponibilità esaurita per questa location</p>
                
                <a href="{{ route('home', ['idPartner' => $partnerId, 'iframe' => $iframe ? '1' : null]) }}" 
                   class="btn btn-outline">
                    Torna alla Home
                </a>
            </div>

            @endif

        </div>
    </div>

    {{-- Mobile Payment Methods --}}
    @if(!$iframe)
    <div class="mobile-only mt-4 text-center pb-2">
        <img src="{{ asset('img/carte_di_credito.png') }}" alt="Metodi di pagamento" class="payment-icons mx-auto" onerror="this.style.display='none'">
    </div>
    @endif

</div>

@push('scripts')
<script>
// Calculate total price in real-time
function calcolaTotale() {
    let totale = 0;
    
    // Loop through all quantity inputs
    document.querySelectorAll('.qty-input').forEach(function(input) {
        const qty = parseInt(input.value) || 0;
        const prezzoRfc = (input.dataset.prezzo || '0').toString().replace(',', '.');
        const prezzo = parseFloat(prezzoRfc) || 0;
        const idIngresso = input.dataset.id;
        
        if (qty > 0) {
            // Add ticket price
            totale += qty * prezzo;
            
            // Add refund if checked
            const rimborsoCheckbox = document.getElementById('rimborso_' + idIngresso);
            if (rimborsoCheckbox && rimborsoCheckbox.checked) {
                const rimborsoRfc = (rimborsoCheckbox.dataset.prezzo || '0').toString().replace(',', '.');
                const prezzoRimborso = parseFloat(rimborsoRfc) || 0;
                totale += qty * prezzoRimborso;
            }
        }
    });
    
    // Update display
    document.getElementById('totale').textContent = 'Totale Euro ' + totale.toFixed(2);
    document.getElementById('totaleAcquisto').value = totale.toFixed(2);
}

// Validate quantity doesn't exceed availability
function validateQuantity(input) {
    const qty = parseInt(input.value) || 0;
    const max = parseInt(input.dataset.max);
    
    if (qty > max) {
        alert('Attenzione quantità non disponibile');
        input.value = '';
        calcolaTotale();
    }
}

// Validate form before submit
function validateForm() {
    const totale = parseFloat(document.getElementById('totaleAcquisto').value) || 0;
    
    if (totale === 0) {
        alert('Attenzione: seleziona una quantità per procedere.');
        return false;
    }
    
    return true;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Quantity inputs
    document.querySelectorAll('.qty-input').forEach(function(input) {
        input.addEventListener('input', function() {
            validateQuantity(this);
            calcolaTotale();
        });
        input.addEventListener('change', calcolaTotale);
        input.addEventListener('keyup', calcolaTotale);
    });
    
    // Refund checkboxes
    document.querySelectorAll('.rimborso-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', calcolaTotale);
    });
    
    // Initial calculation
    calcolaTotale();
});
</script>
@endpush
@endsection
