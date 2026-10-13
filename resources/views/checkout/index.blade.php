@extends('layouts.app')

@section('content')
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
        <img src="{{ asset('img/step_3.jpg') }}" alt="Step 3" class="step-img desktop-only" onerror="this.style.display='none'">
        <img src="{{ asset('img/step_3m.jpg') }}" alt="Step 3" class="step-img mobile-only" onerror="this.style.display='none'">
    </div>

    {{-- Main Panel --}}
    <div class="glass-panel detail-panel animate-fade-in">
        <div class="detail-pad">
            
            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-error mb-3">
                    <div>
                        <h3 class="font-bold mb-1">⚠️ Errori di validazione:</h3>
                        <ul style="margin-left: 1.5rem; list-style-type: disc;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            
            <form id="formCheckout" method="POST" action="{{ route('checkout.process') }}">
                @csrf
                @if($iframe)
                    <input type="hidden" name="iframe" value="1">
                @endif
                
                {{-- Order Summary --}}
                <div class="checkout-section mb-4">
                    <h2 class="section-heading">Riepilogo ordine</h2>
                    <p class="section-desc">Hai scelto: <strong>{{ $nomeLocation }}</strong></p>
                    
                    <div class="table-responsive mt-2">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th class="text-left">Data e ora</th>
                                    <th class="text-left">Biglietto</th>
                                    <th class="text-center">Prezzo</th>
                                    <th class="text-center">Qtà</th>
                                    @if(collect($cart)->some(fn($item) => $item['rimborso'] == 1))
                                    <th class="text-center">Rimborso</th>
                                    @endif
                                    <th class="text-right">Totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cart as $item)
                                <tr class="row-active">
                                    {{-- Data e Ora --}}
                                    <td>
                                        @if($item['ingressoSenzaData'])
                                            {{ $item['etichettaIngresso'] }} - {{ $item['orarioIngresso'] ? \Carbon\Carbon::parse($item['orarioIngresso'])->format('H:i') : '' }}
                                        @else
                                            {{ $item['dataIngresso'] ? \Carbon\Carbon::parse($item['dataIngresso'])->format('d/m/y') : '' }} - {{ $item['orarioIngresso'] ? \Carbon\Carbon::parse($item['orarioIngresso'])->format('H:i') : '' }}
                                        @endif
                                    </td>
                                    
                                    {{-- Nome Biglietto --}}
                                    <td>
                                        <div class="ticket-name">{{ $item['nomeIngresso'] }}</div>
                                    </td>
                                    
                                    {{-- Prezzo --}}
                                    <td class="text-center">
                                        {{ number_format($item['prezzoIngresso'], 2, ',', '.') }} €
                                    </td>
                                    
                                    {{-- Quantità --}}
                                    <td class="text-center font-bold">
                                        {{ $item['quantita'] }}
                                    </td>
                                    
                                    {{-- Rimborso --}}
                                    @if(collect($cart)->some(fn($i) => $i['rimborso'] == 1))
                                        <td class="text-center text-sm">
                                            @if($item['rimborso'])
                                                {{ number_format($item['rimborsoValore'] * $item['quantita'], 2, ',', '.') }} €
                                            @else
                                                -
                                            @endif
                                        </td>
                                    @endif
                                    
                                    {{-- Totale Riga --}}
                                    <td class="text-right font-bold text-dark">
                                        {{ number_format($item['subtotale'], 2, ',', '.') }} €
                                    </td>
                                </tr>
                                @endforeach
                                
                                {{-- Total Row --}}
                                <tr class="row-inactive">
                                    <td colspan="{{ collect($cart)->some(fn($item) => $item['rimborso'] == 1) ? '5' : '4' }}" class="text-right">
                                        <h4 class="font-bold">Totale Euro</h4>
                                    </td>
                                    <td class="text-right">
                                        <h4 class="font-bold">{{ number_format($totaleAcquisto, 2, ',', '.') }} €</h4>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr class="divider">

                {{-- Customer Data Form --}}
                <div class="checkout-section mb-4">
                    <h2 class="section-heading">I tuoi Dati</h2>
                    <p class="section-desc mb-3">
                        <span class="text-danger font-bold">*</span> i campi contrassegnati sono obbligatori e relativi alla persona che usufruirà del servizio.
                    </p>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="nome" id="nome" value="{{ old('nome') }}" class="form-input" autocomplete="off">
                            @error('nome') <p class="text-danger text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="cognome" class="form-label">Cognome <span class="text-danger">*</span></label>
                            <input type="text" name="cognome" id="cognome" value="{{ old('cognome') }}" class="form-input" autocomplete="off">
                            @error('cognome') <p class="text-danger text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono" class="form-label">Telefono <span class="text-danger">*</span></label>
                            <input type="text" name="telefono" id="telefono" value="{{ old('telefono') }}" class="form-input" autocomplete="off">
                            @error('telefono') <p class="text-danger text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        
                        <div class="form-group" x-data="{ query: '{{ old('citta') }}', results: [], open: false }" @click.away="open = false">
                            <label for="citta" class="form-label">Città</label>
                            <div style="position: relative;">
                                <input 
                                    type="text" 
                                    name="citta" 
                                    id="citta" 
                                    x-model="query" 
                                    @input.debounce.300ms="
                                        if (query.length >= 2) {
                                            fetch('/api/comuni?q=' + encodeURIComponent(query))
                                                .then(response => response.json())
                                                .then(data => { results = data; open = true; })
                                        } else {
                                            results = [];
                                            open = false;
                                        }
                                    "
                                    @focus="if(results.length > 0) open = true"
                                    class="form-input" 
                                    autocomplete="off"
                                    placeholder="Inizia a digitare..."
                                >
                                <div x-show="open && results.length > 0" 
                                     style="display: none; position: absolute; z-index: 50; width: 100%; margin-top: 4px; background-color: #1e1e24; border: 1px solid #333; border-radius: 0.375rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5); max-height: 240px; overflow-y: auto;">
                                    <template x-for="comune in results" :key="comune.idComune">
                                        <div 
                                            @click="query = comune.nomeComune; open = false;"
                                            style="padding: 8px 16px; cursor: pointer; transition: background-color 0.2s; color: #fff;"
                                            onmouseover="this.style.backgroundColor='#2a2a33'"
                                            onmouseout="this.style.backgroundColor='transparent'"
                                            x-text="comune.nomeComune + ' (' + comune.siglaProvincia + ')'"
                                        ></div>
                                    </template>
                                </div>
                            </div>
                            @error('citta') <p class="text-danger text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-input" autocomplete="off">
                            @error('email') <p class="text-danger text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        
                        <div class="form-group col-span-2">
                            <label for="ripetiemail" class="form-label">Ripeti E-mail <span class="text-danger">*</span></label>
                            <input type="email" name="ripetiemail" id="ripetiemail" value="{{ old('ripetiemail') }}" class="form-input" autocomplete="off">
                            @error('ripetiemail') <p class="text-danger text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <hr class="divider">

                {{-- Payment Method Selection --}}
                <div class="checkout-section mb-4">
                    <h2 class="section-heading">Metodo di Pagamento</h2>
                    <p class="section-desc mb-3">
                        <span class="text-danger font-bold">*</span> Seleziona il metodo di pagamento preferito
                    </p>
                    
                    <div class="radio-group">
                        @forelse($paymentMethods as $method)
                            <label class="radio-card payment-method-card" data-payment-id="{{ $method->idTipoPagamento }}" data-payment-name="{{ strtolower($method->nomePagamento) }}">
                                <input type="radio" name="tipoPagamento" value="{{ $method->idTipoPagamento }}" class="radio-input payment-method-input" required>
                                <div class="radio-content">
                                    <div class="radio-circle"></div>
                                    <div class="radio-body">
                                        <span class="radio-label">{{ $method->nomePagamento }}</span>
                                        @if($method->descrizionePagamento)
                                            <p class="radio-desc">{{ $method->descrizionePagamento }}</p>
                                        @endif
                                    </div>
                                    @if($method->pivot && $method->pivot->costoPagamento > 0)
                                        <span class="radio-price">+€{{ number_format($method->pivot->costoPagamento, 2, ',', '.') }}</span>
                                    @endif
                                </div>
                            </label>
                        @empty
                            <p class="text-secondary">Nessun metodo di pagamento disponibile</p>
                        @endforelse
                    </div>
                    @error('tipoPagamento') <p class="text-danger text-sm mt-2">{{ $message }}</p> @enderror
                </div>

                <hr class="divider">

                {{-- Shipping Method Selection --}}
                <div class="checkout-section mb-4">
                    <h2 class="section-heading">Metodo di Spedizione</h2>
                    <p class="section-desc mb-3">
                        <span class="text-danger font-bold">*</span> Seleziona come desideri ricevere il voucher
                    </p>
                    
                    <div class="radio-group" id="shipping-methods-group">
                        @forelse($shippingMethods as $method)
                            @php
                                $isEmail = ($method->idSpedizione == 1) || str_contains(strtolower($method->nomeSpedizione . ' ' . $method->descrizioneSpedizione), 'mail');
                                $isPickup = ($method->idSpedizione == 4) || str_contains(strtolower($method->nomeSpedizione . ' ' . $method->descrizioneSpedizione), 'ritiro');
                            @endphp
                            <label class="radio-card shipping-card" data-shipping-id="{{ $method->idSpedizione }}" data-is-email="{{ $isEmail ? '1' : '0' }}" data-is-pickup="{{ $isPickup ? '1' : '0' }}" id="shipping-card-{{ $method->idSpedizione }}">
                                <input type="radio" name="tipoSpedizione" value="{{ $method->idSpedizione }}" data-cost="{{ $method->pivot ? $method->pivot->costoSpedizione : 0 }}" class="radio-input shipping-method" required>
                                <div class="radio-content">
                                    <div class="radio-circle"></div>
                                    <div class="radio-body">
                                        <span class="radio-label">{{ $method->descrizioneSpedizione ?: $method->nomeSpedizione }}</span>
                                        @if($method->testoAggiuntivo)
                                            <p class="radio-desc">{{ $method->testoAggiuntivo }}</p>
                                        @endif
                                    </div>
                                    @if($method->pivot && $method->pivot->costoSpedizione > 0)
                                        <span class="radio-price">+€{{ number_format($method->pivot->costoSpedizione, 2, ',', '.') }}</span>
                                    @else
                                        <span class="radio-price price-free">Gratis</span>
                                    @endif
                                </div>
                            </label>
                        @empty
                            <p class="text-secondary">Nessun metodo di spedizione disponibile</p>
                        @endforelse
                    </div>
                    @error('tipoSpedizione') <p class="text-danger text-sm mt-2">{{ $message }}</p> @enderror
                </div>

                <hr class="divider">

                {{-- Total with Shipping and PayPal Surcharge --}}
                <div class="checkout-section summary-box mb-4">
                    <div class="summary-row">
                        <div>
                            <p class="text-secondary">Subtotale biglietti:</p>
                            <p class="text-secondary" id="shipping-cost-label">Costo spedizione:</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold">€{{ number_format($totaleAcquisto, 2, ',', '.') }}</p>
                            <p class="font-bold" id="shipping-cost">€0,00</p>
                        </div>
                    </div>
                    <div class="summary-row mt-1" id="paypal-fee-row" style="display: none;">
                        <div>
                            <p class="text-secondary">Costi gestione PayPal (+€2/biglietto):</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-primary" id="paypal-fee-amount">€0,00</p>
                        </div>
                    </div>
                    <hr class="divider my-2">
                    <div class="summary-total">
                        <h3 class="total-label">TOTALE:</h3>
                        <h3 class="total-amount" id="total-amount">€{{ number_format($totaleAcquisto, 2, ',', '.') }}</h3>
                    </div>
                </div>

                {{-- Navigation Buttons --}}
                <div class="nav-actions mt-4">
                    <a href="{{ route('location.show', ['id' => $location->idLocation, 'idPartner' => $partnerId, 'iframe' => $iframe ? '1' : null]) }}" 
                       class="btn btn-outline">
                        Indietro
                    </a>
                    
                    <button type="submit" class="btn btn-primary" onclick="return validateForm();">
                        Completa Ordine
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>

            </form>

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
// Dynamic shipping and PayPal fee calculation
const subtotal = {{ (float)$totaleAcquisto }};
const totalTickets = {{ (int)array_sum(array_column($cart, 'quantita')) }};
const paypalFeePerTicket = 2.0;

