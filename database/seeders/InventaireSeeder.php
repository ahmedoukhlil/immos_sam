<?php

namespace Database\Seeders;

use App\Models\Inventaire;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Ce seeder est optionnel et crée un inventaire de test pour l'année 2024.
     */
    public function run(): void
    {
        // Récupérer l'administrateur
        $admin = User::where('email', 'admin@inventaire.com')->first();
        
        if (!$admin) {
            $this->command->warn("⚠ Administrateur non trouvé. Assurez-vous d'avoir exécuté UserSeeder d'abord.");
            return;
        }

        // Créer un inventaire de test pour 2024
        $inventaire = Inventaire::create([
            'annee' => 2024,
            'date_debut' => '2024-12-01',
            'date_fin' => null,
            'statut' => 'en_preparation',
            'created_by' => $admin->id,
            'closed_by' => null,
            'observation' => 'Inventaire de test pour l\'année 2024. En phase de préparation.',
        ]);

        $this->command->info("✓ Inventaire créé : Année {$inventaire->annee} - Statut: {$inventaire->statut}");
        $this->command->info("\n✅ InventaireSeeder terminé : 1 inventaire créé");
    }
}
