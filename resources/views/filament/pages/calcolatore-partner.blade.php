<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form wire:submit="calcola">
                {{ $this->form }}
                
                <div class="mt-6 flex items-center gap-3">
                    <x-filament::button type="submit" size="lg" icon="heroicon-o-calculator" icon-position="before">
                        Calcola
                    </x-filament::button>

                    @if($risultati)
                    <x-filament::button
                        type="button"
                        size="lg"
                        color="info"
                        icon="heroicon-o-printer"
                        icon-position="before"
                        class="hidden-print"
                        x-on:click="
                            let printWindow = window.open('', '_blank', 'width=1100,height=800');
                            printWindow.document.write('<html><head><title>Stampa Calcolatore Partner</title>');
                            printWindow.document.write('<style>');
                            printWindow.document.write('body { font-family: system-ui, sans-serif; padding: 20px; color: #111; }');
                            printWindow.document.write('h2 { margin-top: 0; margin-bottom: 5px; font-size: 24px; font-weight: bold; }');
                            printWindow.document.write('p { margin: 0 0 20px 0; color: #555; font-size: 14px; }');
                            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }');
                            printWindow.document.write('th, td { padding: 6px 8px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: top; }');
                            printWindow.document.write('th { background-color: #f5f5f5; font-weight: bold; text-transform: uppercase; font-size: 10px; color: #555; }');
                            printWindow.document.write('.text-center { text-align: center; }');
                            printWindow.document.write('.text-right { text-align: right; }');
                            printWindow.document.write('.font-bold { font-weight: bold; }');
                            printWindow.document.write('.text-lg { font-size: 14px; }');
                            printWindow.document.write('.text-xs { font-size: 11px; }');
                            printWindow.document.write('.text-green-600 { color: #16a34a; }');
                            printWindow.document.write('.bg-green-50 { background-color: #f0fdf4; }');
                            printWindow.document.write('tfoot tr { background-color: #f0fdf4; font-weight: bold; }');
                            printWindow.document.write('.hidden-print { display: none !important; }');
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
                    @endif
                </div>
            </form>
        </div>

        {{-- Risultati --}}
        @if($risultati)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" id="risultati-calcolatore-stampa">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-user-group class="h-6 w-6 text-primary-500" />
                        <span>Partner: {{ $risultati['partner'] }}</span>
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Location: <span class="font-bold text-gray-900 dark:text-white">{{ $risultati['location'] }}</span> | 
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
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- Totale Venduto Partner -->
                <div class="bg-gray-50 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Totale Venduto</span>
                    <div class="text-xl font-extrabold text-gray-900 dark:text-white mt-1">
                        € {{ number_format($risultati['totaleVenduto'], 2, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-gray-500 mt-1">{{ $risultati['totaleQuantita'] }} biglietti totali</span>
                </div>

                <!-- Totale Provvigione Spettante -->
                <div class="bg-gray-50 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400">Provvigione Spettante</span>
                    <div class="text-xl font-extrabold text-primary-600 dark:text-primary-400 mt-1">
                        € {{ number_format($risultati['totaleProvvigione'], 2, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-gray-500 mt-1">Totale dovuto al partner</span>
                </div>

                <!-- Provvigioni Già Pagate -->
                <div class="bg-green-50 dark:bg-green-950/30 rounded-xl p-4 border border-green-200 dark:border-green-800/60 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-green-700 dark:text-green-400">Già Pagato</span>
                    <div class="text-xl font-extrabold text-green-700 dark:text-green-400 mt-1">
                        € {{ number_format($risultati['totaleGiaPagato'], 2, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-green-600/80 mt-1">Liquidato al partner</span>
                </div>

                <!-- Debito Residuo da Pagare -->
                <div class="bg-amber-50 dark:bg-amber-950/30 rounded-xl p-4 border border-amber-200 dark:border-amber-800/60 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Da Pagare (Residuo)</span>
                    <div class="text-xl font-extrabold text-amber-700 dark:text-amber-400 mt-1">
                        € {{ number_format($risultati['totaleDaPagareResiduo'], 2, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-amber-600/80 mt-1">Ancora da saldare</span>
                </div>

                <!-- Totale Biglietti -->
                <div class="bg-gray-50 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Totale Biglietti</span>
                    <div class="text-xl font-extrabold text-gray-900 dark:text-white mt-1">
                        {{ number_format($risultati['totaleQuantita'], 0, ',', '.') }}
                    </div>
                    <span class="text-[11px] text-gray-500 mt-1">Biglietti venduti</span>
                </div>
            </div>

            {{-- Tabella Dettagli Ordini --}}
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
                            <th class="px-3 py-3 text-center font-bold text-gray-700 dark:text-gray-300 uppercase">
                                Metodo Pag.
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
                            <th class="px-3 py-3 text-right font-bold text-primary-600 dark:text-primary-400 uppercase">
                                Provv. Partner
                            </th>
                            <th class="px-3 py-3 text-center font-bold text-gray-700 dark:text-gray-300 uppercase hidden-print">
                                Pagato Partner
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
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-slate-600">
                                    {{ $dettaglio['metodo_pagamento'] }}
                                </span>
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
                            <td class="px-3 py-3 whitespace-nowrap text-right font-bold text-primary-600 dark:text-primary-400">
                                <div>{{ $dettaglio['provvigione_label'] }}</div>
                                <div class="text-[11px] font-normal text-gray-500 dark:text-gray-400">€ {{ number_format($dettaglio['provvigione'], 2, ',', '.') }}</div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center hidden-print">
                                <button 
                                    type="button" 
                                    wire:click="togglePagamentoPartner({{ $dettaglio['numeroOrdine'] }})" 
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold shadow-sm transition-all cursor-pointer {{ $dettaglio['is_pagato_partner'] ? 'bg-green-100 dark:bg-green-950/60 text-green-700 dark:text-green-300 border border-green-300 dark:border-green-700 hover:bg-green-200' : 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700 hover:bg-amber-200' }}"
                                    title="Clicca per cambiare lo stato di pagamento al partner"
                                >
                                    @if($dettaglio['is_pagato_partner'])
                                        <x-heroicon-s-check-circle class="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
                                        <span>Pagato</span>
                                    @else
                                        <x-heroicon-s-clock class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" />
                                        <span>Da Pagare</span>
                                    @endif
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-slate-900 font-bold">
                        <tr>
                            <td colspan="4" class="px-3 py-3 text-right uppercase text-gray-700 dark:text-gray-300">
                                TOTALI:
                            </td>
                            <td class="px-3 py-3 text-center text-gray-900 dark:text-white">
                                {{ number_format($risultati['totaleQuantita'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3"></td>
                            <td class="px-3 py-3 text-right text-gray-900 dark:text-white">
                                € {{ number_format($risultati['totaleVenduto'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-right text-primary-600 dark:text-primary-400 text-sm">
                                € {{ number_format($risultati['totaleProvvigione'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 hidden-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
    </div>

    <style>
        @media print {
            .hidden-print,
            .fi-sidebar,
            .fi-topbar,
            header,
            footer {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            #risultati-calcolatore-stampa {
                box-shadow: none !important;
                padding: 0 !important;
            }
        }
    </style>
</x-filament-panels::page>
