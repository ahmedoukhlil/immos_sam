<?php

namespace Database\Seeders;

use App\Models\Localisation;
use Illuminate\Database\Seeder;

class LocalisationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $localisations = [
            // Bâtiment A, Étage 1
            [
                'code' => 'BUR-101',
                'designation' => 'Bureau Directeur Général',
                'batiment' => 'Bâtiment A',
                'etage' => 1,
                'service_rattache' => 'Direction',
                'responsable' => 'M. Cheikh Ould Mohamed',
                'actif' => true,
            ],
            [
                'code' => 'BUR-102',
                'designation' => 'Bureau Secrétariat',
                'batiment' => 'Bâtiment A',
                'etage' => 1,
                'service_rattache' => 'Administration',
                'responsable' => 'Mme Aicha Mint Hassan',
                'actif' => true,
            ],
            [
                'code' => 'BUR-103',
                'designation' => 'Bureau Comptabilité',
                'batiment' => 'Bâtiment A',
                'etage' => 1,
                'service_rattache' => 'Comptabilité',
                'responsable' => 'M. Mohamed Ould Ahmed',
                'actif' => true,
            ],
            [
                'code' => 'BUR-104',
                'designation' => 'Bureau RH',
                'batiment' => 'Bâtiment A',
                'etage' => 1,
                'service_rattache' => 'Ressources Humaines',
                'responsable' => 'Mme Khadija Mint Salem',
                'actif' => true,
            ],
            [
                'code' => 'BUR-105',
                'designation' => 'Salle Réunion A',
                'batiment' => 'Bâtiment A',
                'etage' => 1,
                'service_rattache' => 'Commun',
                'responsable' => 'M. Ali Ould Brahim',
                'actif' => true,
            ],

            // Bâtiment A, Étage 2
            [
                'code' => 'BUR-201',
                'designation' => 'Bureau Technique 1',
                'batiment' => 'Bâtiment A',
                'etage' => 2,
                'service_rattache' => 'Technique',
                'responsable' => 'M. Sidi Ould Mohamed',
                'actif' => true,
            ],
            [
                'code' => 'BUR-202',
                'designation' => 'Bureau Technique 2',
                'batiment' => 'Bâtiment A',
                'etage' => 2,
                'service_rattache' => 'Technique',
                'responsable' => 'Mme Fatima Mint Salem',
                'actif' => true,
            ],
            [
                'code' => 'BUR-203',
                'designation' => 'Salle Formation',
                'batiment' => 'Bâtiment A',
                'etage' => 2,
                'service_rattache' => 'Commun',
                'responsable' => 'M. Ahmed Ould Cheikh',
                'actif' => true,
            ],
            [
                'code' => 'BUR-204',
                'designation' => 'Bureau Commercial',
                'batiment' => 'Bâtiment A',
                'etage' => 2,
                'service_rattache' => 'Commercial',
                'responsable' => 'M. Brahim Ould Ali',
                'actif' => true,
            ],

            // Bâtiment B
            [
                'code' => 'ATELIER-A',
                'designation' => 'Atelier Principal',
                'batiment' => 'Bâtiment B',
                'etage' => 0,
                'service_rattache' => 'Production',
                'responsable' => 'M. Mohamed Ould Sidi',
                'actif' => true,
            ],
            [
                'code' => 'ATELIER-B',
                'designation' => 'Atelier Secondaire',
                'batiment' => 'Bâtiment B',
                'etage' => 0,
                'service_rattache' => 'Production',
                'responsable' => 'M. Hassan Ould Ahmed',
                'actif' => true,
            ],
            [
                'code' => 'DEPOT-1',
                'designation' => 'Dépôt Matériel',
                'batiment' => 'Bâtiment B',
                'etage' => 0,
                'service_rattache' => 'Logistique',
                'responsable' => 'M. Salem Ould Mohamed',
                'actif' => true,
            ],

            // Bâtiment C
            [
                'code' => 'SALLE-SERVEUR',
                'designation' => 'Salle Serveurs',
                'batiment' => 'Bâtiment C',
                'etage' => 1,
                'service_rattache' => 'Informatique',
                'responsable' => 'M. Cheikh Ould Brahim',
                'actif' => true,
            ],
            [
                'code' => 'GARAGE',
                'designation' => 'Garage Véhicules',
                'batiment' => 'Bâtiment C',
                'etage' => 0,
                'service_rattache' => 'Logistique',
                'responsable' => 'M. Ali Ould Sidi',
                'actif' => true,
            ],
            [
                'code' => 'CAFETERIA',
                'designation' => 'Cafétéria',
                'batiment' => 'Bâtiment C',
                'etage' => 1,
                'service_rattache' => 'Commun',
                'responsable' => 'Mme Mariem Mint Hassan',
                'actif' => true,
            ],
        ];

        $count = 0;
        foreach ($localisations as $data) {
            $localisation = Localisation::create($data);
            
            // Générer automatiquement le QR code
            try {
                $localisation->generateQRCode();
            } catch (\Exception $e) {
                $this->command->warn("⚠ Impossible de générer le QR code pour {$localisation->code}: {$e->getMessage()}");
            }
            
            $count++;
            $this->command->info("✓ Localisation créée : {$localisation->code} - {$localisation->designation}");
        }

        $this->command->info("\n✅ LocalisationSeeder terminé : {$count} localisations créées");
    }
}
