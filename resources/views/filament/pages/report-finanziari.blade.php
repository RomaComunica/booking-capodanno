<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/filament-theme-glossy.css') }}?v={{ time() }}">
    <form wire:submit="save">
        {{ $this->form }}
    </form>

    @if(!empty($stats))
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mt-6">
        
        <!-- Fatturato Lordo -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-banknotes" class="h-6 w-6 text-primary-500" />
                    <span>Fatturato Lordo Ordini</span>
                </div>
            </x-slot>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                € {{ number_format($stats['fatturato_lordo'], 2, ',', '.') }}
            </div>
            <p class="text-sm text-gray-500 mt-2">Vendite totali lorde senza storni.</p>
        </x-filament::section>

        <!-- Provvigioni -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-document-minus" class="h-6 w-6 text-danger-500" />
                    <span>Provvigioni Trattenute / Quota Partner</span>
                </div>
            </x-slot>
            <div class="text-3xl font-bold text-danger-600 dark:text-danger-400">
                - € {{ number_format($stats['provvigioni_trattenute'], 2, ',', '.') }}
            </div>
            <p class="text-sm text-gray-500 mt-2">Deducibile dal lordo ordini.</p>
        </x-filament::section>

        <!-- Margine -->
        <x-filament::section class="bg-primary-50 dark:bg-primary-900/20">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-chart-bar" class="h-6 w-6 text-primary-600" />
                    <span>Margine Operativo (Ordini)</span>
                </div>
            </x-slot>
            <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                € {{ number_format($stats['margine_operativo'], 2, ',', '.') }}
            </div>
            <p class="text-sm text-gray-500 mt-2">Fatturato Lordo - Provvigioni Partner.</p>
        </x-filament::section>

        <!-- Entrate Extra -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-arrow-trending-up" class="h-6 w-6 text-success-500" />
                    <span>Entrate Extra (Regolazioni)</span>
                </div>
            </x-slot>
            <div class="text-3xl font-bold text-success-600 dark:text-success-400">
                + € {{ number_format($stats['entrate_extra'], 2, ',', '.') }}
            </div>
            <p class="text-sm text-gray-500 mt-2">Da registro Entrate/Uscite libere.</p>
        </x-filament::section>

        <!-- Uscite Extra -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-arrow-trending-down" class="h-6 w-6 text-danger-500" />
                    <span>Uscite Extra (Costi/Spese)</span>
                </div>
            </x-slot>
            <div class="text-3xl font-bold text-danger-600 dark:text-danger-400">
                - € {{ number_format($stats['uscite_extra'], 2, ',', '.') }}
            </div>
            <p class="text-sm text-gray-500 mt-2">Da registro Entrate/Uscite libere.</p>
        </x-filament::section>

        <!-- UTILE NETTO FINALE -->
        <x-filament::section class="border-2 border-success-500">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-currency-euro" class="h-6 w-6 text-success-600" />
                    <span class="text-lg font-bold text-success-600">UTILE NETTO FINALE</span>
                </div>
            </x-slot>
            <div class="text-4xl font-black text-success-600 dark:text-success-400 py-4">
                € {{ number_format($stats['utile_netto'], 2, ',', '.') }}
            </div>
            <p class="text-sm text-gray-500">Margine + Entrate Extra - Uscite Extra.</p>
        </x-filament::section>

    </div>

    <!-- Sezione Grafici Finanziari -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <!-- Monthly Financials Line/Bar Chart -->
        <div class="lg:col-span-2">
            <x-filament::section>
                <x-slot name="heading">Andamento Finanziario Mensile</x-slot>
                <div class="h-[300px] relative w-full" wire:ignore>
                    <canvas id="financialsMonthlyChart"></canvas>
                </div>
            </x-filament::section>
        </div>

        <!-- Financial Composition Donut Chart -->
        <div>
            <x-filament::section>
                <x-slot name="heading">Composizione Voci Bilancio</x-slot>
                <div class="h-[300px] relative w-full flex items-center justify-center" wire:ignore>
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

            const drawFinancialCharts = (year, data) => {
                if (!data) return;

                const labels = data.map(d => d.month);
                const revenue = data.map(d => d.revenue);
                const margin = data.map(d => d.margin);
                const profit = data.map(d => d.profit);
                const uscite = data.map(d => d.uscite);

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
                                label: 'Utile Netto (€)',
                                data: profit,
                                borderColor: '#29f48f',
                                backgroundColor: 'rgba(41, 244, 143, 0.15)',
                                borderWidth: 3,
                                fill: false,
                                tension: 0.3,
                                z: 10
                            },
                            {
                                type: 'bar',
                                label: 'Fatturato (€)',
                                data: revenue,
                                backgroundColor: 'rgba(12, 82, 137, 0.7)',
                                borderRadius: 4
                            },
                            {
                                type: 'bar',
                                label: 'Margine (€)',
                                data: margin,
                                backgroundColor: 'rgba(221, 7, 109, 0.7)',
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

                // 2. Composition Donut Chart (for the entire year sum)
                const totalMargin = margin.reduce((a, b) => a + b, 0);
                const totalEntrate = data.map(d => d.entrate).reduce((a, b) => a + b, 0);
                const totalUscite = uscite.reduce((a, b) => a + b, 0);

                const ctxComp = document.getElementById('financialsCompositionChart').getContext('2d');
                compositionChartInstance = new Chart(ctxComp, {
                    type: 'doughnut',
                    data: {
                        labels: ['Margine Operativo', 'Entrate Extra', 'Uscite Extra'],
                        datasets: [{
                            data: [totalMargin, totalEntrate, totalUscite],
                            backgroundColor: [
                                '#0c5289', // brand blue
                                '#29f48f', // neon mint
                                '#DD076D'  // fuchsia
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
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
