<?php

namespace App\Http\Controllers;

use App\Models\Emplacement;
use App\Models\Gesimmo;
use App\Http\Requests\StoreBienRequest;
use App\Http\Requests\UpdateBienRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BienController extends Controller
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
    public function store(StoreBienRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Bien $bien)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bien $bien)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBienRequest $request, Bien $bien)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bien $bien)
    {
        //
    }

    /**
     * Génère le code-barres Code 128 d'une immobilisation
     * 
     * @param Gesimmo $bien
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateQRCode(Gesimmo $bien)
    {
        try {
            $barcode = $bien->generateBarcode();
            
            return redirect()->back()->with('success', 'Code-barres généré avec succès');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la génération du code-barres: ' . $e->getMessage());
        }
    }


    /**
     * Génère l'étiquette PDF avec un code-barres SVG fourni côté client
     * 
     * @param Gesimmo $bien
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function downloadEtiquetteWithBarcode(Gesimmo $bien, \Illuminate\Http\Request $request)
    {
        try {
            $request->validate([
                'svg' => 'required|string',
            ]);

            // Charger les relations nécessaires
            $bien->load(['emplacement.localisation', 'designation', 'categorie', 'etat', 'code']);

            // Générer le PDF avec le SVG fourni
            // Dimensions Code 128 standard : 50mm × 20mm (minimum 37.3mm × 12.7mm)
            // 50mm = 141.73 points, 20mm = 56.69 points (1 mm = 2.83465 points)
            $pdf = Pdf::loadView('pdf.etiquette-bien', [
                'bien' => $bien,
                'barcodeSvg' => $request->input('svg'), // SVG généré côté client
            ])->setPaper([0, 0, 141.73, 56.69], 'portrait')
              ->setOption('isHtml5ParserEnabled', true)
              ->setOption('isRemoteEnabled', true)
              ->setOption('isPhpEnabled', true)
              ->setOption('defaultFont', 'DejaVu Sans')
              ->setOption('enableFontSubsetting', true)
              ->setOption('isFontSubsettingEnabled', true);

            $filename = 'etiquette_' . \Illuminate\Support\Str::slug($bien->code_formate ?? $bien->NumOrdre) . '.pdf';

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération de l\'étiquette: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharge l'étiquette PDF d'un bien
     * 
     * Format : 8x5 cm
     * Contenu : QR code, code inventaire, désignation, localisation, date acquisition
     * 
     * @param Bien $bien
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function downloadEtiquette(Gesimmo $bien)
    {
        try {
            // Charger les relations nécessaires (sans 'code' car généré côté client)
            $bien->load(['emplacement.localisation', 'designation', 'categorie', 'etat']);

            // Générer le PDF avec les dimensions Code 128 standard
            // Dimensions minimales : 37.3mm × 12.7mm, on utilise 50mm × 20mm pour l'étiquette
            // 50mm = 141.73 points, 20mm = 56.69 points (1 mm = 2.83465 points)
            $pdf = Pdf::loadView('pdf.etiquette-bien', [
                'bien' => $bien,
            ])->setPaper([0, 0, 141.73, 56.69], 'portrait')
              ->setOption('isHtml5ParserEnabled', true)
              ->setOption('isRemoteEnabled', true)
              ->setOption('isPhpEnabled', true)
              ->setOption('defaultFont', 'DejaVu Sans')
              ->setOption('enableFontSubsetting', true)
              ->setOption('isFontSubsettingEnabled', true); // 8cm x 5cm en points

            $filename = 'etiquette_' . Str::slug($bien->code_formate ?? $bien->NumOrdre) . '.pdf';

            // Retourner le PDF en stream pour l'impression (au lieu de download)
            return $pdf->stream($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la génération de l\'étiquette: ' . $e->getMessage());
        }
    }

    /**
     * Imprime en masse les étiquettes de plusieurs biens
     * 
     * Disposition : 12 étiquettes par page A4 (3 colonnes x 4 lignes)
     * Marges : 10mm, Espacement : 5mm entre étiquettes
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function imprimerEtiquettes(Request $request)
    {
        try {
            $request->validate([
                'biens' => 'required|array|min:1',
                'biens.*' => 'exists:gesimmo,NumOrdre',
            ]);

            $bienIds = $request->input('biens', []);
            
            // Récupérer les immobilisations avec leurs relations (sans 'code' car généré côté client)
            $biens = Gesimmo::whereIn('NumOrdre', $bienIds)
                ->with(['emplacement.localisation', 'designation', 'categorie', 'etat'])
                ->get();

            if ($biens->isEmpty()) {
                return redirect()->back()->with('error', 'Aucun bien sélectionné.');
            }

            // Générer le PDF multi-pages
            $pdf = Pdf::loadView('pdf.etiquettes-biens', [
                'biens' => $biens,
            ])->setPaper('a4', 'portrait');

            $filename = 'etiquettes_' . now()->format('Y-m-d_His') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'impression des étiquettes: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page pour imprimer les étiquettes groupées par emplacement
     * 
     * Disposition : 33 étiquettes par page A4 (3 colonnes x 11 lignes)
     * Format : Groupé par emplacement, génération côté client avec jsbarcode et pdf-lib
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function imprimerEtiquettesParEmplacement(Request $request)
    {
        try {
            $request->validate([
                'idEmplacement' => 'required|exists:emplacement,idEmplacement',
            ]);

            $idEmplacement = $request->input('idEmplacement');
            
            // Récupérer toutes les immobilisations de cet emplacement avec leurs relations
            $biens = Gesimmo::where('idEmplacement', $idEmplacement)
                ->with([
                    'emplacement.localisation',
                    'emplacement.affectation',
                    'designation',
                    'categorie',
                    'etat',
                    'natureJuridique',
                    'sourceFinancement'
                ])
                ->orderBy('NumOrdre')
                ->get();

            if ($biens->isEmpty()) {
                return redirect()->back()->with('error', 'Aucun bien trouvé pour cet emplacement.');
            }

            // Récupérer les informations de l'emplacement
            $emplacement = \App\Models\Emplacement::with(['localisation', 'affectation'])
                ->find($idEmplacement);

            // Préparer les données pour le JavaScript
            $biensData = $biens->map(function($bien) {
                $qrSvg = QrCode::format('svg')
                    ->size(220)
                    ->margin(0)
                    ->errorCorrection('M')
                    ->generate((string) $bien->NumOrdre);

                return [
                    'NumOrdre' => $bien->NumOrdre,
                    'code_formate' => $bien->code_formate ?? '',
                    'designation' => $bien->designation->designation ?? '',
                    // Pour le code-barres, on utilise uniquement NumOrdre
                    'barcode_value' => (string)$bien->NumOrdre,
                    'qr_data_uri' => 'data:image/svg+xml;base64,' . base64_encode($qrSvg),
                ];
            })->toArray();

            $qrSvg = QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate("EMP-{$idEmplacement}");

            $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

            return view('pdf.etiquettes-biens-par-emplacement-client', [
                'biens' => $biens,
                'biensData' => $biensData,
                'emplacement' => $emplacement,
                'qrDataUri' => $qrDataUri,
                'includeEmplacementQr' => true,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'impression des étiquettes: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page pour imprimer toutes les étiquettes,
     * groupées emplacement par emplacement, dans un seul fichier PDF.
     */
    public function imprimerEtiquettesTousEmplacements(Request $request)
    {
        try {
            $validated = $request->validate([
                'filterLocalisation' => 'nullable|exists:localisation,idLocalisation',
                'filterAffectation' => 'nullable|exists:affectation,idAffectation',
            ]);

            $filterLocalisation = $validated['filterLocalisation'] ?? null;
            $filterAffectation = $validated['filterAffectation'] ?? null;

            $query = Emplacement::with([
                'localisation',
                'affectation',
                'immobilisations' => function ($q) {
                    $q->with(['designation'])
                        ->orderBy('NumOrdre');
                },
            ])->whereHas('immobilisations');

            if (!empty($filterLocalisation)) {
                $query->where('idLocalisation', $filterLocalisation);
            }

            if (!empty($filterAffectation)) {
                $query->where('idAffectation', $filterAffectation);
            }

            $emplacements = $query->orderBy('Emplacement')->get();

            if ($emplacements->isEmpty()) {
                return redirect()->back()->with('error', 'Aucun emplacement avec des biens trouvé pour ces filtres.');
            }

            $emplacementsData = [];
            $qrDataUris = [];

            foreach ($emplacements as $emplacement) {
                $emplacementsData[] = [
                    'idEmplacement' => $emplacement->idEmplacement,
                    'Emplacement' => $emplacement->Emplacement,
                    'CodeEmplacement' => $emplacement->CodeEmplacement,
                    'localisation' => $emplacement->localisation ? [
                        'idLocalisation' => $emplacement->localisation->idLocalisation,
                        'Localisation' => $emplacement->localisation->Localisation,
                    ] : null,
                    'affectation' => $emplacement->affectation ? [
                        'idAffectation' => $emplacement->affectation->idAffectation,
                        'Affectation' => $emplacement->affectation->Affectation,
                    ] : null,
                    'biens' => $emplacement->immobilisations->map(function ($bien) {
                        $qrSvg = QrCode::format('svg')
                            ->size(220)
                            ->margin(0)
                            ->errorCorrection('M')
                            ->generate((string) $bien->NumOrdre);

                        return [
                            'NumOrdre' => $bien->NumOrdre,
                            'code_formate' => $bien->code_formate ?? '',
                            'designation' => $bien->designation->designation ?? '',
                            'barcode_value' => (string) $bien->NumOrdre,
                            'qr_data_uri' => 'data:image/svg+xml;base64,' . base64_encode($qrSvg),
                        ];
                    })->values()->all(),
                ];

                $qrSvg = QrCode::format('svg')
                    ->size(300)
                    ->margin(1)
                    ->errorCorrection('H')
                    ->generate("EMP-{$emplacement->idEmplacement}");

                $qrDataUris[(string) $emplacement->idEmplacement] = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
            }

            return view('pdf.etiquettes-biens-tous-emplacements-client', [
                'emplacementsData' => $emplacementsData,
                'qrDataUris' => $qrDataUris,
                'filters' => [
                    'localisation' => $filterLocalisation,
                    'affectation' => $filterAffectation,
                ],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'impression groupée: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page d'impression des étiquettes pour un intervalle de NumOrdre
     * avec la même mise en page que l'impression par emplacement.
     */
    public function imprimerEtiquettesParIntervalle(Request $request)
    {
        try {
            $validated = $request->validate([
                'numOrdreMin' => 'required|integer|min:1',
                'numOrdreMax' => 'required|integer|min:1|gte:numOrdreMin',
            ]);

            $numOrdreMin = (int) $validated['numOrdreMin'];
            $numOrdreMax = (int) $validated['numOrdreMax'];

            $biens = Gesimmo::whereBetween('NumOrdre', [$numOrdreMin, $numOrdreMax])
                ->with([
                    'emplacement.localisation',
                    'emplacement.affectation',
                    'designation',
                    'categorie',
                    'etat',
                    'natureJuridique',
                    'sourceFinancement'
                ])
                ->orderBy('NumOrdre')
                ->get();

            if ($biens->isEmpty()) {
                return redirect()->back()->with('error', 'Aucun bien trouvé pour cet intervalle de NumOrdre.');
            }

            $biensData = $biens->map(function ($bien) {
                $qrSvg = QrCode::format('svg')
                    ->size(220)
                    ->margin(0)
                    ->errorCorrection('M')
                    ->generate((string) $bien->NumOrdre);

                return [
                    'NumOrdre' => $bien->NumOrdre,
                    'code_formate' => $bien->code_formate ?? '',
                    'designation' => $bien->designation->designation ?? '',
                    'barcode_value' => (string) $bien->NumOrdre,
                    'qr_data_uri' => 'data:image/svg+xml;base64,' . base64_encode($qrSvg),
                ];
            })->toArray();

            $rangeIdentifier = "RANGE-{$numOrdreMin}-{$numOrdreMax}";
            $qrSvg = QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($rangeIdentifier);

            $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

            $emplacement = (object) [
                'idEmplacement' => $rangeIdentifier,
                'Emplacement' => "Intervalle NumOrdre {$numOrdreMin} - {$numOrdreMax}",
                'localisation' => null,
                'affectation' => null,
            ];

            return view('pdf.etiquettes-biens-par-emplacement-client', [
                'biens' => $biens,
                'biensData' => $biensData,
                'emplacement' => $emplacement,
                'qrDataUri' => $qrDataUri,
                'includeEmplacementQr' => false,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'impression des étiquettes: ' . $e->getMessage());
        }
    }

    /**
     * Exporte les biens en format Excel (CSV)
     * 
     * Colonnes : Code, Désignation, Nature, Localisation, Service, Valeur, État, Date acquisition
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportExcel(Request $request)
    {
        try {
            // Récupérer les IDs sélectionnés si fournis
            $ids = $request->input('ids');
            
            // Récupérer toutes les immobilisations avec relations
            $query = Gesimmo::with([
                'designation',
                'categorie',
                'etat',
                'emplacement.localisation',
                'emplacement.affectation',
                'natureJuridique',
                'sourceFinancement',
            ]);
            
            if ($ids) {
                $idsArray = is_array($ids) ? $ids : explode(',', $ids);
                // Filtrer les IDs valides (entiers)
                $idsArray = array_filter(array_map('intval', $idsArray));
                if (!empty($idsArray)) {
                    $query->whereIn('NumOrdre', $idsArray);
                }
            }
            
            $biens = $query->orderBy('NumOrdre')->get();
            
            if ($biens->isEmpty()) {
                return redirect()->back()->with('warning', 'Aucun bien à exporter.');
            }

            $filename = 'biens_' . now()->format('Y-m-d_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            // Ajouter BOM pour Excel UTF-8
            $callback = function() use ($biens) {
                $file = fopen('php://output', 'w');
                
                // Ajouter BOM UTF-8 pour Excel
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // En-têtes
                fputcsv($file, [
                    'NumOrdre',
                    'Code',
                    'Désignation',
                    'Catégorie',
                    'État',
                    'Emplacement',
                    'Localisation',
                    'Nature Juridique',
                    'Source Financement',
                    'Date Acquisition',
                    'Observations',
                ], ';');

                // Données
                foreach ($biens as $bien) {
                    // DateAcquisition est un entier (année), pas une date
                    $dateAcquisition = '';
                    if ($bien->DateAcquisition && $bien->DateAcquisition > 1970) {
                        $dateAcquisition = (string)$bien->DateAcquisition;
                    }
                    
                    // Gérer les valeurs null pour éviter les erreurs
                    $codeFormate = '';
                    try {
                        $codeFormate = $bien->code_formate ?? '';
                    } catch (\Exception $e) {
                        $codeFormate = '';
                    }
                    
                    fputcsv($file, [
                        $bien->NumOrdre ?? '',
                        $codeFormate,
                        ($bien->designation && isset($bien->designation->designation)) ? $bien->designation->designation : 'N/A',
                        ($bien->categorie && isset($bien->categorie->Categorie)) ? $bien->categorie->Categorie : 'N/A',
                        ($bien->etat && isset($bien->etat->Etat)) ? $bien->etat->Etat : 'N/A',
                        ($bien->emplacement && isset($bien->emplacement->Emplacement)) ? $bien->emplacement->Emplacement : 'N/A',
                        ($bien->emplacement && $bien->emplacement->localisation && isset($bien->emplacement->localisation->Localisation)) 
                            ? $bien->emplacement->localisation->Localisation 
                            : 'N/A',
                        ($bien->natureJuridique && isset($bien->natureJuridique->NatJur)) ? $bien->natureJuridique->NatJur : 'N/A',
                        ($bien->sourceFinancement && isset($bien->sourceFinancement->SourceFin)) ? $bien->sourceFinancement->SourceFin : 'N/A',
                        $dateAcquisition,
                        $bien->Observations ?? '',
                    ], ';');
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'export Excel des biens', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Si on est dans une requête AJAX ou API, retourner une réponse JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'Erreur lors de l\'export Excel: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Erreur lors de l\'export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Exporte les biens en format PDF
     * 
     * Génère une liste complète formatée avec tableau, en-tête et pied de page
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function exportPDF(Request $request)
    {
        try {
            // Récupérer toutes les immobilisations avec relations
            $biens = Gesimmo::with([
                'designation.categorie',
                'categorie',
                'etat',
                'emplacement.localisation',
                'emplacement.affectation',
                'natureJuridique',
                'sourceFinancement',
            ])
                ->orderBy('NumOrdre')
                ->get();

            if ($biens->isEmpty()) {
                return redirect()->back()->with('warning', 'Aucun bien à exporter.');
            }

            // Générer le PDF
            $pdf = Pdf::loadView('pdf.liste-biens', [
                'biens' => $biens,
                'date' => now(),
            ])->setPaper('a4', 'landscape');

            $filename = 'liste_biens_' . now()->format('Y-m-d_His') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'export PDF: ' . $e->getMessage());
        }
    }
}
