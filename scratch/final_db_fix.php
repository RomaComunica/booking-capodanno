<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "Running Final DB Fix...\n";

function verifyAndCreate($table, $callback) {
    if (Schema::hasTable($table)) {
        echo "Table '$table' exists.\n";
        // Optional: Schema::drop($table); to force recreate
    } else {
        echo "Creating '$table'...\n";
        try {
            Schema::create($table, $callback);
            echo "Created '$table'.\n";
        } catch (\Exception $e) {
            echo "ERROR creating '$table': " . $e->getMessage() . "\n";
        }
    }
}

// DOMAIN TABLES
// 1. Categorie Location
verifyAndCreate('categorie_location', function (Blueprint $table) {
    $table->increments('idCatLocation');
    $table->string('nomeCatLocation', 255);
});

// 2. Tipologie Location
verifyAndCreate('tipologie_location', function (Blueprint $table) {
    $table->increments('idTipLocation');
    $table->string('nomeTipLocation', 255);
});

// 3. Location
verifyAndCreate('location', function (Blueprint $table) {
    $table->increments('idLocation');
    $table->string('nomeLocation', 255);
    $table->text('descrizioneLocation')->nullable();
    $table->string('indirizzoLocation', 255)->nullable();
    $table->string('cittaLocation', 255)->nullable();
    $table->string('capLocation', 10)->nullable();
    $table->string('telefonoLocation', 50)->nullable();
    $table->string('emailLocation', 255)->nullable();
    $table->string('sitoLocation', 255)->nullable();
    $table->string('latitudineLocation', 50)->nullable();
    $table->string('longitudineLocation', 50)->nullable();
    $table->boolean('attivaLocation')->default(1);
    $table->string('slugLocation', 255)->nullable();
    // New fields requested
    $table->string('locandina', 255)->nullable();
    $table->text('note')->nullable();
});

// 4. Tipologia Ingresso
verifyAndCreate('tipologia_ingresso', function (Blueprint $table) {
    $table->increments('idIngressoCont');
    $table->integer('idIngresso');
    $table->integer('idLocation');
    $table->string('nomeIngresso', 255);
    $table->decimal('prezzoIngresso', 10, 2)->default(0);
    $table->tinyInteger('tipoProvvigione')->default(0);
    $table->decimal('valoreProvvigione', 10, 2)->default(0);
    $table->tinyInteger('tipoQuantitaBonus')->default(0);
    $table->integer('quantitaBonus')->default(0);
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
    $table->integer('ordine')->default(0);
});

// 5. Cross Tables (Simplified)
verifyAndCreate('cross_location_categorie', function (Blueprint $table) {
    $table->integer('idLocation');
    $table->integer('idCatLocation');
    $table->primary(['idLocation', 'idCatLocation']);
});
verifyAndCreate('cross_location_tipologie', function (Blueprint $table) {
    $table->integer('idLocation');
    $table->integer('idTipLocation');
    $table->primary(['idLocation', 'idTipLocation']);
});
verifyAndCreate('cross_partner_ingressi', function (Blueprint $table) {
    $table->integer('idPartnerIngressi');
    $table->integer('idTipologiaIngresso');
    $table->integer('quantitaIngressi')->default(0);
    $table->integer('idLocation');
    $table->primary(['idPartnerIngressi', 'idTipologiaIngresso']);
});
verifyAndCreate('cross_pagamenti_location', function (Blueprint $table) {
    $table->integer('idLocationPagamento');
    $table->integer('idPagamento');
    $table->boolean('pagamentoLocationAttivo')->default(1);
    $table->primary(['idLocationPagamento', 'idPagamento']);
});
verifyAndCreate('cross_spedizioni_location', function (Blueprint $table) {
    $table->integer('idLocationSpedizione');
    $table->integer('idSpedizione');
    $table->boolean('spedizioneLocationAttiva')->default(1);
    $table->primary(['idLocationSpedizione', 'idSpedizione']);
});
verifyAndCreate('tipo_pagamenti', function (Blueprint $table) {
    $table->increments('idTipoPagamento');
    $table->string('nomeTipoPagamento', 255);
    $table->boolean('attivo')->default(1);
});
verifyAndCreate('tipo_spedizioni', function (Blueprint $table) {
    $table->increments('idSpedizione');
    $table->string('nomeSpedizione', 255);
    $table->decimal('costoSpedizione', 10, 2)->default(0);
});

// 1. Partner (Original)
verifyAndCreate('partner', function (Blueprint $table) {
    $table->increments('idPartner');
    $table->string('nomePartner', 255);
    $table->string('emailPartner', 100)->nullable();
    $table->string('passwordPartner', 255)->nullable();
    $table->boolean('attivoPartner')->default(1);
    $table->string('codicePartner', 50)->nullable();
});

// 2. Ordini
verifyAndCreate('ordini', function (Blueprint $table) {
    $table->increments('idordine'); // Safest for SQLite
    $table->string('numeroOrdine', 50)->index();
    $table->dateTime('dataOrdine')->nullable();
    $table->dateTime('dataPagamentoOrdine')->nullable();
    $table->decimal('totaleOrdine', 10, 2)->default(0);
    $table->string('statoOrdine', 50)->default('pending');
    $table->integer('idLocationOrdine')->nullable();
    $table->integer('idPartnerOrdine')->nullable();
    $table->integer('tipoPagamentoOrdine')->nullable();
    $table->integer('tipoSpedizioneOrdine')->nullable();
    $table->string('nomeClienteOrdine', 100)->nullable();
    $table->string('cognomeClienteOrdine', 100)->nullable();
    $table->string('emailClienteOrdine', 100)->nullable();
    $table->string('telefonoClienteOrdine', 50)->nullable();
});

// 3. Righe Ordini
verifyAndCreate('righe_ordini', function (Blueprint $table) {
    $table->increments('idRigaOrdine');
    $table->string('numeroOrdine', 50)->index();
    $table->integer('idTipologiaIngresso')->nullable();
    $table->string('nomeIngresso', 255)->nullable();
    $table->integer('quantitaIngressi')->default(0);
    $table->decimal('prezzoIngresso', 10, 2)->default(0);
    $table->decimal('totaleRiga', 10, 2)->default(0);
});

// 4. Vouchers
verifyAndCreate('vouchers', function (Blueprint $table) {
    $table->increments('idVoucher');
    $table->string('codiceVoucher', 50)->index();
    $table->string('numeroOrdineVoucher', 50)->index();
    $table->integer('idIngressoVoucher')->nullable();
    $table->boolean('utilizzato')->default(0);
});

echo "Double checking tables existence...\n";
$tables = ['partner', 'ordini', 'righe_ordini', 'vouchers', 'location'];
foreach ($tables as $t) {
    echo "$t: " . (Schema::hasTable($t) ? "OK" : "MISSING") . "\n";
}
