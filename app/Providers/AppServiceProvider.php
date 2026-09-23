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
        // Auto-sync payment and shipping methods into database once without needing SSH migrations
        try {
            if (!\Illuminate\Support\Facades\Cache::has('payment_shipping_synced_v3')) {
                \Illuminate\Support\Facades\Cache::forever('payment_shipping_synced_v3', true);
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
                    ['idTipoPagamento' => 3],
                    [
                        'nomePagamento' => 'Contanti',
                        'descrizioneTipoPagamento' => 'Contanti',
                        'descrizionePagamento' => 'Pagamento in contanti al ritiro presso la nostra sede',
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
                    $activePaymentIds = [1, 2, 3, 4, 5];
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

            // Auto-sync shipping methods into database
            if (Schema::hasTable('tipo_spedizione')) {
                DB::table('tipo_spedizione')->updateOrInsert(
                    ['idSpedizione' => 1],
                    [
                        'nomeSpedizione' => 'Voucher via E-mail',
                        'descrizioneSpedizione' => 'Voucher via E-mail',
                        'testoAggiuntivo' => 'Ricevi il voucher direttamente via email',
                        'attivo' => 1,
                    ]
                );
                DB::table('tipo_spedizione')->updateOrInsert(
                    ['idSpedizione' => 4],
                    [
                        'nomeSpedizione' => 'Ritiro in sede',
                        'descrizioneSpedizione' => 'Ritiro in sede',
                        'testoAggiuntivo' => 'Ritiro presso la nostra sede (Via dei CARRARESI 12 - 00164 Roma)',
                        'attivo' => 1,
                    ]
                );

                // Auto-attach shipping methods to all locations
                if (Schema::hasTable('cross_spedizioni_location') && Schema::hasTable('location')) {
                    $locations = DB::table('location')->pluck('idLocation');
                    $activeShippingIds = [1, 4];
                    foreach ($locations as $locId) {
                        foreach ($activeShippingIds as $sId) {
                            DB::table('cross_spedizioni_location')->updateOrInsert(
                                [
                                    'idLocationSpedizione' => $locId,
                                    'idSpedizione' => $sId,
                                ],
                                [
                                    'spedizioneLocationAttiva' => 1,
                                    'costoSpedizione' => 0,
                                ]
                            );
                        }
                    }
                }
            }
            }
        } catch (\Exception $e) {
            // Ignore during setup/migrations
        }

        // Auto-ensure cross_partner_ingressi table exists and legacy entries are synced
        try {
            if (!Schema::hasTable('cross_partner_ingressi')) {
                Schema::create('cross_partner_ingressi', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->increments('idCrossPartnerIngressi');
                    $table->integer('idTipologiaIngresso');
                    $table->integer('idPartnerIngressi');
                    $table->integer('idLocation')->default(0);
                    $table->integer('quantitaIngressi')->default(0);
                    $table->integer('quantitaIngressiTerra')->default(0);
                    $table->timestamps();
                });
            }

            if (!\Illuminate\Support\Facades\Cache::has('cross_partner_legacy_synced_v1')) {
                \Illuminate\Support\Facades\Cache::forever('cross_partner_legacy_synced_v1', true);
                if (Schema::hasTable('cross_partner_ingressi') && Schema::hasTable('tipologia_ingresso')) {
                    $orphans = DB::table('cross_partner_ingressi')
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('tipologia_ingresso')
                                ->whereColumn('tipologia_ingresso.idIngressoCont', 'cross_partner_ingressi.idTipologiaIngresso');
                        })
                        ->get();

                    foreach ($orphans as $orphan) {
                        $matched = DB::table('tipologia_ingresso')
                            ->where('idLocation', $orphan->idLocation)
                            ->where('idIngresso', $orphan->idTipologiaIngresso)
                            ->first();
                        if ($matched) {
                            DB::table('cross_partner_ingressi')
                                ->where('idCrossPartnerIngressi', $orphan->idCrossPartnerIngressi)
                                ->update(['idTipologiaIngresso' => $matched->idIngressoCont]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore during setup/migrations
        }
    }
}
