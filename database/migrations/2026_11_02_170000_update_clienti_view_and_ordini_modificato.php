<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ordini') && !Schema::hasColumn('ordini', 'modificato_manualmente')) {
            Schema::table('ordini', function (Blueprint $table) {
                $table->boolean('modificato_manualmente')->default(false);
            });
        }

        if (!Schema::hasTable('clienti')) {
            Schema::create('clienti', function (Blueprint $table) {
                $table->id('idCliente');
                $table->string('nomeCliente', 255)->nullable();
                $table->string('cognomeCliente', 255)->nullable();
                $table->string('emailCliente', 255)->nullable();
                $table->string('telefonoCliente', 50)->nullable();
                $table->string('cittaCliente', 255)->nullable();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('clienti', 'cittaCliente')) {
            Schema::table('clienti', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        if (Schema::hasTable('ordini') && Schema::hasColumn('ordini', 'modificato_manualmente')) {
            Schema::table('ordini', function (Blueprint $table) {
                $table->dropColumn('modificato_manualmente');
            });
        }

        DB::statement("DROP VIEW IF EXISTS clienti_view");
        DB::statement("
            CREATE VIEW clienti_view AS
            SELECT 
                emailClienteOrdine as email,
                MAX(nomeClienteOrdine) as nome,
                MAX(cognomeClienteOrdine) as cognome,
                MAX(telefonoClienteOrdine) as telefono,
                COUNT(idordine) as numero_ordini,
                SUM(totaleOrdine) as totale_speso,
                MAX(dataOrdine) as ultimo_ordine
            FROM ordini
            WHERE emailClienteOrdine IS NOT NULL AND emailClienteOrdine != ''
            GROUP BY emailClienteOrdine
        ");
    }
};
