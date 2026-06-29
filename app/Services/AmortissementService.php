<?php

namespace App\Services;

use App\Models\Gesimmo;
use Carbon\Carbon;

class AmortissementService
{
    const SEUIL_AMORTISSEMENT = 50000;

    /**
     * Vérifie si un bien est amortissable selon le CGI Mauritanie 2024.
     * Conditions : valeur >= 50 000 MRU, catégorie avec durée définie, date de mise en service renseignée.
     */
    public function isAmortissable(Gesimmo $bien): bool
    {
        if (!$bien->valeur_acquisition || $bien->valeur_acquisition < self::SEUIL_AMORTISSEMENT) {
            return false;
        }

        if (!$bien->date_mise_en_service) {
            return false;
        }

        $bien->loadMissing('categorie');

        if (!$bien->categorie || !$bien->categorie->duree_amortissement || !$bien->categorie->taux_amortissement) {
            return false;
        }

        return true;
    }

    /**
     * Retourne la raison pour laquelle un bien n'est pas amortissable, ou null s'il l'est.
     */
    public function raisonNonAmortissable(Gesimmo $bien): ?string
    {
        if (!$bien->valeur_acquisition) {
            return 'Valeur d\'acquisition non renseignée.';
        }

        if ($bien->valeur_acquisition < self::SEUIL_AMORTISSEMENT) {
            return 'Valeur inférieure au seuil de ' . number_format(self::SEUIL_AMORTISSEMENT, 0, ',', ' ') . ' MRU (comptabilisé en charge).';
        }

        if (!$bien->date_mise_en_service) {
            return 'Date de mise en service non renseignée.';
        }

        $bien->loadMissing('categorie');

        if (!$bien->categorie || !$bien->categorie->duree_amortissement) {
            return 'Durée d\'amortissement non définie pour cette catégorie.';
        }

        return null;
    }

    /**
     * Génère le tableau complet d'amortissement linéaire avec prorata temporis.
     *
     * @return array{lignes: array, totaux: array}|null
     */
    public function calculerTableau(Gesimmo $bien): ?array
    {
        if (!$this->isAmortissable($bien)) {
            return null;
        }

        $valeur = (float) $bien->valeur_acquisition;
        $dateMiseEnService = Carbon::parse($bien->date_mise_en_service);
        $duree = (int) $bien->categorie->duree_amortissement;
        $taux = (float) $bien->categorie->taux_amortissement;

        $dotationAnnuelle = round($valeur * $taux / 100, 2);

        // Prorata temporis pour l'année 1
        $finExercice1 = Carbon::create($dateMiseEnService->year, 12, 31);
        $joursUtilisation = $dateMiseEnService->diffInDays($finExercice1) + 1;
        $prorata = $joursUtilisation / 365;
        $dotationAnnee1 = round($dotationAnnuelle * $prorata, 2);

        $lignes = [];
        $cumulAmortissement = 0;
        $annee = $dateMiseEnService->year;

        // Année 1 (prorata)
        $cumulAmortissement += $dotationAnnee1;
        $vnc = round($valeur - $cumulAmortissement, 2);
        $lignes[] = [
            'exercice' => $annee,
            'valeur_amortissable' => $valeur,
            'taux' => $taux,
            'dotation' => $dotationAnnee1,
            'cumul' => round($cumulAmortissement, 2),
            'vnc' => $vnc,
            'prorata' => $prorata < 1 ? round($prorata * 100, 1) . '%' : null,
        ];

        // Années pleines (année 2 à N-1 si prorata, ou année 2 à N si pas de prorata)
        $nbAnneesRestantes = $duree - 1;
        $hasProrata = $prorata < 1;

        for ($i = 0; $i < $nbAnneesRestantes; $i++) {
            $annee++;
            $dotation = $dotationAnnuelle;

            // S'assurer que le cumul ne dépasse pas la valeur
            if ($cumulAmortissement + $dotation > $valeur) {
                $dotation = round($valeur - $cumulAmortissement, 2);
            }

            if ($dotation <= 0) {
                break;
            }

            $cumulAmortissement += $dotation;
            $vnc = round($valeur - $cumulAmortissement, 2);

            $lignes[] = [
                'exercice' => $annee,
                'valeur_amortissable' => $valeur,
                'taux' => $taux,
                'dotation' => $dotation,
                'cumul' => round($cumulAmortissement, 2),
                'vnc' => $vnc,
                'prorata' => null,
            ];
        }

        // Année complémentaire si prorata (pour le complément)
        if ($hasProrata && $cumulAmortissement < $valeur) {
            $annee++;
            $complement = round($valeur - $cumulAmortissement, 2);

            if ($complement > 0) {
                $cumulAmortissement += $complement;
                $lignes[] = [
                    'exercice' => $annee,
                    'valeur_amortissable' => $valeur,
                    'taux' => $taux,
                    'dotation' => $complement,
                    'cumul' => round($cumulAmortissement, 2),
                    'vnc' => 0,
                    'prorata' => 'Complément',
                ];
            }
        }

        return [
            'lignes' => $lignes,
            'totaux' => [
                'valeur_acquisition' => $valeur,
                'total_amortissement' => round($cumulAmortissement, 2),
                'dotation_annuelle' => $dotationAnnuelle,
                'duree' => $duree,
                'taux' => $taux,
            ],
        ];
    }

    /**
     * Calcule la VNC (Valeur Nette Comptable) à la date du jour.
     */
    public function calculerVNC(Gesimmo $bien): ?float
    {
        $tableau = $this->calculerTableau($bien);
        if (!$tableau) {
            return null;
        }

        $exerciceActuel = now()->year;

        foreach ($tableau['lignes'] as $ligne) {
            if ($ligne['exercice'] >= $exerciceActuel) {
                return $ligne['vnc'];
            }
        }

        // Bien totalement amorti
        $derniereLigne = end($tableau['lignes']);
        return $derniereLigne['vnc'];
    }

    /**
     * Retourne un résumé de l'amortissement pour l'affichage.
     */
    public function calculerResume(Gesimmo $bien): ?array
    {
        if (!$this->isAmortissable($bien)) {
            return null;
        }

        $tableau = $this->calculerTableau($bien);
        if (!$tableau) {
            return null;
        }

        $valeur = (float) $bien->valeur_acquisition;
        $vnc = $this->calculerVNC($bien);
        $amortissementCumule = $valeur - $vnc;
        $pourcentageAmorti = $valeur > 0 ? round(($amortissementCumule / $valeur) * 100, 1) : 0;

        $dateMiseEnService = Carbon::parse($bien->date_mise_en_service);
        $duree = (int) $bien->categorie->duree_amortissement;
        $dateFin = $dateMiseEnService->copy()->addYears($duree);
        $anneesRestantes = max(0, $dateFin->year - now()->year);

        $estTotalementAmorti = $vnc <= 0;

        return [
            'valeur_acquisition' => $valeur,
            'dotation_annuelle' => $tableau['totaux']['dotation_annuelle'],
            'amortissement_cumule' => round($amortissementCumule, 2),
            'vnc' => $vnc,
            'pourcentage_amorti' => $pourcentageAmorti,
            'duree' => $duree,
            'taux' => $tableau['totaux']['taux'],
            'annees_restantes' => $anneesRestantes,
            'est_totalement_amorti' => $estTotalementAmorti,
            'date_debut' => $dateMiseEnService->format('d/m/Y'),
            'date_fin' => $dateFin->format('d/m/Y'),
            'type_cgi' => $bien->categorie->type_cgi ?? '',
        ];
    }
}