function handlePaymentShippingSync() {
    const selectedPayment = document.querySelector('input[name="tipoPagamento"]:checked');
    const paymentCard = selectedPayment ? selectedPayment.closest('.payment-method-card') : null;
    const paymentName = paymentCard ? (paymentCard.dataset.paymentName || '').toLowerCase() : '';
    const isContanti = selectedPayment && (selectedPayment.value === '3' || paymentName.includes('contanti'));

    const shippingCards = document.querySelectorAll('.shipping-card');

    shippingCards.forEach(card => {
        const isEmail = card.dataset.isEmail === '1';
        const isPickup = card.dataset.isPickup === '1';
        const input = card.querySelector('input[name="tipoSpedizione"]');

        if (isContanti) {
            // Chi sceglie Contanti: SOLO Ritiro in sede, NON voucher via email
            if (isEmail) {
                card.style.display = 'none';
                if (input) {
                    input.disabled = true;
                    if (input.checked) input.checked = false;
                }
            } else if (isPickup) {
                card.style.display = '';
                if (input) {
                    input.disabled = false;
                    input.checked = true; // Auto-seleziona Ritiro in sede
                }
            } else {
                card.style.display = 'none';
                if (input) {
                    input.disabled = true;
                    if (input.checked) input.checked = false;
                }
            }
        } else {
            // Per tutti gli altri pagamenti: mostra tutti i metodi di spedizione
            card.style.display = '';
            if (input) {
                input.disabled = false;
            }
        }
    });

    // Se non è contanti e nessun metodo di spedizione è selezionato, seleziona il primo disponibile
    if (!isContanti) {
        const anyChecked = document.querySelector('input[name="tipoSpedizione"]:checked');
        if (!anyChecked) {
            const firstAvailable = document.querySelector('.shipping-card:not([style*="display: none"]) input[name="tipoSpedizione"]');
            if (firstAvailable) {
                firstAvailable.checked = true;
            }
        }
    }

    updateTotal();
}

