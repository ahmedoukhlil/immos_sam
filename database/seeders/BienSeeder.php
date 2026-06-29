<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\Localisation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class BienSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        
        // Récupérer les utilisateurs et localisations
        $admin = User::where('email', 'admin@inventaire.com')->first();
        $agent1 = User::where('email', 'agent1@inventaire.com')->first();
        $agent2 = User::where('email', 'agent2@inventaire.com')->first();
        
        $localisations = Localisation::all()->keyBy('code');
        
        $biens = [];
        $count = 0;

        // BUR-101 (6 biens)
        $bur101 = $localisations['BUR-101'];
        $biens[] = ['Bureau direction', 'mobilier', 150000, 'bon', $bur101->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Fauteuil cuir', 'mobilier', 45000, 'bon', $bur101->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Ordinateur Dell', 'informatique', 85000, 'bon', $bur101->id, $admin->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Imprimante HP', 'informatique', 35000, 'moyen', $bur101->id, $admin->id, $faker->dateTimeBetween('2019-01-01', '2020-12-31')];
        $biens[] = ['Téléphone IP', 'informatique', 12000, 'bon', $bur101->id, $admin->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];
        $biens[] = ['Armoire métallique', 'mobilier', 28000, 'bon', $bur101->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];

        // BUR-102 (4 biens)
        $bur102 = $localisations['BUR-102'];
        $biens[] = ['Bureau standard', 'mobilier', 45000, 'bon', $bur102->id, $agent1->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Chaise bureau', 'mobilier', 15000, 'moyen', $bur102->id, $agent1->id, $faker->dateTimeBetween('2019-01-01', '2020-12-31')];
        $biens[] = ['Ordinateur portable', 'informatique', 65000, 'bon', $bur102->id, $agent1->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Téléphone fixe', 'informatique', 8000, 'bon', $bur102->id, $agent1->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];

        // BUR-103 (5 biens)
        $bur103 = $localisations['BUR-103'];
        $biens[] = ['Bureau comptable', 'mobilier', 50000, 'bon', $bur103->id, $agent1->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Bureau comptable', 'mobilier', 50000, 'bon', $bur103->id, $agent1->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Ordinateur fixe', 'informatique', 75000, 'bon', $bur103->id, $agent1->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Ordinateur fixe', 'informatique', 75000, 'bon', $bur103->id, $agent1->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Calculatrice professionnelle', 'informatique', 5000, 'neuf', $bur103->id, $agent1->id, $faker->dateTimeBetween('2023-01-01', '2024-12-31')];

        // BUR-104 (4 biens)
        $bur104 = $localisations['BUR-104'];
        $biens[] = ['Bureau RH', 'mobilier', 48000, 'bon', $bur104->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Armoire dossiers', 'mobilier', 32000, 'bon', $bur104->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Ordinateur', 'informatique', 72000, 'bon', $bur104->id, $admin->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Scanner', 'informatique', 18000, 'bon', $bur104->id, $admin->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];

        // BUR-105 - Salle Réunion (6 biens)
        $bur105 = $localisations['BUR-105'];
        $biens[] = ['Table réunion 12 places', 'mobilier', 180000, 'bon', $bur105->id, $admin->id, $faker->dateTimeBetween('2019-01-01', '2020-12-31')];
        $biens[] = ['Lot 12 chaises conférence', 'mobilier', 96000, 'bon', $bur105->id, $admin->id, $faker->dateTimeBetween('2019-01-01', '2020-12-31')];
        $biens[] = ['Vidéoprojecteur', 'informatique', 95000, 'bon', $bur105->id, $admin->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Écran projection', 'materiel', 35000, 'bon', $bur105->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Tableau blanc', 'materiel', 12000, 'bon', $bur105->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];

        // BUR-201 (5 biens)
        $bur201 = $localisations['BUR-201'];
        $biens[] = ['Bureau technique', 'mobilier', 55000, 'bon', $bur201->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Ordinateur station de travail', 'informatique', 125000, 'bon', $bur201->id, $agent2->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Imprimante 3D', 'informatique', 185000, 'bon', $bur201->id, $agent2->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];
        $biens[] = ['Écran 4K', 'informatique', 45000, 'bon', $bur201->id, $agent2->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];
        $biens[] = ['Armoire technique', 'mobilier', 38000, 'bon', $bur201->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];

        // BUR-202 (5 biens)
        $bur202 = $localisations['BUR-202'];
        $biens[] = ['Bureau technique', 'mobilier', 55000, 'bon', $bur202->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Ordinateur station de travail', 'informatique', 125000, 'bon', $bur202->id, $agent2->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Oscilloscope', 'informatique', 285000, 'bon', $bur202->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Multimètre professionnel', 'materiel', 15000, 'bon', $bur202->id, $agent2->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Établi technique', 'mobilier', 32000, 'bon', $bur202->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];

        // BUR-203 - Salle Formation (8 biens)
        $bur203 = $localisations['BUR-203'];
        $biens[] = ['Lot 15 tables formation', 'mobilier', 225000, 'bon', $bur203->id, $admin->id, $faker->dateTimeBetween('2019-01-01', '2020-12-31')];
        $biens[] = ['Lot 30 chaises formation', 'mobilier', 180000, 'bon', $bur203->id, $admin->id, $faker->dateTimeBetween('2019-01-01', '2020-12-31')];
        $biens[] = ['Tableau blanc', 'materiel', 12000, 'bon', $bur203->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Tableau blanc', 'materiel', 12000, 'bon', $bur203->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Vidéoprojecteur', 'informatique', 95000, 'bon', $bur203->id, $admin->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Ordinateur formateur', 'informatique', 85000, 'bon', $bur203->id, $admin->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Écran interactif', 'informatique', 125000, 'bon', $bur203->id, $admin->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];
        $biens[] = ['Système audio', 'materiel', 45000, 'bon', $bur203->id, $admin->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];

        // BUR-204 (4 biens)
        $bur204 = $localisations['BUR-204'];
        $biens[] = ['Bureau commercial', 'mobilier', 48000, 'bon', $bur204->id, $agent1->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Ordinateur portable', 'informatique', 65000, 'bon', $bur204->id, $agent1->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Téléphone IP', 'informatique', 12000, 'bon', $bur204->id, $agent1->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];
        $biens[] = ['Armoire commerciale', 'mobilier', 35000, 'bon', $bur204->id, $agent1->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];

        // ATELIER-A (8 biens)
        $atelierA = $localisations['ATELIER-A'];
        $biens[] = ['Perceuse à colonne', 'materiel', 450000, 'bon', $atelierA->id, $agent2->id, $faker->dateTimeBetween('2018-01-01', '2019-12-31')];
        $biens[] = ['Tour à métaux', 'materiel', 850000, 'moyen', $atelierA->id, $agent2->id, $faker->dateTimeBetween('2018-01-01', '2019-12-31')];
        $biens[] = ['Poste à souder', 'materiel', 125000, 'bon', $atelierA->id, $agent2->id, $faker->dateTimeBetween('2019-01-01', '2020-12-31')];
        $biens[] = ['Compresseur', 'materiel', 280000, 'bon', $atelierA->id, $agent2->id, $faker->dateTimeBetween('2019-01-01', '2020-12-31')];
        $biens[] = ['Lot 5 établis', 'mobilier', 125000, 'bon', $atelierA->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Armoire outils', 'mobilier', 45000, 'bon', $atelierA->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Scie circulaire', 'materiel', 85000, 'bon', $atelierA->id, $agent2->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Meuleuse d\'angle', 'materiel', 35000, 'moyen', $atelierA->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];

        // ATELIER-B (5 biens)
        $atelierB = $localisations['ATELIER-B'];
        $biens[] = ['Perceuse visseuse', 'materiel', 25000, 'bon', $atelierB->id, $agent2->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Scie sauteuse', 'materiel', 18000, 'bon', $atelierB->id, $agent2->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Ponceuse excentrique', 'materiel', 22000, 'bon', $atelierB->id, $agent2->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];
        $biens[] = ['Établi léger', 'mobilier', 28000, 'bon', $atelierB->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Armoire outils légers', 'mobilier', 32000, 'bon', $atelierB->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];

        // DEPOT-1 (6 biens)
        $depot1 = $localisations['DEPOT-1'];
        $biens[] = ['Lot 5 rayonnages métalliques', 'mobilier', 250000, 'bon', $depot1->id, $agent2->id, $faker->dateTimeBetween('2019-01-01', '2020-12-31')];
        $biens[] = ['Transpalette manuel', 'materiel', 45000, 'bon', $depot1->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Chariot élévateur électrique', 'materiel', 1200000, 'bon', $depot1->id, $agent2->id, $faker->dateTimeBetween('2018-01-01', '2019-12-31')];
        $biens[] = ['Armoire stockage', 'mobilier', 38000, 'bon', $depot1->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Armoire stockage', 'mobilier', 38000, 'bon', $depot1->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Étagères métalliques', 'mobilier', 45000, 'bon', $depot1->id, $agent2->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];

        // SALLE-SERVEUR (8 biens)
        $salleServeur = $localisations['SALLE-SERVEUR'];
        $biens[] = ['Serveur Dell PowerEdge', 'informatique', 450000, 'bon', $salleServeur->id, $admin->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Serveur HP ProLiant', 'informatique', 380000, 'bon', $salleServeur->id, $admin->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Switch Cisco 48 ports', 'informatique', 185000, 'bon', $salleServeur->id, $admin->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];
        $biens[] = ['Routeur entreprise', 'informatique', 95000, 'bon', $salleServeur->id, $admin->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];
        $biens[] = ['Onduleur 10KVA', 'informatique', 320000, 'bon', $salleServeur->id, $admin->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Baie serveur 42U', 'materiel', 150000, 'bon', $salleServeur->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Climatisation serveur', 'materiel', 280000, 'bon', $salleServeur->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Firewall entreprise', 'informatique', 125000, 'bon', $salleServeur->id, $admin->id, $faker->dateTimeBetween('2022-01-01', '2023-12-31')];

        // GARAGE (4 biens - véhicules)
        $garage = $localisations['GARAGE'];
        $biens[] = ['Véhicule Toyota Hilux 2020', 'vehicule', 3500000, 'bon', $garage->id, $admin->id, Carbon::parse('2020-06-15')];
        $biens[] = ['Véhicule Renault Kangoo 2019', 'vehicule', 1800000, 'moyen', $garage->id, $admin->id, Carbon::parse('2019-03-20')];
        $biens[] = ['Chariot de manutention', 'materiel', 85000, 'bon', $garage->id, $agent2->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Compresseur mobile', 'materiel', 125000, 'bon', $garage->id, $agent2->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];

        // CAFETERIA (3 biens)
        $cafeteria = $localisations['CAFETERIA'];
        $biens[] = ['Réfrigérateur commercial', 'materiel', 185000, 'bon', $cafeteria->id, $admin->id, $faker->dateTimeBetween('2020-01-01', '2021-12-31')];
        $biens[] = ['Micro-ondes', 'materiel', 15000, 'bon', $cafeteria->id, $admin->id, $faker->dateTimeBetween('2021-01-01', '2022-12-31')];
        $biens[] = ['Lot tables et chaises cafétéria', 'mobilier', 120000, 'bon', $cafeteria->id, $admin->id, $faker->dateTimeBetween('2019-01-01', '2020-12-31')];

        // Créer tous les biens
        foreach ($biens as $bienData) {
            [$designation, $nature, $valeur, $etat, $localisationId, $userId, $dateAcquisition] = $bienData;
            
            // Générer le code d'inventaire unique
            $codeInventaire = Bien::generateCodeInventaire();
            
            // Générer une observation aléatoire avec Faker
            $observation = $faker->optional(0.3)->sentence();
            
            // Déterminer le service usager selon la localisation
            $localisation = Localisation::find($localisationId);
            $serviceUsager = $localisation->service_rattache ?? 'Commun';
            
            $bien = Bien::create([
                'code_inventaire' => $codeInventaire,
                'designation' => $designation,
                'date_acquisition' => $dateAcquisition,
                'nature' => $nature,
                'service_usager' => $serviceUsager,
                'localisation_id' => $localisationId,
                'valeur_acquisition' => $valeur,
                'etat' => $etat,
                'observation' => $observation,
                'user_id' => $userId,
            ]);
            
            // Générer automatiquement le QR code
            try {
                $bien->generateQRCode();
            } catch (\Exception $e) {
                $this->command->warn("⚠ Impossible de générer le QR code pour {$bien->code_inventaire}: {$e->getMessage()}");
            }
            
            $count++;
            $this->command->info("✓ Bien créé : {$bien->code_inventaire} - {$bien->designation}");
        }

        $this->command->info("\n✅ BienSeeder terminé : {$count} biens créés");
    }
}
