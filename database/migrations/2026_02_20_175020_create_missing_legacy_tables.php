<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tipo_biglietto')) {
            Schema::create('tipo_biglietto', function (Blueprint $table) {
                $table->integer('idBiglietto')->autoIncrement();
                $table->string('nomeTipoBiglietto', 255)->default('');
            });
        }

        if (!Schema::hasTable('tipo_spedizione')) {
            Schema::create('tipo_spedizione', function (Blueprint $table) {
                $table->integer('idSpedizione')->autoIncrement();
                $table->string('nomeSpedizione', 255)->default('');
                $table->text('descrizioneSpedizione')->nullable();
                $table->text('testoAggiuntivo')->nullable();
                $table->integer('attivo')->default(1);
            });
        }

        if (!Schema::hasTable('voucher')) {
            Schema::create('voucher', function (Blueprint $table) {
                $table->integer('idVoucher')->autoIncrement();
                $table->integer('numeroVoucher')->default(0);
                $table->string('codiceVoucher', 255)->default('');
                $table->integer('numeroOrdineVoucher')->default(0);
                $table->integer('idLocationVoucher')->default(0);
                $table->dateTime('dataVoucher')->nullable();
                $table->string('nomeVoucher', 255)->default('');
                $table->string('cognomeVoucher', 255)->default('');
                $table->string('telefonoVoucher', 50)->default('');
                $table->string('emailVoucher', 255)->default('');
            });
        }

        if (!Schema::hasTable('entrateuscite')) {
            Schema::create('entrateuscite', function (Blueprint $table) {
                $table->integer('idEntrateUscite')->autoIncrement();
                $table->string('causale', 255)->default('');
                $table->decimal('Importo', 10, 2)->default(0);
                $table->dateTime('dataCausale')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entrateuscite');
        Schema::dropIfExists('voucher');
        Schema::dropIfExists('tipo_spedizione');
        Schema::dropIfExists('tipo_biglietto');
    }
};
