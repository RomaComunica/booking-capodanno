<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipologia_ingresso', function (Blueprint $table) {
            $table->integer('idIngressoCont')->autoIncrement();
            $table->integer('idIngresso');
            $table->integer('idLocation');
            $table->string('nomeIngresso', 255);
            $table->decimal('prezzoIngresso', 10, 2)->default(0);
            $table->tinyInteger('tipoProvvigione')->default(0);
            $table->decimal('valoreProvvigione', 10, 2)->default(0);
            $table->tinyInteger('tipoQuantitaBonus')->default(0);
            $table->integer('quantitaBonus')->default(0);
            $table->tinyInteger('tipoProvvigioneBonus')->default(0);
            $table->decimal('valoreProvvigioneBonus', 10, 2)->default(0);
            $table->tinyInteger('tipoProvvigionePartner')->default(0);
            $table->decimal('valoreProvvigionePartner', 10, 2)->default(0);
            $table->tinyInteger('tipoQuantitaBonusPartner')->default(0);
            $table->integer('quantitaBonusPartner')->default(0);
            $table->decimal('valoreProvvigioneBonusPartner', 10, 2)->default(0);
            $table->integer('giacenzaBiglietti')->default(0);
            $table->boolean('ingressoAttivo')->default(1);
            $table->integer('tipoBigliettoIngresso')->nullable();
            $table->time('orarioIngresso')->nullable();
            $table->date('dataIngresso')->nullable();
            $table->integer('giorniPrimaIngresso')->default(1);
            $table->boolean('ingressoSenzaData')->default(0);
            $table->string('etichettaIngresso', 255)->nullable();
            $table->boolean('opzioneRimborso')->default(0);
            $table->decimal('rimborsoValorePersona', 10, 2)->nullable();
            $table->dateTime('rimborsoDataScadenza')->nullable();
            $table->text('rimborsoTesto')->nullable();
            $table->integer('ordine')->default(0); // For drag-and-drop ordering
            $table->timestamps();
            
            $table->foreign('idLocation')->references('idLocation')->on('location')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipologia_ingresso');
    }
};
