@extends('layouts.app')

@section('content')
<div class="container main-content">
    <div class="page-header mb-3">
        <h1 class="page-title text-gold">Il tuo Carrello</h1>
    </div>

    @if(count($cart) > 0)
        <div class="glass-panel" style="overflow: hidden;">
            <div class="table-responsive">
                <table class="modern-table" style="min-width: 600px;">
                    <thead>
                        <tr>
                            <th class="text-left">Evento / Location</th>
                            <th class="text-left">Biglietto</th>
                            <th class="text-center">Prezzo</th>
                            <th class="text-center">Quantità</th>
                            <th class="text-center">Totale</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cart as $id => $item)
                            <tr class="row-active">
                                <td class="font-bold text-dark">{{ session('nomeLocation') }}</td>
                                <td>{{ $item['nomeIngresso'] ?? 'Biglietto' }}</td>
                                <td class="text-center">€ {{ number_format($item['prezzoIngresso'] ?? 0, 2, ',', '.') }}</td>
                                <td class="text-center font-bold">{{ $item['quantita'] ?? 0 }}</td>
                                <td class="text-center text-gold font-bold">€ {{ number_format($item['subtotale'] ?? 0, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <form action="{{ route('cart.remove', $id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger" style="background: none; border: none; font-weight: 600; cursor: pointer; text-decoration: underline;">Rimuovi</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="flex-between" style="padding: 1.5rem; background-color: rgba(255,255,255,0.02); border-top: 1px solid var(--border-color);">
                <div class="total-amount" style="font-size: 1.5rem;">
                    Totale: <span class="text-gold font-bold">€ {{ number_format($total, 2, ',', '.') }}</span>
                </div>
                <div style="display: flex; gap: 1.5rem; align-items: center;">
                    <form action="{{ route('cart.clear') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="text-secondary" style="background: none; border: none; cursor: pointer; text-decoration: underline;">Svuota carrello</button>
                    </form>
                    <a href="{{ route('checkout.index') }}" class="btn btn-primary">
                        Procedi al Checkout
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="empty-state glass-panel text-center">
            <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="mx-auto" style="color:var(--text-secondary); margin-bottom:1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <p class="empty-text mb-3">Il tuo carrello è vuoto.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">
                Torna agli Eventi
            </a>
        </div>
    @endif
</div>
@endsection
