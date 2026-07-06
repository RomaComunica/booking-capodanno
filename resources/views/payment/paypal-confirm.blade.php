@extends('layouts.app')

@section('content')
<div class="container main-content">
    <div class="max-w-2xl mx-auto">
        <div class="glass-panel detail-panel p-6">
            
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center p-4 rounded-full mb-4" style="background-color: rgba(255, 215, 0, 0.1);">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-gold">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h1 class="page-title" style="font-size: 1.8rem;">Conferma Pagamento PayPal</h1>
                <p class="text-secondary">Verifica i dettagli del tuo ordine prima di confermare</p>
            </div>

            <!-- Order Summary -->
            <div class="my-4 pb-4 border-bottom">
                <h2 class="section-heading text-sm uppercase tracking-wide text-gold">Riepilogo Ordine</h2>
                
                @if(isset($cart) && count($cart) > 0)
                    <div class="items-list mt-3">
                        @foreach($cart as $item)
                            <div class="flex-between mb-2">
                                <span class="text-primary font-bold">
                                    {{ $item['quantita'] }}x {{ $item['nomeIngresso'] }}
                                </span>
                                <span class="font-bold text-gold">
                                    €{{ number_format($item['subtotale'], 2, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4 pt-4 border-top">
                    <div class="flex-between">
                        <span class="font-bold uppercase" style="font-size: 1.2rem;">Totale</span>
                        <span class="text-gold font-bold" style="font-size: 1.5rem;">€{{ number_format($amount ?? 0, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            @if(isset($customer))
                <div class="mb-6">
                    <h2 class="section-heading text-sm uppercase tracking-wide text-gold">Dati Cliente</h2>
                    <div class="bg-highlight rounded p-4 mt-2 form-grid" style="gap: 1rem;">
                        <p><span class="text-secondary">Nome:</span> <strong class="ml-1">{{ $customer['nome'] }} {{ $customer['cognome'] }}</strong></p>
                        <p><span class="text-secondary">Email:</span> <strong class="ml-1">{{ $customer['email'] }}</strong></p>
                        <p><span class="text-secondary">Telefono:</span> <strong class="ml-1">{{ $customer['telefono'] }}</strong></p>
                    </div>
                </div>
            @endif

            <!-- PayPal Payer Info -->
            @if(isset($payer) && $payer['success'])
                <div class="mb-6">
                    <h2 class="section-heading text-sm uppercase tracking-wide text-gold">Account PayPal</h2>
                    <div class="alert alert-info mt-2">
                        @if(isset($payer['email']))
                            <p class="mb-1"><span class="text-secondary">Email PayPal:</span> <strong class="text-primary ml-1">{{ $payer['email'] }}</strong></p>
                        @endif
                        @if(isset($payer['first_name']) && isset($payer['last_name']))
                            <p><span class="text-secondary">Nome:</span> <strong class="text-primary ml-1">{{ $payer['first_name'] }} {{ $payer['last_name'] }}</strong></p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="flex-between mt-6 pt-4 border-top" style="gap: 1rem;">
                <form action="{{ route('payment.paypal.confirm') }}" method="POST" style="flex: 2;">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-block py-3">
                        Conferma e Completa Ordine
                    </button>
                </form>
                
                <a href="{{ route('payment.paypal.cancel') }}" class="btn btn-outline py-3 text-center" style="flex: 1;">
                    Annulla
                </a>
            </div>

            <p class="text-secondary text-center text-sm mt-4">
                Cliccando "Conferma" autorizzi il pagamento tramite PayPal
            </p>
        </div>
    </div>
</div>
@endsection
