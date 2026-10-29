<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Form di Selezione --}}
        <div class="glossy-card bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-slate-700/60">
            <form wire:submit="calcola">
                {{ $this->form }}
                
                <div class="mt-6 flex items-center gap-3">
                    <x-filament::button type="submit" size="lg" icon="heroicon-o-calculator" icon-position="before">
                        Calcola Report
                    </x-filament::button>

                    @if($risultati)
                    <x-filament::button
                        type="button"
                        size="lg"
                        color="success"
                        icon="heroicon-o-printer"
                        icon-position="before"
                        x-on:click="
                            let printWindow = window.open('', '_blank', 'width=1100,height=800');
                            printWindow.document.write('<html><head><title>Stampa Calcolatore Location</title>');
                            printWindow.document.write('<style>');
                            printWindow.document.write('body { font-family: system-ui, sans-serif; padding: 20px; color: #111; }');
                            printWindow.document.write('svg { width: 20px !important; height: 20px !important; max-width: 20px !important; max-height: 20px !important; display: inline-block; vertical-align: middle; margin-right: 6px; }');
                            printWindow.document.write('h2 { margin-top: 0; margin-bottom: 5px; font-size: 22px; font-weight: bold; display: flex; align-items: center; gap: 8px; }');
                            printWindow.document.write('h3 { margin-top: 25px; margin-bottom: 12px; font-size: 16px; font-weight: bold; border-bottom: 2px solid #333; padding-bottom: 5px; display: flex; align-items: center; gap: 6px; }');
                            printWindow.document.write('p { margin: 0 0 15px 0; color: #555; font-size: 13px; }');
                            printWindow.document.write('.kpi-grid { display: flex; gap: 10px; margin: 14px 0 18px 0; }');
                            printWindow.document.write('.kpi-card { flex: 1; border: 1px solid #ddd; border-radius: 6px; padding: 8px 10px; background: #fafafa; }');
                            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }');
                            printWindow.document.write('th, td { padding: 8px 10px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: top; }');
                            printWindow.document.write('th { background-color: #f5f5f5; font-weight: bold; text-transform: uppercase; font-size: 11px; color: #555; }');
                            printWindow.document.write('.text-center { text-align: center; }');
                            printWindow.document.write('.text-right { text-align: right; }');
                            printWindow.document.write('.font-bold { font-weight: bold; }');
                            printWindow.document.write('.text-lg { font-size: 15px; }');
                            printWindow.document.write('.bg-green-light { background-color: #f0fdf4; }');
                            printWindow.document.write('.hidden-print { display: none !important; }');
                            printWindow.document.write('.visible-print { display: inline !important; }');
                            printWindow.document.write('</style>');
                            printWindow.document.write('</head><body>');
                            printWindow.document.write(document.getElementById('risultati-calcolatore-stampa').innerHTML);
                            printWindow.document.write('</body></html>');
                            printWindow.document.close();
                            printWindow.focus();
                            setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
                        "
                    >
                        Stampa / PDF
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        size="lg"
                        color="info"
                        icon="heroicon-o-building-storefront"
                        icon-position="before"
                        x-on:click="
                            let printWindow = window.open('', '_blank', 'width=1100,height=800');
                            printWindow.document.write('<html><head><title>Report Vendite Location</title>');
                            printWindow.document.write('<style>');
                            printWindow.document.write('body { font-family: system-ui, sans-serif; padding: 20px; color: #111; }');
                            printWindow.document.write('svg { width: 20px !important; height: 20px !important; max-width: 20px !important; max-height: 20px !important; display: inline-block; vertical-align: middle; margin-right: 6px; }');
                            printWindow.document.write('h2 { margin-top: 0; margin-bottom: 5px; font-size: 22px; font-weight: bold; display: flex; align-items: center; gap: 8px; }');
                            printWindow.document.write('h3 { margin-top: 25px; margin-bottom: 12px; font-size: 16px; font-weight: bold; border-bottom: 2px solid #333; padding-bottom: 5px; display: flex; align-items: center; gap: 6px; }');
                            printWindow.document.write('p { margin: 0 0 15px 0; color: #555; font-size: 13px; }');
                            printWindow.document.write('.kpi-grid { display: flex; gap: 10px; margin: 14px 0 18px 0; }');
                            printWindow.document.write('.kpi-card { flex: 0 0 auto; min-width: 200px; border: 1px solid #ddd; border-radius: 6px; padding: 8px 10px; background: #fafafa; }');
                            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }');
                            printWindow.document.write('th, td { padding: 8px 10px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: top; }');
                            printWindow.document.write('th { background-color: #f5f5f5; font-weight: bold; text-transform: uppercase; font-size: 11px; color: #555; }');
                            printWindow.document.write('.text-center { text-align: center; }');
                            printWindow.document.write('.text-right { text-align: right; }');
                            printWindow.document.write('.font-bold { font-weight: bold; }');
                            printWindow.document.write('.text-lg { font-size: 15px; }');
                            printWindow.document.write('.hidden-print { display: none !important; }');
                            printWindow.document.write('.visible-print { display: inline !important; }');
                            printWindow.document.write('</style>');
                            printWindow.document.write('</head><body>');
                            printWindow.document.write(document.getElementById('risultati-calcolatore-stampa').innerHTML);
                            printWindow.document.write('</body></html>');
                            printWindow.document.close();
                            printWindow.document.querySelectorAll('.report-locale-hide').forEach(el => el.remove());
                            printWindow.document.querySelectorAll('[data-locale-colspan]').forEach(el => el.setAttribute('colspan', el.getAttribute('data-locale-colspan')));
                            printWindow.focus();
                            setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
                        "
                    >
                        Stampa Report Locale
                    </x-filament::button>
                    @endif
                </div>
            </form>
        </div>

        {{-- Risultati --}}
        @if($risultati)
        <div id="risultati-calcolatore-stampa" class="space-y-6 bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-slate-700/60">
            
            {{-- Header Report --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-slate-700 pb-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-map-pin class="h-6 w-6 text-primary-500" style="width: 22px; height: 22px;" />
                        <span>Report: {{ $risultati['location'] }}</span>
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Periodo: <span class="font-bold text-gray-900 dark:text-white">{{ $risultati['periodo'] }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-primary-50 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 font-bold rounded-lg text-xs border border-primary-200 dark:border-primary-800">
                        {{ count($risultati['dettagli']) }} righe ordine trovate
                    </span>
                </div>
            </div>

            {{-- Financial Summary KPI Cards Grid --}}
            <div class="kpi-grid grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- Totale Venduto -->
                <div class="kpi-card bg-gray-50 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Totale Venduto</span>
                    <div class="text-xl font-extrabold text-gray-900 dark:text-white mt-1">
                        € {{ number_format($risultati['totaleVenduto'], 2, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-gray-500 mt-1">{{ $risultati['totaleQuantita'] }} biglietti totali</span>
                </div>

                <!-- Ns Provvigioni -->
                <div class="kpi-card report-locale-hide bg-gray-50 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400">Ns Provvigione</span>
                    <div class="text-xl font-extrabold text-primary-600 dark:text-primary-400 mt-1">
                        € {{ number_format($risultati['totaleProvvigioni'], 2, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-gray-500 mt-1">Nostro utile lordo</span>
                </div>

                <!-- Totale Spettante Location -->
                <div class="kpi-card report-locale-hide bg-gray-50 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Spettante Location</span>
                    <div class="text-xl font-extrabold text-gray-900 dark:text-white mt-1">
                        € {{ number_format($risultati['totaleDare'], 2, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-gray-500 mt-1">Venduto meno provvigioni</span>
                </div>

                <!-- Già Pagato Location -->
                <div class="kpi-card report-locale-hide bg-green-50 dark:bg-green-950/30 rounded-xl p-4 border border-green-200 dark:border-green-800/60 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-green-700 dark:text-green-400">Già Pagato</span>
                    <div class="text-xl font-extrabold text-green-700 dark:text-green-400 mt-1">
                        € {{ number_format($risultati['totaleGiaPagato'], 2, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-green-600/80 mt-1">Saldato alla location</span>
                </div>

                <!-- Debito Residuo da Pagare -->
                <div class="kpi-card report-locale-hide bg-amber-50 dark:bg-amber-950/30 rounded-xl p-4 border border-amber-200 dark:border-amber-800/60 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Da Pagare (Residuo)</span>
                    <div class="text-xl font-extrabold text-amber-700 dark:text-amber-400 mt-1">
                        € {{ number_format($risultati['totaleDaPagareResiduo'], 2, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-amber-600/80 mt-1">Ancora da saldare</span>
                </div>
            </div>

            {{-- Tabella Dettagli --}}
            <div class="overflow-x-auto mt-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-100 dark:bg-slate-900">
                        <tr>
                            <th class="px-3 py-3 text-left font-bold text-gray-700 dark:text-gray-300 uppercase">
                                Ordine / Cliente
                            </th>
                            <th class="px-3 py-3 text-left font-bold text-gray-700 dark:text-gray-300 uppercase">
                                Location
                            </th>
                            <th class="px-3 py-3 text-center font-bold text-gray-700 dark:text-gray-300 uppercase report-locale-hide">
                                Metodo Pag.
                            </th>
                            <th class="px-3 py-3 text-center font-bold text-gray-700 dark:text-gray-300 uppercase">
                                Tavolo
                            </th>
                            <th class="px-3 py-3 text-left font-bold text-gray-700 dark:text-gray-300 uppercase">
                                Biglietto
                            </th>
                            <th class="px-3 py-3 text-center font-bold text-gray-700 dark:text-gray-300 uppercase">
                                Q.tà
                            </th>
                            <th class="px-3 py-3 text-right font-bold text-gray-700 dark:text-gray-300 uppercase">
                                Prezzo
                            </th>
                            <th class="px-3 py-3 text-right font-bold text-gray-700 dark:text-gray-300 uppercase">
                                Venduto
                            </th>
                            <th class="px-3 py-3 text-right font-bold text-primary-600 dark:text-primary-400 uppercase report-locale-hide">
                                Provv.
                            </th>
                            <th class="px-3 py-3 text-right font-bold text-gray-900 dark:text-white uppercase report-locale-hide">
                                Quota Loc.
                            </th>
                            <th class="px-3 py-3 text-center font-bold text-gray-700 dark:text-gray-300 uppercase hidden-print report-locale-hide">
                                Pagata Location
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($risultati['dettagli'] as $dettaglio)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-3 py-3 whitespace-nowrap text-gray-900 dark:text-white">
                                <div class="font-bold">#{{ $dettaglio['numeroOrdine'] }}</div>
                                <div class="text-[11px] text-gray-600 dark:text-gray-300">{{ $dettaglio['cliente'] }}</div>
                            </td>
                            <td class="px-3 py-3 text-gray-900 dark:text-white font-medium">
                                {{ $dettaglio['location'] }}
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap report-locale-hide">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600">
                                    {{ $dettaglio['metodo_pagamento'] }}
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                <span class="visible-print" style="display: none; font-weight: bold; color: #111;">
                                    {{ $tavoli[$dettaglio['index']] ?: '—' }}
                                </span>
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.500ms="tavoli.{{ $dettaglio['index'] }}" 
                                    class="block w-full max-w-[100px] mx-auto rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs hidden-print"
                                    placeholder="Tavolo..."
                                >
                            </td>
                            <td class="px-3 py-3 text-gray-900 dark:text-white">
                                {{ $dettaglio['biglietto'] }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center font-bold text-gray-900 dark:text-white">
                                {{ $dettaglio['quantita'] }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">
                                € {{ number_format($dettaglio['prezzo'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right font-semibold text-gray-900 dark:text-white">
                                € {{ number_format($dettaglio['venduto'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right font-semibold text-primary-600 dark:text-primary-400 report-locale-hide">
                                € {{ number_format($dettaglio['provvigione'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right font-extrabold text-gray-900 dark:text-white report-locale-hide">
                                € {{ number_format($dettaglio['dare'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center hidden-print report-locale-hide">
                                <button 
                                    type="button" 
                                    wire:click="togglePagamentoLocation({{ $dettaglio['numeroOrdine'] }})" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold shadow-xs transition-all cursor-pointer"
                                    style="{{ $dettaglio['is_pagata_location'] ? 'background-color: #dcfce7 !important; color: #15803d !important; border: 1px solid #86efac !important;' : 'background-color: #fee2e2 !important; color: #b91c1c !important; border: 1px solid #fca5a5 !important;' }}"
                                    title="Clicca per cambiare lo stato di pagamento alla location"
                                >
                                    @if($dettaglio['is_pagata_location'])
                                        <x-heroicon-s-check-circle class="h-4 w-4" style="color: #16a34a !important; flex-shrink: 0;" />
                                        <span style="color: #15803d !important; font-weight: 700 !important;">Pagata</span>
                                    @else
                                        <x-heroicon-s-x-circle class="h-4 w-4" style="color: #dc2626 !important; flex-shrink: 0;" />
                                        <span style="color: #b91c1c !important; font-weight: 700 !important;">Non Pagata</span>
                                    @endif
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-slate-900 font-bold text-gray-900 dark:text-white border-t-2 border-gray-300 dark:border-slate-700">
                        <tr>
                            <td colspan="5" data-locale-colspan="4" class="px-3 py-4 text-right">
                                TOTALI COMPLESSIVI:
                            </td>
                            <td class="px-3 py-4 text-center font-extrabold">
                                {{ $risultati['totaleQuantita'] }}
                            </td>
                            <td class="px-3 py-4"></td>
                            <td class="px-3 py-4 text-right font-extrabold">
                                € {{ number_format($risultati['totaleVenduto'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-4 text-right font-extrabold text-primary-600 dark:text-primary-400 report-locale-hide">
                                € {{ number_format($risultati['totaleProvvigioni'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-4 text-right font-extrabold text-green-600 dark:text-green-400 text-sm report-locale-hide">
                                € {{ number_format($risultati['totaleDare'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-4 hidden-print report-locale-hide"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Tavoli Aggregati --}}
            @php $tavoliAggregati = $this->getTavoliAggregati(); @endphp
            @if(!empty($tavoliAggregati))
            <div class="mt-8 border-t border-gray-200 dark:border-slate-700 pt-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <x-heroicon-o-user-group class="h-5 w-5 text-primary-500" style="width: 20px; height: 20px;" />
                    <span>Raggruppamento Tavoli Richiesti / Assegnati</span>
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($tavoliAggregati as $tavoloGroup)
                    <div class="bg-gray-50 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-700/60 pb-2 mb-2">
                                <span class="font-extrabold text-sm text-gray-900 dark:text-white">
                                    {{ $tavoloGroup['nome'] }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-primary-100 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300">
                                    {{ $tavoloGroup['totalePersone'] }} persone
                                </span>
                            </div>
                            <ul class="text-xs space-y-1 text-gray-600 dark:text-gray-300 mt-2">
                                @foreach($tavoloGroup['clienti'] as $cl)
                                <li>• {{ $cl }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
</x-filament-panels::page>
