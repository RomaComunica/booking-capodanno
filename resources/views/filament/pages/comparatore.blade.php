<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/filament-theme-glossy.css') }}?v={{ time() }}">
    <style>
        /* Direct Page Overrides for Comparatore High Contrast */
        .dark select,
        .dark select option,
        select option {
            background-color: #1e293b !important;
            color: #ffffff !important;
        }
        .dark label, .dark .fi-fo-field-wrp-label, .dark .fi-fo-field-wrp-label span {
            color: #f8fafc !important;
            font-weight: 700 !important;
        }
        .dark input, .dark .fi-input-wrp, .dark .fi-select-input {
            background-color: #1e293b !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.25) !important;
        }
        .dark .glossy-table-row:hover,
        .dark .glossy-table-row:hover td {
            background-color: #1e293b !important;
            color: #ffffff !important;
        }
        .glossy-table-row:hover,
        .glossy-table-row:hover td {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
    </style>

    <!-- Sezione Fissa: Confronto del Giorno di Oggi con lo Scorso Anno -->
    @if($oggiStats)
        <div class="glossy-card p-6 mb-6">
            <div class="flex items-center gap-2 mb-4 border-b border-gray-200/60 dark:border-slate-700/60 pb-3">
                <x-filament::icon icon="heroicon-o-presentation-chart-line" class="h-6 w-6 text-primary-500" />
                <h3 class="text-md font-bold" style="color: #ffffff;">Confronto Rapido: Oggi vs Stesso Giorno Anno Scorso</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Fatturato Oggi -->
                <div class="bg-white/60 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200/60 dark:border-slate-700/60 flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wider font-bold" style="color: #cbd5e1;">Fatturato Lordo</div>
                        <div class="text-2xl font-extrabold mt-1" style="color: #ffffff;">
                            € {{ number_format($oggiStats['revenue_2'], 2, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-xs mt-2 pt-2 border-t border-gray-200/60 dark:border-slate-700/60 flex items-center justify-between font-semibold" style="color: #cbd5e1;">
                        <span>Scorso Anno ({{ $oggiStats['periodo_1_label'] }}): € {{ number_format($oggiStats['revenue_1'], 2, ',', '.') }}</span>
                        @if($oggiStats['revenue_diff'] > 0)
                            <span class="font-bold inline-flex items-center gap-0.5" style="color: #29f48f;">
                                <x-heroicon-m-arrow-trending-up class="h-3.5 w-3.5" />
                                +{{ $oggiStats['revenue_diff_pct'] }}%
                            </span>
                        @elseif($oggiStats['revenue_diff'] < 0)
                            <span class="font-bold inline-flex items-center gap-0.5" style="color: #f472b6;">
                                <x-heroicon-m-arrow-trending-down class="h-3.5 w-3.5" />
                                {{ $oggiStats['revenue_diff_pct'] }}%
                            </span>
                        @else
                            <span style="color: #94a3b8;">=</span>
                        @endif
                    </div>
                </div>

                <!-- Biglietti Oggi -->
                <div class="bg-white/60 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200/60 dark:border-slate-700/60 flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wider font-bold" style="color: #cbd5e1;">Biglietti Venduti</div>
                        <div class="text-2xl font-extrabold mt-1" style="color: #ffffff;">
                            {{ number_format($oggiStats['tickets_2'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-xs mt-2 pt-2 border-t border-gray-200/60 dark:border-slate-700/60 flex items-center justify-between font-semibold" style="color: #cbd5e1;">
                        <span>Scorso Anno ({{ $oggiStats['periodo_1_label'] }}): {{ number_format($oggiStats['tickets_1'], 0, ',', '.') }}</span>
                        @if($oggiStats['tickets_diff'] > 0)
                            <span class="font-bold inline-flex items-center gap-0.5" style="color: #29f48f;">
                                <x-heroicon-m-arrow-trending-up class="h-3.5 w-3.5" />
                                +{{ $oggiStats['tickets_diff_pct'] }}%
                            </span>
                        @elseif($oggiStats['tickets_diff'] < 0)
                            <span class="font-bold inline-flex items-center gap-0.5" style="color: #f472b6;">
                                <x-heroicon-m-arrow-trending-down class="h-3.5 w-3.5" />
                                {{ $oggiStats['tickets_diff_pct'] }}%
                            </span>
                        @else
                            <span style="color: #94a3b8;">=</span>
                        @endif
                    </div>
                </div>

                <!-- Ordini Oggi -->
                <div class="bg-white/60 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200/60 dark:border-slate-700/60 flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wider font-bold" style="color: #cbd5e1;">Ordini Conclusi</div>
                        <div class="text-2xl font-extrabold mt-1" style="color: #ffffff;">
                            {{ number_format($oggiStats['orders_2'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-xs mt-2 pt-2 border-t border-gray-200/60 dark:border-slate-700/60 flex items-center justify-between font-semibold" style="color: #cbd5e1;">
                        <span>Scorso Anno ({{ $oggiStats['periodo_1_label'] }}): {{ number_format($oggiStats['orders_1'], 0, ',', '.') }}</span>
                        @if($oggiStats['orders_diff'] > 0)
                            <span class="font-bold inline-flex items-center gap-0.5" style="color: #29f48f;">
                                <x-heroicon-m-arrow-trending-up class="h-3.5 w-3.5" />
                                +{{ $oggiStats['orders_diff_pct'] }}%
                            </span>
                        @elseif($oggiStats['orders_diff'] < 0)
                            <span class="font-bold inline-flex items-center gap-0.5" style="color: #f472b6;">
                                <x-heroicon-m-arrow-trending-down class="h-3.5 w-3.5" />
                                {{ $oggiStats['orders_diff_pct'] }}%
                            </span>
                        @else
                            <span style="color: #94a3b8;">=</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Form di Configurazione -->
    <div class="glossy-card p-6">
        <form wire:submit.prevent="confronta">
            {{ $this->form }}
            
            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" icon="heroicon-m-arrow-path-rounded-square" size="lg">
                    Confronta Periodi
                </x-filament::button>
            </div>
        </form>
    </div>

    @if($risultati)
        <!-- Intestazione Risultati -->
        <div class="mt-4 mb-2">
            <h2 class="text-xl font-bold" style="color: #ffffff;">
                Risultati del Confronto
            </h2>
            <p class="text-sm mt-1" style="color: #cbd5e1;">
                Confronto tra <span class="font-bold" style="color: #ffffff;">{{ $risultati['periodo_1_label'] }}</span> (Precedente) e <span class="font-bold" style="color: #29f48f;">{{ $risultati['periodo_2_label'] }}</span> (Successivo)
            </p>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card Fatturato -->
            <div class="glossy-card glossy-kpi-card p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold uppercase tracking-wider" style="color: #cbd5e1;">Fatturato Lordo</span>
                        <x-filament::icon icon="heroicon-o-currency-euro" class="h-6 w-6 text-primary-500" />
                    </div>
                    <div class="text-2xl font-extrabold" style="color: #ffffff;">
                        € {{ number_format($risultati['revenue_2'], 2, ',', '.') }}
                    </div>
                    <div class="text-sm mt-1 font-semibold" style="color: #cbd5e1;">
                        Precedente: € {{ number_format($risultati['revenue_1'], 2, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-200/60 dark:border-slate-700/60 flex items-center justify-between font-semibold">
                    <span class="text-sm" style="color: #cbd5e1;">Variazione:</span>
                    @if($risultati['revenue_diff'] > 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full" style="background-color: rgba(41, 244, 143, 0.15); color: #29f48f; border: 1px solid rgba(41, 244, 143, 0.4);">
                            <x-heroicon-m-arrow-trending-up class="h-4 w-4" />
                            +€{{ number_format($risultati['revenue_diff'], 2, ',', '.') }} 
                            @if($risultati['revenue_diff_pct'] !== null)
                                ({{ $risultati['revenue_diff_pct'] }}%)
                            @endif
                        </span>
                    @elseif($risultati['revenue_diff'] < 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full" style="background-color: rgba(244, 114, 182, 0.15); color: #f472b6; border: 1px solid rgba(244, 114, 182, 0.4);">
                            <x-heroicon-m-arrow-trending-down class="h-4 w-4" />
                            -€{{ number_format(abs($risultati['revenue_diff']), 2, ',', '.') }} 
                            @if($risultati['revenue_diff_pct'] !== null)
                                ({{ $risultati['revenue_diff_pct'] }}%)
                            @endif
                        </span>
                    @else
                        <span class="inline-flex items-center text-sm font-semibold px-2.5 py-0.5 rounded-full" style="color: #cbd5e1; background-color: #1e293b;">
                            = € 0,00
                        </span>
                    @endif
                </div>
            </div>

            <!-- Card Biglietti Venduti -->
            <div class="glossy-card glossy-kpi-card p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold uppercase tracking-wider" style="color: #cbd5e1;">Biglietti Venduti</span>
                        <x-filament::icon icon="heroicon-o-ticket" class="h-6 w-6 text-primary-500" />
                    </div>
                    <div class="text-2xl font-extrabold" style="color: #ffffff;">
                        {{ number_format($risultati['tickets_2'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm mt-1 font-semibold" style="color: #cbd5e1;">
                        Precedente: {{ number_format($risultati['tickets_1'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-200/60 dark:border-slate-700/60 flex items-center justify-between font-semibold">
                    <span class="text-sm" style="color: #cbd5e1;">Variazione:</span>
                    @if($risultati['tickets_diff'] > 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full" style="background-color: rgba(41, 244, 143, 0.15); color: #29f48f; border: 1px solid rgba(41, 244, 143, 0.4);">
                            <x-heroicon-m-arrow-trending-up class="h-4 w-4" />
                            +{{ number_format($risultati['tickets_diff'], 0, ',', '.') }}
                            @if($risultati['tickets_diff_pct'] !== null)
                                ({{ $risultati['tickets_diff_pct'] }}%)
                            @endif
                        </span>
                    @elseif($risultati['tickets_diff'] < 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full" style="background-color: rgba(244, 114, 182, 0.15); color: #f472b6; border: 1px solid rgba(244, 114, 182, 0.4);">
                            <x-heroicon-m-arrow-trending-down class="h-4 w-4" />
                            -{{ number_format(abs($risultati['tickets_diff']), 0, ',', '.') }}
                            @if($risultati['tickets_diff_pct'] !== null)
                                ({{ $risultati['tickets_diff_pct'] }}%)
                            @endif
                        </span>
                    @else
                        <span class="inline-flex items-center text-sm font-semibold px-2.5 py-0.5 rounded-full" style="color: #cbd5e1; background-color: #1e293b;">
                            = 0
                        </span>
                    @endif
                </div>
            </div>

            <!-- Card Ordini Totali -->
            <div class="glossy-card glossy-kpi-card p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold uppercase tracking-wider" style="color: #cbd5e1;">Ordini Conclusi</span>
                        <x-filament::icon icon="heroicon-o-shopping-cart" class="h-6 w-6 text-primary-500" />
                    </div>
                    <div class="text-2xl font-extrabold" style="color: #ffffff;">
                        {{ number_format($risultati['orders_2'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm mt-1 font-semibold" style="color: #cbd5e1;">
                        Precedente: {{ number_format($risultati['orders_1'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-200/60 dark:border-slate-700/60 flex items-center justify-between font-semibold">
                    <span class="text-sm" style="color: #cbd5e1;">Variazione:</span>
                    @if($risultati['orders_diff'] > 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full" style="background-color: rgba(41, 244, 143, 0.15); color: #29f48f; border: 1px solid rgba(41, 244, 143, 0.4);">
                            <x-heroicon-m-arrow-trending-up class="h-4 w-4" />
                            +{{ number_format($risultati['orders_diff'], 0, ',', '.') }}
                            @if($risultati['orders_diff_pct'] !== null)
                                ({{ $risultati['orders_diff_pct'] }}%)
                            @endif
                        </span>
                    @elseif($risultati['orders_diff'] < 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full" style="background-color: rgba(244, 114, 182, 0.15); color: #f472b6; border: 1px solid rgba(244, 114, 182, 0.4);">
                            <x-heroicon-m-arrow-trending-down class="h-4 w-4" />
                            -{{ number_format(abs($risultati['orders_diff']), 0, ',', '.') }}
                            @if($risultati['orders_diff_pct'] !== null)
                                ({{ $risultati['orders_diff_pct'] }}%)
                            @endif
                        </span>
                    @else
                        <span class="inline-flex items-center text-sm font-semibold px-2.5 py-0.5 rounded-full" style="color: #cbd5e1; background-color: #1e293b;">
                            = 0
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Focalizzazione Mese per Grafici Temporali -->
        <div x-data="{ filterMonth: 'all' }" x-init="$watch('filterMonth', value => window.dispatchEvent(new CustomEvent('filter-charts-month', { detail: value })))">
            @if(!$risultati['isMonthly'])
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-start sm:items-center glossy-card p-4 gap-4">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-magnifying-glass-plus" class="h-5 w-5 text-primary-500" />
                        <span class="text-sm font-bold" style="color: #ffffff;">Zoom/Focalizza l'andamento grafico su un singolo mese:</span>
                    </div>
                    <select x-model="filterMonth" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #475569 !important; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer;">
                        <option value="all" style="background-color: #0f172a !important; color: #ffffff !important;">Tutto il periodo</option>
                        <option value="01" style="background-color: #0f172a !important; color: #ffffff !important;">Gennaio</option>
                        <option value="02" style="background-color: #0f172a !important; color: #ffffff !important;">Febbraio</option>
                        <option value="03" style="background-color: #0f172a !important; color: #ffffff !important;">Marzo</option>
                        <option value="04" style="background-color: #0f172a !important; color: #ffffff !important;">Aprile</option>
                        <option value="05" style="background-color: #0f172a !important; color: #ffffff !important;">Maggio</option>
                        <option value="06" style="background-color: #0f172a !important; color: #ffffff !important;">Giugno</option>
                        <option value="07" style="background-color: #0f172a !important; color: #ffffff !important;">Luglio</option>
                        <option value="08" style="background-color: #0f172a !important; color: #ffffff !important;">Agosto</option>
                        <option value="09" style="background-color: #0f172a !important; color: #ffffff !important;">Settembre</option>
                        <option value="10" style="background-color: #0f172a !important; color: #ffffff !important;">Ottobre</option>
                        <option value="11" style="background-color: #0f172a !important; color: #ffffff !important;">Novembre</option>
                        <option value="12" style="background-color: #0f172a !important; color: #ffffff !important;">Dicembre</option>
                    </select>
                </div>
            @endif

            <!-- Sezione Grafici Temporali -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <!-- Grafico Fatturato nel tempo -->
                <x-filament::section class="h-full flex flex-col">
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="h-6 w-6 text-primary-500" />
                            <span>Andamento temporale del Fatturato</span>
                        </div>
                    </x-slot>
                    <div class="h-[300px] relative w-full" wire:ignore>
                        <canvas id="revenueComparisonChart"></canvas>
                    </div>
                </x-filament::section>

                <!-- Grafico Biglietti nel tempo -->
                <x-filament::section class="h-full flex flex-col">
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-ticket" class="h-6 w-6 text-primary-500" />
                            <span>Andamento temporale dei Biglietti Venduti</span>
                        </div>
                    </x-slot>
                    <div class="h-[300px] relative w-full" wire:ignore>
                        <canvas id="ticketsComparisonChart"></canvas>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <!-- Dettaglio Location -->
        <x-filament::section class="mt-6">
            <x-slot name="heading">Dettaglio Vendite per Location</x-slot>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                    <thead class="bg-gray-100 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Location</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Biglietti ({{ $risultati['periodo_1_short'] ?? 'P1' }})</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Biglietti ({{ $risultati['periodo_2_short'] ?? 'P2' }})</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Delta Biglietti</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Fatturato ({{ $risultati['periodo_1_short'] ?? 'P1' }})</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Fatturato ({{ $risultati['periodo_2_short'] ?? 'P2' }})</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Delta Fatturato</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                        @forelse($risultati['locationBreakdown'] as $loc)
                            <tr class="glossy-table-row transition-colors">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                    {{ $loc['nomeLocation'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-200">
                                    {{ number_format($loc['tickets_1'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white">
                                    {{ number_format($loc['tickets_2'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($loc['tickets_diff'] > 0)
                                        <span class="text-green-600 dark:text-green-400 font-bold">+{{ $loc['tickets_diff'] }} ({{ $loc['tickets_diff_pct'] }}%)</span>
                                    @elseif($loc['tickets_diff'] < 0)
                                        <span class="text-danger-600 dark:text-danger-400 font-bold">{{ $loc['tickets_diff'] }} ({{ $loc['tickets_diff_pct'] }}%)</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">=</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-200">
                                    € {{ number_format($loc['revenue_1'], 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                                    € {{ number_format($loc['revenue_2'], 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($loc['revenue_diff'] > 0)
                                        <span class="text-green-600 dark:text-green-400 font-bold">+€{{ number_format($loc['revenue_diff'], 2, ',', '.') }} ({{ $loc['revenue_diff_pct'] }}%)</span>
                                    @elseif($loc['revenue_diff'] < 0)
                                        <span class="text-danger-600 dark:text-danger-400 font-bold">-€{{ number_format(abs($loc['revenue_diff']), 2, ',', '.') }} ({{ $loc['revenue_diff_pct'] }}%)</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">=</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Nessuna vendita registrata nei periodi selezionati.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <!-- Chart.js Logic -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                let revenueChartInstance = null;
                let ticketsChartInstance = null;
                let currentChartData = null;
                let currentFilterMonth = 'all';

                const drawCharts = (data, filterMonth = 'all') => {
                    if (!data) return;
                    currentChartData = data;

                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#cbd5e1' : '#475569';
                    const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.06)';

                    let labels = data.chartLabels;
                    const p1Label = data.periodo_1_label;
                    const p2Label = data.periodo_2_label;

                    // Extract datasets
                    let r1 = data.chartData1.map(d => d.revenue);
                    let r2 = data.chartData2.map(d => d.revenue);
                    let t1 = data.chartData1.map(d => d.tickets);
                    let t2 = data.chartData2.map(d => d.tickets);

                    // Filter by month client-side if requested and it is daily data
                    if (!data.isMonthly && filterMonth !== 'all') {
                        labels = [];
                        r1 = [];
                        r2 = [];
                        t1 = [];
                        t2 = [];

                        const count = Math.max(data.chartData1.length, data.chartData2.length);
                        for (let i = 0; i < count; i++) {
                            const d1 = data.chartData1[i] || {};
                            const d2 = data.chartData2[i] || {};
                            const dateStr = d2.date || d1.date;
                            if (dateStr) {
                                const parts = dateStr.split('-');
                                const monthPart = parts[1]; // MM
                                if (monthPart === filterMonth) {
                                    const dayPart = parts[2]; // DD
                                    labels.push("G. " + parseInt(dayPart));
                                    r1.push(d1.revenue || 0);
                                    r2.push(d2.revenue || 0);
                                    t1.push(d1.tickets || 0);
                                    t2.push(d2.tickets || 0);
                                }
                            }
                        }

                        // If no data points found for this month, show a fallback label
                        if (labels.length === 0) {
                            labels = ['Nessun dato'];
                            r1 = [0];
                            r2 = [0];
                            t1 = [0];
                            t2 = [0];
                        }
                    }

                    // Destroy existing instances
                    if (revenueChartInstance) revenueChartInstance.destroy();
                    if (ticketsChartInstance) ticketsChartInstance.destroy();

                    // 1. Revenue Chart
                    const ctxRev = document.getElementById('revenueComparisonChart').getContext('2d');
                    revenueChartInstance = new Chart(ctxRev, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: p1Label,
                                    data: r1,
                                    borderColor: '#94a3b8',
                                    backgroundColor: 'rgba(148, 163, 184, 0.15)',
                                    fill: true,
                                    tension: 0.3
                                },
                                {
                                    label: p2Label,
                                    data: r2,
                                    borderColor: '#38bdf8',
                                    backgroundColor: 'rgba(56, 189, 248, 0.2)',
                                    fill: true,
                                    tension: 0.3
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { color: textColor, font: { weight: 'bold' } }
                                },
                            },
                            scales: {
                                x: {
                                    ticks: { color: textColor },
                                    grid: { color: gridColor }
                                },
                                y: {
                                    ticks: {
                                        color: textColor,
                                        callback: function(value) { return '€ ' + value.toLocaleString('it-IT'); }
                                    },
                                    grid: { color: gridColor }
                                }
                            }
                        }
                    });

                    // 2. Tickets Chart
                    const ctxTick = document.getElementById('ticketsComparisonChart').getContext('2d');
                    ticketsChartInstance = new Chart(ctxTick, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: p1Label,
                                    data: t1,
                                    backgroundColor: '#94a3b8',
                                    borderRadius: 4
                                },
                                {
                                    label: p2Label,
                                    data: t2,
                                    backgroundColor: '#DD076D',
                                    borderRadius: 4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { color: textColor, font: { weight: 'bold' } }
                                },
                            },
                            scales: {
                                x: {
                                    ticks: { color: textColor },
                                    grid: { color: gridColor }
                                },
                                y: {
                                    ticks: { color: textColor },
                                    grid: { color: gridColor }
                                }
                            }
                        }
                    });
                };

                // Draw initial charts
                @if($risultati)
                    drawCharts(@json($risultati), currentFilterMonth);
                @endif

                // Listen to Livewire events for updates
                window.addEventListener('draw-comparison-charts', (event) => {
                    const data = event.detail.resultados || event.detail[0] || event.detail;
                    drawCharts(data, currentFilterMonth);
                });

                // Listen to Alpine.js month filter events
                window.addEventListener('filter-charts-month', (event) => {
                    currentFilterMonth = event.detail;
                    drawCharts(currentChartData, currentFilterMonth);
                });
            });
        </script>
    @endif
</x-filament-panels::page>
