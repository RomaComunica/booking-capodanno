<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    echo "Seeding Admin User...\n";
    
    $user = User::updateOrCreate(
        ['email' => 'admin@capodannoroma.it'],
        [
            'name' => 'Admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]
    );
    
    echo "User created/updated: " . $user->email . "\n";
    
    // Verify Payment Table
    $count = DB::table('cross_pagamenti_location')->count();
    echo "Cross Pagamenti Location count: $count\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
