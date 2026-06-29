<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Appel des seeders dans l'ordre des dépendances
        $this->call([
            UserSeeder::class,
            LocalisationSeeder::class,
            BienSeeder::class,
            InventaireSeeder::class,
        ]);
    }
}
