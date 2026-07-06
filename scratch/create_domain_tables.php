<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

function createTable($tableName, $callback) {
    try {
        if (Schema::hasTable($tableName)) {
            echo "Table '$tableName' already exists. Dropping...\n";
            Schema::drop($tableName);
        }
        
        echo "Creating table '$tableName'...\n";
        Schema::create($tableName, $callback);
        echo "Table '$tableName' created successfully.\n\n";
    } catch (\Exception $e) {
        echo "ERROR creating '$tableName': " . $e->getMessage() . "\n\n";
    }
}

echo "Starting Manual Domain Tables Creation...\n";
echo "DB Connection: " . DB::getDefaultConnection() . "\n";
echo "DB Database: " . config('database.connections.sqlite.database') . "\n\n";

// Disable Foreign Keys for SQLite to avoid issues during drop/create
DB::statement('PRAGMA foreign_keys = OFF');

/*
// 1. Categorie Location
createTable('categorie_location', function (Blueprint $table) {
    $table->integer('idCatLocation')->primary()->autoIncrement();
    $table->string('nomeCatLocation', 255);
});

// 2. Tipologie Location
createTable('tipologie_location', function (Blueprint $table) {
    $table->integer('idTipLocation')->primary()->autoIncrement();
    $table->string('nomeTipLocation', 255);
});

// 3. Location
createTable('location', function (Blueprint $table) {
    $table->integer('idLocation')->primary()->autoIncrement();
    $table->string('nomeLocation', 255);
    $table->string('indirizzoLocation', 255)->nullable();
    $table->string('telefonoLocation', 50)->nullable();
    $table->string('emailLocation', 100)->nullable();
    $table->string('riferimentoLocation', 100)->nullable();
    $table->string('telefonoRifLocation', 50)->nullable();
    $table->string('emailRifLocation', 100)->nullable();
    $table->boolean('attivaLocation')->default(1);
});

// 4. Cross Tables (Simple, no foreign keys enforced at DB level for SQLite simplicity in this manual execution)
createTable('cross_location_categorie', function (Blueprint $table) {
    $table->integer('idLocation');
    $table->integer('idCatLocation');
    $table->primary(['idLocation', 'idCatLocation']);
});

createTable('cross_location_tipologie', function (Blueprint $table) {
    $table->integer('idLocation');
    $table->integer('idTipLocation');
    $table->primary(['idLocation', 'idTipLocation']);
});

createTable('cross_pagamenti_location', function (Blueprint $table) {
    $table->integer('idLocationPagamento');
    $table->integer('idPagamento');
    $table->boolean('pagamentoLocationAttivo')->default(1);
    $table->primary(['idLocationPagamento', 'idPagamento']);
});

createTable('cross_spedizioni_location', function (Blueprint $table) {
    $table->integer('idLocationSpedizione');
    $table->integer('idSpedizione');
    $table->boolean('spedizioneLocationAttiva')->default(1);
    $table->primary(['idLocationSpedizione', 'idSpedizione']);
});
*/

// 5. Tipologia Ingresso
// 5. Tipologia Ingresso (FIXED)
createTable('tipologia_ingresso', function (Blueprint $table) {
    // SQLite primary key auto-increment syntax requires integer primary key
    $table->integer('idIngressoCont')->primary(); 
    // Wait, ->autoIncrement() is implied by integer primary key in SQLite but Laravel handles it.
    // The previous error "more than one primary key" suggests a conflict.
    // Let's rely on standard Laravel definition which usually works.
    
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

// 6. Cross Partner Ingressi
createTable('cross_partner_ingressi', function (Blueprint $table) {
    $table->integer('idPartnerIngressi');
    $table->integer('idTipologiaIngresso');
    $table->integer('quantitaIngressi')->default(0);
    $table->integer('quantitaIngressiTerra')->default(0);
    $table->integer('idLocation');
    $table->primary(['idPartnerIngressi', 'idTipologiaIngresso']);
});

echo "DONE.\n";
