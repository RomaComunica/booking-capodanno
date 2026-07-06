<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS clienti_view");
    }
};
