<?php

namespace App\Livewire\Traits;

use App\Models\LocalisationImmo;
use App\Models\Affectation;
use App\Models\Emplacement;
use Illuminate\Support\Facades\Cache;

/**
 * Trait pour optimiser les requêtes d'options dans les composants Livewire
 * Utilise le cache Laravel pour des performances ultra-rapides
 */
trait WithCachedOptions
{
    /**
     * Retourne les options de localisations avec cache
     * 
     * @param int $cacheDuration Durée du cache en secondes (default: 300 = 5 minutes)
     * @return array
     */
    protected function getCachedLocalisationOptions(int $cacheDuration = 300): array
    {
        return Cache::remember('localisation_options_all', $cacheDuration, function () {
            return LocalisationImmo::select('idLocalisation', 'Localisation', 'CodeLocalisation')
                ->orderBy('Localisation')
                ->get()
                ->map(function ($localisation) {
                    return [
                        'value' => (string)$localisation->idLocalisation,
                        'text' => ($localisation->CodeLocalisation ? $localisation->CodeLocalisation . ' - ' : '') . $localisation->Localisation,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Retourne les options d'affectations filtrées par localisation avec cache
     * 
     * @param int|null $idLocalisation ID de la localisation pour filtrer
     * @param int $cacheDuration Durée du cache en secondes
     * @return array
     */
    protected function getCachedAffectationOptions(?int $idLocalisation = null, int $cacheDuration = 300): array
    {
        // Si aucune localisation, retourner toutes les affectations
        if (empty($idLocalisation)) {
            return Cache::remember('affectation_options_all', $cacheDuration, function () {
                return Affectation::select('idAffectation', 'Affectation', 'CodeAffectation')
                    ->orderBy('Affectation')
                    ->get()
                    ->map(function ($affectation) {
                        return [
                            'value' => (string)$affectation->idAffectation,
                            'text' => ($affectation->CodeAffectation ? $affectation->CodeAffectation . ' - ' : '') . $affectation->Affectation,
                        ];
                    })
                    ->toArray();
            });
        }

        // Avec filtrage par localisation — on cherche les affectations utilisées
        // par des emplacements dans la localisation sélectionnée
        $cacheKey = 'affectation_options_loc_' . $idLocalisation;
        
        return Cache::remember($cacheKey, $cacheDuration, function () use ($idLocalisation) {
            return Affectation::select('idAffectation', 'Affectation', 'CodeAffectation')
                ->whereHas('emplacements', function ($query) use ($idLocalisation) {
                    $query->where('idLocalisation', $idLocalisation);
                })
                ->orderBy('Affectation')
                ->get()
                ->map(function ($affectation) {
                    return [
                        'value' => (string)$affectation->idAffectation,
                        'text' => ($affectation->CodeAffectation ? $affectation->CodeAffectation . ' - ' : '') . $affectation->Affectation,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Retourne les options d'emplacements filtrés par localisation et/ou affectation avec cache
     * 
     * @param int|null $idLocalisation ID de la localisation pour filtrer
     * @param int|null $idAffectation ID de l'affectation pour filtrer
     * @param int $cacheDuration Durée du cache en secondes
     * @param bool $withDetails Inclure les détails (localisation, affectation) dans le texte
     * @return array
     */
    protected function getCachedEmplacementOptions(
        ?int $idLocalisation = null,
        ?int $idAffectation = null,
        int $cacheDuration = 300,
        bool $withDetails = false
    ): array {
        // Si aucun filtre, retourner tous les emplacements
        if (empty($idLocalisation) && empty($idAffectation)) {
            $cacheKey = $withDetails ? 'emplacement_options_all_detailed' : 'emplacement_options_all';
            
            return Cache::remember($cacheKey, $cacheDuration, function () use ($withDetails) {
                $query = Emplacement::select('idEmplacement', 'Emplacement', 'CodeEmplacement', 'idLocalisation', 'idAffectation');
                
                if ($withDetails) {
                    $query->with(['localisation:idLocalisation,Localisation,CodeLocalisation', 'affectation:idAffectation,Affectation']);
                }
                
                return $query->orderBy('Emplacement')
                    ->get()
                    ->map(function ($emplacement) use ($withDetails) {
                        $text = ($emplacement->CodeEmplacement ? $emplacement->CodeEmplacement . ' - ' : '') . $emplacement->Emplacement;
                        
                        if ($withDetails && $emplacement->localisation) {
                            $text = $emplacement->localisation->Localisation . ' > ' . 
                                   ($emplacement->affectation ? $emplacement->affectation->Affectation . ' > ' : '') . 
                                   $text;
                        }
                        
                        return [
                            'value' => (string)$emplacement->idEmplacement,
                            'text' => $text,
                        ];
                    })
                    ->toArray();
            });
        }

        // Avec filtrage
        $cacheKey = 'emplacement_options_' . ($idLocalisation ?? 'none') . '_' . ($idAffectation ?? 'none');
        if ($withDetails) {
            $cacheKey .= '_detailed';
        }
        
        return Cache::remember($cacheKey, $cacheDuration, function () use ($idLocalisation, $idAffectation, $withDetails) {
            $query = Emplacement::select('idEmplacement', 'Emplacement', 'CodeEmplacement', 'idLocalisation', 'idAffectation');
            
            if (!empty($idLocalisation)) {
                $query->where('idLocalisation', $idLocalisation);
            }
            
            if (!empty($idAffectation)) {
                $query->where('idAffectation', $idAffectation);
            }
            
            if ($withDetails) {
                $query->with(['localisation:idLocalisation,Localisation,CodeLocalisation', 'affectation:idAffectation,Affectation']);
            }
            
            return $query->orderBy('Emplacement')
                ->get()
                ->map(function ($emplacement) use ($withDetails) {
                    $text = ($emplacement->CodeEmplacement ? $emplacement->CodeEmplacement . ' - ' : '') . $emplacement->Emplacement;
                    
                    if ($withDetails && $emplacement->localisation) {
                        $text = $emplacement->localisation->Localisation . ' > ' . 
                               ($emplacement->affectation ? $emplacement->affectation->Affectation . ' > ' : '') . 
                               $text;
                    }
                    
                    return [
                        'value' => (string)$emplacement->idEmplacement,
                        'text' => $text,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Invalide le cache pour les localisations
     */
    protected function invalidateLocalisationCache(): void
    {
        Cache::forget('localisation_options_all');
    }

    /**
     * Invalide le cache pour les affectations
     * 
     * @param int|null $idLocalisation Si fourni, invalide seulement le cache pour cette localisation
     */
    protected function invalidateAffectationCache(?int $idLocalisation = null): void
    {
        if ($idLocalisation) {
            Cache::forget('affectation_options_loc_' . $idLocalisation);
        } else {
            // Invalider le cache global
            Cache::forget('affectation_options_all');
            
            // Note: Pour invalider tous les caches par localisation, il faudrait les lister
            // ou utiliser des tags de cache (disponible avec Redis)
        }
    }

    /**
     * Invalide le cache pour les emplacements
     * 
     * @param int|null $idLocalisation
     * @param int|null $idAffectation
     */
    protected function invalidateEmplacementCache(?int $idLocalisation = null, ?int $idAffectation = null): void
    {
        $cacheKey = 'emplacement_options_' . ($idLocalisation ?? 'none') . '_' . ($idAffectation ?? 'none');
        Cache::forget($cacheKey);
        Cache::forget($cacheKey . '_detailed');
        Cache::forget('emplacement_options_all');
        Cache::forget('emplacement_options_all_detailed');
    }
}
