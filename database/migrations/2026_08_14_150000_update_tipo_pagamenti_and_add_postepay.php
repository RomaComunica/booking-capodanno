<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tipo_pagamenti')) {
            // 1. Uniform payment labels
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
        }

        // Attach Ricarica PostePay (4) to all locations if cross_pagamenti_location exists
        if (Schema::hasTable('cross_pagamenti_location') && Schema::hasTable('location')) {
            $locations = DB::table('location')->pluck('idLocation');
            foreach ($locations as $locationId) {
                DB::table('cross_pagamenti_location')->updateOrInsert(
                    [
                        'idLocationPagamento' => $locationId,
                        'idPagamento' => 4,
                    ],
                    [
                        'pagamentoLocationAttivo' => 1,
                        'costoPagamento' => 0,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        //
    }
};
