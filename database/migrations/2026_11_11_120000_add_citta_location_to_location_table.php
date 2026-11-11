<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('location') && !Schema::hasColumn('location', 'cittaLocation')) {
            Schema::table('location', function (Blueprint $table) {
                $table->string('cittaLocation', 100)->nullable()->default('Roma')->after('indirizzoLocation');
            });

            DB::table('location')
                ->whereNull('cittaLocation')
                ->orWhere('cittaLocation', '')
                ->update(['cittaLocation' => 'Roma']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('location') && Schema::hasColumn('location', 'cittaLocation')) {
            Schema::table('location', function (Blueprint $table) {
                $table->dropColumn('cittaLocation');
            });
        }
    }
};
