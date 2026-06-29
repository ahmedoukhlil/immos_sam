<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventaire;
use App\Models\InventaireLocalisation;
use App\Models\InventaireScan;
use App\Models\Gesimmo;
use App\Services\InventaireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller API pour la gestion des inventaires depuis la PWA
 * Gère les opérations d'inventaire via l'API
 */
class InventaireController extends Controller
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
     * Récupérer l'inventaire actuellement en cours
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function current()
    {
        $inventaire = Inventaire::whereIn('statut', ['en_preparation', 'en_cours'])
            ->with(['creator'])
            ->withCount(['inventaireLocalisations', 'inventaireScans'])
            ->orderBy('annee', 'desc')
            ->first();

        if (!$inventaire) {
            return response()->json([
                'message' => 'Aucun inventaire en cours'
            ], 404);
        }

        // Calculer les statistiques
        $stats = $this->inventaireService->calculerStatistiques($inventaire);

        return response()->json([
            'inventaire' => [
            'id' => $inventaire->id,
            'annee' => $inventaire->annee,
                'date_debut' => $inventaire->date_debut->format('Y-m-d'),
                'date_fin' => $inventaire->date_fin?->format('Y-m-d'),
            'statut' => $inventaire->statut,
                'observation' => $inventaire->observation,
                'created_by' => $inventaire->created_by,
                'creator_name' => $inventaire->creator->name ?? null,
            ],
            'statistiques' => $stats
        ]);
    }

    /**
     * Récupérer les localisations assignées à l'utilisateur pour cet inventaire
     * 
     * @param Request $request
     * @param Inventaire $inventaire
     * @return \Illuminate\Http\JsonResponse
     */
    public function mesLocalisations(Request $request, Inventaire $inventaire)
    {
        $userId = $request->user()->idUser;

        $localisations = InventaireLocalisation::where('inventaire_id', $inventaire->id)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereNull('user_id'); // Localisations non assignées accessibles
            })
            ->with(['localisation'])
            ->get()
            ->map(function ($invLoc) {
                return [
                    'id' => $invLoc->id,
                    'inventaire_id' => $invLoc->inventaire_id,
                    'localisation_id' => $invLoc->localisation_id,
                    'date_debut_scan' => $invLoc->date_debut_scan?->format('Y-m-d H:i:s'),
                    'date_fin_scan' => $invLoc->date_fin_scan?->format('Y-m-d H:i:s'),
                    'statut' => $invLoc->statut,
                    'nombre_biens_attendus' => $invLoc->nombre_biens_attendus,
                    'nombre_biens_scannes' => $invLoc->nombre_biens_scannes,
                    'user_id' => $invLoc->user_id,
                    'localisation' => $invLoc->localisation ? [
                        'id' => $invLoc->localisation->idLocalisation,
                        'idLocalisation' => $invLoc->localisation->idLocalisation,
                        'code' => $invLoc->localisation->CodeLocalisation,
                        'designation' => $invLoc->localisation->Localisation,
                        'Localisation' => $invLoc->localisation->Localisation,
                        'CodeLocalisation' => $invLoc->localisation->CodeLocalisation,
                    ] : null
                ];
            });

        return response()->json([
            'localisations' => $localisations
        ]);
    }

    /**
     * Démarrer le scan d'une localisation
     * 
     * @param Request $request
     * @param Inventaire $inventaire
     * @return \Illuminate\Http\JsonResponse
     */
    public function demarrerLocalisation(Request $request, Inventaire $inventaire)
    {
        // Valider les données
        $validated = $request->validate([
            'localisation_id' => 'required|exists:localisation,idLocalisation',
            'user_id' => 'required|exists:users,idUser',
        ]);

        // Vérifier que l'inventaire est en_cours ou en_preparation
        if (!in_array($inventaire->statut, ['en_cours', 'en_preparation'])) {
            \Log::warning('[API] Inventaire pas en cours', [
                'inventaire_id' => $inventaire->id,
                'statut_actuel' => $inventaire->statut,
                'statuts_valides' => ['en_cours', 'en_preparation']
            ]);
            
            return response()->json([
                'message' => 'L\'inventaire n\'est pas en cours',
                'statut_actuel' => $inventaire->statut,
                'statuts_valides' => ['en_cours', 'en_preparation']
            ], 400);
        }

        // Vérifier que l'utilisateur correspond à celui authentifié
        if ($validated['user_id'] != $request->user()->idUser) {
            return response()->json([
                'message' => 'Non autorisé'
            ], 403);
        }

        try {
            // Récupérer ou créer l'InventaireLocalisation
            $inventaireLocalisation = InventaireLocalisation::firstOrCreate(
                [
                    'inventaire_id' => $inventaire->id,
                    'localisation_id' => $validated['localisation_id'],
                ],
                [
                    'statut' => 'en_attente',
                    'nombre_biens_attendus' => 0,
                    'nombre_biens_scannes' => 0,
                    'user_id' => $validated['user_id'],
                ]
            );

            // Recalculer les compteurs pour décider si la localisation peut être réouverte
            [$biensAttendus, $biensScannes] = $this->recalculerCompteursLocalisation($inventaireLocalisation);
            $biensRestants = max(0, $biensAttendus - $biensScannes);

            // ✅ CAS 1 : Localisation déjà terminée
            if ($inventaireLocalisation->statut === 'termine') {
                // Si des biens restent à scanner, autoriser la réouverture
                if ($biensRestants > 0) {
                    $inventaireLocalisation->update([
                        'statut' => 'en_cours',
                        'date_fin_scan' => null,
                        'user_id' => $validated['user_id'],
                        'date_debut_scan' => $inventaireLocalisation->date_debut_scan ?? now(),
                    ]);

                    return response()->json([
                        'message' => 'Localisation réouverte pour scanner les biens restants',
                        'reopened' => true,
                        'biens_restants' => $biensRestants,
                        'inventaire_localisation' => $this->formatInventaireLocalisation($inventaireLocalisation)
                    ]);
                }

                return response()->json([
                    'message' => 'Cette localisation a déjà été inventoriée',
                    'statut' => 'termine',
                    'reopened' => false,
                    'biens_restants' => 0,
                    'inventaire_localisation' => $this->formatInventaireLocalisation($inventaireLocalisation)
                ], 400);
            }

            // ✅ CAS 2 : Localisation déjà en cours → Retourner telle quelle
            if ($inventaireLocalisation->statut === 'en_cours') {
                \Log::info('[API] Localisation déjà en cours, retour des données existantes', [
                    'inventaire_localisation_id' => $inventaireLocalisation->id
                ]);
                
                return response()->json([
                    'message' => 'Localisation déjà active',
                    'inventaire_localisation' => $this->formatInventaireLocalisation($inventaireLocalisation)
                ]);
            }

            // ✅ CAS 3 : Localisation en attente → Démarrer
            $inventaireLocalisation->update([
                'statut' => 'en_cours',
                'date_debut_scan' => now(),
            ]);

            \Log::info('[API] Localisation démarrée', [
                'inventaire_localisation_id' => $inventaireLocalisation->id
            ]);

            return response()->json([
                'message' => 'Localisation démarrée',
                'inventaire_localisation' => $this->formatInventaireLocalisation($inventaireLocalisation)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du démarrage du scan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminer le scan d'une localisation
     * 
     * @param Request $request
     * @param Inventaire $inventaire
     * @return \Illuminate\Http\JsonResponse
     */
    public function terminerLocalisation(Request $request, Inventaire $inventaire)
    {
        $validated = $request->validate([
            'inventaire_localisation_id' => 'required|exists:inventaire_localisations,id',
        ]);

        $inventaireLocalisation = InventaireLocalisation::findOrFail($validated['inventaire_localisation_id']);

        // Vérifier que c'est bien pour cet inventaire
        if ($inventaireLocalisation->inventaire_id != $inventaire->id) {
            return response()->json([
                'message' => 'Localisation non liée à cet inventaire'
            ], 400);
        }

        // Vérifier que l'utilisateur est celui qui scanne
        if ($inventaireLocalisation->user_id != $request->user()->idUser) {
            return response()->json([
                'message' => 'Vous n\'êtes pas assigné à cette localisation'
            ], 403);
        }

        try {
            $result = $this->inventaireService->terminerLocalisation($inventaireLocalisation);

            if (!$result) {
                return response()->json([
                    'message' => 'Impossible de terminer la localisation'
                ], 400);
        }

            $inventaireLocalisation->refresh();

        return response()->json([
                'message' => 'Scan de la localisation terminé',
                'inventaire_localisation' => $this->formatInventaireLocalisation($inventaireLocalisation)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la fermeture du scan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques d'un inventaire
     * 
     * @param Inventaire $inventaire
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Inventaire $inventaire)
    {
        $stats = $this->inventaireService->calculerStatistiques($inventaire);

        return response()->json([
            'statistiques' => $stats
        ]);
    }

    /**
     * Formater un InventaireLocalisation pour l'API
     * 
     * @param InventaireLocalisation $invLoc
     * @return array
     */
    private function formatInventaireLocalisation(InventaireLocalisation $invLoc)
    {
        $invLoc->load('localisation');

        return [
            'id' => $invLoc->id,
            'inventaire_id' => $invLoc->inventaire_id,
            'localisation_id' => $invLoc->localisation_id,
            'date_debut_scan' => $invLoc->date_debut_scan?->format('Y-m-d H:i:s'),
            'date_fin_scan' => $invLoc->date_fin_scan?->format('Y-m-d H:i:s'),
            'statut' => $invLoc->statut,
            'nombre_biens_attendus' => $invLoc->nombre_biens_attendus,
            'nombre_biens_scannes' => $invLoc->nombre_biens_scannes,
            'user_id' => $invLoc->user_id,
            'localisation' => $invLoc->localisation ? [
                'id' => $invLoc->localisation->idLocalisation,
                'idLocalisation' => $invLoc->localisation->idLocalisation,
                'code' => $invLoc->localisation->CodeLocalisation,
                'designation' => $invLoc->localisation->Localisation,
                'Localisation' => $invLoc->localisation->Localisation,
                'CodeLocalisation' => $invLoc->localisation->CodeLocalisation,
            ] : null
        ];
    }

    /**
     * Recalcule les compteurs de biens attendus/scannés d'une localisation d'inventaire.
     */
    private function recalculerCompteursLocalisation(InventaireLocalisation $inventaireLocalisation): array
    {
        $biensAttendus = Gesimmo::whereHas('emplacement', function ($q) use ($inventaireLocalisation) {
            $q->where('idLocalisation', $inventaireLocalisation->localisation_id);
        })->count();

        $biensScannes = InventaireScan::where('inventaire_id', $inventaireLocalisation->inventaire_id)
            ->where('inventaire_localisation_id', $inventaireLocalisation->id)
            ->distinct('bien_id')
            ->count('bien_id');

        $inventaireLocalisation->update([
            'nombre_biens_attendus' => $biensAttendus,
            'nombre_biens_scannes' => $biensScannes,
        ]);

        return [$biensAttendus, $biensScannes];
    }
}
