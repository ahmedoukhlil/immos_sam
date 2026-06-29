<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventaire;
use App\Models\InventaireScan;
use App\Models\InventaireLocalisation;
use App\Models\Emplacement;
use App\Models\Etat;
use App\Models\Gesimmo;
use App\Services\InventaireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controller API pour l'enregistrement des scans depuis la PWA
 * Gère les opérations de scan via l'API
 */
class ScanController extends Controller
{
    protected $inventaireService;

    /**
     * Constructeur avec injection de dépendance
     * 
     * @param InventaireService $inventaireService
     */
    public function __construct(InventaireService $inventaireService)
    {
        $this->inventaireService = $inventaireService;
    }

    /**
     * Enregistrer un nouveau scan
     * 
     * @param Request $request
     * @param Inventaire $inventaire
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Inventaire $inventaire)
    {
        // Valider les données
        // Note: bien_id peut être un NumOrdre (Gesimmo) ou un id (Bien) selon le workflow
        $validated = $request->validate([
            'inventaire_localisation_id' => 'required|exists:inventaire_localisations,id',
            'bien_id' => 'required|integer',
            'statut_scan' => 'required|in:present,deplace,absent,deteriore',
            'localisation_reelle_id' => 'required|exists:localisation,idLocalisation',
            'etat_constate' => 'required|in:bon_etat,neuf,defectueux',
            'commentaire' => 'nullable|string|max:1000',
            'photo' => 'nullable|string', // Base64 image
        ]);

        // Vérifier que l'inventaire est en_cours
        if ($inventaire->statut !== 'en_cours') {
            return response()->json([
                'message' => 'L\'inventaire n\'est pas en cours'
            ], 400);
        }

        // Chercher le bien dans Gesimmo par NumOrdre
        $gesimmo = Gesimmo::find($validated['bien_id']);
        if (!$gesimmo) {
            return response()->json([
                'message' => 'Bien non trouvé (NumOrdre: ' . $validated['bien_id'] . ')'
            ], 404);
        }
        $bienCode = 'GS' . $gesimmo->NumOrdre;

        // Vérifier que le bien n'a pas déjà été scanné dans cet inventaire
        $dejaScanne = InventaireScan::where('inventaire_id', $inventaire->id)
            ->where('bien_id', $validated['bien_id'])
            ->exists();

        if ($dejaScanne) {
            return response()->json([
                'message' => 'Ce bien a déjà été scanné dans cet inventaire'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Traiter la photo si présente
            $photoPath = null;
            if (!empty($validated['photo'])) {
                $photoPath = $this->savePhotoFromBase64($validated['photo'], $bienCode);
            }

            // Créer le scan directement (compatible Gesimmo et Bien)
            $scan = InventaireScan::create([
                'inventaire_id' => $inventaire->id,
                'inventaire_localisation_id' => $validated['inventaire_localisation_id'],
                'bien_id' => $validated['bien_id'],
                'date_scan' => now(),
                'statut_scan' => $validated['statut_scan'],
                'localisation_reelle_id' => $validated['localisation_reelle_id'],
                'etat_constate' => $validated['etat_constate'],
                'commentaire' => $validated['commentaire'] ?? null,
                'photo_path' => $photoPath,
                'user_id' => $request->user()->idUser,
            ]);

            // Mettre à jour le compteur de biens scannés
            $invLoc = InventaireLocalisation::find($validated['inventaire_localisation_id']);
            if ($invLoc) {
                $invLoc->increment('nombre_biens_scannes');
            }

            DB::commit();

            return response()->json([
                'message' => 'Scan enregistré avec succès',
                'scan' => [
                    'id' => $scan->id,
                    'inventaire_id' => $scan->inventaire_id,
                    'bien_id' => $scan->bien_id,
                    'statut_scan' => $scan->statut_scan,
                    'date_scan' => $scan->date_scan->format('Y-m-d H:i:s'),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l\'enregistrement du scan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer plusieurs scans en batch (pour sync offline)
     * 
     * @param Request $request
     * @param Inventaire $inventaire
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeBatch(Request $request, Inventaire $inventaire)
    {
        $validated = $request->validate([
            'scans' => 'required|array|min:1|max:50',
            'scans.*.inventaire_localisation_id' => 'required|exists:inventaire_localisations,id',
            'scans.*.bien_id' => 'required|integer',
            'scans.*.statut_scan' => 'required|in:present,deplace,absent,deteriore',
            'scans.*.localisation_reelle_id' => 'required|exists:localisation,idLocalisation',
            'scans.*.etat_constate' => 'required|in:bon_etat,neuf,defectueux',
            'scans.*.commentaire' => 'nullable|string|max:1000',
            'scans.*.photo' => 'nullable|string',
        ]);

        // Vérifier que l'inventaire est en_cours
        if ($inventaire->statut !== 'en_cours') {
            return response()->json([
                'message' => 'L\'inventaire n\'est pas en cours'
            ], 400);
        }

        $results = [
            'success' => [],
            'errors' => []
        ];

        DB::beginTransaction();

        try {
            foreach ($validated['scans'] as $index => $scanData) {
                try {
                    // Chercher le bien dans Gesimmo par NumOrdre
                    $gesimmo = Gesimmo::find($scanData['bien_id']);
                    if (!$gesimmo) {
                        $results['errors'][] = [
                            'index' => $index,
                            'bien_id' => $scanData['bien_id'],
                            'error' => 'Bien non trouvé (NumOrdre: ' . $scanData['bien_id'] . ')'
                        ];
                        continue;
                    }
                    $bienCode = 'GS' . $gesimmo->NumOrdre;

                    // Vérifier doublon
                    $dejaScanne = InventaireScan::where('inventaire_id', $inventaire->id)
                        ->where('bien_id', $scanData['bien_id'])
                        ->exists();

                    if ($dejaScanne) {
                        $results['errors'][] = [
                            'index' => $index,
                            'bien_id' => $scanData['bien_id'],
                            'error' => 'Déjà scanné'
                        ];
                        continue;
                    }

                    // Traiter photo
                    $photoPath = null;
                    if (!empty($scanData['photo'])) {
                        $photoPath = $this->savePhotoFromBase64($scanData['photo'], $bienCode);
                    }

                    // Créer le scan directement (compatible Gesimmo et Bien)
                    $scan = InventaireScan::create([
                        'inventaire_id' => $inventaire->id,
                        'inventaire_localisation_id' => $scanData['inventaire_localisation_id'],
                        'bien_id' => $scanData['bien_id'],
                        'date_scan' => now(),
                        'statut_scan' => $scanData['statut_scan'],
                        'localisation_reelle_id' => $scanData['localisation_reelle_id'],
                        'etat_constate' => $scanData['etat_constate'],
                        'commentaire' => $scanData['commentaire'] ?? null,
                        'photo_path' => $photoPath,
                        'user_id' => $request->user()->idUser,
                    ]);

                    $results['success'][] = [
                        'index' => $index,
                        'scan_id' => $scan->id,
                        'bien_id' => $scanData['bien_id']
                    ];

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'index' => $index,
                        'bien_id' => $scanData['bien_id'] ?? null,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Batch traité',
                'results' => $results,
                'summary' => [
                    'total' => count($validated['scans']),
                    'success' => count($results['success']),
                    'errors' => count($results['errors'])
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors du traitement du batch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sauvegarder une photo depuis une string Base64
     * 
     * @param string $base64Image
     * @param string $bienCode
     * @return string|null
     */
    private function savePhotoFromBase64($base64Image, $bienCode)
    {
        try {
            // Extraire les données de l'image
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif, etc.

                // Vérifier le type d'image
                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    throw new \Exception('Type d\'image non supporté');
                }

                // Décoder
                $image = base64_decode($base64Image);

                if ($image === false) {
                    throw new \Exception('Échec du décodage Base64');
                }

                // Générer un nom de fichier unique
                $filename = 'scan_' . $bienCode . '_' . time() . '_' . Str::random(8) . '.' . $type;
                $path = 'photos/' . date('Y/m') . '/' . $filename;

                // Sauvegarder
                Storage::disk('public')->put($path, $image);

                return $path;

            } else {
                throw new \Exception('Format Base64 invalide');
            }

        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer le scan
            \Log::error('Erreur sauvegarde photo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer la liste des états (pour PWA modal)
     * Utilise la table etat sans modifier la BD
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEtats()
    {
        // 3 états : Neuf, Bon état, Défectueuse
        $etats = Etat::orderBy('Etat')
            ->get(['idEtat', 'Etat', 'CodeEtat'])
            ->map(function ($etat) {
                $constate = $this->mapEtatToConstate($etat->idEtat);
                return [
                    'id' => $etat->idEtat,
                    'label' => $etat->Etat,
                    'code' => $etat->CodeEtat,
                    'require_photo' => ($constate === 'defectueux'),
                ];
            });

        return response()->json(['etats' => $etats]);
    }

    /**
     * Mapper idEtat (table etat) vers etat_constate (enum inventaire_scans)
     * Table etat: 1=Bon Etat (BE), 2=Neuf (NF), 3=Defectueux (DFCT)
     * Enum etat_constate: bon_etat, neuf, defectueux
     *
     * @param int|null $idEtat
     * @return string
     */
    private function mapEtatToConstate(?int $idEtat): string
    {
        if (!$idEtat) {
            return 'bon_etat';
        }

        $etat = Etat::find($idEtat);
        if (!$etat) {
            return 'bon_etat';
        }

        // Mapping par CodeEtat
        $mapCode = [
            'BE' => 'bon_etat',
            'NF' => 'neuf',
            'DFCT' => 'defectueux',
        ];

        if ($etat->CodeEtat && isset($mapCode[strtoupper($etat->CodeEtat)])) {
            return $mapCode[strtoupper($etat->CodeEtat)];
        }

        // Mapping par libellé Etat
        $mapLabel = [
            'bon etat' => 'bon_etat',
            'bon état' => 'bon_etat',
            'neuf' => 'neuf',
            'defectueux' => 'defectueux',
            'défectueux' => 'defectueux',
            'defectueuse' => 'defectueux',
            'défectueuse' => 'defectueux',
        ];
        $label = mb_strtolower(trim($etat->Etat));
        return $mapLabel[$label] ?? 'bon_etat';
    }

    /**
     * NOUVEAU WORKFLOW: Récupérer tous les biens d'un emplacement
     * 
     * @param int $idEmplacement
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBiensByEmplacement($idEmplacement)
    {
        // Vérifier que l'emplacement existe
        $emplacement = Emplacement::with(['localisation', 'affectation'])
            ->find($idEmplacement);

        if (!$emplacement) {
            return response()->json([
                'message' => 'Emplacement non trouvé'
            ], 404);
        }

        // Déterminer l'inventaire actif (priorité à "en_cours")
        $inventaire = $this->getInventaireActif();

        $inventaireLocalisation = null;
        $biensDejaScannes = collect();
        $reopened = false;
        $biensRestants = null;

        if ($inventaire && $emplacement->idLocalisation) {
            $inventaireLocalisation = InventaireLocalisation::firstOrCreate(
                [
                    'inventaire_id' => $inventaire->id,
                    'localisation_id' => $emplacement->idLocalisation,
                ],
                [
                    'statut' => 'en_attente',
                    'nombre_biens_attendus' => 0,
                    'nombre_biens_scannes' => 0,
                    'user_id' => optional(request()->user())->idUser,
                ]
            );

            $nombreBiensAttendus = Gesimmo::whereHas('emplacement', function ($q) use ($inventaireLocalisation) {
                $q->where('idLocalisation', $inventaireLocalisation->localisation_id);
            })->count();

            $nombreBiensScannes = InventaireScan::where('inventaire_id', $inventaire->id)
                ->where('inventaire_localisation_id', $inventaireLocalisation->id)
                ->distinct('bien_id')
                ->count('bien_id');

            $biensRestants = max(0, $nombreBiensAttendus - $nombreBiensScannes);

            $inventaireLocalisation->update([
                'nombre_biens_attendus' => $nombreBiensAttendus,
                'nombre_biens_scannes' => $nombreBiensScannes,
            ]);

            // Si la localisation est marquée terminée mais incomplète, la réouvrir automatiquement
            if ($inventaireLocalisation->statut === 'termine' && $biensRestants > 0) {
                $inventaireLocalisation->update([
                    'statut' => 'en_cours',
                    'date_fin_scan' => null,
                    'date_debut_scan' => $inventaireLocalisation->date_debut_scan ?? now(),
                    'user_id' => optional(request()->user())->idUser ?? $inventaireLocalisation->user_id,
                ]);
                $reopened = true;
            }

        }

        // Récupérer tous les biens de cet emplacement
        $biens = Gesimmo::where('idEmplacement', $idEmplacement)
            ->with([
                'designation',
                'categorie',
                'etat',
                'emplacement',
                'natureJuridique',
                'sourceFinancement'
            ])
            ->orderBy('NumOrdre')
            ->get()
            ->map(function ($bien) {
                return [
                    'num_ordre' => $bien->NumOrdre,
                    'code_barre_128' => (string)$bien->NumOrdre, // Code-barres 128 = NumOrdre
                    'designation' => $bien->designation->designation ?? 'N/A',
                    'categorie' => $bien->categorie->Categorie ?? 'N/A',
                    'etat' => $bien->etat->Etat ?? 'N/A',
                    'date_acquisition' => $bien->DateAcquisition,
                    'observations' => $bien->Observations,
                ];
            });

        // Précharger les biens déjà scannés de CET emplacement dans l'inventaire actif
        if ($inventaire && $biens->isNotEmpty()) {
            $numOrdresAttendus = $biens->pluck('num_ordre')->map(fn ($n) => (int) $n)->all();

            $biensDejaScannes = InventaireScan::where('inventaire_id', $inventaire->id)
                ->whereIn('bien_id', $numOrdresAttendus)
                ->distinct('bien_id')
                ->get(['bien_id', 'etat_constate'])
                ->map(function ($scan) {
                    return [
                        'num_ordre' => (int) $scan->bien_id,
                        'etat_constate' => $scan->etat_constate,
                    ];
                });

            if ($inventaireLocalisation) {
                $biensRestants = max(0, $biens->count() - $biensDejaScannes->count());

                if ($inventaireLocalisation->statut === 'termine' && $biensRestants > 0) {
                    $inventaireLocalisation->update([
                        'statut' => 'en_cours',
                        'date_fin_scan' => null,
                        'date_debut_scan' => $inventaireLocalisation->date_debut_scan ?? now(),
                        'user_id' => optional(request()->user())->idUser ?? $inventaireLocalisation->user_id,
                    ]);
                    $reopened = true;
                }
            }
        }

        return response()->json([
            'emplacement' => [
                'id' => $emplacement->idEmplacement,
                'code' => $emplacement->CodeEmplacement,
                'nom' => $emplacement->Emplacement,
                'localisation' => $emplacement->localisation ? [
                    'id' => $emplacement->localisation->idLocalisation,
                    'nom' => $emplacement->localisation->Localisation,
                    'code' => $emplacement->localisation->CodeLocalisation,
                ] : null,
                'affectation' => $emplacement->affectation ? [
                    'id' => $emplacement->affectation->idAffectation,
                    'nom' => $emplacement->affectation->Affectation,
                    'code' => $emplacement->affectation->CodeAffectation,
                ] : null,
            ],
            'biens' => $biens,
            'total' => $biens->count(),
            'biens_deja_scannes' => $biensDejaScannes->values(),
            'inventaire_localisation' => $inventaireLocalisation ? [
                'id' => $inventaireLocalisation->id,
                'statut' => $inventaireLocalisation->statut,
                'nombre_biens_attendus' => (int) $inventaireLocalisation->nombre_biens_attendus,
                'nombre_biens_scannes' => (int) $inventaireLocalisation->nombre_biens_scannes,
                'biens_restants' => (int) ($biensRestants ?? 0),
                'reopened' => $reopened,
            ] : null,
        ], 200);
    }

    /**
     * NOUVEAU WORKFLOW: Enregistrer un scan pour un emplacement
     * Le code-barres 128 contient uniquement le NumOrdre
     * 
     * @param Request $request
     * @param int $idEmplacement
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeScanEmplacement(Request $request, $idEmplacement)
    {
        $validated = $request->validate([
            'num_ordre' => 'required|integer', // Code-barres 128 = NumOrdre
        ]);

        // Vérifier que l'emplacement existe
        $emplacement = Emplacement::find($idEmplacement);
        if (!$emplacement) {
            return response()->json([
                'message' => 'Emplacement non trouvé'
            ], 404);
        }

        // Rechercher le bien par NumOrdre (clé primaire)
        $bien = Gesimmo::where('NumOrdre', $validated['num_ordre'])
            ->with(['designation', 'categorie', 'etat', 'emplacement.localisation', 'emplacement.affectation'])
            ->first();

        if (!$bien) {
            return response()->json([
                'message' => 'Bien non trouvé',
                'num_ordre' => $validated['num_ordre'],
            ], 404);
        }

        // Vérifier si le bien appartient à cet emplacement
        $appartient = ($bien->idEmplacement == $idEmplacement);

        // Construire les infos de l'emplacement initial si le bien est déplacé
        $emplacementInitial = null;
        if (!$appartient && $bien->emplacement) {
            $emplacementInitial = [
                'id' => $bien->emplacement->idEmplacement,
                'nom' => $bien->emplacement->Emplacement,
                'code' => $bien->emplacement->CodeEmplacement ?? null,
                'localisation' => $bien->emplacement->localisation->Localisation ?? null,
                'affectation' => $bien->emplacement->affectation->Affectation ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'bien' => [
                'num_ordre' => $bien->NumOrdre,
                'designation' => $bien->designation->designation ?? 'N/A',
                'categorie' => $bien->categorie->Categorie ?? 'N/A',
                'etat' => $bien->etat->Etat ?? 'N/A',
                'appartient_emplacement' => $appartient,
                'statut' => $appartient ? 'present' : 'deplace',
                'emplacement_actuel' => $bien->idEmplacement,
                'emplacement_initial' => $emplacementInitial,
            ],
        ], 200);
    }

    /**
     * NOUVEAU WORKFLOW: Terminer le scan d'un emplacement
     * Calcule les écarts entre biens attendus et biens scannés
     * ET sauvegarde les scans dans la base de données
     * 
     * @param Request $request
     * @param int $idEmplacement
     * @return \Illuminate\Http\JsonResponse
     */
    public function terminerScanEmplacement(Request $request, $idEmplacement)
    {
        $validated = $request->validate([
            'biens_scannes' => 'required|array',
            'biens_scannes.*.num_ordre' => 'required|integer',
            'biens_scannes.*.etat_id' => 'nullable|integer|exists:etat,idEtat',
            'biens_scannes.*.etat_constate' => 'nullable|string|in:bon_etat,neuf,defectueux',
            'biens_scannes.*.photo' => 'nullable|string', // Base64 image
        ]);

        $user = $request->user();

        // Vérifier que l'emplacement existe
        $emplacement = Emplacement::with(['localisation', 'affectation'])
            ->find($idEmplacement);

        if (!$emplacement) {
            return response()->json([
                'message' => 'Emplacement non trouvé'
            ], 404);
        }

        // Trouver l'inventaire en cours
        $inventaire = $this->getInventaireActif();
        
        if (!$inventaire) {
            return response()->json([
                'message' => 'Aucun inventaire en cours'
            ], 404);
        }

        // Récupérer la localisation de l'emplacement
        $localisation = $emplacement->localisation;
        
        if (!$localisation) {
            return response()->json([
                'message' => 'Localisation non trouvée pour cet emplacement'
            ], 404);
        }

        // Trouver ou créer l'InventaireLocalisation
        $inventaireLocalisation = InventaireLocalisation::firstOrCreate(
            [
                'inventaire_id' => $inventaire->id,
                'localisation_id' => $localisation->idLocalisation,
            ],
            [
                'statut' => 'en_attente',
                'nombre_biens_attendus' => 0,
                'nombre_biens_scannes' => 0,
                'user_id' => $user->idUser,
            ]
        );

        // Si c'est la première fois, calculer le nombre de biens attendus
        if ($inventaireLocalisation->nombre_biens_attendus == 0) {
            $nombreBiensAttendus = $localisation->emplacements()
                ->withCount('immobilisations')
                ->get()
                ->sum('immobilisations_count');
            
            $inventaireLocalisation->update([
                'nombre_biens_attendus' => $nombreBiensAttendus
            ]);
        }

        // Démarrer le scan si pas encore démarré
        if ($inventaireLocalisation->statut === 'en_attente') {
            $inventaireLocalisation->update([
                'statut' => 'en_cours',
                'date_debut_scan' => now(),
                'user_id' => $user->idUser,
            ]);
        }

        // Ajouter l'agent à la table pivot s'il n'y est pas déjà
        $inventaireLocalisation->agents()->syncWithoutDetaching([$user->idUser]);

        // Récupérer tous les biens attendus de cet emplacement
        $biensAttendus = Gesimmo::where('idEmplacement', $idEmplacement)
            ->with(['designation', 'categorie'])
            ->get();

        $biensAttendusList = $biensAttendus->pluck('NumOrdre')->toArray();
        $biensScannesList = array_map(fn($b) => is_array($b) ? $b['num_ordre'] : $b, $validated['biens_scannes']);

        // Calculer les écarts
        $biensManquants = array_diff($biensAttendusList, $biensScannesList);
        $biensEnTrop = array_diff($biensScannesList, $biensAttendusList);

        // Sauvegarder les scans dans la base de données (avec etat_constate et photo)
        $biensIntrouvables = [];
        DB::transaction(function () use ($inventaire, $inventaireLocalisation, $validated, $emplacement, $idEmplacement, $user, &$biensIntrouvables) {
            foreach ($validated['biens_scannes'] as $scanItem) {
                $numOrdre = is_array($scanItem) ? $scanItem['num_ordre'] : $scanItem;
                
                // Vérifier que le bien existe dans gesimmo
                $bien = Gesimmo::find($numOrdre);
                if (!$bien) {
                    $biensIntrouvables[] = $numOrdre;
                    continue;
                }

                // Utiliser etat_id (table etat) si fourni, sinon etat_constate direct
                $etatConstate = $scanItem['etat_constate'] ?? null;
                if (!$etatConstate && isset($scanItem['etat_id'])) {
                    $etatConstate = $this->mapEtatToConstate((int) $scanItem['etat_id']);
                }
                $etatConstate = $etatConstate ?: 'bon_etat';
                $photoBase64 = $scanItem['photo'] ?? null;

                $scanExistant = InventaireScan::where('inventaire_id', $inventaire->id)
                    ->where('bien_id', $numOrdre)
                    ->first();

                if (!$scanExistant) {
                    $photoPath = null;
                    if ($photoBase64) {
                        $photoPath = $this->savePhotoFromBase64($photoBase64, 'GS' . $numOrdre);
                    }

                    // Détecter si le bien est déplacé (emplacement initial ≠ emplacement scanné)
                    $statutScan = 'present';
                    if ($bien->idEmplacement != $idEmplacement) {
                        $statutScan = 'deplace';
                    }

                    InventaireScan::create([
                        'inventaire_id' => $inventaire->id,
                        'inventaire_localisation_id' => $inventaireLocalisation->id,
                        'bien_id' => $numOrdre,
                        'date_scan' => now(),
                        'statut_scan' => $statutScan,
                        'localisation_reelle_id' => $emplacement->idLocalisation,
                        'etat_constate' => $etatConstate,
                        'photo_path' => $photoPath,
                        'user_id' => $user->idUser,
                    ]);
                }
            }
        });

        // Mettre à jour le nombre de biens scannés dans InventaireLocalisation
        // Compter les scans uniques pour cette localisation
        $nombreBiensScannes = InventaireScan::where('inventaire_localisation_id', $inventaireLocalisation->id)
            ->distinct('bien_id')
            ->count('bien_id');
        
        // Si le count distinct ne fonctionne pas, utiliser une autre méthode
        if ($nombreBiensScannes == 0) {
            $nombreBiensScannes = InventaireScan::where('inventaire_localisation_id', $inventaireLocalisation->id)
                ->select('bien_id')
                ->distinct()
                ->get()
                ->count();
        }
        
        $inventaireLocalisation->update([
            'nombre_biens_scannes' => $nombreBiensScannes,
            'statut' => 'termine',
            'date_fin_scan' => now(),
        ]);

        // Détails des biens manquants
        $detailsManquants = Gesimmo::whereIn('NumOrdre', $biensManquants)
            ->with(['designation', 'categorie'])
            ->get()
            ->map(function ($bien) {
                return [
                    'num_ordre' => $bien->NumOrdre,
                    'code_inventaire' => $bien->code_inventaire ?? "GS{$bien->NumOrdre}",
                    'designation' => $bien->designation->designation ?? 'N/A',
                    'categorie' => $bien->categorie->Categorie ?? 'N/A',
                ];
            });

        // Détails des biens déplacés (scannés mais pas dans cet emplacement)
        $detailsEnTrop = Gesimmo::whereIn('NumOrdre', $biensEnTrop)
            ->with(['designation', 'categorie', 'emplacement.localisation', 'emplacement.affectation'])
            ->get()
            ->map(function ($bien) {
                return [
                    'num_ordre' => $bien->NumOrdre,
                    'code_inventaire' => $bien->code_inventaire ?? "GS{$bien->NumOrdre}",
                    'designation' => $bien->designation->designation ?? 'N/A',
                    'categorie' => $bien->categorie->Categorie ?? 'N/A',
                    'statut' => 'deplace',
                    'emplacement_initial' => $bien->emplacement ? [
                        'id' => $bien->emplacement->idEmplacement,
                        'nom' => $bien->emplacement->Emplacement,
                        'code' => $bien->emplacement->CodeEmplacement ?? null,
                        'localisation' => $bien->emplacement->localisation->Localisation ?? null,
                        'affectation' => $bien->emplacement->affectation->Affectation ?? null,
                    ] : null,
                ];
            });

        return response()->json([
            'emplacement' => [
                'id' => $emplacement->idEmplacement,
                'nom' => $emplacement->Emplacement,
                'code' => $emplacement->CodeEmplacement,
            ],
            'statistiques' => [
                'total_attendu' => count($biensAttendusList),
                'total_scanne' => count($biensScannesList),
                'total_manquant' => count($biensManquants),
                'total_en_trop' => count($biensEnTrop),
                'total_introuvables' => count($biensIntrouvables),
                'taux_conformite' => count($biensAttendusList) > 0 
                    ? round((count($biensScannesList) - count($biensEnTrop) - count($biensIntrouvables)) / count($biensAttendusList) * 100, 2) 
                    : 0,
            ],
            'biens_manquants' => $detailsManquants,
            'biens_en_trop' => $detailsEnTrop,
            'biens_introuvables' => $biensIntrouvables,
        ], 200);
    }

    /**
     * Retourne l'inventaire actif en priorisant un inventaire en cours.
     */
    private function getInventaireActif(): ?Inventaire
    {
        return Inventaire::query()
            ->where(function ($q) {
                $q->where('statut', 'en_cours')
                  ->orWhere('statut', 'en_preparation');
            })
            ->orderByRaw("CASE WHEN statut = 'en_cours' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->first();
    }
}
