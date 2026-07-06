<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

try {
    echo "Creating users table...\n";
    Schema::dropIfExists('users');
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email', 191)->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
    
    echo "Seeding user...\n";
    \App\Models\User::updateOrCreate(
        ['email' => 'admin@capodannoroma.it'],
        [
            'name' => 'Admin',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]
    );

    echo "Success!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    // echo "Trace: " . $e->getTraceAsString() . "\n";
}