function updateTotal() {
    const selectedShipping = document.querySelector('.shipping-method:checked');
    const shippingCost = selectedShipping ? (parseFloat(selectedShipping.dataset.cost) || 0) : 0;
    
    const selectedPayment = document.querySelector('input[name="tipoPagamento"]:checked');
    const isPayPal = selectedPayment && selectedPayment.value === '1';
    const paypalFee = isPayPal ? (totalTickets * paypalFeePerTicket) : 0;
    
    const shippingEl = document.getElementById('shipping-cost');
    if (shippingEl) {
        shippingEl.textContent = '€' + shippingCost.toFixed(2).replace('.', ',');
    }
    
    const paypalRow = document.getElementById('paypal-fee-row');
    const paypalAmountEl = document.getElementById('paypal-fee-amount');
    if (paypalRow && paypalAmountEl) {
        if (isPayPal && paypalFee > 0) {
            paypalRow.style.display = 'flex';
            paypalAmountEl.textContent = '+€' + paypalFee.toFixed(2).replace('.', ',');
        } else {
            paypalRow.style.display = 'none';
        }
    }
    
    const total = subtotal + shippingCost + paypalFee;
    const totalAmountEl = document.getElementById('total-amount');
    if (totalAmountEl) {
        totalAmountEl.textContent = '€' + total.toFixed(2).replace('.', ',');
    }
}

