<x-filament-widgets::widget>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- 1. Fatturato Totale -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200 dark:border-slate-700/60 shadow-sm transition-all duration-200 hover:shadow-md">
            <div class="px-5 py-3 flex items-center justify-between text-white font-bold text-sm tracking-wide" style="background: linear-gradient(135deg, #0c5289 0%, #1e6fa8 100%);">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-currency-euro class="h-5 w-5 text-white/90" />
                    <span>Fatturato Totale</span>
                </div>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full font-semibold">{{ $year }}</span>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        € {{ number_format($stats['total_revenue'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">
                        Vendite lorde concluse nell'anno
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Ordini Totali -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200 dark:border-slate-700/60 shadow-sm transition-all duration-200 hover:shadow-md">
            <div class="px-5 py-3 flex items-center justify-between text-white font-bold text-sm tracking-wide" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-shopping-cart class="h-5 w-5 text-white/90" />
                    <span>Ordini Totali</span>
                </div>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full font-semibold">{{ $year }}</span>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        {{ number_format($stats['total_orders'], 0, ',', '.') }}
                    </div>
                </div>
                <!-- Breakdown Stati Ordine -->
                <div class="flex flex-wrap items-center gap-1.5 mt-3 pt-3 border-t border-gray-100 dark:border-slate-700/60 text-xs">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        Completati: {{ number_format($stats['completed_orders'] ?? 0, 0, ',', '.') }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        In Corso: {{ number_format($stats['pending_orders'] ?? 0, 0, ',', '.') }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold bg-danger-50 dark:bg-danger-950/40 text-danger-700 dark:text-danger-400 border border-danger-200 dark:border-danger-800/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-danger-500"></span>
                        Annullati: {{ number_format($stats['cancelled_orders'] ?? 0, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 3. Biglietti Venduti -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200 dark:border-slate-700/60 shadow-sm transition-all duration-200 hover:shadow-md">
            <div class="px-5 py-3 flex items-center justify-between text-white font-bold text-sm tracking-wide" style="background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-ticket class="h-5 w-5 text-white/90" />
                    <span>Biglietti Venduti</span>
                </div>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full font-semibold">{{ $year }}</span>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        {{ number_format($stats['total_tickets'], 0, ',', '.') }}
                    </div>
                </div>
                <!-- Breakdown per Metodo di Pagamento -->
                <div class="flex flex-wrap items-center gap-1.5 mt-3 pt-3 border-t border-gray-100 dark:border-slate-700/60 text-xs">
                    @if(!empty($stats['tickets_by_payment']))
                        @foreach($stats['tickets_by_payment'] as $metodo => $qta)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-semibold bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-slate-700">
                                <span class="font-bold text-gray-900 dark:text-white">{{ $metodo }}:</span> {{ number_format($qta, 0, ',', '.') }}
                            </span>
                        @endforeach
                    @else
                        <span class="text-gray-400 text-xs italic">Nessun biglietto registrato</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4. Utile Netto (da Biglietti) -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200 dark:border-slate-700/60 shadow-sm transition-all duration-200 hover:shadow-md">
            <div class="px-5 py-3 flex items-center justify-between text-white font-bold text-sm tracking-wide" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-banknotes class="h-5 w-5 text-white/90" />
                    <span>Utile Netto (da Biglietti)</span>
                </div>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full font-semibold">Provvigioni</span>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl font-extrabold text-green-600 dark:text-green-400 tracking-tight">
                        € {{ number_format($stats['net_profit_tickets'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">
                        Percentuali e provvigioni lorde maturate sui biglietti
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Uscite Extra -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200 dark:border-slate-700/60 shadow-sm transition-all duration-200 hover:shadow-md">
            <div class="px-5 py-3 flex items-center justify-between text-white font-bold text-sm tracking-wide" style="background: linear-gradient(135deg, #be123c 0%, #DD076D 100%);">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-trending-down class="h-5 w-5 text-white/90" />
                    <span>Uscite Extra</span>
                </div>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full font-semibold">Spese</span>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl font-extrabold text-danger-600 dark:text-danger-400 tracking-tight">
                        € {{ number_format($stats['extra_expenses'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">
                        Totale uscite registrate in Entrate/Uscite Extra
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Utile al Netto delle Spese -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200 dark:border-slate-700/60 shadow-sm transition-all duration-200 hover:shadow-md">
            <div class="px-5 py-3 flex items-center justify-between text-white font-bold text-sm tracking-wide" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-calculator class="h-5 w-5 text-white/90" />
                    <span>Utile al Netto delle Spese</span>
                </div>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full font-semibold">Bilancio</span>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl font-extrabold tracking-tight {{ $stats['profit_after_expenses'] >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-danger-600 dark:text-danger-400' }}">
                        € {{ number_format($stats['profit_after_expenses'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">
                        Utile netto biglietti meno uscite extra (+ entrate extra)
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
