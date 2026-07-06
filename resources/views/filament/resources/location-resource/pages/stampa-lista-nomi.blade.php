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
            /* Force table styling for print */
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }
        }
    </style>

    <div class="no-print mb-4 flex justify-end">
        <x-filament::button
            tag="button"
            icon="heroicon-o-printer"
            onclick="window.print()"
        >
            Stampa Lista
        </x-filament::button>
    </div>

    <div class="mb-6 no-print bg-white p-4 rounded shadow-sm dark:bg-gray-800">
        {{ $this->form }}
    </div>

    @if(empty($this->data['location_id']) || empty($this->data['data_evento']))
        <div class="p-4 bg-gray-100 rounded text-center text-gray-500 no-print">
            Seleziona una Location e una Data per visualizzare la lista.
        </div>
    @else
        <div class="print-area">
            <h2 class="text-2xl font-bold mb-4">Lista Nomi - {{ \App\Models\Location::find($this->data['location_id'])->nomeLocation }}</h2>
            <p class="mb-4">Data Evento: {{ \Carbon\Carbon::parse($this->data['data_evento'])->format('d/m/Y') }}</p>

            @if($this->righe->isEmpty())
                <p>Nessun ingresso trovato per questa data.</p>
            @else
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 border">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Cognome e Nome</th>
                            <th class="px-6 py-3">Tipologia</th>
                            <th class="px-6 py-3">Voucher</th>
                            <th class="px-6 py-3">Q.tà</th>
                            <th class="px-6 py-3">Ordine</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->righe as $riga)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $riga->cognomeClienteOrdine }} {{ $riga->nomeClienteOrdine }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $riga->nomeIngressoOrdine }}
                                </td>
                                <td class="px-6 py-4 font-mono">
                                    {{ $riga->codiceVoucher }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $riga->quantitaIngressoOrdine }}
                                </td>
                                <td class="px-6 py-4">
                                    #{{ $riga->numeroOrdine }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="mt-8 text-sm text-gray-500 text-right">
                    Totale Ingressi: {{ $this->righe->sum('quantitaIngressoOrdine') }}
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