document.querySelectorAll('.shipping-method').forEach(el => el.addEventListener('change', updateTotal));
document.querySelectorAll('input[name="tipoPagamento"]').forEach(el => el.addEventListener('change', handlePaymentShippingSync));
document.addEventListener('DOMContentLoaded', () => {
    handlePaymentShippingSync();
    updateTotal();
});

function validateForm() {
    const nome = document.getElementById('nome').value.trim();
    const cognome = document.getElementById('cognome').value.trim();
    const email = document.getElementById('email').value.trim();
    const ripetiemail = document.getElementById('ripetiemail').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    
    if (!nome) {
        alert('Inserisci il nome');
        document.getElementById('nome').focus();
        return false;
    }
    
    if (!cognome) {
        alert('Inserisci il cognome');
        document.getElementById('cognome').focus();
        return false;
    }
    
    if (!telefono) {
        alert('Inserisci il telefono');
        document.getElementById('telefono').focus();
        return false;
    }
    
    if (!email) {
        alert('Inserisci l\'email');
        document.getElementById('email').focus();
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Email non valida');
        document.getElementById('email').focus();
        return false;
    }
    
    if (!ripetiemail) {
        alert('Ripeti l\'email');
        document.getElementById('ripetiemail').focus();
        return false;
    }
    
    if (email !== ripetiemail) {
        alert('Le email non corrispondono');
        document.getElementById('ripetiemail').focus();
        return false;
    }
    
    // Check payment method selected
    const paymentMethod = document.querySelector('input[name="tipoPagamento"]:checked');
    if (!paymentMethod) {
        alert('Seleziona un metodo di pagamento');
        return false;
    }
    
    // Check shipping method selected
    const shippingMethod = document.querySelector('input[name="tipoSpedizione"]:checked');
    if (!shippingMethod) {
        alert('Seleziona un metodo di spedizione');
        return false;
    }

    // Check Contanti restriction
    const paymentCard = paymentMethod.closest('.payment-method-card');
    const paymentName = paymentCard ? (paymentCard.dataset.paymentName || '').toLowerCase() : '';
    const isContanti = paymentMethod.value === '3' || paymentName.includes('contanti');
    const shippingCard = shippingMethod.closest('.shipping-card');
    if (isContanti && shippingCard && shippingCard.dataset.isEmail === '1') {
        alert('Per il pagamento in contanti il metodo di spedizione deve essere "Ritiro in sede".');
        return false;
    }
    
    return true;
}
</script>
@endpush
@endsection
