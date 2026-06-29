<?php

namespace App\Http\Controllers;

use App\Models\LocalisationImmo;
use App\Http\Requests\StoreLocalisationRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LocalisationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLocalisationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Localisation $localisation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Localisation $localisation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Localisation $localisation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Localisation $localisation)
    {
        //
    }

    /**
     * Génère le QR code d'une localisation
     * 
     * @param Localisation $localisation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateQRCode(LocalisationImmo $localisation)
    {
        // Note: La génération de QR code pour les localisations n'est pas implémentée dans le nouveau modèle
        // Cette fonctionnalité peut être ajoutée si nécessaire
        return redirect()->back()->with('info', 'La génération de QR code pour les localisations n\'est pas encore implémentée.');
    }

    /**
     * Télécharge l'étiquette PDF d'une localisation
     * 
     * @param LocalisationImmo $localisation
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function downloadEtiquette(LocalisationImmo $localisation)
    {
        try {
            // Générer le PDF
            $pdf = Pdf::loadView('pdf.etiquette-localisation', [
                'localisation' => $localisation,
            ])->setPaper([0, 0, 283.46, 226.77], 'portrait'); // 10x8 cm en points

            $filename = 'etiquette_' . Str::slug($localisation->CodeLocalisation ?? $localisation->idLocalisation) . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la génération de l\'étiquette: ' . $e->getMessage());
        }
    }

    /**
     * Imprime en masse les étiquettes de plusieurs localisations
     * 
     * Disposition : 4 étiquettes par page A4 (2 colonnes x 2 lignes)
     * Marges : 10mm, Espacement : 5mm entre étiquettes
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function imprimerEtiquettes(Request $request)
    {
        try {
            $request->validate([
                'localisations' => 'required|array|min:1',
                'localisations.*' => 'exists:localisation,idLocalisation',
            ]);

            $localisationIds = $request->input('localisations', []);
            
            // Récupérer les localisations
            $localisations = LocalisationImmo::whereIn('idLocalisation', $localisationIds)->get();

            if ($localisations->isEmpty()) {
                return redirect()->back()->with('error', 'Aucune localisation sélectionnée.');
            }

            // Note: La génération de QR code pour les localisations n'est pas implémentée dans le nouveau modèle

            // Générer le PDF multi-pages
            $pdf = Pdf::loadView('pdf.etiquettes-localisations', [
                'localisations' => $localisations,
            ])->setPaper('a4', 'portrait');

            $filename = 'etiquettes_localisations_' . now()->format('Y-m-d_His') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'impression des étiquettes: ' . $e->getMessage());
        }
    }

    /**
     * Exporte les localisations en format Excel (CSV)
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportExcel(Request $request)
    {
        try {
            // Récupérer les IDs sélectionnés depuis la requête (si fournis)
            $ids = $request->input('ids');
            
            if ($ids) {
                // Convertir la chaîne d'IDs séparés par des virgules en tableau
                $idsArray = explode(',', $ids);
                $localisations = LocalisationImmo::whereIn('idLocalisation', $idsArray)->get();
            } else {
                // Exporter toutes les localisations
                $localisations = LocalisationImmo::all();
            }

            if ($localisations->isEmpty()) {
                return redirect()->back()->with('warning', 'Aucune localisation à exporter.');
            }

            // Nom du fichier
            $filename = 'localisations_' . now()->format('Y-m-d_His') . '.csv';

            // En-têtes HTTP pour forcer le téléchargement
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            // Callback pour générer le CSV
            $callback = function() use ($localisations) {
                $file = fopen('php://output', 'w');
                
                // Ajouter le BOM UTF-8 pour Excel
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // En-têtes de colonnes
                fputcsv($file, [
                    'ID Localisation',
                    'Localisation',
                    'Code Localisation',
                    'Nombre d\'emplacements',
                ], ';');

                // Données
                foreach ($localisations as $localisation) {
                    $localisation->loadCount('emplacements');
                    fputcsv($file, [
                        $localisation->idLocalisation,
                        $localisation->Localisation,
                        $localisation->CodeLocalisation ?? '',
                        $localisation->emplacements_count,
                    ], ';');
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'export: ' . $e->getMessage());
        }
    }
}
