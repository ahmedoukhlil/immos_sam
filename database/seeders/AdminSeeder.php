<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $existing = User::where('users', 'admin1')->first();

        if ($existing) {
            $this->command->warn("L'utilisateur admin1 existe déjà (idUser: {$existing->idUser})");
            return;
        }

        User::create([
            'users' => 'admin1',
            'mdp'   => Hash::make('password'),
            'role'  => 'admin',
        ]);

        $this->command->info("Utilisateur admin1 créé avec succès.");
    }
}
