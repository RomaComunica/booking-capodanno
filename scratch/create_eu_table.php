<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::create('entrate_uscite', function (Blueprint $table) {
    $table->id('idEntrataUscita');
    $table->date('dataOperazione');
    $table->enum('tipoOperazione', ['E', 'U']); // Entrata or Uscita
    $table->string('causale');
    $table->decimal('importo', 10, 2);
    $table->text('descrizione')->nullable();
    $table->timestamps();
});

echo "Table entrate_uscite created successfully.\n";
