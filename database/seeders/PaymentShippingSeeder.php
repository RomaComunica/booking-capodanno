<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentShippingSeeder extends Seeder
{
    public function run()
    {
        // Insert payment methods
        $paymentMethods = [
            ['idTipoPagamento' => 1, 'nomePagamento' => 'PayPal Express Checkout', 'descrizionePagamento' => 'Pagamento sicuro tramite PayPal'],
            ['idTipoPagamento' => 2, 'nomePagamento' => 'Bonifico Bancario', 'descrizionePagamento' => 'Bonifico bancario anticipato'],
            ['idTipoPagamento' => 3, 'nomePagamento' => 'Contanti', 'descrizionePagamento' => 'Pagamento in contanti al ritiro'],
            ['idTipoPagamento' => 4, 'nomePagamento' => 'Ricarica Postepay', 'descrizionePagamento' => 'Ricarica su carta Postepay'],
            ['idTipoPagamento' => 5, 'nomePagamento' => 'Carta di Credito (Nexi)', 'descrizionePagamento' => 'Pagamento con carta di credito tramite Nexi XPay'],
        ];

        foreach ($paymentMethods as $method) {
            DB::table('tipo_pagamenti')->updateOrInsert(
                ['idTipoPagamento' => $method['idTipoPagamento']],
                $method
            );
        }

        // Insert shipping methods
        $shippingMethods = [
            ['idSpedizione' => 1, 'nomeSpedizione' => 'Voucher via E-mail', 'descrizioneSpedizione' => 'Ricevi il voucher direttamente via email'],
            ['idSpedizione' => 2, 'nomeSpedizione' => 'Consegna a domicilio dentro GRA', 'descrizioneSpedizione' => 'Consegna a domicilio dentro il Grande Raccordo Anulare'],
            ['idSpedizione' => 3, 'nomeSpedizione' => 'Consegna a domicilio fuori GRA', 'descrizioneSpedizione' => 'Consegna a domicilio fuori dal Grande Raccordo Anulare'],
            ['idSpedizione' => 4, 'nomeSpedizione' => 'Ritiro in ufficio', 'descrizioneSpedizione' => 'Ritiro presso Via dei CARRARESI 12 - 00164 - Roma (9:30-17:30)'],
        ];

        foreach ($shippingMethods as $method) {
            DB::table('tipo_spedizione')->updateOrInsert(
                ['idSpedizione' => $method['idSpedizione']],
                $method
            );
        }

        // Get all locations
        $locations = DB::table('location')->pluck('idLocation');

        // Associate all payment methods with all locations
        foreach ($locations as $locationId) {
            foreach ($paymentMethods as $method) {
                DB::table('cross_pagamenti_location')->updateOrInsert(
                    [
                        'idLocationPagamento' => $locationId,
                        'idPagamento' => $method['idTipoPagamento'],
                    ],
                    [
                        'pagamentoLocationAttivo' => 1,
                        'costoPagamento' => 0, // No extra cost for payments
                    ]
                );
            }

            // Associate all shipping methods with all locations
            foreach ($shippingMethods as $index => $method) {
                $cost = 0;
                if ($method['idSpedizione'] == 2) $cost = 10; // Inside GRA
                if ($method['idSpedizione'] == 3) $cost = 15; // Outside GRA

                DB::table('cross_spedizioni_location')->updateOrInsert(
                    [
                        'idLocationSpedizione' => $locationId,
                        'idSpedizione' => $method['idSpedizione'],
                    ],
                    [
                        'spedizioneLocationAttiva' => 1,
                        'costoSpedizione' => $cost,
                    ]
                );
            }
        }

        $this->command->info('Payment and shipping methods seeded successfully!');
    }
}
