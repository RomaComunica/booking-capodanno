<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-sync payment methods into database without needing SSH migrations
        try {
            if (Schema::hasTable('tipo_pagamenti')) {
                DB::table('tipo_pagamenti')->updateOrInsert(
                    ['idTipoPagamento' => 1],
                    [
                        'nomePagamento' => 'PayPal (+2€ Costi Gestione)',
                        'descrizioneTipoPagamento' => 'PayPal (+2€ Costi Gestione)',
                        'descrizionePagamento' => 'Pagamento sicuro tramite PayPal (+2€ costi di gestione per biglietto)',
                        'attivo' => 1,
                    ]
                );
                DB::table('tipo_pagamenti')->updateOrInsert(
                    ['idTipoPagamento' => 2],
                    [
                        'nomePagamento' => 'Bonifico Bancario',
                        'descrizioneTipoPagamento' => 'Bonifico Bancario',
                        'descrizionePagamento' => 'Bonifico bancario anticipato',
                        'attivo' => 1,
                    ]
                );
                DB::table('tipo_pagamenti')->updateOrInsert(
                    ['idTipoPagamento' => 4],
                    [
                        'nomePagamento' => 'Ricarica PostePay',
                        'descrizioneTipoPagamento' => 'Ricarica PostePay',
                        'descrizionePagamento' => 'Ricarica su carta PostePay anticipata',
                        'attivo' => 1,
                    ]
                );
                DB::table('tipo_pagamenti')->updateOrInsert(
                    ['idTipoPagamento' => 5],
                    [
                        'nomePagamento' => 'Carta di Credito o Poste Pay',
                        'descrizioneTipoPagamento' => 'Carta di Credito o Poste Pay',
                        'descrizionePagamento' => 'Pagamento sicuro con Carta di Credito o PostePay',
                        'attivo' => 1,
                    ]
                );

                // Auto-attach payment methods to all locations
                if (Schema::hasTable('cross_pagamenti_location') && Schema::hasTable('location')) {
                    $locations = DB::table('location')->pluck('idLocation');
                    $activePaymentIds = [1, 2, 4, 5];
                    foreach ($locations as $locId) {
                        foreach ($activePaymentIds as $pId) {
                            DB::table('cross_pagamenti_location')->updateOrInsert(
                                [
                                    'idLocationPagamento' => $locId,
                                    'idPagamento' => $pId,
                                ],
                                [
                                    'pagamentoLocationAttivo' => 1,
                                    'costoPagamento' => 0,
                                ]
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore during setup/migrations
        }
    }
}
