<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Categorie;
use App\Models\Designation;
use App\Models\Gesimmo;

class CategorieSeeder extends Seeder
{
    /**
     * Catégories exactes du tableau CGI Mauritanie 2023 (Art. 25, point 9)
     * Source : finances.gov.mr/sites/default/files/2023-03/CGI-Fr-2023.pdf, page 19
     */
    private array $categories = [
        ['code' => 'FRET', 'label' => 'Frais d\'établissement',                          'duree' => 2,  'taux' => 50.00, 'type_cgi' => 'Frais d\'établissement'],
        ['code' => 'CIND', 'label' => 'Construction à usage industriel',                  'duree' => 20, 'taux' => 5.00,  'type_cgi' => 'Construction à usage industriel'],
        ['code' => 'CCOM', 'label' => 'Construction à usage commercial et d\'habitation', 'duree' => 25, 'taux' => 4.00,  'type_cgi' => 'Construction à usage commercial et d\'habitation'],
        ['code' => 'MTRP', 'label' => 'Matériel de transport',                            'duree' => 4,  'taux' => 25.00, 'type_cgi' => 'Matériel de transport'],
        ['code' => 'MEXP', 'label' => 'Matériel d\'exploitation',                         'duree' => 5,  'taux' => 20.00, 'type_cgi' => 'Matériel d\'exploitation'],
        ['code' => 'MCEX', 'label' => 'Matériel complexe d\'exploitation',                'duree' => 10, 'taux' => 10.00, 'type_cgi' => 'Matériel complexe d\'exploitation'],
        ['code' => 'MOUT', 'label' => 'Matériel et outillage',                            'duree' => 5,  'taux' => 20.00, 'type_cgi' => 'Matériel et outillage'],
        ['code' => 'MINF', 'label' => 'Matériel informatique',                            'duree' => 4,  'taux' => 25.00, 'type_cgi' => 'Matériel informatique'],
        ['code' => 'LG02', 'label' => 'Logiciels informatiques (2 ans)',                  'duree' => 2,  'taux' => 50.00, 'type_cgi' => 'Logiciels informatiques'],
        ['code' => 'LG04', 'label' => 'Logiciels informatiques (4 ans)',                  'duree' => 4,  'taux' => 25.00, 'type_cgi' => 'Logiciels informatiques'],
        ['code' => 'LG08', 'label' => 'Logiciels informatiques (8 ans)',                  'duree' => 8,  'taux' => 12.50, 'type_cgi' => 'Logiciels informatiques'],
        ['code' => 'MMOB', 'label' => 'Matériel et mobilier de bureau',                   'duree' => 10, 'taux' => 10.00, 'type_cgi' => 'Matériel et mobilier de bureau'],
        ['code' => 'IAAM', 'label' => 'Installations, agencements, aménagements',         'duree' => 10, 'taux' => 10.00, 'type_cgi' => 'Installations, agencements, aménagements'],
        ['code' => 'BNPO', 'label' => 'Bateaux et navires de pêche d\'occasion',          'duree' => 6,  'taux' => 16.66,'type_cgi' => 'Bateaux et navires de pêche d\'occasion'],
        ['code' => 'BNPN', 'label' => 'Bateaux et navires de pêche neufs',                'duree' => 8,  'taux' => 12.50, 'type_cgi' => 'Bateaux et navires de pêche neufs'],
        ['code' => 'AAER', 'label' => 'Avions et aéronefs civils',                        'duree' => 20, 'taux' => 5.00,  'type_cgi' => 'Avions et aéronefs civils'],
    ];

