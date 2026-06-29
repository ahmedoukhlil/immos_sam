<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            // Administrateurs
            [
                'name' => 'Administrateur',
                'email' => 'admin@inventaire.com',
                'password' => 'password',
                'role' => 'admin',
                'telephone' => '+222 12 34 56 78',
                'service' => 'Direction',
                'actif' => true,
            ],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@inventaire.com',
                'password' => 'password',
                'role' => 'admin',
                'telephone' => '+222 11 22 33 44',
                'service' => 'Direction',
                'actif' => true,
            ],
            
            // Agents
            [
                'name' => 'Mohamed Ould Ahmed',
                'email' => 'agent1@inventaire.com',
                'password' => 'password',
                'role' => 'agent',
                'telephone' => '+222 23 45 67 89',
                'service' => 'Comptabilité',
                'actif' => true,
            ],
            [
                'name' => 'Fatima Mint Salem',
                'email' => 'agent2@inventaire.com',
                'password' => 'password',
                'role' => 'agent',
                'telephone' => '+222 34 56 78 90',
                'service' => 'Technique',
                'actif' => true,
            ],
            [
                'name' => 'Ahmed Ould Mohamed',
                'email' => 'agent3@inventaire.com',
                'password' => 'password',
                'role' => 'agent',
                'telephone' => '+222 45 67 89 01',
                'service' => 'Maintenance',
                'actif' => true,
            ],
            [
                'name' => 'Aicha Mint Ali',
                'email' => 'agent4@inventaire.com',
                'password' => 'password',
                'role' => 'agent',
                'telephone' => '+222 56 78 90 12',
                'service' => 'Ressources Humaines',
                'actif' => true,
            ],
            [
                'name' => 'Sidi Ould Brahim',
                'email' => 'agent5@inventaire.com',
                'password' => 'password',
                'role' => 'agent',
                'telephone' => '+222 67 89 01 23',
                'service' => 'Informatique',
                'actif' => true,
            ],
            
            // Agent inactif (pour test)
            [
                'name' => 'Agent Inactif',
                'email' => 'agent.inactif@inventaire.com',
                'password' => 'password',
                'role' => 'agent',
                'telephone' => '+222 99 99 99 99',
                'service' => 'Archives',
                'actif' => false,
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($users as $userData) {
            // Vérifier si l'utilisateur existe déjà
            $existingUser = User::where('email', $userData['email'])->first();
            
            if ($existingUser) {
                $this->command->warn("⚠ Utilisateur déjà existant : {$userData['email']}");
                $skipped++;
                continue;
            }

            // Hasher le mot de passe
            $userData['password'] = Hash::make($userData['password']);

            // Créer l'utilisateur
            $user = User::create($userData);
            
            $roleLabel = $user->role === 'admin' ? 'Administrateur' : 'Agent';
            $this->command->info("✓ {$roleLabel} créé : {$user->email} ({$user->name})");
            $created++;
        }

        $this->command->info("\n✅ UserSeeder terminé : {$created} utilisateur(s) créé(s), {$skipped} ignoré(s)");
    }
}
