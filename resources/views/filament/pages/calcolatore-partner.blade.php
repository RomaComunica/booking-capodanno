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
                        x-on:click="window.print()"
                    >
                        Stampa / PDF
                    </x-filament::button>
                    @endif
                </div>
            </form>
        </div>

        {{-- Risultati --}}
        @if($risultati)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" id="risultati-calcolatore">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $risultati['partner'] }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Location: {{ $risultati['location'] }} | Periodo: {{ $risultati['periodo'] }}
                    </p>
                </div>
            </div>

            {{-- Tabella Dettagli --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Location
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
                                Provvigione
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($risultati['dettagli'] as $dettaglio)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $dettaglio['location'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
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
                                {{ $dettaglio['provvigione_label'] }}
                                <br>
                                <span class="text-xs">
                                    (€{{ number_format($dettaglio['provvigione'], 2, ',', '.') }})
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-green-50 dark:bg-green-900">
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
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
                                €{{ number_format($risultati['totaleProvvigione'], 2, ',', '.') }}
                            </td>
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
            .fi-header,
            nav,
            header,
            form {
                display: none !important;
            }
            
            .fi-main {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .fi-main-ctn {
                max-width: 100% !important;
            }

            #risultati-calcolatore {
                box-shadow: none !important;
                border: none !important;
            }

            @page {
                margin: 1cm;
                size: A4 landscape;
            }
        }
    </style>
</x-filament-panels::page>
