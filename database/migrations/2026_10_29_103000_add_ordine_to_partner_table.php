<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner')) {
            if (!Schema::hasColumn('partner', 'ordine')) {
                Schema::table('partner', function (Blueprint $table) {
                    $table->integer('ordine')->default(0);
                });

                // Inizializza l'ordine dei partner esistenti in base al loro idPartner
                try {
                    DB::statement('UPDATE partner SET ordine = idPartner WHERE ordine = 0 OR ordine IS NULL');
                } catch (\Throwable $e) {
                    // Ignore
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('partner')) {
            if (Schema::hasColumn('partner', 'ordine')) {
                Schema::table('partner', function (Blueprint $table) {
                    $table->dropColumn('ordine');
                });
            }
        }
    }
};
