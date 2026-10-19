@extends('layouts.app')

@section('meta_title', 'Acquista Ticket Online - ' . config('app.name', 'Capodanno Roma'))
@section('meta_description', 'Scegli la location perfetta e prenota il tuo Capodanno online in pochi click.')

@section('content')
<div class="container main-content">
    
    @if(!$iframe)
    {{-- Header with Partner Logo --}}
    <div class="page-header flex-between mb-2">
        <div class="header-titles">
            @if($partner && $partner->logo_url)
                <img src="{{ $partner->logo_url }}" alt="{{ $partner->nomePartner ?? 'Partner' }}" class="partner-logo">
            @elseif($partner && file_exists(public_path("partner/loghi/{$partnerId}.jpg")))
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
        <img src="{{ asset('img/step_1.jpg') }}" alt="Step 1" class="step-img desktop-only" onerror="this.style.display='none'">
        <img src="{{ asset('img/step_1m.jpg') }}" alt="Step 1" class="step-img mobile-only" onerror="this.style.display='none'">
    </div>

    {{-- Filters Section --}}
    <div class="glass-panel filter-section mb-3 animate-fade-in">
        <form action="{{ route('home') }}" method="GET" class="filter-form">
            @if($iframe)
                <input type="hidden" name="iframe" value="1">
            @endif
            
            <div class="filter-grid" style="grid-template-columns: 1fr;">
                {{-- Search --}}
                <div class="form-group flex" style="flex-direction: row; gap: 1rem; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label for="search" class="form-label hidden">Cerca Location</label>
                        <select 
                            id="search"
                            name="search" 
                            class="form-input"
                            style="padding: 1rem; font-size: 1.1rem; cursor: pointer;"
                        >
                            <option value="">Tutte le location...</option>
                            @foreach($availableLocations as $loc)
                                <option value="{{ $loc->nomeLocation }}" {{ request('search') == $loc->nomeLocation ? 'selected' : '' }}>
                                    {{ $loc->nomeLocation }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-actions mt-0">
                        @if(request('search'))
                            <a href="{{ route('home', ['iframe' => $iframe ? '1' : null]) }}" class="btn btn-outline" style="padding-top: 1rem; padding-bottom: 1rem;">
                                Reset
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary" style="padding-top: 1rem; padding-bottom: 1rem;">
                            Cerca
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Locations Grid --}}
    @if($locations->count() > 0)
        <div class="location-grid mb-3">
            @foreach($locations as $index => $location)
                <div class="location-card glass-panel animate-fade-in" style="animation-delay: {{ min($index * 0.1, 0.5) }}s">
                    {{-- Location Image (if exists) --}}
                    @if($location->image_url)
                        <div class="card-image-wrapper">
                            <img 
                                src="{{ $location->image_url }}" 
                                alt="{{ $location->nomeLocation }}"
                                class="card-image"
                            >
                            <div class="card-image-overlay"></div>
                        </div>
                    @else
                        <div class="card-image-placeholder">
                            <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    @endif

                    <div class="card-body">
                        <h2 class="card-title">{{ $location->nomeLocation }}</h2>
                        
                        @if($location->indirizzoLocation)
                            <p class="card-meta">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>{{ $location->indirizzoLocation }}</span>
                            </p>
                        @endif

                        @if($location->telefonoLocation)
                            <p class="card-meta">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ $location->telefonoLocation }}
                            </p>
                        @endif

                        {{-- Active tickets count --}}
                        @php
                            $activeTickets = $location->ingressi->where('ingressoAttivo', true)->count();
                        @endphp
                        @if($activeTickets > 0)
                            <p class="card-highlight mt-2">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                                </svg>
                                {{ $activeTickets }} {{ $activeTickets == 1 ? 'tipologia di ingresso' : 'tipologie di ingresso' }}
                            </p>
                        @endif
                    </div>
                    
                    <div class="card-footer">
                        <a href="{{ route('location.show', ['id' => $location->idLocation, 'idPartner' => $partnerId, 'iframe' => $iframe ? '1' : null]) }}" 
                           class="btn btn-primary btn-block">
                            Vedi Dettagli
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-left:4px">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        
        {{-- Pagination --}}
        <div class="pagination-wrapper mt-4">
            {{ $locations->appends(request()->query())->links() }}
        </div>
    @else
        <div class="empty-state glass-panel text-center">
            <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="mx-auto" style="color:var(--text-secondary); margin-bottom:1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="empty-text">Nessuna location trovata.</p>
            @if(request()->hasAny(['search', 'categoria', 'tipologia']))
                <a href="{{ route('home', ['iframe' => $iframe ? '1' : null]) }}" class="btn btn-outline mt-2">
                    Rimuovi filtri
                </a>
            @endif
        </div>
    @endif

    {{-- Mobile Payment Methods --}}
    @if(!$iframe)
    <div class="mobile-only mt-4 text-center pb-2">
        <img src="{{ asset('img/carte_di_credito.png') }}" alt="Metodi di pagamento" class="payment-icons mx-auto" onerror="this.style.display='none'">
    </div>
    @endif
</div>
@endsection
