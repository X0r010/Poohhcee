<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            OrderImportSeeder::class,
            // DanielCaesarImportSeeder::class, // Add here if you also use this seeder
        ]);
    }
}