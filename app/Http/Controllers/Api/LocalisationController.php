<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocalisationImmo;
use App\Models\Emplacement;
use App\Models\Gesimmo;
use Illuminate\Http\Request;

/**
 * Controller API pour la gestion des localisations depuis la PWA
 * Gère les opérations de localisation via l'API
 */
class LocalisationController extends Controller
{
    /**
     * Récupérer une localisation par son code
     * Utilisé lors du scan QR code d'une localisation
     * 
     * @param string $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function byCode($code)
    {
        $localisation = LocalisationImmo::where('CodeLocalisation', $code)
            ->first();

        if (!$localisation) {
            return response()->json([
                'message' => 'Localisation non trouvée'
            ], 404);
        }

        $localisation->loadCount('emplacements');

        return response()->json([
            'localisation' => $this->formatLocalisation($localisation)
        ]);
    }

    /**
     * Récupérer toutes les immobilisations d'une localisation (via ses emplacements)
     * Utilisé pour charger les immobilisations attendues lors du scan d'une localisation
     * 
     * @param LocalisationImmo $localisation
     * @return \Illuminate\Http\JsonResponse
     */
    public function biens(LocalisationImmo $localisation)
    {
        // Récupérer tous les emplacements de cette localisation
        $emplacements = $localisation->emplacements()->pluck('idEmplacement');
        
        // Récupérer toutes les immobilisations de ces emplacements
        $biens = Gesimmo::whereIn('idEmplacement', $emplacements)
            ->with([
                'designation',
                'categorie',
                'etat',
                'emplacement.localisation',
            ])
            ->get()
            ->map(function ($bien) use ($localisation) {
                return [
                    'id' => $bien->NumOrdre,            // Alias pour PWA v1 (bien.id)
                    'NumOrdre' => $bien->NumOrdre,
                    'code' => $bien->code_formate ?? '',
                    'code_inventaire' => $bien->code_formate ?? '',  // Alias pour PWA v1
                    'designation' => $bien->designation ? $bien->designation->designation : 'N/A',
                    'categorie' => $bien->categorie ? $bien->categorie->Categorie : 'N/A',
                    'etat' => $bien->etat ? $bien->etat->Etat : 'N/A',
                    'emplacement' => $bien->emplacement ? $bien->emplacement->Emplacement : 'N/A',
                    'localisation_id' => $bien->emplacement?->idLocalisation ?? $localisation->idLocalisation,
                    'DateAcquisition' => $bien->DateAcquisition,  // Année (entier)
                ];
            });

        return response()->json([
            'biens' => $biens,
            'total' => $biens->count()
        ]);
    }

    /**
     * Récupérer les détails d'une localisation
     * 
     * @param LocalisationImmo $localisation
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(LocalisationImmo $localisation)
    {
        $localisation->loadCount('emplacements');

        return response()->json([
            'localisation' => $this->formatLocalisation($localisation)
        ]);
    }

    /**
     * Formater une localisation pour l'API avec aliases PWA
     */
    private function formatLocalisation(LocalisationImmo $localisation): array
    {
        return [
            'id' => $localisation->idLocalisation,              // Alias pour PWA v1
            'idLocalisation' => $localisation->idLocalisation,
            'code' => $localisation->CodeLocalisation,          // Alias pour PWA v1
            'designation' => $localisation->Localisation,       // Alias pour PWA v1
            'Localisation' => $localisation->Localisation,
            'CodeLocalisation' => $localisation->CodeLocalisation,
            'emplacements_count' => $localisation->emplacements_count ?? null,
        ];
    }
}
