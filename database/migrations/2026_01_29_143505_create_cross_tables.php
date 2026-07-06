<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cross table: location <-> categories
        Schema::create('cross_location_categorie', function (Blueprint $table) {
            $table->integer('idCrossCat')->autoIncrement();
            $table->integer('idLocation');
            $table->integer('idCatLocation');
            $table->foreign('idLocation')->references('idLocation')->on('location')->onDelete('cascade');
            $table->foreign('idCatLocation')->references('idCatLocation')->on('categorie_location')->onDelete('cascade');
        });

        // Cross table: location <-> types
        Schema::create('cross_location_tipologie', function (Blueprint $table) {
            $table->integer('idCrossTip')->autoIncrement();
            $table->integer('idLocation');
            $table->integer('idTipLocation');
            $table->foreign('idLocation')->references('idLocation')->on('location')->onDelete('cascade');
            $table->foreign('idTipLocation')->references('idTipLocation')->on('tipologie_location')->onDelete('cascade');
        });

        // Cross table: location <-> shipping
        Schema::create('cross_spedizioni_location', function (Blueprint $table) {
            $table->integer('idLocationSpedizione');
            $table->integer('idSpedizione');
            $table->boolean('spedizioneLocationAttiva')->default(1);
            $table->decimal('costoSpedizione', 10, 2)->nullable()->default(0);
            $table->primary(['idLocationSpedizione', 'idSpedizione']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cross_spedizioni_location');
        Schema::dropIfExists('cross_location_tipologie');
        Schema::dropIfExists('cross_location_categorie');
    }
};
