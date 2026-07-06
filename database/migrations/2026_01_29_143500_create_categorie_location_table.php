<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorie_location', function (Blueprint $table) {
            $table->integer('idCatLocation')->autoIncrement();
            $table->string('nomeCatLocation', 255);
        });

        Schema::create('tipologie_location', function (Blueprint $table) {
            $table->integer('idTipLocation')->autoIncrement();
            $table->string('nomeTipLocation', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorie_location');
        Schema::dropIfExists('tipologie_location');
    }
};
