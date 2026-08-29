<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Remove old admin user if present
        User::where('email', 'admin@poohhcee.com')->delete();

        // Account 1: Tharo
        User::updateOrCreate(
            ['email' => 'tharo@poohhcee.com'],
            [
                'name' => 'Tharo',
                'password' => Hash::make('Xoro0962236827'),
                'email_verified_at' => now(),
            ]
        );

        // Account 2: Neath
        User::updateOrCreate(
            ['email' => 'neath@poohhcee.com'],
            [
                'name' => 'Neath',
                'password' => Hash::make('086847409'),
                'email_verified_at' => now(),
            ]
        );
    }
}