<x-filament-panels::page>
    <style>
        @media print {
            .fi-sidebar, .fi-header, .fi-topbar, .fi-form, .no-print {
                display: none !important;
            }
            .fi-main {
                margin: 0 !important;
                padding: 0 !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }
            th {
                background-color: #f3f4f6;
            }
        }
    </style>

    <div class="no-print mb-4 flex justify-end">
        <x-filament::button
            tag="button"
            icon="heroicon-o-printer"
            onclick="window.print()"
        >
            Stampa Report
        </x-filament::button>
    </div>

    <div class="mb-6 no-print bg-white p-4 rounded shadow-sm dark:bg-gray-800">
        {{ $this->form }}
    </div>

    @if(empty($this->report))
        <div class="p-4 bg-gray-100 rounded text-center text-gray-500 no-print">
            Seleziona un periodo per visualizzare il report.
        </div>
    @else
        <div class="print-area bg-white p-6 rounded shadow-sm dark:bg-gray-800">
            <div class="mb-8">
                <h2 class="text-3xl font-bold mb-2">Report Entrate</h2>
                <div class="text-gray-600 dark:text-gray-400">
                    <p>Periodo: <strong>{{ $this->report['periodo']['dal'] }}</strong> - <strong>{{ $this->report['periodo']['al'] }}</strong></p>
                    @if(!empty($this->data['location_id']))
                        <p>Location Filtro: <strong>{{ \App\Models\Location::find($this->data['location_id'])->nomeLocation }}</strong></p>
                    @endif
                    <p class="text-sm mt-1">Generato il: {{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <table class="w-full text-base text-left rtl:text-right text-gray-700 dark:text-gray-300 border mb-8">
                <thead class="text-sm uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 border">Metodo Pagamento</th>
                        <th class="px-4 py-3 border">Location</th>
                        <th class="px-4 py-3 border">Tipologia Ticket</th>
                        <th class="px-4 py-3 border text-center">N. Ordini</th>
                        <th class="px-4 py-3 border text-center">Q.tà Biglietti</th>
                        <th class="px-4 py-3 border text-right">Incasso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->report['dettagli'] as $row)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-3 border font-bold text-gray-900 dark:text-white">
                                {{ $row['metodo'] }}
                            </td>
                            <td class="px-4 py-3 border">
                                {{ $row['location'] }}
                            </td>
                            <td class="px-4 py-3 border text-gray-600 dark:text-gray-400">
                                {{ $row['ticket'] }}
                            </td>
                            <td class="px-4 py-3 border text-center">
                                {{ $row['ordini'] }}
                            </td>
                            <td class="px-4 py-3 border text-center font-medium">
                                {{ $row['quantita'] }}
                            </td>
                            <td class="px-4 py-3 border text-right font-mono">
                                € {{ number_format($row['totale'], 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-50 dark:bg-gray-800 font-bold text-lg">
                        <td class="px-4 py-4 border text-right" colspan="5">TOTALE COMPLESSIVO</td>
                        <td class="px-6 py-4 border text-right text-green-600 dark:text-green-400">
                            € {{ number_format($this->report['totale'], 2, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-12 pt-8 border-t border-gray-300 text-center text-sm text-gray-500">
                <p>Documento generato dal sistema gestionale</p>
            </div>
        </div>
    @endif
</x-filament-panels::page>
