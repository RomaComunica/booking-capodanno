<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/filament-theme-glossy.css') }}?v={{ time() }}">

    <style>
        .pl-kpi-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        @media (max-width: 640px) {
            .pl-kpi-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
        }
        .pl-kpi-card {
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 105px;
        }
        .pl-kpi-red {
            background-color: #fef2f2 !important;
            border: 1px solid #fecaca !important;
        }
        .dark .pl-kpi-red {
            background-color: rgba(69, 10, 10, 0.35) !important;
            border-color: rgba(185, 28, 28, 0.4) !important;
        }
        .pl-kpi-green {
            background-color: #f0fdf4 !important;
            border: 1px solid #bbf7d0 !important;
        }
        .dark .pl-kpi-green {
            background-color: rgba(5, 46, 22, 0.35) !important;
            border-color: rgba(22, 101, 52, 0.4) !important;
        }
        .pl-kpi-gray {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
        }
        .dark .pl-kpi-gray {
            background-color: rgba(30, 41, 59, 0.7) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        .pl-kpi-amber {
            background-color: #fffbeb !important;
            border: 1px solid #fde68a !important;
        }
        .dark .pl-kpi-amber {
            background-color: rgba(69, 26, 3, 0.35) !important;
            border-color: rgba(180, 83, 9, 0.4) !important;
        }
        .pl-table-wrapper {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .dark .pl-table-wrapper {
            background: #0f172a;
            border-color: rgba(255, 255, 255, 0.08);
        }
        .pl-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .pl-table th {
            padding: 12px 14px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            background-color: #f8fafc;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .pl-table th {
            background-color: #1e293b;
            color: #cbd5e1;
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        .pl-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .dark .pl-table td {
            border-bottom-color: rgba(255, 255, 255, 0.06);
        }
        .pl-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .dark .pl-table tbody tr:hover {
            background-color: rgba(30, 41, 59, 0.5);
        }
        .pl-badge-saldata {
            background-color: #dcfce7 !important;
            color: #15803d !important;
            border: 1px solid #86efac !important;
            padding: 4px 10px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .pl-badge-debito {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
            border: 1px solid #fca5a5 !important;
            padding: 4px 10px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .pl-btn-salda-tutto {
            background-color: #16a34a !important;
            color: #ffffff !important;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            border: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .pl-btn-salda-tutto:hover {
            background-color: #15803d !important;
        }
        .pl-btn-ordini {
            background-color: #f1f5f9;
            color: #334155;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            border: 1px solid #cbd5e1;
        }
        .dark .pl-btn-ordini {
            background-color: #334155;
            color: #f8fafc;
            border-color: #475569;
        }
        .pl-btn-ordini:hover {
            background-color: #e2e8f0;
        }
        .dark .pl-btn-ordini:hover {
            background-color: #475569;
        }
    </style>

    @php
        $selectedCount = count(array_filter($this->selectedLocations));
    @endphp

    <div class="space-y-6">
        {{-- Barra Filtro Periodo --}}
        <div class="glossy-card bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-slate-700/60">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-funnel class="h-5 w-5 text-primary-500" />
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Filtro Periodo Vendite</h3>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Solo ordini completati
                </span>
            </div>
            {{ $this->form }}
        </div>

        {{-- Panoramica Globale KPI Cards (2 per riga) --}}
        <div class="pl-kpi-grid">
            <!-- Residuo Totale da Saldare -->
            <div class="pl-kpi-card pl-kpi-red">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-red-700 dark:text-red-300">Totale Residuo da Saldare</span>
                    <x-heroicon-s-exclamation-circle class="h-6 w-6 text-red-500" />
                </div>
                <div class="text-2xl font-extrabold text-red-700 dark:text-red-300 mt-2">
                    € {{ number_format($report['totaleDaPagareResiduo'], 2, ',', '.') }}
                </div>
                <span class="text-xs text-red-600/90 dark:text-red-300/80 mt-1">
                    {{ $report['conteggioOrdiniDebito'] }} ordini ancora da pagare
                </span>
            </div>

            <!-- Già Pagato -->
            <div class="pl-kpi-card pl-kpi-green">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-green-700 dark:text-green-300">Già Saldato alle Location</span>
                    <x-heroicon-s-check-circle class="h-6 w-6 text-green-500" />
                </div>
                <div class="text-2xl font-extrabold text-green-700 dark:text-green-300 mt-2">
                    € {{ number_format($report['totaleGiaPagato'], 2, ',', '.') }}
                </div>
                <span class="text-xs text-green-600/90 dark:text-green-300/80 mt-1">
                    Pagamenti fornitore registrati
                </span>
            </div>

            <!-- Totale Spettante Fornitori -->
            <div class="pl-kpi-card pl-kpi-gray">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200">Spettante Location Totale</span>
                    <x-heroicon-o-building-storefront class="h-6 w-6 text-gray-500" />
                </div>
                <div class="text-2xl font-extrabold text-gray-900 dark:text-white mt-2">
                    € {{ number_format($report['totaleDare'], 2, ',', '.') }}
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Al netto di ns. provvigioni
                </span>
            </div>

            <!-- Location con Debito Aperto -->
            <div class="pl-kpi-card pl-kpi-amber">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-200">Strutture con Saldo Aperto</span>
                    <x-heroicon-o-clock class="h-6 w-6 text-amber-500" />
                </div>
                <div class="text-2xl font-extrabold text-amber-800 dark:text-amber-200 mt-2">
                    {{ $report['conteggioLocationDebito'] }}
                </div>
                <span class="text-xs text-amber-700/90 dark:text-amber-300/80 mt-1">
                    su {{ count($report['locations']) }} strutture con vendite
                </span>
            </div>
        </div>

        {{-- Barra Strumenti Tabella: Ricerca, Filtri e Azione di Massa --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow border border-gray-200 dark:border-slate-700/60">
            <!-- Ricerca & Filtri Stato -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-64">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cerca location..."
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white pl-8 pr-3 py-2 shadow-xs focus:ring-primary-500 focus:border-primary-500"
                    />
                    <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400 absolute left-2.5 top-2.5" />
                </div>

                <div class="inline-flex rounded-lg border border-gray-200 dark:border-slate-700 p-0.5 bg-gray-50 dark:bg-slate-900 text-xs">
                    <button
                        type="button"
                        wire:click="$set('filtroStato', 'solo_debiti')"
                        class="px-3 py-1.5 rounded-md font-semibold transition-colors {{ $filtroStato === 'solo_debiti' ? 'bg-white dark:bg-gray-800 text-primary-600 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}"
                    >
                        Solo con Debito
                    </button>
                    <button
                        type="button"
                        wire:click="$set('filtroStato', 'tutte')"
                        class="px-3 py-1.5 rounded-md font-semibold transition-colors {{ $filtroStato === 'tutte' ? 'bg-white dark:bg-gray-800 text-primary-600 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}"
                    >
                        Tutte ({{ count($report['locations']) }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('filtroStato', 'saldate')"
                        class="px-3 py-1.5 rounded-md font-semibold transition-colors {{ $filtroStato === 'saldate' ? 'bg-white dark:bg-gray-800 text-primary-600 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}"
                    >
                        Già Saldate
                    </button>
                </div>
            </div>

            <!-- Azione Massiva Selezionate -->
            <div class="flex items-center gap-2">
                @if($selectedCount > 0)
                <button
                    type="button"
                    wire:click="saldaLocationSelezionate"
                    wire:confirm="Confermi di voler registrare come pagati tutti gli ordini arretrati per le {{ $selectedCount }} location selezionate?"
                    class="pl-btn-salda-tutto"
                >
                    <x-heroicon-s-check class="h-4 w-4" />
                    <span>Salda {{ $selectedCount }} Location Selezionate</span>
                </button>
                @endif
            </div>
        </div>

        {{-- Tabella Principale per Location --}}
        <div class="pl-table-wrapper">
            <table class="pl-table">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;">
                            <input
                                type="checkbox"
                                wire:click="toggleSelectAllLocations($event.target.checked)"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                title="Seleziona tutte quelle con debito"
                            />
                        </th>
                        <th style="text-align: left;">Location</th>
                        <th style="text-align: center; white-space: nowrap;">Ordini / Biglietti</th>
                        <th style="text-align: right; white-space: nowrap;">Totale Spettante</th>
                        <th style="text-align: right; white-space: nowrap; color: #16a34a;">Già Pagato</th>
                        <th style="text-align: right; white-space: nowrap; color: #dc2626;">Da Pagare</th>
                        <th style="text-align: center; white-space: nowrap;">Stato</th>
                        <th style="text-align: center; white-space: nowrap; width: 220px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['locations'] as $idLoc => $loc)
                    @php
                        $haDebito = $loc['totaleDaPagareResiduo'] > 0.01;
                    @endphp
                    <tr style="{{ $haDebito ? '' : 'opacity: 0.8;' }}">
                        <!-- Checkbox -->
                        <td style="text-align: center;">
                            @if($haDebito)
                            <input
                                type="checkbox"
                                wire:model.live="selectedLocations.{{ $idLoc }}"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                            @else
                            <x-heroicon-m-check class="h-4 w-4 text-green-500 mx-auto" />
                            @endif
                        </td>

                        <!-- Nome Location -->
                        <td>
                            <div style="font-weight: 700; font-size: 14px;" class="text-gray-900 dark:text-white">
                                {{ $loc['nomeLocation'] }}
                            </div>
                        </td>

                        <!-- Ordini e Biglietti -->
                        <td style="text-align: center; white-space: nowrap;">
                            <span style="font-weight: 700;" class="text-gray-900 dark:text-white">{{ $loc['totaleOrdini'] }}</span>
                            <span class="text-gray-500">ord.</span>
                            <span class="text-gray-400">/</span>
                            <span style="font-weight: 600;" class="text-gray-700 dark:text-gray-300">{{ $loc['bigliettiTotali'] }}</span>
                            <span class="text-gray-500">bigl.</span>
                        </td>

                        <!-- Spettante -->
                        <td style="text-align: right; font-weight: 700; white-space: nowrap;" class="text-gray-900 dark:text-white">
                            € {{ number_format($loc['totaleDare'], 2, ',', '.') }}
                        </td>

                        <!-- Già Pagato -->
                        <td style="text-align: right; font-weight: 600; color: #16a34a; white-space: nowrap;">
                            € {{ number_format($loc['totaleGiaPagato'], 2, ',', '.') }}
                        </td>

                        <!-- Da Pagare -->
                        <td style="text-align: right; font-weight: 800; font-size: 14px; white-space: nowrap; color: {{ $haDebito ? '#dc2626' : '#94a3b8' }};">
                            € {{ number_format($loc['totaleDaPagareResiduo'], 2, ',', '.') }}
                        </td>

                        <!-- Badge Stato -->
                        <td style="text-align: center; white-space: nowrap;">
                            @if($haDebito)
                            <span class="pl-badge-debito">
                                <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #dc2626;"></span>
                                {{ $loc['ordiniDaPagare'] }} da pagare
                            </span>
                            @else
                            <span class="pl-badge-saldata">
                                <x-heroicon-s-check class="h-3.5 w-3.5" />
                                Saldata
                            </span>
                            @endif
                        </td>

                        <!-- Azioni -->
                        <td style="text-align: center; white-space: nowrap;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <!-- Dettaglio Ordini -->
                                <button
                                    type="button"
                                    wire:click="apriDettaglioOrdini({{ $idLoc }})"
                                    class="pl-btn-ordini"
                                    title="Vedi singoli ordini"
                                >
                                    <x-heroicon-o-list-bullet class="h-4 w-4" />
                                    <span>Ordini ({{ count($loc['ordini']) }})</span>
                                </button>

                                <!-- Salda Tutto -->
                                @if($haDebito)
                                <button
                                    type="button"
                                    wire:click="saldaTuttiOrdiniLocation({{ $idLoc }})"
                                    wire:confirm="Confermi di voler segnare come PAGATI tutti i {{ $loc['ordiniDaPagare'] }} ordini in sospeso di {{ addslashes($loc['nomeLocation']) }} (€ {{ number_format($loc['totaleDaPagareResiduo'], 2, ',', '.') }})?"
                                    class="pl-btn-salda-tutto"
                                    title="Segna tutti gli ordini arretrati come pagati"
                                >
                                    <x-heroicon-s-check-circle class="h-4 w-4" />
                                    <span>Salda Tutto</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding: 40px; text-align: center; color: #64748b;">
                            Nessuna location trovata con i filtri correnti.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modal / Slide-over Dettaglio Singoli Ordini della Location --}}
        @if($dettaglioLocationId)
        <div style="position: fixed; inset: 0; z-index: 50; overflow-y: auto; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 16px;">
            <div style="background-color: #ffffff; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-width: 900px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; border: 1px solid #e2e8f0; overflow: hidden;" class="dark:bg-gray-800 dark:border-slate-700">
                <!-- Header Modal -->
                <div style="padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;" class="dark:border-slate-700">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 8px; margin: 0;" class="text-gray-900 dark:text-white">
                            <x-heroicon-o-building-storefront class="h-5 w-5 text-primary-500" />
                            <span>{{ $dettaglioLocationNome }} — Dettaglio Ordini</span>
                        </h3>
                        <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">
                            Gestisci o salda i singoli ordini di questa struttura
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="chiudiDettaglioOrdini"
                        style="color: #94a3b8; font-size: 28px; line-height: 1; border: none; background: none; cursor: pointer; padding: 4px;"
                    >
                        &times;
                    </button>
                </div>

                <!-- Azioni Rapide Modal -->
                @php
                    $ordiniSelezionatiCount = count(array_filter($this->selectedOrdini));
                @endphp
                <div style="padding: 12px 24px; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; font-size: 12px;" class="dark:bg-slate-900 dark:border-slate-700">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button
                            type="button"
                            wire:click="toggleSelectAllOrdini(true)"
                            style="color: #0c5289; font-weight: 600; background: none; border: none; cursor: pointer; text-decoration: underline;"
                        >
                            Seleziona non pagati
                        </button>
                        <span style="color: #cbd5e1;">|</span>
                        <button
                            type="button"
                            wire:click="toggleSelectAllOrdini(false)"
                            style="color: #64748b; background: none; border: none; cursor: pointer; text-decoration: underline;"
                        >
                            Deseleziona
                        </button>
                    </div>

                    @if($ordiniSelezionatiCount > 0)
                    <button
                        type="button"
                        wire:click="saldaOrdiniSelezionati"
                        wire:confirm="Confermi di voler segnare come pagati i {{ $ordiniSelezionatiCount }} ordini selezionati?"
                        class="pl-btn-salda-tutto"
                    >
                        Salda Selezionati ({{ $ordiniSelezionatiCount }})
                    </button>
                    @endif
                </div>

                <!-- Tabella Ordini nel Modal -->
                <div style="padding: 24px; overflow-y: auto; flex: 1;">
                    <table class="pl-table">
                        <thead>
                            <tr>
                                <th style="width: 30px; text-align: center;"></th>
                                <th style="text-align: left;"># Ordine</th>
                                <th style="text-align: left;">Cliente</th>
                                <th style="text-align: center;">Metodo</th>
                                <th style="text-align: center;">Biglietti</th>
                                <th style="text-align: right;">Spettante</th>
                                <th style="text-align: center; width: 140px;">Stato / Azione</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dettaglioOrdini as $ord)
                            <tr>
                                <td style="text-align: center;">
                                    @if(!$ord['isPaid'])
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedOrdini.{{ $ord['numeroOrdine'] }}"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    @endif
                                </td>
                                <td style="font-weight: 700;" class="text-gray-900 dark:text-white">
                                    #{{ $ord['numeroOrdine'] }}
                                </td>
                                <td class="text-gray-800 dark:text-gray-200">
                                    {{ $ord['cliente'] }}
                                </td>
                                <td style="text-align: center;" class="text-gray-600 dark:text-gray-400">
                                    {{ $ord['metodoPagamento'] }}
                                </td>
                                <td style="text-align: center; font-weight: 600;" class="text-gray-900 dark:text-white">
                                    {{ $ord['biglietti'] }}
                                </td>
                                <td style="text-align: right; font-weight: 700;" class="text-gray-900 dark:text-white">
                                    € {{ number_format($ord['dare'], 2, ',', '.') }}
                                </td>
                                <td style="text-align: center;">
                                    <button
                                        type="button"
                                        wire:click="togglePagamentoOrdine({{ $ord['numeroOrdine'] }})"
                                        style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.15s; {{ $ord['isPaid'] ? 'background-color: #dcfce7 !important; color: #15803d !important; border: 1px solid #86efac !important;' : 'background-color: #fee2e2 !important; color: #b91c1c !important; border: 1px solid #fca5a5 !important;' }}"
                                    >
                                        @if($ord['isPaid'])
                                            <x-heroicon-s-check-circle class="h-3.5 w-3.5" style="color: #16a34a !important;" />
                                            <span>Pagata</span>
                                        @else
                                            <x-heroicon-s-x-circle class="h-3.5 w-3.5" style="color: #dc2626 !important;" />
                                            <span>Non Pagata</span>
                                        @endif
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer Modal -->
                <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; background-color: #f8fafc; display: flex; align-items: center; justify-content: space-between;" class="dark:bg-slate-900 dark:border-slate-700">
                    <span style="font-size: 12px; color: #64748b;">
                        {{ count($dettaglioOrdini) }} ordini totali trovati per questa location
                    </span>
                    <button
                        type="button"
                        wire:click="chiudiDettaglioOrdini"
                        style="padding: 8px 16px; background-color: #e2e8f0; color: #334155; font-weight: 600; border-radius: 8px; font-size: 12px; border: none; cursor: pointer;"
                    >
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
