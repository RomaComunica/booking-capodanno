<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';
    protected $description = 'Create admin user with known credentials';

    public function handle()
    {
        $email = 'admin@booking.com';
        $password = 'password';

        // Check if user exists
        $user = User::where('email', $email)->first();

        if ($user) {
            $this->info("Admin user already exists!");
            $this->info("Email: {$email}");
            $this->info("Password: {$password}");
            
            // Update password
            $user->password = Hash::make($password);
            $user->save();
            $this->info("Password reset to: {$password}");
        } else {
            // Create new user
            $user = User::create([
                'name' => 'Admin',
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            
            $this->info("Admin user created successfully!");
            $this->info("Email: {$email}");
            $this->info("Password: {$password}");
        }

        return 0;
    }
}
