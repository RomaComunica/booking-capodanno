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
        Schema::create('comuni_italiani', function (Blueprint $table) {
            $table->id('idComune');
            $table->string('nomeComune', 255)->index();
            $table->string('siglaProvincia', 2)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comuni_italiani');
    }
};