    /**
     * Règles de re-catégorisation des désignations par mots-clés.
     * Ordre : du plus spécifique au plus général.
     */
    private array $regles = [
        // Logiciels (LG02 par défaut pour les logiciels courants)
        'LG02' => [
            'logiciel', 'software', 'licence', 'license', 'application',
        ],
        // Matériel informatique (MINF)
        'MINF' => [
            'ordinateur', 'laptop', 'pc ', 'imprimante', 'scanner', 'serveur', 'switch',
            'switsh', 'routeur', 'écran', 'ecran', 'clavier', 'souris', 'onduleur',
            'tablette', 'disque dur', 'kack', 'unité de serv', 'machine pointage',
        ],
        // Matériel de transport (MTRP)
        'MTRP' => [
            'véhicule', 'vehicule', 'voiture', 'camion', 'bus', 'moto', 'pick-up',
            'pickup', '4x4', 'ambulance', 'minibus',
        ],
        // Matériel et mobilier de bureau (MMOB)
        'MMOB' => [
            'bureau ', 'armoire', 'bibliothèque', 'étagère', 'etager', 'stand',
            'banquette', 'canapé', 'canape', 'table basse', 'table de réunion',
            'table reunion', 'fauteuil', 'chaise', 'siège', 'siege', 'banc',
            'tableau blanc', 'tableau blanche', 'porte document', 'porte du vêtement',
            'commode', 'table bureau', 'table ', 'porte du dossier', 'meuble',
            'téléphone', 'telephone', 'calculatrice',
        ],
        // Matériel d'exploitation (MEXP) — électroménager, sécurité, audiovisuel
        'MEXP' => [
            'climatiseur', 'clim', 'télévision', 'television', 'frigo', 'réfrigérateur',
            'congélateur', 'baffe', 'projecteur', 'écran géant', 'ecran géant',
            'écran affichage', 'ecran affichage', 'caméra', 'camera', 'surveillance',
            'extincteur', 'alarme', 'système de sécurité', 'systéme de sécurité',
            'gr cam', 'ascenseur', 'groupe électrogène', 'groupe electrogene',
        ],
        // Installations, agencements, aménagements (IAAM)
        'IAAM' => [
            'installation', 'aménagement', 'amenagement', 'agencement', 'réseau',
        ],
        // Constructions industrielles (CIND)
        'CIND' => [
            'bâtiment industriel', 'hangar', 'entrepôt', 'entrepot', 'usine',
        ],
        // Constructions commerciales (CCOM)
        'CCOM' => [
            'bâtiment', 'batiment', 'construction', 'immeuble', 'local', 'bureau principal',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Remplacement des catégories (tableau CGI Mauritanie 2023)...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('categorie')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $mapCode = [];
            foreach ($this->categories as $cat) {
                $id = DB::table('categorie')->insertGetId([
                    'CodeCategorie'       => $cat['code'],
                    'Categorie'           => $cat['label'],
                    'duree_amortissement' => $cat['duree'],
                    'taux_amortissement'  => $cat['taux'],
                    'type_cgi'            => $cat['type_cgi'],
                ]);
                $mapCode[$cat['code']] = $id;
                $this->command->line("  ✓ [{$cat['code']}] {$cat['label']} — {$cat['taux']}% / {$cat['duree']} ans");
            }

            $this->command->info('Re-catégorisation des désignations...');
            $parDefaut = [];

            foreach (Designation::all() as $designation) {
                $code = $this->detecterCategorie($designation->designation);

                if ($code && isset($mapCode[$code])) {
                    $designation->update(['idCat' => $mapCode[$code]]);
                    $this->command->line("  ✓ \"{$designation->designation}\" → {$code}");
                } else {
                    // Par défaut : Matériel d'exploitation
                    $designation->update(['idCat' => $mapCode['MEXP']]);
                    $parDefaut[] = $designation->designation;
                    $this->command->warn("  ? \"{$designation->designation}\" → MEXP (par défaut)");
                }
            }

            $this->command->info('Mise à jour des immobilisations...');
            foreach (Designation::all() as $designation) {
                Gesimmo::where('idDesignation', $designation->id)
                    ->update(['idCategorie' => $designation->idCat]);
            }

            $this->command->info('');
            $this->command->info('✅ Terminé. ' . count($parDefaut) . ' désignation(s) par défaut (MEXP) :');
            foreach ($parDefaut as $nom) {
                $this->command->warn("   - {$nom}");
            }
        });
    }

    private function detecterCategorie(string $designation): ?string
    {
        $lower = mb_strtolower($designation);

        foreach ($this->regles as $code => $motsCles) {
            foreach ($motsCles as $mot) {
                if (str_contains($lower, mb_strtolower($mot))) {
                    return $code;
                }
            }
        }

        return null;
    }
}
