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
            if (!\Illuminate\Support\Facades\Cache::has('payment_shipping_synced_v4')) {
                \Illuminate\Support\Facades\Cache::forever('payment_shipping_synced_v4', true);
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

                // Initialize cross_pagamenti_location once in DB (without overwriting future per-location choices)
                if (Schema::hasTable('cross_pagamenti_location') && Schema::hasTable('location')) {
                    $alreadyInitialized = \App\Models\Setting::getVal('location_payments_initialized_v1', false);
                    $locations = DB::table('location')->pluck('idLocation');
                    $activePaymentIds = [1, 2, 3, 4, 5];

                    foreach ($locations as $locId) {
                        foreach ($activePaymentIds as $pId) {
                            $exists = DB::table('cross_pagamenti_location')
                                ->where('idLocationPagamento', $locId)
                                ->where('idPagamento', $pId)
                                ->exists();

                            if (!$exists) {
                                DB::table('cross_pagamenti_location')->insert([
                                    'idLocationPagamento' => $locId,
                                    'idPagamento' => $pId,
                                    'pagamentoLocationAttivo' => 1,
                                ]);
                            } elseif (!$alreadyInitialized) {
                                DB::table('cross_pagamenti_location')
                                    ->where('idLocationPagamento', $locId)
                                    ->where('idPagamento', $pId)
                                    ->update(['pagamentoLocationAttivo' => 1]);
                            }
                        }
                    }

                    if (!$alreadyInitialized) {
                        \App\Models\Setting::setVal('sistema', 'location_payments_initialized_v1', true, 'boolean');
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

            if (!\Illuminate\Support\Facades\Cache::has('cross_partner_legacy_synced_v2')) {
                \Illuminate\Support\Facades\Cache::forever('cross_partner_legacy_synced_v2', true);
                if (Schema::hasTable('cross_partner_ingressi') && Schema::hasTable('tipologia_ingresso')) {
                    $orphans = DB::table('cross_partner_ingressi')
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('tipologia_ingresso')
                                ->whereColumn('tipologia_ingresso.idIngressoCont', 'cross_partner_ingressi.idTipologiaIngresso')
                                ->whereColumn('tipologia_ingresso.idLocation', 'cross_partner_ingressi.idLocation');
                        })
                        ->get();

                    foreach ($orphans as $orphan) {
                        $matched = DB::table('tipologia_ingresso')
                            ->where('idLocation', $orphan->idLocation)
                            ->where('idIngresso', $orphan->idTipologiaIngresso)
                            ->orderByDesc('ingressoAttivo')
                            ->orderByDesc('idIngressoCont')
                            ->first();
                        if ($matched) {
                            $alreadyExists = DB::table('cross_partner_ingressi')
                                ->where('idTipologiaIngresso', $matched->idIngressoCont)
                                ->where('idPartnerIngressi', $orphan->idPartnerIngressi)
                                ->exists();
                            if (!$alreadyExists) {
                                DB::table('cross_partner_ingressi')
                                    ->where('idCrossPartnerIngressi', $orphan->idCrossPartnerIngressi)
                                    ->update(['idTipologiaIngresso' => $matched->idIngressoCont]);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore during setup/migrations
        }
        // Auto-ensure partner table has 'ordine' column
        try {
            if (Schema::hasTable('partner') && !Schema::hasColumn('partner', 'ordine')) {
                Schema::table('partner', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->integer('ordine')->default(0);
                });
                try {
                    DB::statement('UPDATE partner SET ordine = idPartner WHERE ordine = 0 OR ordine IS NULL');
                } catch (\Throwable $e) {
                    // Ignore
                }
            }
        } catch (\Exception $e) {
            // Ignore during setup/migrations
        }

        // Auto-ensure location table has 'cittaLocation' column (default 'Roma')
        try {
            if (Schema::hasTable('location') && !Schema::hasColumn('location', 'cittaLocation')) {
                Schema::table('location', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('cittaLocation', 100)->nullable()->default('Roma')->after('indirizzoLocation');
                });
                try {
                    DB::table('location')
                        ->whereNull('cittaLocation')
                        ->orWhere('cittaLocation', '')
                        ->update(['cittaLocation' => 'Roma']);
                } catch (\Throwable $e) {
                    // Ignore
                }
            }
        } catch (\Throwable $e) {
            // Ignore during setup/migrations
        }

        // Auto-ensure ordini has 'modificato_manualmente' column & clienti_view unions ordini + manual clienti
        try {
            if (Schema::hasTable('ordini') && !Schema::hasColumn('ordini', 'modificato_manualmente')) {
                Schema::table('ordini', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->boolean('modificato_manualmente')->default(false);
                });
            }

            if (Schema::hasTable('ordini') && !\Illuminate\Support\Facades\Cache::has('clienti_view_union_v1')) {
                if (!Schema::hasTable('clienti')) {
                    Schema::create('clienti', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->id('idCliente');
                        $table->string('nomeCliente', 255)->nullable();
                        $table->string('cognomeCliente', 255)->nullable();
                        $table->string('emailCliente', 255)->nullable();
                        $table->string('telefonoCliente', 50)->nullable();
                        $table->string('cittaCliente', 255)->nullable();
                        $table->timestamps();
                    });
                } elseif (!Schema::hasColumn('clienti', 'cittaCliente')) {
                    Schema::table('clienti', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('cittaCliente', 255)->nullable();
                    });
                }

                DB::statement("DROP VIEW IF EXISTS clienti_view");
                DB::statement("
                    CREATE VIEW clienti_view AS
                    SELECT
                        email,
                        MAX(nome) as nome,
                        MAX(cognome) as cognome,
                        MAX(telefono) as telefono,
                        SUM(is_ordine) as numero_ordini,
                        SUM(importo_ordine) as totale_speso,
                        MAX(data_rif) as ultimo_ordine
                    FROM (
                        SELECT
                            LOWER(TRIM(emailClienteOrdine)) as email,
                            nomeClienteOrdine as nome,
                            cognomeClienteOrdine as cognome,
                            telefonoClienteOrdine as telefono,
                            1 as is_ordine,
                            COALESCE(totaleOrdine, 0) as importo_ordine,
                            dataOrdine as data_rif
                        FROM ordini
                        WHERE emailClienteOrdine IS NOT NULL AND TRIM(emailClienteOrdine) != ''
                        UNION ALL
                        SELECT
                            LOWER(TRIM(emailCliente)) as email,
                            nomeCliente as nome,
                            cognomeCliente as cognome,
                            telefonoCliente as telefono,
                            0 as is_ordine,
                            0 as importo_ordine,
                            created_at as data_rif
                        FROM clienti
                        WHERE emailCliente IS NOT NULL AND TRIM(emailCliente) != ''
                    ) AS combined_clienti
                    GROUP BY email
                ");

                \Illuminate\Support\Facades\Cache::forever('clienti_view_union_v1', true);
            }
        } catch (\Throwable $e) {
            // Ignore during setup/migrations
        }
    }
}
