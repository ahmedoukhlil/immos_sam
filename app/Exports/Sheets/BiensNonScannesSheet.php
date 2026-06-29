<?php

namespace App\Exports\Sheets;

use App\Models\Inventaire;
use App\Services\RapportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Sheet Excel pour les biens non scannés
 */
class BiensNonScannesSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $inventaire;
    protected $biensNonScannes;

    public function __construct(Inventaire $inventaire)
    {
        $this->inventaire = $inventaire;
        $rapportService = app(RapportService::class);
        $this->biensNonScannes = $rapportService->getBiensNonScannes($inventaire);
    }

    /**
     * Titre de l'onglet
     */
    public function title(): string
    {
        return 'Biens Non Scannés';
    }

    /**
     * Collection de données
     */
    public function collection()
    {
        return $this->biensNonScannes->map(function ($bien) {
            return [
                'code' => $bien['code'] ?? 'N/A',
                'designation' => $bien['designation'] ?? 'N/A',
                'nature' => ucfirst($bien['nature'] ?? 'N/A'),
                'localisation' => $bien['localisation'] ?? 'N/A',
                'service' => $bien['service'] ?? 'N/A',
                'valeur' => number_format($bien['valeur'] ?? 0, 0, ',', ' '),
            ];
        });
    }

    /**
     * En-têtes de colonnes
     */
    public function headings(): array
    {
        return [
            'Code Inventaire',
            'Désignation',
            'Nature',
            'Localisation',
            'Service',
            'Valeur (MRU)',
        ];
    }

    /**
     * Styles
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6B7280']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    /**
     * Largeurs des colonnes
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 40,
            'C' => 15,
            'D' => 15,
            'E' => 20,
            'F' => 15,
        ];
    }
}

