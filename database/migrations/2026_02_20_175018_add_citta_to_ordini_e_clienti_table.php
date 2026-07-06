<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('ordini', 'cittaClienteOrdine')) {
            Schema::table('ordini', function (Blueprint $table) {
                $table->string('cittaClienteOrdine', 255)->nullable();
            });
        }
        
        if (!Schema::hasColumn('clienti', 'cittaCliente')) {
            Schema::table('clienti', function (Blueprint $table) {
                $table->string('cittaCliente', 255)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clienti', function (Blueprint $table) {
            if (Schema::hasColumn('clienti', 'cittaCliente')) {
                $table->dropColumn('cittaCliente');
            }
        });
        
        Schema::table('ordini', function (Blueprint $table) {
            if (Schema::hasColumn('ordini', 'cittaClienteOrdine')) {
                $table->dropColumn('cittaClienteOrdine');
            }
        });
    }
};
