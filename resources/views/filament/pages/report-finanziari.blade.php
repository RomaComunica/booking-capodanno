<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/filament-theme-glossy.css') }}?v={{ time() }}">
    <form wire:submit="save">
        {{ $this->form }}
    </form>

    @if(!empty($stats))
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mt-6">
        
        <!-- 1. Fatturato Lordo -->
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 flex items-center justify-between text-white">
                <span class="text-sm font-bold uppercase tracking-wider">Fatturato Lordo Ordini</span>
                <x-filament::icon icon="heroicon-m-banknotes" class="h-6 w-6 opacity-90" />
            </div>
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div class="text-3xl font-extrabold text-gray-900 dark:text-white">
                    € {{ number_format($stats['fatturato_lordo'], 2, ',', '.') }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Totale complessivo incassato dagli ordini.</p>
            </div>
        </div>

        <!-- 2. Spettante Location / Fornitori -->
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 bg-gradient-to-r from-amber-500 to-orange-600 flex items-center justify-between text-white">
                <span class="text-sm font-bold uppercase tracking-wider">Spettante Location / Fornitori</span>
                <x-filament::icon icon="heroicon-m-building-storefront" class="h-6 w-6 opacity-90" />
            </div>
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400">
                    € {{ number_format($stats['spettante_location'], 2, ',', '.') }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Quota da versare alle location sui biglietti.</p>
            </div>
        </div>

        <!-- 3. Nostra Provvigione (Utile Biglietti) -->
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 bg-gradient-to-r from-pink-600 to-rose-600 flex items-center justify-between text-white">
                <span class="text-sm font-bold uppercase tracking-wider">Nostra Provvigione (Biglietti)</span>
                <x-filament::icon icon="heroicon-m-ticket" class="h-6 w-6 opacity-90" />
            </div>
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div class="text-3xl font-extrabold text-pink-600 dark:text-pink-400">
                    € {{ number_format($stats['provvigioni_nostre'], 2, ',', '.') }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Nostra percentuale o quota fissa trattenuta.</p>
            </div>
        </div>

        <!-- 4. Entrate Extra -->
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 flex items-center justify-between text-white">
                <span class="text-sm font-bold uppercase tracking-wider">Entrate Extra</span>
                <x-filament::icon icon="heroicon-m-arrow-trending-up" class="h-6 w-6 opacity-90" />
            </div>
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">
                    + € {{ number_format($stats['entrate_extra'], 2, ',', '.') }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Entrate e regolazioni extra registrate.</p>
            </div>
        </div>

        <!-- 5. Uscite Extra -->
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 bg-gradient-to-r from-red-600 to-rose-700 flex items-center justify-between text-white">
                <span class="text-sm font-bold uppercase tracking-wider">Uscite Extra (Costi / Spese)</span>
                <x-filament::icon icon="heroicon-m-arrow-trending-down" class="h-6 w-6 opacity-90" />
            </div>
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div class="text-3xl font-extrabold text-red-600 dark:text-red-400">
                    - € {{ number_format($stats['uscite_extra'], 2, ',', '.') }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Spese di gestione, marketing e costi extra.</p>
            </div>
        </div>

        <!-- 6. UTILE NETTO FINALE -->
        <div class="rounded-2xl border-2 border-emerald-500 dark:border-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/20 shadow-md overflow-hidden flex flex-col">
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-green-600 flex items-center justify-between text-white">
                <span class="text-sm font-black uppercase tracking-wider">UTILE NETTO FINALE</span>
                <x-filament::icon icon="heroicon-m-currency-euro" class="h-6 w-6 opacity-90" />
            </div>
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div class="text-4xl font-black text-emerald-600 dark:text-emerald-400">
                    € {{ number_format($stats['utile_netto'], 2, ',', '.') }}
                </div>
                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 mt-3">Nostra Provvigione + Entrate Extra - Uscite Extra.</p>
            </div>
        </div>

    </div>

    <!-- Sezione Grafici Finanziari -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <!-- Monthly Financials Line/Bar Chart -->
        <div class="lg:col-span-2">
            <x-filament::section>
                <x-slot name="heading">Andamento Finanziario Mensile</x-slot>
                <div class="h-[320px] relative w-full" wire:ignore>
                    <canvas id="financialsMonthlyChart"></canvas>
                </div>
            </x-filament::section>
        </div>

        <!-- Financial Composition Donut Chart -->
        <div>
            <x-filament::section>
                <x-slot name="heading">Composizione Utile Netto</x-slot>
                <div class="h-[320px] relative w-full flex items-center justify-center" wire:ignore>
                    <canvas id="financialsCompositionChart"></canvas>
                </div>
            </x-filament::section>
        </div>
    </div>

    <!-- Chart.js Logic -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let financialsChartInstance = null;
            let compositionChartInstance = null;

            const isDark = () => document.documentElement.classList.contains('dark');
            const getTextColor = () => isDark() ? '#94a3b8' : '#64748b';
            const getGridColor = () => isDark() ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

            const drawFinancialCharts = (year, data) => {
                if (!data) return;

                const labels = data.map(d => d.month);
                const revenue = data.map(d => d.revenue);
                const commissions = data.map(d => d.commissions);
                const uscite = data.map(d => d.uscite);
                const profit = data.map(d => d.profit);

                // Destroy existing instances
                if (financialsChartInstance) financialsChartInstance.destroy();
                if (compositionChartInstance) compositionChartInstance.destroy();

                // 1. Monthly financials chart (Combo Bar/Line)
                const ctxFin = document.getElementById('financialsMonthlyChart').getContext('2d');
                financialsChartInstance = new Chart(ctxFin, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                type: 'line',
                                label: 'Utile Netto Reale (€)',
                                data: profit,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.15)',
                                borderWidth: 3,
                                pointBackgroundColor: '#10b981',
                                pointRadius: 4,
                                fill: false,
                                tension: 0.3,
                                z: 10
                            },
                            {
                                type: 'bar',
                                label: 'Fatturato Lordo (€)',
                                data: revenue,
                                backgroundColor: 'rgba(59, 130, 246, 0.75)',
                                borderRadius: 4
                            },
                            {
                                type: 'bar',
                                label: 'Nostra Provvigione (€)',
                                data: commissions,
                                backgroundColor: 'rgba(236, 72, 153, 0.75)',
                                borderRadius: 4
                            },
                            {
                                type: 'bar',
                                label: 'Uscite Extra (€)',
                                data: uscite,
                                backgroundColor: 'rgba(239, 68, 68, 0.75)',
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
                                labels: { color: getTextColor() }
                            },
                        },
                        scales: {
                            x: {
                                ticks: { color: getTextColor() },
                                grid: { color: getGridColor() }
                            },
                            y: {
                                ticks: {
                                    color: getTextColor(),
                                    callback: function(value) { return '€ ' + value.toLocaleString('it-IT'); }
                                },
                                grid: { color: getGridColor() }
                            }
                        }
                    }
                });

                // 2. Composition Donut Chart (for the entire year sum)
                const totalCommissions = commissions.reduce((a, b) => a + b, 0);
                const totalEntrate = data.map(d => d.entrate).reduce((a, b) => a + b, 0);
                const totalUscite = uscite.reduce((a, b) => a + b, 0);

                const ctxComp = document.getElementById('financialsCompositionChart').getContext('2d');
                compositionChartInstance = new Chart(ctxComp, {
                    type: 'doughnut',
                    data: {
                        labels: ['Nostra Provvigione Biglietti', 'Entrate Extra', 'Uscite Extra'],
                        datasets: [{
                            data: [totalCommissions, totalEntrate, totalUscite],
                            backgroundColor: [
                                '#ec4899', // Pink
                                '#10b981', // Emerald
                                '#ef4444'  // Red
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { 
                                position: 'bottom',
                                labels: { color: getTextColor() }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return ' ' + context.label + ': € ' + context.raw.toLocaleString('it-IT', { minimumFractionDigits: 2 });
                                    }
                                }
                            }
                        }
                    }
                });
            };

            // Draw initial charts
            @if(!empty($monthlyFinancials))
                drawFinancialCharts('{{ $data["year"] ?? date("Y") }}', @json($monthlyFinancials));
            @endif

            // Listen to Livewire events for updates
            window.addEventListener('draw-financial-charts', (event) => {
                const year = event.detail.year;
                const data = event.detail.data;
                drawFinancialCharts(year, data);
            });
        });
    </script>
    @endif

</x-filament-panels::page>
