<x-filament-panels::page>
    <!-- Sezione Fissa: Confronto del Giorno di Oggi con lo Scorso Anno -->
    @if($oggiStats)
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-800 mb-6">
            <div class="flex items-center gap-2 mb-4 border-b border-gray-50 dark:border-gray-850 pb-3">
                <x-filament::icon icon="heroicon-o-presentation-chart-line" class="h-6 w-6 text-primary-500" />
                <h3 class="text-md font-bold text-gray-900 dark:text-white">Confronto Rapido: Oggi vs Stesso Giorno Anno Scorso</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Fatturato Oggi -->
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-4 border border-gray-100 dark:border-gray-800 flex flex-col justify-between">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Fatturato Lordo</div>
                        <div class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">
                            € {{ number_format($oggiStats['revenue_2'], 2, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <span>Scorso Anno ({{ $oggiStats['periodo_1_label'] }}): € {{ number_format($oggiStats['revenue_1'], 2, ',', '.') }}</span>
                        @if($oggiStats['revenue_diff'] > 0)
                            <span class="text-green-600 font-semibold inline-flex items-center gap-0.5">
                                <x-heroicon-m-arrow-trending-up class="h-3 w-3" />
                                +{{ $oggiStats['revenue_diff_pct'] }}%
                            </span>
                        @elseif($oggiStats['revenue_diff'] < 0)
                            <span class="text-danger-600 font-semibold inline-flex items-center gap-0.5">
                                <x-heroicon-m-arrow-trending-down class="h-3 w-3" />
                                {{ $oggiStats['revenue_diff_pct'] }}%
                            </span>
                        @else
                            <span class="text-gray-400">=</span>
                        @endif
                    </div>
                </div>

                <!-- Biglietti Oggi -->
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-4 border border-gray-100 dark:border-gray-800 flex flex-col justify-between">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Biglietti Venduti</div>
                        <div class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">
                            {{ number_format($oggiStats['tickets_2'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <span>Scorso Anno ({{ $oggiStats['periodo_1_label'] }}): {{ number_format($oggiStats['tickets_1'], 0, ',', '.') }}</span>
                        @if($oggiStats['tickets_diff'] > 0)
                            <span class="text-green-600 font-semibold inline-flex items-center gap-0.5">
                                <x-heroicon-m-arrow-trending-up class="h-3 w-3" />
                                +{{ $oggiStats['tickets_diff_pct'] }}%
                            </span>
                        @elseif($oggiStats['tickets_diff'] < 0)
                            <span class="text-danger-600 font-semibold inline-flex items-center gap-0.5">
                                <x-heroicon-m-arrow-trending-down class="h-3 w-3" />
                                {{ $oggiStats['tickets_diff_pct'] }}%
                            </span>
                        @else
                            <span class="text-gray-400">=</span>
                        @endif
                    </div>
                </div>

                <!-- Ordini Oggi -->
                <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-4 border border-gray-100 dark:border-gray-800 flex flex-col justify-between">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Ordini Conclusi</div>
                        <div class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">
                            {{ number_format($oggiStats['orders_2'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <span>Scorso Anno ({{ $oggiStats['periodo_1_label'] }}): {{ number_format($oggiStats['orders_1'], 0, ',', '.') }}</span>
                        @if($oggiStats['orders_diff'] > 0)
                            <span class="text-green-600 font-semibold inline-flex items-center gap-0.5">
                                <x-heroicon-m-arrow-trending-up class="h-3 w-3" />
                                +{{ $oggiStats['orders_diff_pct'] }}%
                            </span>
                        @elseif($oggiStats['orders_diff'] < 0)
                            <span class="text-danger-600 font-semibold inline-flex items-center gap-0.5">
                                <x-heroicon-m-arrow-trending-down class="h-3 w-3" />
                                {{ $oggiStats['orders_diff_pct'] }}%
                            </span>
                        @else
                            <span class="text-gray-400">=</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Form di Configurazione -->
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-800">
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
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                Risultati del Confronto
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Confronto tra <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $risultati['periodo_1_label'] }}</span> (Precedente) e <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $risultati['periodo_2_label'] }}</span> (Successivo)
            </p>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card Fatturato -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fatturato Lordo</span>
                        <x-filament::icon icon="heroicon-o-currency-euro" class="h-6 w-6 text-primary-500" />
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        € {{ number_format($risultati['revenue_2'], 2, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        Precedente: € {{ number_format($risultati['revenue_1'], 2, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-50 dark:border-gray-800 flex items-center justify-between">
                    <span class="text-sm text-gray-500">Variazione:</span>
                    @if($risultati['revenue_diff'] > 0)
                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/30 px-2.5 py-0.5 rounded-full">
                            <x-heroicon-m-arrow-trending-up class="h-4 w-4" />
                            +€{{ number_format($risultati['revenue_diff'], 2, ',', '.') }} 
                            @if($risultati['revenue_diff_pct'] !== null)
                                ({{ $risultati['revenue_diff_pct'] }}%)
                            @endif
                        </span>
                    @elseif($risultati['revenue_diff'] < 0)
                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-950/30 px-2.5 py-0.5 rounded-full">
                            <x-heroicon-m-arrow-trending-down class="h-4 w-4" />
                            -€{{ number_format(abs($risultati['revenue_diff']), 2, ',', '.') }} 
                            @if($risultati['revenue_diff_pct'] !== null)
                                ({{ $risultati['revenue_diff_pct'] }}%)
                            @endif
                        </span>
                    @else
                        <span class="inline-flex items-center text-sm font-semibold text-gray-500 bg-gray-50 dark:bg-gray-800 px-2.5 py-0.5 rounded-full">
                            = € 0,00
                        </span>
                    @endif
                </div>
            </div>

            <!-- Card Biglietti Venduti -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Biglietti Venduti</span>
                        <x-filament::icon icon="heroicon-o-ticket" class="h-6 w-6 text-primary-500" />
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($risultati['tickets_2'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        Precedente: {{ number_format($risultati['tickets_1'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-50 dark:border-gray-800 flex items-center justify-between">
                    <span class="text-sm text-gray-500">Variazione:</span>
                    @if($risultati['tickets_diff'] > 0)
                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/30 px-2.5 py-0.5 rounded-full">
                            <x-heroicon-m-arrow-trending-up class="h-4 w-4" />
                            +{{ number_format($risultati['tickets_diff'], 0, ',', '.') }}
                            @if($risultati['tickets_diff_pct'] !== null)
                                ({{ $risultati['tickets_diff_pct'] }}%)
                            @endif
                        </span>
                    @elseif($risultati['tickets_diff'] < 0)
                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-950/30 px-2.5 py-0.5 rounded-full">
                            <x-heroicon-m-arrow-trending-down class="h-4 w-4" />
                            -{{ number_format(abs($risultati['tickets_diff']), 0, ',', '.') }}
                            @if($risultati['tickets_diff_pct'] !== null)
                                ({{ $risultati['tickets_diff_pct'] }}%)
                            @endif
                        </span>
                    @else
                        <span class="inline-flex items-center text-sm font-semibold text-gray-500 bg-gray-50 dark:bg-gray-800 px-2.5 py-0.5 rounded-full">
                            = 0
                        </span>
                    @endif
                </div>
            </div>

            <!-- Card Ordini Totali -->
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ordini Conclusi</span>
                        <x-filament::icon icon="heroicon-o-shopping-cart" class="h-6 w-6 text-primary-500" />
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($risultati['orders_2'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        Precedente: {{ number_format($risultati['orders_1'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-50 dark:border-gray-800 flex items-center justify-between">
                    <span class="text-sm text-gray-500">Variazione:</span>
                    @if($risultati['orders_diff'] > 0)
                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/30 px-2.5 py-0.5 rounded-full">
                            <x-heroicon-m-arrow-trending-up class="h-4 w-4" />
                            +{{ number_format($risultati['orders_diff'], 0, ',', '.') }}
                            @if($risultati['orders_diff_pct'] !== null)
                                ({{ $risultati['orders_diff_pct'] }}%)
                            @endif
                        </span>
                    @elseif($risultati['orders_diff'] < 0)
                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-950/30 px-2.5 py-0.5 rounded-full">
                            <x-heroicon-m-arrow-trending-down class="h-4 w-4" />
                            -{{ number_format(abs($risultati['orders_diff']), 0, ',', '.') }}
                            @if($risultati['orders_diff_pct'] !== null)
                                ({{ $risultati['orders_diff_pct'] }}%)
                            @endif
                        </span>
                    @else
                        <span class="inline-flex items-center text-sm font-semibold text-gray-500 bg-gray-50 dark:bg-gray-800 px-2.5 py-0.5 rounded-full">
                            = 0
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Focalizzazione Mese per Grafici Temporali -->
        <div x-data="{ filterMonth: 'all' }" x-init="$watch('filterMonth', value => window.dispatchEvent(new CustomEvent('filter-charts-month', { detail: value })))">
            @if(!$risultati['isMonthly'])
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white dark:bg-gray-900 rounded-xl p-4 border border-gray-100 dark:border-gray-800 shadow-sm gap-4">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-magnifying-glass-plus" class="h-5 w-5 text-primary-500" />
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Zoom/Focalizza l'andamento grafico su un singolo mese:</span>
                    </div>
                    <select x-model="filterMonth" class="text-xs bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-lg p-1.5 min-w-[150px]">
                        <option value="all">Tutto il periodo</option>
                        <option value="01">Gennaio</option>
                        <option value="02">Febbraio</option>
                        <option value="03">Marzo</option>
                        <option value="04">Aprile</option>
                        <option value="05">Maggio</option>
                        <option value="06">Giugno</option>
                        <option value="07">Luglio</option>
                        <option value="08">Agosto</option>
                        <option value="09">Settembre</option>
                        <option value="10">Ottobre</option>
                        <option value="11">Novembre</option>
                        <option value="12">Dicembre</option>
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
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Location</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Biglietti ({{ $risultati['periodo_1_short'] ?? 'P1' }})</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Biglietti ({{ $risultati['periodo_2_short'] ?? 'P2' }})</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Delta Biglietti</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Fatturato ({{ $risultati['periodo_1_short'] ?? 'P1' }})</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Fatturato ({{ $risultati['periodo_2_short'] ?? 'P2' }})</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Delta Fatturato</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($risultati['locationBreakdown'] as $loc)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $loc['nomeLocation'] }}
                                </td>
                                <td class="px-4 py-3 text-center text-gray-500">
                                    {{ number_format($loc['tickets_1'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-800 dark:text-gray-200">
                                    {{ number_format($loc['tickets_2'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($loc['tickets_diff'] > 0)
                                        <span class="text-green-600 font-semibold">+{{ $loc['tickets_diff'] }} ({{ $loc['tickets_diff_pct'] }}%)</span>
                                    @elseif($loc['tickets_diff'] < 0)
                                        <span class="text-danger-600 font-semibold">{{ $loc['tickets_diff'] }} ({{ $loc['tickets_diff_pct'] }}%)</span>
                                    @else
                                        <span class="text-gray-400">=</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-gray-500">
                                    € {{ number_format($loc['revenue_1'], 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-gray-200">
                                    € {{ number_format($loc['revenue_2'], 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($loc['revenue_diff'] > 0)
                                        <span class="text-green-600 font-semibold">+€{{ number_format($loc['revenue_diff'], 2, ',', '.') }} ({{ $loc['revenue_diff_pct'] }}%)</span>
                                    @elseif($loc['revenue_diff'] < 0)
                                        <span class="text-danger-600 font-semibold">-€{{ number_format(abs($loc['revenue_diff']), 2, ',', '.') }} ({{ $loc['revenue_diff_pct'] }}%)</span>
                                    @else
                                        <span class="text-gray-400">=</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
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
                                    backgroundColor: 'rgba(148, 163, 184, 0.1)',
                                    fill: true,
                                    tension: 0.3
                                },
                                {
                                    label: p2Label,
                                    data: r2,
                                    borderColor: '#0c5289',
                                    backgroundColor: 'rgba(12, 82, 137, 0.2)',
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
                                legend: { position: 'top' },
                            },
                            scales: {
                                y: {
                                    ticks: {
                                        callback: function(value) { return '€ ' + value.toLocaleString('it-IT'); }
                                    }
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
                                legend: { position: 'top' },
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
