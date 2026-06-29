<?php

namespace App\Http\Controllers;

use App\Models\Emplacement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Contrôleur pour générer les QR codes des emplacements
 * Pour l'inventaire via PWA Scanner
 */
class QRCodeEmplacementController extends Controller
{
    /**
     * Affiche la page de génération des QR codes
     */
    public function index(Request $request)
    {
        $query = Emplacement::with(['localisation', 'affectation'])
            ->orderBy('CodeEmplacement');

        // Filtres optionnels
        if ($request->filled('localisation')) {
            $query->where('idLocalisation', $request->localisation);
        }

        if ($request->filled('affectation')) {
            $query->where('idAffectation', $request->affectation);
        }

        $emplacements = $query->get();

        // Charger les localisations et affectations pour les filtres
        $localisations = \App\Models\LocalisationImmo::orderBy('Localisation')->get();
        $affectations = \App\Models\Affectation::orderBy('Affectation')->get();

        return view('qrcodes.emplacements', compact('emplacements', 'localisations', 'affectations'));
    }

    /**
     * Génère un QR code unique pour un emplacement
     * Format: EMP-{idEmplacement}
     */
    public function generate($idEmplacement)
    {
        $emplacement = Emplacement::with(['localisation', 'affectation'])
            ->findOrFail($idEmplacement);

        // Format du QR code : EMP-{id}
        $qrData = "EMP-{$idEmplacement}";

        // Générer le QR code
        $qrCode = QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($qrData);

        return response($qrCode)
            ->header('Content-Type', 'image/svg+xml');
    }

    /**
     * Télécharge un PDF avec tous les QR codes
     */
    public function downloadPdf(Request $request)
    {
        $query = Emplacement::with(['localisation', 'affectation'])
            ->orderBy('CodeEmplacement');

        // Filtres optionnels
        if ($request->filled('localisation')) {
            $query->where('idLocalisation', $request->localisation);
        }

        if ($request->filled('affectation')) {
            $query->where('idAffectation', $request->affectation);
        }

        $emplacements = $query->get();

        // Générer les QR codes pour chaque emplacement (SVG, pas besoin d'imagick)
        $qrCodes = [];
        foreach ($emplacements as $emplacement) {
            $qrData = "EMP-{$emplacement->idEmplacement}";
            
            $qrCode = QrCode::format('svg')
                ->size(250)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($qrData);

            $qrCodes[] = [
                'emplacement' => $emplacement,
                'qrCode' => base64_encode($qrCode),
                'qrData' => $qrData
            ];
        }

        // Générer le PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('qrcodes.emplacements-pdf', [
            'qrCodes' => $qrCodes,
            'date' => now()->format('d/m/Y')
        ]);

        return $pdf->download('qrcodes-emplacements-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Imprime les QR codes sélectionnés
     */
    public function printSelected(Request $request)
    {
        $ids = $request->input('emplacements', []);
        
        if (empty($ids)) {
            return redirect()->back()->with('error', 'Aucun emplacement sélectionné');
        }

        $emplacements = Emplacement::with(['localisation', 'affectation'])
            ->whereIn('idEmplacement', $ids)
            ->orderBy('CodeEmplacement')
            ->get();

        // Générer les QR codes (SVG, pas besoin d'imagick)
        $qrCodes = [];
        foreach ($emplacements as $emplacement) {
            $qrData = "EMP-{$emplacement->idEmplacement}";
            
            $qrCode = QrCode::format('svg')
                ->size(250)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($qrData);

            $qrCodes[] = [
                'emplacement' => $emplacement,
                'qrCode' => $qrCode, // SVG directement, pas besoin de base64
                'qrData' => $qrData
            ];
        }

        // Afficher la vue d'impression
        return view('qrcodes.emplacements-print', [
            'qrCodes' => $qrCodes,
            'date' => now()->format('d/m/Y')
        ]);
    }
}
