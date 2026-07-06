<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('partner')) {
            Schema::create('partner', function (Blueprint $table) {
                $table->integer('idPartner')->autoIncrement();
                $table->integer('tipoPartner')->default(0);
                $table->string('nomePartner', 255)->default('');
                $table->string('emailPartner', 255)->default('');
                $table->string('telefonoPartner', 50)->default('');
                $table->string('urlPartner', 255)->default('');
                $table->integer('attivoPartner')->default(0);
                $table->integer('LocationPartner')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ordini')) {
            Schema::create('ordini', function (Blueprint $table) {
                $table->integer('idordine')->autoIncrement();
                $table->integer('numeroOrdine')->default(0);
                $table->dateTime('dataOrdine')->nullable();
                $table->integer('idLocationOrdine')->default(0);
                $table->string('IPcliente', 255)->nullable();
                $table->string('nomeClienteOrdine', 255)->default('');
                $table->string('cognomeClienteOrdine', 255)->default('');
                $table->string('indirizzoClienteOrdine', 255)->default('');
                $table->string('capClienteOrdine', 20)->default('');
                $table->string('cittaClienteOrdine', 255)->default('');
                $table->string('provinciaClienteOrdine', 20)->default('');
                $table->string('telefonoClienteOrdine', 50)->default('');
                $table->string('emailClienteOrdine', 255)->default('');
                $table->text('noteClienteOridine')->default(''); // Note typo in noteClienteOridine
                $table->decimal('totaleOrdine', 10, 2)->default(0);
                $table->integer('tipoPagamentoOrdine')->default(0);
                $table->integer('tipoSpedizioneOrdine')->default(0);
                $table->integer('idPartnerOrdine')->default(0);
                $table->integer('ordineConfermato')->default(0);
                $table->dateTime('dataPagamentoOrdine')->nullable();
                $table->integer('statoOrdine')->default(0);
                $table->dateTime('dataPagamentoOrdineFornitore')->nullable();
                $table->text('noteOrdine')->default('');
                $table->dateTime('dataPagamentoOrdinePartner')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('righe_ordine')) {
            Schema::create('righe_ordine', function (Blueprint $table) {
                $table->integer('idRigaOrdine')->autoIncrement();
                $table->integer('numeroOrdine')->default(0);
                $table->integer('idIngressoOrdine')->default(0);
                $table->integer('idClienteOrdine')->default(0);
                $table->integer('idPartnerOrdine')->default(0);
                $table->integer('quantitaIngressoOrdine')->default(0);
                $table->decimal('prezzoIngressoordine', 10, 2)->nullable()->default(0); // Note small 'o' in prezzoIngressoordine
                $table->string('nomeIngressoOrdine', 255)->default('');
                $table->string('orarioIngressoOrdine', 50)->default('00:00');
                $table->dateTime('dataIngressoOrdine')->nullable();
                $table->integer('ingressoSenzaDataOrdine')->default(0);
                $table->string('etichettaIngressoOrdine', 255)->default('');
                $table->integer('opzioneRimborso')->default(0);
                $table->decimal('rimborsoValore', 10, 2)->nullable();
                $table->dateTime('rimborsoData')->nullable();
                $table->decimal('totaleRiga', 10, 2)->nullable()->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('righe_ordine');
        Schema::dropIfExists('ordini');
        Schema::dropIfExists('partner');
    }
};
