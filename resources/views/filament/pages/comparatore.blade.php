<x-filament-panels::page>
    <!-- Chart.js CDN Loaded in Header -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Responsive High Contrast Overrides */
        .dark select,
        .dark select option {
            background-color: #1e293b !important;
            color: #ffffff !important;
        }
        html:not(.dark) select,
        html:not(.dark) select option {
            background-color: #ffffff !important;
            color: #0f172a !important;
        }
        .dark label, .dark .fi-fo-field-wrp-label, .dark .fi-fo-field-wrp-label span {
            color: #ffffff !important;
            font-weight: 700 !important;
        }
        html:not(.dark) label, html:not(.dark) .fi-fo-field-wrp-label, html:not(.dark) .fi-fo-field-wrp-label span {
            color: #0f172a !important;
            font-weight: 700 !important;
        }
        .dark input, .dark .fi-input-wrp, .dark .fi-select-input {
            background-color: #1e293b !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.25) !important;
        }
        html:not(.dark) input, html:not(.dark) .fi-input-wrp, html:not(.dark) .fi-select-input {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        .dark .glossy-table-row:hover,
        .dark .glossy-table-row:hover td {
            background-color: #1e293b !important;
            color: #ffffff !important;
        }
        html:not(.dark) .glossy-table-row:hover,
        html:not(.dark) .glossy-table-row:hover td {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }

        /* Year Filter Controls - High Contrast Dark/Light Styles */
        .year-preset-btn {
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            transition: all 0.15s ease;
            cursor: pointer;
            border: none;
            outline: none;
        }
        .dark .year-preset-btn {
            color: #cbd5e1;
            background-color: transparent;
        }
        html:not(.dark) .year-preset-btn {
            color: #475569;
            background-color: transparent;
        }
        .dark .year-preset-btn:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.08);
        }
        html:not(.dark) .year-preset-btn:hover {
            color: #0f172a;
            background-color: rgba(0, 0, 0, 0.05);
        }
        .year-preset-btn.is-active {
            background-color: #0c5289 !important;
            color: #ffffff !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.15);
        }

        .year-chip-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .dark .year-chip-btn {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        html:not(.dark) .year-chip-btn {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }
        .dark .year-chip-btn.is-active {
            background-color: #0f172a;
            border: 2px solid #0c5289;
            color: #ffffff;
            box-shadow: 0 0 0 1px #0c5289;
            opacity: 1;
        }
        html:not(.dark) .year-chip-btn.is-active {
            background-color: #ffffff;
            border: 2px solid #0c5289;
            color: #0c5289;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            opacity: 1;
        }
        .year-chip-btn.is-inactive {
            opacity: 0.45;
        }
        .year-chip-btn.is-inactive:hover {
            opacity: 0.8;
        }

        .dark .filter-toolbar-box {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        html:not(.dark) .filter-toolbar-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
    </style>

    <!-- Sezione Fissa: Confronto del Giorno di Oggi con lo Scorso Anno -->
    @if($oggiStats)
        <div class="glossy-card p-6 mb-6">
            <div class="flex items-center gap-2 mb-4 border-b border-gray-200 dark:border-slate-700/60 pb-3">
                <x-filament::icon icon="heroicon-o-presentation-chart-line" class="h-6 w-6 text-primary-500" />
                <h3 class="text-md font-bold text-gray-900 dark:text-white">Confronto Rapido: Oggi vs Stesso Giorno Anno Scorso</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Fatturato Oggi -->
                <div class="bg-gray-50 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200 dark:border-slate-700/60 flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wider font-bold text-gray-600 dark:text-gray-300">Fatturato Lordo</div>
                        <div class="text-2xl font-extrabold mt-1 text-gray-900 dark:text-white">
                            € {{ number_format($oggiStats['revenue_2'], 2, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-xs mt-2 pt-2 border-t border-gray-200 dark:border-slate-700/60 flex items-center justify-between font-semibold text-gray-600 dark:text-gray-300">
                        <span>Scorso Anno ({{ $oggiStats['periodo_1_label'] }}): € {{ number_format($oggiStats['revenue_1'], 2, ',', '.') }}</span>
                        @if($oggiStats['revenue_diff'] > 0)
                            <span class="font-bold inline-flex items-center gap-0.5 text-green-600 dark:text-green-400">
                                <x-heroicon-m-arrow-trending-up class="h-3.5 w-3.5" />
                                +{{ $oggiStats['revenue_diff_pct'] }}%
                            </span>
                        @elseif($oggiStats['revenue_diff'] < 0)
                            <span class="font-bold inline-flex items-center gap-0.5 text-danger-600 dark:text-danger-400">
                                <x-heroicon-m-arrow-trending-down class="h-3.5 w-3.5" />
                                {{ $oggiStats['revenue_diff_pct'] }}%
                            </span>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">=</span>
                        @endif
                    </div>
                </div>

                <!-- Biglietti Oggi -->
                <div class="bg-gray-50 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200 dark:border-slate-700/60 flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wider font-bold text-gray-600 dark:text-gray-300">Biglietti Venduti</div>
                        <div class="text-2xl font-extrabold mt-1 text-gray-900 dark:text-white">
                            {{ number_format($oggiStats['tickets_2'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-xs mt-2 pt-2 border-t border-gray-200 dark:border-slate-700/60 flex items-center justify-between font-semibold text-gray-600 dark:text-gray-300">
                        <span>Scorso Anno ({{ $oggiStats['periodo_1_label'] }}): {{ number_format($oggiStats['tickets_1'], 0, ',', '.') }}</span>
                        @if($oggiStats['tickets_diff'] > 0)
                            <span class="font-bold inline-flex items-center gap-0.5 text-green-600 dark:text-green-400">
                                <x-heroicon-m-arrow-trending-up class="h-3.5 w-3.5" />
                                +{{ $oggiStats['tickets_diff_pct'] }}%
                            </span>
                        @elseif($oggiStats['tickets_diff'] < 0)
                            <span class="font-bold inline-flex items-center gap-0.5 text-danger-600 dark:text-danger-400">
                                <x-heroicon-m-arrow-trending-down class="h-3.5 w-3.5" />
                                {{ $oggiStats['tickets_diff_pct'] }}%
                            </span>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">=</span>
                        @endif
                    </div>
                </div>

                <!-- Ordini Oggi -->
                <div class="bg-gray-50 dark:bg-slate-800/80 rounded-xl p-4 border border-gray-200 dark:border-slate-700/60 flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wider font-bold text-gray-600 dark:text-gray-300">Ordini Conclusi</div>
                        <div class="text-2xl font-extrabold mt-1 text-gray-900 dark:text-white">
                            {{ number_format($oggiStats['orders_2'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-xs mt-2 pt-2 border-t border-gray-200 dark:border-slate-700/60 flex items-center justify-between font-semibold text-gray-600 dark:text-gray-300">
                        <span>Scorso Anno ({{ $oggiStats['periodo_1_label'] }}): {{ number_format($oggiStats['orders_1'], 0, ',', '.') }}</span>
                        @if($oggiStats['orders_diff'] > 0)
                            <span class="font-bold inline-flex items-center gap-0.5 text-green-600 dark:text-green-400">
                                <x-heroicon-m-arrow-trending-up class="h-3.5 w-3.5" />
                                +{{ $oggiStats['orders_diff_pct'] }}%
                            </span>
                        @elseif($oggiStats['orders_diff'] < 0)
                            <span class="font-bold inline-flex items-center gap-0.5 text-danger-600 dark:text-danger-400">
                                <x-heroicon-m-arrow-trending-down class="h-3.5 w-3.5" />
                                {{ $oggiStats['orders_diff_pct'] }}%
                            </span>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">=</span>
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
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                Risultati del Confronto
            </h2>
            <p class="text-sm mt-1 text-gray-600 dark:text-gray-300">
                Confronto tra <span class="font-bold text-gray-900 dark:text-white">{{ $risultati['periodo_1_label'] }}</span> (Precedente) e <span class="font-bold text-primary-600 dark:text-primary-400">{{ $risultati['periodo_2_label'] }}</span> (Successivo)
            </p>
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card Fatturato -->
            <div class="glossy-card glossy-kpi-card p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Fatturato Lordo</span>
                        <x-filament::icon icon="heroicon-o-currency-euro" class="h-6 w-6 text-primary-500" />
                    </div>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-white">
                        € {{ number_format($risultati['revenue_2'], 2, ',', '.') }}
                    </div>
                    <div class="text-sm mt-1 font-semibold text-gray-600 dark:text-gray-300">
                        Precedente: € {{ number_format($risultati['revenue_1'], 2, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-slate-700/60 flex items-center justify-between font-semibold">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Variazione:</span>
                    @if($risultati['revenue_diff'] > 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                            <x-heroicon-m-arrow-trending-up class="h-4 w-4" />
                            +€{{ number_format($risultati['revenue_diff'], 2, ',', '.') }} 
                            @if($risultati['revenue_diff_pct'] !== null)
                                ({{ $risultati['revenue_diff_pct'] }}%)
                            @endif
                        </span>
                    @elseif($risultati['revenue_diff'] < 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full bg-danger-50 dark:bg-danger-950/40 text-danger-700 dark:text-danger-400 border border-danger-200 dark:border-danger-800">
                            <x-heroicon-m-arrow-trending-down class="h-4 w-4" />
                            -€{{ number_format(abs($risultati['revenue_diff']), 2, ',', '.') }} 
                            @if($risultati['revenue_diff_pct'] !== null)
                                ({{ $risultati['revenue_diff_pct'] }}%)
                            @endif
                        </span>
                    @else
                        <span class="inline-flex items-center text-sm font-semibold px-2.5 py-0.5 rounded-full text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-800">
                            = € 0,00
                        </span>
                    @endif
                </div>
            </div>

            <!-- Card Biglietti Venduti -->
            <div class="glossy-card glossy-kpi-card p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Biglietti Venduti</span>
                        <x-filament::icon icon="heroicon-o-ticket" class="h-6 w-6 text-primary-500" />
                    </div>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-white">
                        {{ number_format($risultati['tickets_2'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm mt-1 font-semibold text-gray-600 dark:text-gray-300">
                        Precedente: {{ number_format($risultati['tickets_1'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-slate-700/60 flex items-center justify-between font-semibold">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Variazione:</span>
                    @if($risultati['tickets_diff'] > 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                            <x-heroicon-m-arrow-trending-up class="h-4 w-4" />
                            +{{ number_format($risultati['tickets_diff'], 0, ',', '.') }}
                            @if($risultati['tickets_diff_pct'] !== null)
                                ({{ $risultati['tickets_diff_pct'] }}%)
                            @endif
                        </span>
                    @elseif($risultati['tickets_diff'] < 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full bg-danger-50 dark:bg-danger-950/40 text-danger-700 dark:text-danger-400 border border-danger-200 dark:border-danger-800">
                            <x-heroicon-m-arrow-trending-down class="h-4 w-4" />
                            -{{ number_format(abs($risultati['tickets_diff']), 0, ',', '.') }}
                            @if($risultati['tickets_diff_pct'] !== null)
                                ({{ $risultati['tickets_diff_pct'] }}%)
                            @endif
                        </span>
                    @else
                        <span class="inline-flex items-center text-sm font-semibold px-2.5 py-0.5 rounded-full text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-800">
                            = 0
                        </span>
                    @endif
                </div>
            </div>

            <!-- Card Ordini Totali -->
            <div class="glossy-card glossy-kpi-card p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Ordini Conclusi</span>
                        <x-filament::icon icon="heroicon-o-shopping-cart" class="h-6 w-6 text-primary-500" />
                    </div>
                    <div class="text-2xl font-extrabold text-gray-900 dark:text-white">
                        {{ number_format($risultati['orders_2'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm mt-1 font-semibold text-gray-600 dark:text-gray-300">
                        Precedente: {{ number_format($risultati['orders_1'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-slate-700/60 flex items-center justify-between font-semibold">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Variazione:</span>
                    @if($risultati['orders_diff'] > 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                            <x-heroicon-m-arrow-trending-up class="h-4 w-4" />
                            +{{ number_format($risultati['orders_diff'], 0, ',', '.') }}
                            @if($risultati['orders_diff_pct'] !== null)
                                ({{ $risultati['orders_diff_pct'] }}%)
                            @endif
                        </span>
                    @elseif($risultati['orders_diff'] < 0)
                        <span class="inline-flex items-center gap-1 text-sm font-bold px-2.5 py-0.5 rounded-full bg-danger-50 dark:bg-danger-950/40 text-danger-700 dark:text-danger-400 border border-danger-200 dark:border-danger-800">
                            <x-heroicon-m-arrow-trending-down class="h-4 w-4" />
                            -{{ number_format(abs($risultati['orders_diff']), 0, ',', '.') }}
                            @if($risultati['orders_diff_pct'] !== null)
                                ({{ $risultati['orders_diff_pct'] }}%)
                            @endif
                        </span>
                    @else
                        <span class="inline-flex items-center text-sm font-semibold px-2.5 py-0.5 rounded-full text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-800">
                            = 0
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Focalizzazione Mese per Grafici Temporali -->
        <div x-data="{ 
            filterMonth: 'all',
            updateMonth(val) {
                this.filterMonth = val;
                if (window.onMonthFilterChanged) {
                    window.onMonthFilterChanged(val);
                }
            }
        }">
            @if(!$risultati['isMonthly'])
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-start sm:items-center glossy-card p-4 gap-4">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-magnifying-glass-plus" class="h-5 w-5 text-primary-500" />
                        <span class="text-sm font-bold text-gray-900 dark:text-white">Zoom/Focalizza l'andamento grafico su un singolo mese:</span>
                    </div>
                    <select x-model="filterMonth" @change="updateMonth($event.target.value)" class="bg-white dark:bg-slate-800 text-gray-900 dark:text-white border border-gray-300 dark:border-slate-700 rounded-lg px-3 py-2 text-xs font-bold shadow-sm cursor-pointer">
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
                        <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="h-6 w-6 text-primary-500" />
                            <span>Andamento temporale del Fatturato</span>
                        </div>
                    </x-slot>
                    <div style="height: 300px; min-height: 300px; position: relative; width: 100%;" id="revenueChartContainer">
                        <canvas id="revenueComparisonChart"></canvas>
                    </div>
                </x-filament::section>

                <!-- Grafico Biglietti nel tempo -->
                <x-filament::section class="h-full flex flex-col">
                    <x-slot name="heading">
                        <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                            <x-filament::icon icon="heroicon-o-ticket" class="h-6 w-6 text-primary-500" />
                            <span>Andamento temporale dei Biglietti Venduti</span>
                        </div>
                    </x-slot>
                    <div style="height: 300px; min-height: 300px; position: relative; width: 100%;" id="ticketsChartContainer">
                        <canvas id="ticketsComparisonChart"></canvas>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <!-- Riepilogo per Città -->
        @if(!empty($risultati['cityBreakdown']))
            <x-filament::section class="mt-6">
                <x-slot name="heading">Riepilogo Vendite per Città</x-slot>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-100 dark:bg-slate-800">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Città</th>
                                <th class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Biglietti ({{ $risultati['periodo_1_short'] ?? 'P1' }})</th>
                                <th class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Biglietti ({{ $risultati['periodo_2_short'] ?? 'P2' }})</th>
                                <th class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Delta Biglietti</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Fatturato ({{ $risultati['periodo_1_short'] ?? 'P1' }})</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Fatturato ({{ $risultati['periodo_2_short'] ?? 'P2' }})</th>
                                <th class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Delta Fatturato</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                            @foreach($risultati['cityBreakdown'] as $cRow)
                                <tr class="glossy-table-row transition-colors">
                                    <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-300">
                                            {{ $cRow['citta'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-200">
                                        {{ number_format($cRow['tickets_1'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white">
                                        {{ number_format($cRow['tickets_2'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($cRow['tickets_diff'] > 0)
                                            <span class="text-green-600 dark:text-green-400 font-bold">+{{ $cRow['tickets_diff'] }} ({{ $cRow['tickets_diff_pct'] }}%)</span>
                                        @elseif($cRow['tickets_diff'] < 0)
                                            <span class="text-danger-600 dark:text-danger-400 font-bold">{{ $cRow['tickets_diff'] }} ({{ $cRow['tickets_diff_pct'] }}%)</span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">=</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-200">
                                        € {{ number_format($cRow['revenue_1'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">
                                        € {{ number_format($cRow['revenue_2'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if($cRow['revenue_diff'] > 0)
                                            <span class="text-green-600 dark:text-green-400 font-bold">+€{{ number_format($cRow['revenue_diff'], 2, ',', '.') }} ({{ $cRow['revenue_diff_pct'] }}%)</span>
                                        @elseif($cRow['revenue_diff'] < 0)
                                            <span class="text-danger-600 dark:text-danger-400 font-bold">-€{{ number_format(abs($cRow['revenue_diff']), 2, ',', '.') }} ({{ $cRow['revenue_diff_pct'] }}%)</span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">=</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        <!-- Dettaglio Location -->
        <x-filament::section class="mt-6">
            <x-slot name="heading">Dettaglio Vendite per Location</x-slot>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                    <thead class="bg-gray-100 dark:bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Location</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white bg-gray-100 dark:bg-slate-800">Città</th>
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
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-200">
                                        {{ $loc['cittaLocation'] ?? 'Roma' }}
                                    </span>
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
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Nessuna vendita registrata nei periodi selezionati.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    <!-- Sezione Fissa: Panoramica Storica Multianno di Tutti i Fatturati -->
    @if(!empty($allYearsStats) && !empty($allYearsStats['datasets']))
        @php
            $availableYears = array_map('strval', $allYearsStats['years'] ?? []);
        @endphp
        <div class="mt-8 border-t border-gray-200 dark:border-slate-800 pt-6" x-data="{
            selectedMonth: 'all',
            activeYears: @js($availableYears),
            allAvailableYears: @js($availableYears),
            toggleYear(y) {
                const strY = String(y);
                if (this.activeYears.includes(strY)) {
                    if (this.activeYears.length > 1) {
                        this.activeYears = this.activeYears.filter(item => item !== strY);
                    }
                } else {
                    this.activeYears.push(strY);
                }
                if (window.onAllYearsSelectionChanged) {
                    window.onAllYearsSelectionChanged(this.activeYears);
                }
            },
            isYearActive(y) {
                return this.activeYears.includes(String(y));
            },
            selectAll() {
                this.activeYears = [...this.allAvailableYears];
                if (window.onAllYearsSelectionChanged) {
                    window.onAllYearsSelectionChanged(this.activeYears);
                }
            },
            selectRecent(count) {
                this.activeYears = this.allAvailableYears.slice(-count);
                if (window.onAllYearsSelectionChanged) {
                    window.onAllYearsSelectionChanged(this.activeYears);
                }
            },
            onMonthChange(m) {
                this.selectedMonth = m;
                if (window.onAllYearsMonthFilterChanged) {
                    window.onAllYearsMonthFilterChanged(m);
                }
            }
        }">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
                        <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                            <x-filament::icon icon="heroicon-o-presentation-chart-line" class="h-6 w-6 text-primary-500" />
                            <span class="text-lg font-bold">Confronto Storico Fatturati: Tutti gli Anni</span>
                        </div>
                        
                        <!-- Controlli: Focus Mese & Preset Anni -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Preset Rapidi -->
                            <div class="flex items-center gap-1 p-1 rounded-lg filter-toolbar-box">
                                <button type="button" @click="selectAll()" class="year-preset-btn" :class="activeYears.length === allAvailableYears.length ? 'is-active' : ''">
                                    Tutti
                                </button>
                                <button type="button" @click="selectRecent(3)" class="year-preset-btn" :class="activeYears.length === 3 ? 'is-active' : ''">
                                    Ultimi 3 Anni
                                </button>
                                <button type="button" @click="selectRecent(2)" class="year-preset-btn" :class="activeYears.length === 2 ? 'is-active' : ''">
                                    Ultimi 2 Anni
                                </button>
                            </div>

                            <!-- Selettore Zoom per Mese -->
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-magnifying-glass-plus" class="h-4 w-4 text-primary-500" />
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-200">Focus Mese:</span>
                                <select x-model="selectedMonth" @change="onMonthChange($event.target.value)" class="bg-white dark:bg-slate-800 text-gray-900 dark:text-white border border-gray-300 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs font-bold shadow-sm cursor-pointer">
                                    <option value="all">Tutto l'anno (12 Mesi)</option>
                                    <option value="12">Dicembre (Focus Vendite)</option>
                                    <option value="11">Novembre</option>
                                    <option value="10">Ottobre</option>
                                    <option value="09">Settembre</option>
                                    <option value="08">Agosto</option>
                                    <option value="07">Luglio</option>
                                    <option value="06">Giugno</option>
                                    <option value="05">Maggio</option>
                                    <option value="04">Aprile</option>
                                    <option value="03">Marzo</option>
                                    <option value="02">Febbraio</option>
                                    <option value="01">Gennaio</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </x-slot>

                <!-- Chips Interattivi Filtro Anni -->
                <div class="flex flex-wrap items-center gap-2 mb-4 pb-3 border-b border-gray-200 dark:border-slate-700/60">
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-200 mr-1">Filtra Anni:</span>
                    @foreach($allYearsStats['datasets'] as $ds)
                        <button type="button" 
                                @click="toggleYear('{{ $ds['year'] }}')"
                                class="year-chip-btn"
                                :class="isYearActive('{{ $ds['year'] }}') ? 'is-active' : 'is-inactive'">
                            <span class="inline-block w-2.5 h-2.5 rounded-full shadow-sm" style="background-color: {{ $ds['color'] }};"></span>
                            <span>{{ $ds['year'] }}</span>
                        </button>
                    @endforeach
                </div>

                <!-- Box Riepilogativo Totali per Anno con Badge e Delta % -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6" id="allYearsCardsContainer">
                    @foreach($allYearsStats['datasets'] as $ds)
                        <div class="bg-gray-50 dark:bg-slate-800/80 rounded-xl p-3 border border-gray-200 dark:border-slate-700/60 flex flex-col justify-between shadow-sm cursor-pointer transition-all hover:scale-[1.02]" 
                             id="year-card-{{ $ds['year'] }}"
                             @click="toggleYear('{{ $ds['year'] }}')"
                             :style="isYearActive('{{ $ds['year'] }}') ? 'opacity: 1;' : 'opacity: 0.35; filter: grayscale(70%);'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    {{ $ds['year'] }}
                                </span>
                                <span class="inline-block w-3 h-3 rounded-full shadow-sm" style="background-color: {{ $ds['color'] }};"></span>
                            </div>
                            <div class="text-base font-extrabold text-gray-900 dark:text-white mt-1 year-total-value">
                                € {{ number_format($ds['total'], 2, ',', '.') }}
                            </div>
                            <div class="text-[11px] mt-1 pt-1 border-t border-gray-200 dark:border-slate-700/60 font-semibold text-gray-500 dark:text-gray-400 flex items-center justify-between year-growth-value">
                                <span>vs prec.</span>
                                @if($ds['growth_pct'] !== null)
                                    @if($ds['growth_pct'] > 0)
                                        <span class="text-green-600 dark:text-green-400 font-bold">
                                            +{{ $ds['growth_pct'] }}%
                                        </span>
                                    @elseif($ds['growth_pct'] < 0)
                                        <span class="text-danger-600 dark:text-danger-400 font-bold">
                                            {{ $ds['growth_pct'] }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">= 0%</span>
                                    @endif
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Grafico Multi-Linea Tutti gli Anni -->
                <div style="height: 380px; min-height: 380px; position: relative; width: 100%;" id="allYearsChartContainer">
                    <canvas id="allYearsRevenueChart"></canvas>
                </div>
            </x-filament::section>
        </div>
    @endif

    <script>
        (function() {
            let revenueChartInstance = null;
            let ticketsChartInstance = null;
            let allYearsChartInstance = null;
            let currentChartData = @json($risultati ?? null);
            const allYearsData = @json($allYearsStats ?? null);
            let currentFilterMonth = 'all';
            let currentAllYearsMonth = 'all';
            let currentActiveYears = (@json($allYearsStats['years'] ?? [])).map(String);

            const checkIsDark = function() {
                return document.documentElement.classList.contains('dark') || 
                       document.body.classList.contains('dark') ||
                       document.documentElement.getAttribute('data-theme') === 'dark' ||
                       (localStorage.getItem('theme') === 'dark');
            };

            const getContext = function(containerId, canvasId) {
                const container = document.getElementById(containerId);
                if (!container) return null;
                let canvas = document.getElementById(canvasId);
                if (!canvas) {
                    container.innerHTML = '<canvas id="' + canvasId + '"></canvas>';
                    canvas = document.getElementById(canvasId);
                }
                return canvas ? canvas.getContext('2d') : null;
            };

            const drawCharts = function(data, filterMonth) {
                if (!data || !data.chartLabels || typeof Chart === 'undefined') return;

                const isDark = checkIsDark();
                const textColor = isDark ? '#ffffff' : '#0f172a';
                const gridColor = isDark ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.08)';

                let labels = data.chartLabels;
                let r1 = (data.chartData1 || []).map(function(d) { return d.revenue || 0; });
                let r2 = (data.chartData2 || []).map(function(d) { return d.revenue || 0; });
                let t1 = (data.chartData1 || []).map(function(d) { return d.tickets || 0; });
                let t2 = (data.chartData2 || []).map(function(d) { return d.tickets || 0; });

                const p1Label = data.periodo_1_label || 'Periodo 1';
                const p2Label = data.periodo_2_label || 'Periodo 2';

                // Client-side Month Zoom Filtering
                if (!data.isMonthly && filterMonth && filterMonth !== 'all') {
                    const filteredLabels = [];
                    const filteredR1 = [];
                    const filteredR2 = [];
                    const filteredT1 = [];
                    const filteredT2 = [];

                    const count = Math.max((data.chartData1 || []).length, (data.chartData2 || []).length);
                    for (let i = 0; i < count; i++) {
                        const d1 = (data.chartData1 || [])[i] || {};
                        const d2 = (data.chartData2 || [])[i] || {};
                        const dateStr = d2.date || d1.date;
                        if (dateStr) {
                            const parts = dateStr.split('-');
                            if (parts.length >= 3 && parts[1] === filterMonth) {
                                filteredLabels.push("G. " + parseInt(parts[2]));
                                filteredR1.push(d1.revenue || 0);
                                filteredR2.push(d2.revenue || 0);
                                filteredT1.push(d1.tickets || 0);
                                filteredT2.push(d2.tickets || 0);
                            }
                        }
                    }

                    if (filteredLabels.length > 0) {
                        labels = filteredLabels;
                        r1 = filteredR1;
                        r2 = filteredR2;
                        t1 = filteredT1;
                        t2 = filteredT2;
                    } else {
                        labels = ['Nessun dato per questo mese'];
                        r1 = [0];
                        r2 = [0];
                        t1 = [0];
                        t2 = [0];
                    }
                }

                // Destroy existing instances
                if (revenueChartInstance) {
                    try { revenueChartInstance.destroy(); } catch(e) {}
                    revenueChartInstance = null;
                }
                if (ticketsChartInstance) {
                    try { ticketsChartInstance.destroy(); } catch(e) {}
                    ticketsChartInstance = null;
                }

                // 1. Revenue Chart
                const ctxRev = getContext('revenueChartContainer', 'revenueComparisonChart');
                if (ctxRev) {
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
                                    tension: 0.35,
                                    pointRadius: 2,
                                    pointHoverRadius: 5
                                },
                                {
                                    label: p2Label,
                                    data: r2,
                                    borderColor: '#0c5289',
                                    backgroundColor: 'rgba(12, 82, 137, 0.2)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 3,
                                    pointHoverRadius: 6
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { color: textColor, font: { weight: '700', size: 12 } }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        label: function(context) {
                                            let l = context.dataset.label || '';
                                            if (l) l += ': ';
                                            if (context.parsed.y !== null) {
                                                l += new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(context.parsed.y);
                                            }
                                            return l;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: { color: textColor, maxRotation: 45, font: { weight: '600', size: 11 } },
                                    grid: { color: gridColor }
                                },
                                y: {
                                    ticks: {
                                        color: textColor,
                                        font: { weight: '600', size: 11 },
                                        callback: function(value) { return '€ ' + value.toLocaleString('it-IT'); }
                                    },
                                    grid: { color: gridColor }
                                }
                            }
                        }
                    });
                }

                // 2. Tickets Chart
                const ctxTick = getContext('ticketsChartContainer', 'ticketsComparisonChart');
                if (ctxTick) {
                    ticketsChartInstance = new Chart(ctxTick, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: p1Label,
                                    data: t1,
                                    borderColor: '#94a3b8',
                                    backgroundColor: 'rgba(148, 163, 184, 0.15)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 2,
                                    pointHoverRadius: 5
                                },
                                {
                                    label: p2Label,
                                    data: t2,
                                    borderColor: '#29f48f',
                                    backgroundColor: 'rgba(41, 244, 143, 0.2)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 3,
                                    pointHoverRadius: 6
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { color: textColor, font: { weight: '700', size: 12 } }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            scales: {
                                x: {
                                    ticks: { color: textColor, maxRotation: 45, font: { weight: '600', size: 11 } },
                                    grid: { color: gridColor }
                                },
                                y: {
                                    ticks: { color: textColor, font: { weight: '600', size: 11 } },
                                    grid: { color: gridColor }
                                }
                            }
                        }
                    });
                }
            };

            // Draw Multi-Year Comparison Chart with Month Zoom and Year Filtering
            const drawAllYearsChart = function(filterMonth = 'all', activeYears = null) {
                if (!allYearsData || !allYearsData.datasets || !allYearsData.labels || typeof Chart === 'undefined') return;

                if (activeYears) {
                    currentActiveYears = activeYears.map(String);
                }

                const isDark = checkIsDark();
                const textColor = isDark ? '#ffffff' : '#0f172a';
                const gridColor = isDark ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.08)';

                const ctxAllYears = getContext('allYearsChartContainer', 'allYearsRevenueChart');
                if (!ctxAllYears) return;

                if (allYearsChartInstance) {
                    try { allYearsChartInstance.destroy(); } catch(e) {}
                    allYearsChartInstance = null;
                }

                let chartLabels = allYearsData.labels;
                const isSingleMonth = filterMonth !== 'all';

                if (isSingleMonth) {
                    const mNum = parseInt(filterMonth);
                    const daysInMonth = (mNum === 2) ? 29 : ([4, 6, 9, 11].includes(mNum) ? 30 : 31);
                    chartLabels = [];
                    for (let d = 1; d <= daysInMonth; d++) {
                        chartLabels.push('G. ' + d);
                    }
                }

                // Filter datasets by active years
                const filteredDatasets = allYearsData.datasets.filter(function(ds) {
                    return currentActiveYears.includes(String(ds.year));
                });

                let prevYearTotal = null;
                const datasets = filteredDatasets.map(function(ds) {
                    let seriesData = ds.data;
                    let displayTotal = ds.total;

                    if (isSingleMonth && ds.daily && ds.daily[filterMonth]) {
                        const daysInMonth = chartLabels.length;
                        seriesData = ds.daily[filterMonth].slice(0, daysInMonth);
                        const mNum = parseInt(filterMonth);
                        displayTotal = (ds.monthly_totals && ds.monthly_totals[mNum] !== undefined)
                            ? Number(ds.monthly_totals[mNum])
                            : seriesData.reduce((acc, val) => acc + val, 0);
                    }

                    // Update Card in the UI for this year
                    const cardEl = document.getElementById('year-card-' + ds.year);
                    if (cardEl) {
                        const totalValEl = cardEl.querySelector('.year-total-value');
                        const growthValEl = cardEl.querySelector('.year-growth-value');
                        if (totalValEl) {
                            totalValEl.innerText = '€ ' + Number(displayTotal).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                        if (growthValEl) {
                            if (prevYearTotal !== null && prevYearTotal > 0) {
                                const diffPct = Math.round(((displayTotal - prevYearTotal) / prevYearTotal) * 1000) / 10;
                                if (diffPct > 0) {
                                    growthValEl.innerHTML = '<span>vs prec.</span><span class="text-green-600 dark:text-green-400 font-bold">+' + diffPct + '%</span>';
                                } else if (diffPct < 0) {
                                    growthValEl.innerHTML = '<span>vs prec.</span><span class="text-danger-600 dark:text-danger-400 font-bold">' + diffPct + '%</span>';
                                } else {
                                    growthValEl.innerHTML = '<span>vs prec.</span><span class="text-gray-400 dark:text-gray-500">= 0%</span>';
                                }
                            } else {
                                growthValEl.innerHTML = '<span>vs prec.</span><span class="text-gray-400 dark:text-gray-500">-</span>';
                            }
                        }
                    }
                    prevYearTotal = displayTotal;

                    return {
                        label: ds.label + ' (€ ' + Number(displayTotal).toLocaleString('it-IT', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ')',
                        data: seriesData,
                        borderColor: ds.color,
                        backgroundColor: ds.color + '18',
                        fill: false,
                        tension: 0.35,
                        pointRadius: isSingleMonth ? 2.5 : 3.5,
                        pointHoverRadius: isSingleMonth ? 5.5 : 6.5,
                        borderWidth: 2.5
                    };
                });

                allYearsChartInstance = new Chart(ctxAllYears, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: datasets
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
                                labels: {
                                    color: textColor,
                                    font: { weight: '700', size: 12 },
                                    boxWidth: 14,
                                    padding: 10,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label.indexOf('(') !== -1) {
                                            label = label.split('(')[0].trim();
                                        }
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: textColor, font: { weight: '700', size: 11 }, maxRotation: 45 },
                                grid: { color: gridColor }
                            },
                            y: {
                                ticks: {
                                    color: textColor,
                                    font: { weight: '700', size: 11 },
                                    callback: function(value) {
                                        return '€ ' + value.toLocaleString('it-IT');
                                    }
                                },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            };

            // Global filter listeners
            window.onAllYearsSelectionChanged = function(activeYears) {
                drawAllYearsChart(currentAllYearsMonth, activeYears);
            };

            window.onAllYearsMonthFilterChanged = function(month) {
                currentAllYearsMonth = month;
                drawAllYearsChart(currentAllYearsMonth, currentActiveYears);
            };

            // Global month filter listener for top comparison charts
            window.onMonthFilterChanged = function(month) {
                currentFilterMonth = month;
                drawCharts(currentChartData, currentFilterMonth);
            };

            // Initial render
            const init = function() {
                if (currentChartData) {
                    drawCharts(currentChartData, currentFilterMonth);
                }
                drawAllYearsChart(currentAllYearsMonth, currentActiveYears);
            };

            init();
            setTimeout(init, 100);
            setTimeout(init, 300);

            // MutationObserver to immediately update chart text contrast when theme is switched
            try {
                const themeObserver = new MutationObserver(function() {
                    init();
                });
                themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });
            } catch(e) {}

            // Listen to Livewire dynamic comparison submissions
            if (typeof Livewire !== 'undefined') {
                Livewire.on('draw-comparison-charts', function(payload) {
                    const res = Array.isArray(payload) ? (payload[0]?.resultados || payload[0]) : payload;
                    if (res) {
                        currentChartData = res;
                        setTimeout(function() {
                            drawCharts(currentChartData, currentFilterMonth);
                            drawAllYearsChart(currentAllYearsMonth, currentActiveYears);
                        }, 100);
                    }
                });
            }

            window.addEventListener('draw-comparison-charts', function(event) {
                const data = event.detail?.resultados || event.detail?.[0] || event.detail;
                if (data) {
                    currentChartData = data;
                    setTimeout(function() {
                        drawCharts(currentChartData, currentFilterMonth);
                        drawAllYearsChart(currentAllYearsMonth, currentActiveYears);
                    }, 100);
                }
            });

            document.addEventListener('livewire:navigated', function() {
                setTimeout(init, 100);
            });
        })();
    </script>
</x-filament-panels::page>
