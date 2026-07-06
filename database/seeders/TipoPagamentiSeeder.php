<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoPagamentiSeeder extends Seeder
{
    public function run()
    {
        // Check if table is empty
        if (DB::table('tipo_pagamenti')->count() == 0) {
            DB::table('tipo_pagamenti')->insert([
                ['descrizioneTipoPagamento' => 'PayPal', 'attivo' => true],
                ['descrizioneTipoPagamento' => 'Carta di Credito (Nexi)', 'attivo' => true],
                ['descrizioneTipoPagamento' => 'Bonifico Bancario', 'attivo' => false],
            ]);
        }
    }
}
