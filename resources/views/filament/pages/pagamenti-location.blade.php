<x-filament-panels::page>
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

        {{-- Panoramica Globale KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Residuo Totale da Saldare -->
            <div class="bg-red-50 dark:bg-red-950/30 rounded-xl p-5 border border-red-200 dark:border-red-900/60 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-red-700 dark:text-red-400">Totale Residuo da Saldare</span>
                    <x-heroicon-s-exclamation-circle class="h-6 w-6 text-red-500" />
                </div>
                <div class="text-2xl font-extrabold text-red-700 dark:text-red-400 mt-2">
                    € {{ number_format($report['totaleDaPagareResiduo'], 2, ',', '.') }}
                </div>
                <span class="text-xs text-red-600/90 dark:text-red-400/90 mt-1">
                    {{ $report['conteggioOrdiniDebito'] }} ordini ancora da pagare
                </span>
            </div>

            <!-- Già Pagato -->
            <div class="bg-green-50 dark:bg-green-950/30 rounded-xl p-5 border border-green-200 dark:border-green-800/60 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-green-700 dark:text-green-400">Già Saldato alle Location</span>
                    <x-heroicon-s-check-circle class="h-6 w-6 text-green-500" />
                </div>
                <div class="text-2xl font-extrabold text-green-700 dark:text-green-400 mt-2">
                    € {{ number_format($report['totaleGiaPagato'], 2, ',', '.') }}
                </div>
                <span class="text-xs text-green-600/90 dark:text-green-400/90 mt-1">
                    Pagamenti fornitore registrati
                </span>
            </div>

            <!-- Totale Spettante Fornitori -->
            <div class="bg-gray-50 dark:bg-slate-800/80 rounded-xl p-5 border border-gray-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Spettante Location Totale</span>
                    <x-heroicon-o-building-storefront class="h-6 w-6 text-gray-400" />
                </div>
                <div class="text-2xl font-extrabold text-gray-900 dark:text-white mt-2">
                    € {{ number_format($report['totaleDare'], 2, ',', '.') }}
                </div>
                <span class="text-xs text-gray-500 mt-1">
                    Al netto di ns. provvigioni
                </span>
            </div>

            <!-- Location con Debito Aperto -->
            <div class="bg-amber-50 dark:bg-amber-950/30 rounded-xl p-5 border border-amber-200 dark:border-amber-800/60 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Strutture con Saldo Aperto</span>
                    <x-heroicon-o-clock class="h-6 w-6 text-amber-500" />
                </div>
                <div class="text-2xl font-extrabold text-amber-700 dark:text-amber-400 mt-2">
                    {{ $report['conteggioLocationDebito'] }}
                </div>
                <span class="text-xs text-amber-600/90 dark:text-amber-400/90 mt-1">
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
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-lg shadow transition cursor-pointer"
                >
                    <x-heroicon-s-check class="h-4 w-4" />
                    <span>Salda {{ $selectedCount }} Location Selezionate</span>
                </button>
                @endif
            </div>
        </div>

        {{-- Tabella Principale per Location --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-slate-700 shadow bg-white dark:bg-gray-800">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-4 py-3 text-center" style="width: 40px;">
                            <input
                                type="checkbox"
                                wire:click="toggleSelectAllLocations($event.target.checked)"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                title="Seleziona tutte quelle con debito"
                            />
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Location / Città
                        </th>
                        <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">
                            Ordini / Biglietti
                        </th>
                        <th class="px-4 py-3 text-right font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">
                            Totale Spettante
                        </th>
                        <th class="px-4 py-3 text-right font-bold text-green-700 dark:text-green-400 uppercase tracking-wider whitespace-nowrap">
                            Già Pagato
                        </th>
                        <th class="px-4 py-3 text-right font-bold text-red-700 dark:text-red-400 uppercase tracking-wider whitespace-nowrap">
                            Da Pagare
                        </th>
                        <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">
                            Stato
                        </th>
                        <th class="px-4 py-3 text-center font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap" style="width: 220px;">
                            Azioni
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                    @forelse($report['locations'] as $idLoc => $loc)
                    @php
                        $haDebito = $loc['totaleDaPagareResiduo'] > 0.01;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors {{ $haDebito ? '' : 'opacity-80' }}">
                        <!-- Checkbox -->
                        <td class="px-4 py-3 text-center">
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
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            <div class="font-bold text-sm text-gray-900 dark:text-white">
                                {{ $loc['nomeLocation'] }}
                            </div>
                        </td>

                        <!-- Ordini e Biglietti -->
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <span class="font-bold text-gray-900 dark:text-white">{{ $loc['totaleOrdini'] }}</span>
                            <span class="text-gray-500">ord.</span>
                            <span class="text-gray-400">/</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $loc['bigliettiTotali'] }}</span>
                            <span class="text-gray-500">bigl.</span>
                        </td>

                        <!-- Spettante -->
                        <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white whitespace-nowrap">
                            € {{ number_format($loc['totaleDare'], 2, ',', '.') }}
                        </td>

                        <!-- Già Pagato -->
                        <td class="px-4 py-3 text-right font-semibold text-green-600 dark:text-green-400 whitespace-nowrap">
                            € {{ number_format($loc['totaleGiaPagato'], 2, ',', '.') }}
                        </td>

                        <!-- Da Pagare -->
                        <td class="px-4 py-3 text-right font-extrabold text-sm {{ $haDebito ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }} whitespace-nowrap">
                            € {{ number_format($loc['totaleDaPagareResiduo'], 2, ',', '.') }}
                        </td>

                        <!-- Badge Stato -->
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if($haDebito)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300 border border-red-300 dark:border-red-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>
                                {{ $loc['ordiniDaPagare'] }} da pagare
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300 border border-green-300 dark:border-green-800">
                                <x-heroicon-s-check class="h-3 w-3" />
                                Saldata
                            </span>
                            @endif
                        </td>

                        <!-- Azioni -->
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Dettaglio Ordini -->
                                <button
                                    type="button"
                                    wire:click="apriDettaglioOrdini({{ $idLoc }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                                    title="Vedi singoli ordini"
                                >
                                    <x-heroicon-o-list-bullet class="h-3.5 w-3.5" />
                                    <span>Ordini ({{ count($loc['ordini']) }})</span>
                                </button>

                                <!-- Salda Tutto -->
                                @if($haDebito)
                                <button
                                    type="button"
                                    wire:click="saldaTuttiOrdiniLocation({{ $idLoc }})"
                                    wire:confirm="Confermi di voler segnare come PAGATI tutti i {{ $loc['ordiniDaPagare'] }} ordini in sospeso di {{ addslashes($loc['nomeLocation']) }} (€ {{ number_format($loc['totaleDaPagareResiduo'], 2, ',', '.') }})?"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold transition shadow-xs cursor-pointer"
                                    title="Segna tutti gli ordini arretrati come pagati"
                                >
                                    <x-heroicon-s-check-circle class="h-3.5 w-3.5" />
                                    <span>Salda Tutto</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                            Nessuna location trovata con i filtri correnti.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modal / Slide-over Dettaglio Singoli Ordini della Location --}}
        @if($dettaglioLocationId)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col border border-gray-200 dark:border-slate-700">
                <!-- Header Modal -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-building-storefront class="h-5 w-5 text-primary-500" />
                            <span>{{ $dettaglioLocationNome }} — Dettaglio Ordini</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Gestisci o salda i singoli ordini di questa struttura
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="chiudiDettaglioOrdini"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold p-1 leading-none"
                    >
                        &times;
                    </button>
                </div>

                <!-- Azioni Rapide Modal -->
                @php
                    $ordiniSelezionatiCount = count(array_filter($this->selectedOrdini));
                @endphp
                <div class="px-6 py-3 bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="toggleSelectAllOrdini(true)"
                            class="text-primary-600 hover:underline font-semibold"
                        >
                            Seleziona non pagati
                        </button>
                        <span class="text-gray-400">|</span>
                        <button
                            type="button"
                            wire:click="toggleSelectAllOrdini(false)"
                            class="text-gray-500 hover:underline"
                        >
                            Deseleziona
                        </button>
                    </div>

                    @if($ordiniSelezionatiCount > 0)
                    <button
                        type="button"
                        wire:click="saldaOrdiniSelezionati"
                        wire:confirm="Confermi di voler segnare come pagati i {{ $ordiniSelezionatiCount }} ordini selezionati?"
                        class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-xs transition"
                    >
                        Salda Selezionati ({{ $ordiniSelezionatiCount }})
                    </button>
                    @endif
                </div>

                <!-- Tabella Ordini nel Modal -->
                <div class="p-6 overflow-y-auto flex-1">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                        <thead class="bg-gray-100 dark:bg-slate-900">
                            <tr>
                                <th class="px-3 py-2 text-center" style="width: 30px;"></th>
                                <th class="px-3 py-2 text-left font-bold text-gray-700 dark:text-gray-300"># Ordine</th>
                                <th class="px-3 py-2 text-left font-bold text-gray-700 dark:text-gray-300">Cliente</th>
                                <th class="px-3 py-2 text-center font-bold text-gray-700 dark:text-gray-300">Metodo</th>
                                <th class="px-3 py-2 text-center font-bold text-gray-700 dark:text-gray-300">Biglietti</th>
                                <th class="px-3 py-2 text-right font-bold text-gray-700 dark:text-gray-300">Spettante</th>
                                <th class="px-3 py-2 text-center font-bold text-gray-700 dark:text-gray-300" style="width: 140px;">Stato / Azione</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @foreach($dettaglioOrdini as $ord)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                <td class="px-3 py-2 text-center">
                                    @if(!$ord['isPaid'])
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedOrdini.{{ $ord['numeroOrdine'] }}"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    @endif
                                </td>
                                <td class="px-3 py-2 font-bold text-gray-900 dark:text-white">
                                    #{{ $ord['numeroOrdine'] }}
                                </td>
                                <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                                    {{ $ord['cliente'] }}
                                </td>
                                <td class="px-3 py-2 text-center text-gray-600 dark:text-gray-400">
                                    {{ $ord['metodoPagamento'] }}
                                </td>
                                <td class="px-3 py-2 text-center font-semibold text-gray-900 dark:text-white">
                                    {{ $ord['biglietti'] }}
                                </td>
                                <td class="px-3 py-2 text-right font-bold text-gray-900 dark:text-white">
                                    € {{ number_format($ord['dare'], 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button
                                        type="button"
                                        wire:click="togglePagamentoOrdine({{ $ord['numeroOrdine'] }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition shadow-2xs cursor-pointer"
                                        style="{{ $ord['isPaid'] ? 'background-color: #dcfce7 !important; color: #15803d !important; border: 1px solid #86efac !important;' : 'background-color: #fee2e2 !important; color: #b91c1c !important; border: 1px solid #fca5a5 !important;' }}"
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
                <div class="px-6 py-3 border-t border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        {{ count($dettaglioOrdini) }} ordini totali trovati per questa location
                    </span>
                    <button
                        type="button"
                        wire:click="chiudiDettaglioOrdini"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 font-semibold rounded-lg text-xs transition"
                    >
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
