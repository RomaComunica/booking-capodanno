<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location', function (Blueprint $table) {
            $table->integer('idLocation')->autoIncrement();
            $table->string('nomeLocation', 255);
            $table->string('indirizzoLocation', 255)->nullable();
            $table->string('telefonoLocation', 50)->nullable();
            $table->string('emailLocation', 100)->nullable();
            $table->string('riferimentoLocation', 100)->nullable();
            $table->string('telefonoRifLocation', 50)->nullable();
            $table->string('emailRifLocation', 100)->nullable();
            $table->string('immagineLocation', 255)->nullable();
            $table->string('locandina', 255)->nullable();
            $table->text('note')->nullable();
            $table->boolean('attivaLocation')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location');
    }
};
