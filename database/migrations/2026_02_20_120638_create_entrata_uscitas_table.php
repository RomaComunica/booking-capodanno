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
        Schema::create('entrate_uscite', function (Blueprint $table) {
            $table->id('idEntrataUscita');
            $table->date('dataOperazione');
            $table->enum('tipoOperazione', ['E', 'U']); // Entrata or Uscita
            $table->string('causale');
            $table->decimal('importo', 10, 2);
            $table->text('descrizione')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrate_uscite');
    }
};
