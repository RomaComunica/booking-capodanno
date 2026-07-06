<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tipo_pagamenti')) {
            Schema::create('tipo_pagamenti', function (Blueprint $table) {
                $table->integer('idTipoPagamento')->autoIncrement();
                $table->string('nomePagamento', 255)->default('');
                $table->text('descrizionePagamento')->nullable();
                $table->string('descrizioneTipoPagamento', 255)->default('');
                $table->boolean('attivo')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_pagamenti');
    }
};
