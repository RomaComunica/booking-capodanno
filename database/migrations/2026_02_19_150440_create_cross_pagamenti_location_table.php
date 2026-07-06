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
        Schema::create('cross_pagamenti_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('idLocationPagamento');
            $table->unsignedInteger('idPagamento');
            $table->boolean('pagamentoLocationAttivo')->default(true);
            $table->decimal('costoPagamento', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cross_pagamenti_location');
    }
};
