<x-filament-widgets::widget>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-7">
        <!-- 1. Fatturato Totale -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200/80 dark:border-slate-700/70 shadow-sm transition-all duration-200 hover:shadow-md hover:border-primary-500/40">
            <!-- Header Banda Superiore Colorata -->
            <div class="px-6 py-4 flex items-center justify-between text-white font-bold text-sm tracking-wide shadow-inner" style="background: linear-gradient(135deg, #0c5289 0%, #1773ba 100%);">
                <div class="flex items-center gap-2.5">
                    <div class="p-1 rounded-lg bg-white/15">
                        <x-heroicon-o-currency-euro class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-sm font-bold tracking-wide">Fatturato Totale</span>
                </div>
                <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full font-bold">{{ $year }}</span>
            </div>
            <!-- Corpo Card con Respiro e Padding -->
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl lg:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight my-1">
                        € {{ number_format($stats['total_revenue'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-medium">
                        Vendite lorde complessive concluse nell'anno {{ $year }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Ordini Totali -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200/80 dark:border-slate-700/70 shadow-sm transition-all duration-200 hover:shadow-md hover:border-amber-500/40">
            <!-- Header Banda Superiore Colorata -->
            <div class="px-6 py-4 flex items-center justify-between text-white font-bold text-sm tracking-wide shadow-inner" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);">
                <div class="flex items-center gap-2.5">
                    <div class="p-1 rounded-lg bg-white/15">
                        <x-heroicon-o-shopping-cart class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-sm font-bold tracking-wide">Ordini Totali</span>
                </div>
                <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full font-bold">{{ $year }}</span>
            </div>
            <!-- Corpo Card con Respiro e Padding -->
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl lg:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight my-1">
                        {{ number_format($stats['total_orders'], 0, ',', '.') }}
                    </div>
                </div>
                <!-- Breakdown Stati Ordine Spaziati -->
                <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-slate-700/60 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800/60">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        Completati: {{ number_format($stats['completed_orders'] ?? 0, 0, ',', '.') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        In Corso: {{ number_format($stats['pending_orders'] ?? 0, 0, ',', '.') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold bg-danger-50 dark:bg-danger-950/40 text-danger-700 dark:text-danger-400 border border-danger-200 dark:border-danger-800/60">
                        <span class="w-2 h-2 rounded-full bg-danger-500"></span>
                        Annullati: {{ number_format($stats['cancelled_orders'] ?? 0, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 3. Biglietti Venduti -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200/80 dark:border-slate-700/70 shadow-sm transition-all duration-200 hover:shadow-md hover:border-cyan-500/40">
            <!-- Header Banda Superiore Colorata -->
            <div class="px-6 py-4 flex items-center justify-between text-white font-bold text-sm tracking-wide shadow-inner" style="background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);">
                <div class="flex items-center gap-2.5">
                    <div class="p-1 rounded-lg bg-white/15">
                        <x-heroicon-o-ticket class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-sm font-bold tracking-wide">Biglietti Venduti</span>
                </div>
                <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full font-bold">{{ $year }}</span>
            </div>
            <!-- Corpo Card con Respiro e Padding -->
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl lg:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight my-1">
                        {{ number_format($stats['total_tickets'], 0, ',', '.') }}
                    </div>
                </div>
                <!-- Breakdown per Metodo di Pagamento con Biglietti e Totale € -->
                <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-slate-700/60 text-xs">
                    @if(!empty($stats['tickets_by_payment']))
                        @foreach($stats['tickets_by_payment'] as $metodo => $item)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold bg-gray-100/90 dark:bg-slate-800 text-gray-800 dark:text-gray-200 border border-gray-200/90 dark:border-slate-700 shadow-2xs">
                                <span class="text-gray-600 dark:text-gray-400 font-semibold">{{ $metodo }}:</span>
                                <span class="text-primary-600 dark:text-primary-400 font-extrabold">{{ number_format(is_array($item) ? $item['tickets'] : $item, 0, ',', '.') }} pz</span>
                                @if(is_array($item) && isset($item['revenue']))
                                    <span class="text-gray-300 dark:text-slate-600 font-normal">|</span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-extrabold">€ {{ number_format($item['revenue'], 2, ',', '.') }}</span>
                                @endif
                            </span>
                        @endforeach
                    @else
                        <span class="text-gray-400 text-xs italic">Nessun biglietto registrato</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4. Utile Netto (da Biglietti) -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200/80 dark:border-slate-700/70 shadow-sm transition-all duration-200 hover:shadow-md hover:border-green-500/40">
            <!-- Header Banda Superiore Colorata -->
            <div class="px-6 py-4 flex items-center justify-between text-white font-bold text-sm tracking-wide shadow-inner" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <div class="flex items-center gap-2.5">
                    <div class="p-1 rounded-lg bg-white/15">
                        <x-heroicon-o-banknotes class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-sm font-bold tracking-wide">Utile Netto (da Biglietti)</span>
                </div>
                <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full font-bold">Provvigioni</span>
            </div>
            <!-- Corpo Card con Respiro e Padding -->
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl lg:text-4xl font-extrabold text-green-600 dark:text-green-400 tracking-tight my-1">
                        € {{ number_format($stats['net_profit_tickets'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-medium">
                        Percentuali e quote provvigionali lorde maturate sui biglietti venduti
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Uscite Extra -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200/80 dark:border-slate-700/70 shadow-sm transition-all duration-200 hover:shadow-md hover:border-pink-500/40">
            <!-- Header Banda Superiore Colorata -->
            <div class="px-6 py-4 flex items-center justify-between text-white font-bold text-sm tracking-wide shadow-inner" style="background: linear-gradient(135deg, #be123c 0%, #DD076D 100%);">
                <div class="flex items-center gap-2.5">
                    <div class="p-1 rounded-lg bg-white/15">
                        <x-heroicon-o-arrow-trending-down class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-sm font-bold tracking-wide">Uscite Extra</span>
                </div>
                <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full font-bold">Spese</span>
            </div>
            <!-- Corpo Card con Respiro e Padding -->
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl lg:text-4xl font-extrabold text-danger-600 dark:text-danger-400 tracking-tight my-1">
                        € {{ number_format($stats['extra_expenses'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-medium">
                        Totale spese e costi registrati in Entrate/Uscite Extra per l'anno
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Utile al Netto delle Spese -->
        <div class="glossy-card overflow-hidden rounded-2xl flex flex-col justify-between border border-gray-200/80 dark:border-slate-700/70 shadow-sm transition-all duration-200 hover:shadow-md hover:border-indigo-500/40">
            <!-- Header Banda Superiore Colorata -->
            <div class="px-6 py-4 flex items-center justify-between text-white font-bold text-sm tracking-wide shadow-inner" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                <div class="flex items-center gap-2.5">
                    <div class="p-1 rounded-lg bg-white/15">
                        <x-heroicon-o-calculator class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-sm font-bold tracking-wide">Utile al Netto delle Spese</span>
                </div>
                <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full font-bold">Bilancio</span>
            </div>
            <!-- Corpo Card con Respiro e Padding -->
            <div class="p-6 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-3xl lg:text-4xl font-extrabold tracking-tight my-1 {{ $stats['profit_after_expenses'] >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-danger-600 dark:text-danger-400' }}">
                        € {{ number_format($stats['profit_after_expenses'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-medium">
                        Utile netto biglietti meno uscite extra (+ eventuali entrate extra)
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
