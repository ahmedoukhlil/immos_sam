<?php

namespace App\Exports\Sheets;

use App\Models\Inventaire;
use App\Services\InventaireService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Sheet Excel pour la synthèse de l'inventaire
 */
class SyntheseSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected $inventaire;
    protected $statistiques;

    public function __construct(Inventaire $inventaire)
    {
        $this->inventaire = $inventaire;
        $inventaireService = app(InventaireService::class);
        $this->statistiques = $inventaireService->calculerStatistiques($inventaire);
    }

    /**
     * Titre de l'onglet
     */
    public function title(): string
    {
        return 'Synthèse';
    }

    /**
     * Contenu de l'onglet
     */
    public function array(): array
    {
        $tauxConformite = $this->statistiques['taux_conformite'];
        $statut = $tauxConformite >= 95 ? 'CONFORME' : 'NON CONFORME';

        return [
            // En-tête
            ['RAPPORT D\'INVENTAIRE - ANNÉE ' . $this->inventaire->annee],
            [''],
            ['Statut Global', $statut],
            ['Taux de Conformité', number_format($tauxConformite, 2) . '%'],
            [''],
            
            // Informations générales
            ['INFORMATIONS GÉNÉRALES'],
            ['Date de début', $this->inventaire->date_debut->format('d/m/Y')],
            ['Date de fin', $this->inventaire->date_fin ? $this->inventaire->date_fin->format('d/m/Y') : 'En cours'],
            ['Durée', $this->statistiques['duree_jours'] . ' jours'],
            ['Créé par', $this->inventaire->creator->name ?? 'N/A'],
            ['Clôturé par', $this->inventaire->closer ? $this->inventaire->closer->name : '-'],
            [''],

            // Statistiques principales
            ['STATISTIQUES PRINCIPALES'],
            ['Indicateur', 'Valeur'],
            ['Total biens attendus', $this->statistiques['total_biens_attendus']],
            ['Total biens scannés', $this->statistiques['total_biens_scannes']],
            ['Biens présents', $this->statistiques['biens_presents']],
            ['Biens déplacés', $this->statistiques['biens_deplaces']],
            ['Biens absents', $this->statistiques['biens_absents']],
            ['Biens détériorés', $this->statistiques['biens_deteriores']],
            ['Progression globale', number_format($this->statistiques['progression_globale'], 2) . '%'],
            [''],

            // Localisations
            ['LOCALISATIONS'],
            ['Total localisations', $this->statistiques['total_localisations']],
            ['Localisations terminées', $this->statistiques['localisations_terminees']],
            ['Localisations en cours', $this->statistiques['localisations_en_cours']],
            ['Localisations en attente', $this->statistiques['localisations_en_attente']],
            [''],

            // Répartition par nature
            ['RÉPARTITION PAR NATURE DE BIEN'],
            ['Nature', 'Total', 'Présents', 'Déplacés', 'Absents', 'Conformité (%)'],
            ...collect($this->statistiques['par_nature'] ?? [])->map(function($stats, $nature) {
                return [
                    ucfirst($nature),
                    $stats['total'] ?? 0,
                    $stats['presents'] ?? 0,
                    $stats['deplaces'] ?? 0,
                    $stats['absents'] ?? 0,
                    number_format($stats['conformite'] ?? 0, 2)
                ];
            })->values()->all(),
            [''],

            // Valeurs
            ['VALEURS FINANCIÈRES'],
            ['Valeur totale scannée', number_format($this->statistiques['valeur_totale_scannee'] ?? 0, 0, ',', ' ') . ' MRU'],
            ['Valeur absente', number_format($this->statistiques['valeur_absente'] ?? 0, 0, ',', ' ') . ' MRU'],
        ];
    }

    /**
     * Styles de l'onglet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // En-tête principal
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Titres de sections
            6 => ['font' => ['bold' => true, 'size' => 12]],
            13 => ['font' => ['bold' => true, 'size' => 12]],
            22 => ['font' => ['bold' => true, 'size' => 12]],
            30 => ['font' => ['bold' => true, 'size' => 12]],
            38 => ['font' => ['bold' => true, 'size' => 12]],
            // En-têtes de tableaux
            14 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            ],
            31 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            ],
        ];
    }

    /**
     * Largeurs des colonnes
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 30,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
        ];
    }
}

