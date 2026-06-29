<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gesimmo;
use Illuminate\Http\Request;

/**
 * Controller API pour la gestion des immobilisations depuis la PWA
 * Gère les opérations d'immobilisation via l'API
 */
class BienController extends Controller
{
    /**
     * Récupérer les détails d'une immobilisation
     * Utilisé lors du scan QR code d'une immobilisation
     * 
     * @param Gesimmo $bien
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Gesimmo $bien)
    {
        // Charger les relations nécessaires (sans 'code' car généré côté client)
        $bien->load([
            'designation.categorie',
            'categorie',
            'etat',
            'emplacement.localisation',
            'emplacement.affectation',
            'natureJuridique',
            'sourceFinancement',
        ]);

        return response()->json([
            'bien' => [
                'id' => $bien->NumOrdre,            // Alias pour PWA v1 (bien.id)
                'NumOrdre' => $bien->NumOrdre,
                'code' => $bien->code_formate,
                'code_inventaire' => $bien->code_formate,  // Alias pour PWA v1
                'designation' => $bien->designation ? [
                    'id' => $bien->designation->id,
                    'designation' => $bien->designation->designation,
                    'CodeDesignation' => $bien->designation->CodeDesignation,
                ] : null,
                'categorie' => $bien->categorie ? [
                    'idCategorie' => $bien->categorie->idCategorie,
                    'Categorie' => $bien->categorie->Categorie,
                    'CodeCategorie' => $bien->categorie->CodeCategorie,
                ] : null,
                'etat' => $bien->etat ? [
                    'idEtat' => $bien->etat->idEtat,
                    'Etat' => $bien->etat->Etat,
                    'CodeEtat' => $bien->etat->CodeEtat,
                ] : null,
                'emplacement' => $bien->emplacement ? [
                    'idEmplacement' => $bien->emplacement->idEmplacement,
                    'Emplacement' => $bien->emplacement->Emplacement,
                    'CodeEmplacement' => $bien->emplacement->CodeEmplacement,
                    'localisation' => $bien->emplacement->localisation ? [
                        'idLocalisation' => $bien->emplacement->localisation->idLocalisation,
                        'Localisation' => $bien->emplacement->localisation->Localisation,
                        'CodeLocalisation' => $bien->emplacement->localisation->CodeLocalisation,
                    ] : null,
                ] : null,
                'localisation_id' => $bien->emplacement?->idLocalisation,  // Pour comparaison PWA v1
                'natureJuridique' => $bien->natureJuridique ? [
                    'idNatJur' => $bien->natureJuridique->idNatJur,
                    'NatJur' => $bien->natureJuridique->NatJur,
                    'CodeNatJur' => $bien->natureJuridique->CodeNatJur,
                ] : null,
                'sourceFinancement' => $bien->sourceFinancement ? [
                    'idSF' => $bien->sourceFinancement->idSF,
                    'SourceFin' => $bien->sourceFinancement->SourceFin,
                    'CodeSourceFin' => $bien->sourceFinancement->CodeSourceFin,
                ] : null,
                'DateAcquisition' => $bien->DateAcquisition,  // Année (entier)
                'Observations' => $bien->Observations,
            ]
        ]);
    }

    /**
     * Récupérer une immobilisation par son code
     * Utilisé lors du scan QR code d'une immobilisation
     * 
     * @param string $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function byCode($code)
    {
        // Le code est au format: CodeNatJur/CodeDesignation/CodeCategorie/Année/CodeSourceFin/NumOrdre
        $parts = explode('/', $code);
        
        if (count($parts) !== 6) {
            return response()->json([
                'message' => 'Format de code invalide'
            ], 400);
        }

        $numOrdre = (int) end($parts);
        $bien = Gesimmo::with([
            'designation.categorie',
            'categorie',
            'etat',
            'emplacement.localisation',
            'emplacement.affectation',
            'natureJuridique',
            'sourceFinancement',
        ])->find($numOrdre);

        if (!$bien) {
            return response()->json([
                'message' => 'Immobilisation non trouvée'
            ], 404);
        }

        // Vérifier que le code correspond
        if ($bien->code_formate !== $code) {
            return response()->json([
                'message' => 'Code d\'immobilisation invalide'
            ], 400);
        }

        return $this->show($bien);
    }

}
