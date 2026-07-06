<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cross_partner_ingressi', function (Blueprint $table) {
            $table->id('idCrossPartnerIngressi');
            $table->integer('idTipologiaIngresso');
            $table->integer('idPartnerIngressi');
            $table->integer('idLocation');
            $table->integer('quantitaIngressi')->default(0);
            $table->integer('quantitaIngressiTerra')->default(0);
            $table->timestamps();
        });
        
        // Create unique index separately for SQLite compatibility
        Schema::table('cross_partner_ingressi', function (Blueprint $table) {
            $table->unique(['idTipologiaIngresso', 'idPartnerIngressi'], 'unique_partner_ticket');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cross_partner_ingressi');
    }
};
