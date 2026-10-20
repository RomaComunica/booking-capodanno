<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('location')) {
            Schema::table('location', function (Blueprint $table) {
                if (!Schema::hasColumn('location', 'ibanLocation')) {
                    $table->string('ibanLocation', 50)->nullable()->default('');
                }
                if (!Schema::hasColumn('location', 'intestatarioLocation')) {
                    $table->string('intestatarioLocation', 255)->nullable()->default('');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('location')) {
            Schema::table('location', function (Blueprint $table) {
                if (Schema::hasColumn('location', 'ibanLocation')) {
                    $table->dropColumn('ibanLocation');
                }
                if (Schema::hasColumn('location', 'intestatarioLocation')) {
                    $table->dropColumn('intestatarioLocation');
                }
            });
        }
    }
};
