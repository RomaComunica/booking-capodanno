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
                        color="success"
                        icon="heroicon-o-printer"
                        icon-position="before"
                        x-on:click="
                            let printWindow = window.open('', '_blank', 'width=1100,height=800');
                            printWindow.document.write('<html><head><title>Stampa Calcolatore Location</title>');
                            printWindow.document.write('<style>');
                            printWindow.document.write('body { font-family: system-ui, sans-serif; padding: 20px; color: #111; }');
                            printWindow.document.write('h2 { margin-top: 0; margin-bottom: 5px; font-size: 24px; font-weight: bold; }');
                            printWindow.document.write('h3 { margin-top: 30px; margin-bottom: 15px; font-size: 18px; font-weight: bold; border-bottom: 2px solid #333; padding-bottom: 5px; }');
                            printWindow.document.write('p { margin: 0 0 20px 0; color: #555; font-size: 14px; }');
                            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }');
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
                    @endif
                </div>
            </form>
        </div>

        {{-- Risultati --}}
        @if($risultati)
        <div id="risultati-calcolatore-stampa" class="space-y-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            
            {{-- Header Location --}}
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Report Location: {{ $risultati['location'] }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Periodo: {{ $risultati['periodo'] }}
                </p>
            </div>

            {{-- Tabella Dettagli --}}
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3 hidden-print" style="display: none;">
                    Elenco Biglietti Venduti
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Cliente
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Tavolo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Biglietto
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Quantità
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Prezzo
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Venduto
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Da Dare
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($risultati['dettagli'] as $dettaglio)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $dettaglio['cliente'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <span class="visible-print" style="display: none; font-weight: bold; color: #111;">
                                        {{ $tavoli[$dettaglio['index']] ?: '—' }}
                                    </span>
                                    <input 
                                        type="text" 
                                        wire:model.live.debounce.500ms="tavoli.{{ $dettaglio['index'] }}" 
                                        class="block w-full max-w-[120px] mx-auto rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm hidden-print"
                                        placeholder="Tavolo..."
                                    >
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $dettaglio['biglietto'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white">
                                    {{ $dettaglio['quantita'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white">
                                    €{{ number_format($dettaglio['prezzo'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white">
                                    €{{ number_format($dettaglio['venduto'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-semibold text-green-600 dark:text-green-400">
                                    €{{ number_format($dettaglio['dare'], 2, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-green-50 dark:bg-green-950/30">
                            <tr class="bg-green-light">
                                <td colspan="3" class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                    TOTALI:
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">
                                    {{ $risultati['totaleQuantita'] }}
                                </td>
                                <td class="px-6 py-4"></td>
                                <td class="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">
                                    €{{ number_format($risultati['totaleVenduto'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-green-600 dark:text-green-400 text-lg">
                                    €{{ number_format($risultati['totaleDare'], 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Tavoli List --}}
            @php
                $tavoliAggregati = $this->getTavoliAggregati();
            @endphp

            @if(count($tavoliAggregati) > 0)
            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                    Aggregazione Tavoli
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Tavolo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Clienti
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Totale Voucher
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Totale Persone
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($tavoliAggregati as $tavolo)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $tavolo['nome'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ implode(', ', $tavolo['clienti']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white">
                                    {{ $tavolo['totaleVoucher'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-900 dark:text-white">
                                    {{ $tavolo['totalePersone'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
</x-filament-panels::page>
