<?php

namespace App\Services;

use App\Models\Emplacement;
use App\Models\Gesimmo;
use App\Models\Inventaire;
use App\Models\InventaireScan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Service dédié à la génération des rapports d'inventaire
 * Centralise la logique de génération des rapports PDF et Excel
 */
class RapportService
{
    /**
     * Générer un rapport PDF complet d'un inventaire
     * 
     * @param Inventaire $inventaire
     * @return string Chemin du fichier PDF généré
     */
    /**
     * Préparer les données pour le rapport PDF
     */
    protected function preparerDonneesRapport(Inventaire $inventaire): array
    {
        $inventaire->load([
            'creator',
            'closer',
            'inventaireLocalisations.localisation',
            'inventaireLocalisations.agent',
            'inventaireScans.bien.designation',
            'inventaireScans.bien.emplacement.localisation',
            'inventaireScans.localisationReelle',
            'inventaireScans.agent'
        ]);

        $inventaireService = app(InventaireService::class);
        $statistiques = $inventaireService->calculerStatistiques($inventaire);
        $anomalies = $inventaireService->detecterAnomalies($inventaire);

        return [
            'inventaire' => $inventaire,
            'statistiques' => $statistiques,
            'anomalies' => $anomalies,
            'detailParEmplacement' => $this->getDetailParEmplacement($inventaire),
            'biensPresents' => $this->getBiensPresents($inventaire),
            'biensDeplaces' => $this->getBiensDeplaces($inventaire),
            'biensAbsents' => $this->getBiensAbsents($inventaire),
            'biensNonScannes' => $this->getBiensNonScannes($inventaire),
            'performanceLocalisations' => $this->getPerformanceLocalisations($inventaire),
            'performanceAgents' => $this->getPerformanceAgents($inventaire),
            'mouvements' => $this->getAnalyseMouvements($inventaire),
            'recommendations' => $this->genererRecommandations($inventaire, $statistiques, $anomalies),
        ];
    }

    /**
     * Rapprochement détaillé par emplacement (PDF et vue rapport)
     * Pour chaque emplacement :
     *  - Biens attendus : trouvés ou manquants
     *  - Biens déplacés : trouvés ici mais enregistrés dans un autre emplacement
     */
    public function getDetailParEmplacement(Inventaire $inventaire): array
    {
        $localisationIds = $inventaire->inventaireLocalisations()->pluck('localisation_id')->unique()->toArray();
        $emplacements = Emplacement::whereIn('idLocalisation', $localisationIds)
            ->with(['localisation', 'affectation', 'immobilisations.designation', 'immobilisations.categorie'])
            ->orderBy('CodeEmplacement')
            ->get();

        // Tous les scans de cet inventaire, indexés par bien_id
        $allScans = $inventaire->inventaireScans()
            ->with(['gesimmo.designation', 'gesimmo.categorie', 'gesimmo.emplacement', 'localisationReelle'])
            ->get();

        $scansByBienId = $allScans->keyBy('bien_id');

        // Scans déplacés : biens scannés dans un emplacement différent de leur emplacement d'origine
        // Groupés par l'emplacement réel (via localisation_reelle_id → emplacements de cette localisation)
        $scansDeplacesParEmplacement = [];
        foreach ($allScans as $scan) {
            if ($scan->statut_scan === 'deplace' && $scan->gesimmo) {
                // L'emplacement où le bien a été trouvé : on le déduit de la localisation réelle
                // On cherche dans quel emplacement scanné ce bien déplacé a été trouvé
                // Le scan a été créé lors du terminerScanEmplacement d'un emplacement spécifique
                // On utilise le fait que le bien n'est PAS dans son emplacement d'origine
                $emplacementOrigine = $scan->gesimmo->idEmplacement;
                // Pour retrouver dans quel emplacement il a été scanné, on regarde la localisation_reelle
                // et on itère les emplacements pour trouver celui qui contient ce scan
                foreach ($emplacements as $emp) {
                    if ($emp->idLocalisation == $scan->localisation_reelle_id && $emp->idEmplacement != $emplacementOrigine) {
                        $scansDeplacesParEmplacement[$emp->idEmplacement][] = $scan;
                        break;
                    }
                }
            }
        }

        $result = [];
        foreach ($emplacements as $emplacement) {
            $lignesAttendues = [];
            $lignesDeplaces = [];
            $totalTrouves = 0;
            $totalManquants = 0;

            // Biens attendus dans cet emplacement
            foreach ($emplacement->immobilisations as $gesimmo) {
                $scan = $scansByBienId->get($gesimmo->NumOrdre);
                $trouve = $scan && in_array($scan->statut_scan, ['present', 'deplace']) ? true : false;
                $estPresent = $scan && $scan->statut_scan === 'present';
                $etat = $scan ? $this->labelEtat($scan->etat_constate) : '-';

                if ($estPresent) {
                    $observation = 'Présent';
                    $totalTrouves++;
                } elseif ($scan && $scan->statut_scan === 'deplace') {
                    $observation = 'Déplacé';
                    $totalManquants++;
                } elseif ($scan && $scan->statut_scan === 'absent') {
                    $observation = 'Absent';
                    $totalManquants++;
                } else {
                    $observation = 'Non scanné';
                    $totalManquants++;
                }

                $lignesAttendues[] = [
                    'num_ordre' => $gesimmo->NumOrdre,
                    'code' => 'GS' . $gesimmo->NumOrdre,
                    'designation' => $gesimmo->designation->designation ?? 'N/A',
                    'categorie' => $gesimmo->categorie->Categorie ?? '-',
                    'attendu' => 'Oui',
                    'trouve' => $estPresent ? 'Oui' : 'Non',
                    'etat' => $etat,
                    'observation' => $observation,
                    'statut_scan' => $scan?->statut_scan,
                ];
            }

            // Biens déplacés trouvés dans cet emplacement (venant d'ailleurs)
            $deplacesTrouves = $scansDeplacesParEmplacement[$emplacement->idEmplacement] ?? [];
            foreach ($deplacesTrouves as $scan) {
                $empOrigine = $scan->gesimmo->emplacement;
                $origineNom = $empOrigine ? ($empOrigine->Emplacement ?? $empOrigine->CodeEmplacement ?? 'N/A') : 'Inconnu';
                $etat = $this->labelEtat($scan->etat_constate);

                $lignesDeplaces[] = [
                    'num_ordre' => $scan->bien_id,
                    'code' => 'GS' . $scan->bien_id,
                    'designation' => $scan->gesimmo->designation->designation ?? 'N/A',
                    'categorie' => $scan->gesimmo->categorie->Categorie ?? '-',
                    'attendu' => 'Non',
                    'trouve' => 'Oui',
                    'etat' => $etat,
                    'observation' => 'Déplacé de: ' . $origineNom,
                    'statut_scan' => 'deplace',
                ];
            }

            $totalAttendus = count($lignesAttendues);
            $tauxConformite = $totalAttendus > 0 ? round(($totalTrouves / $totalAttendus) * 100, 1) : 0;

            $result[] = [
                'emplacement_id' => $emplacement->idEmplacement,
                'code' => $emplacement->CodeEmplacement ?? $emplacement->Emplacement ?? 'N/A',
                'designation' => $emplacement->Emplacement ?? $emplacement->CodeEmplacement ?? 'N/A',
                'localisation' => $emplacement->localisation?->Localisation ?? $emplacement->localisation?->CodeLocalisation ?? 'N/A',
                'affectation' => $emplacement->affectation?->Affectation ?? '-',
                'total_attendus' => $totalAttendus,
                'total_trouves' => $totalTrouves,
                'total_manquants' => $totalManquants,
                'total_deplaces_ici' => count($lignesDeplaces),
                'taux_conformite' => $tauxConformite,
                'lignes_attendues' => $lignesAttendues,
                'lignes_deplaces' => $lignesDeplaces,
            ];
        }

        return $result;
    }

    protected function labelConformite(?string $statut): string
    {
        return match ($statut) {
            'present' => 'Présent',
            'absent' => 'Absent',
            'deplace' => 'Déplacé',
            'deteriore' => 'Détérioré',
            default => $statut ?? 'Non scanné',
        };
    }

    protected function labelEtat(?string $etat): string
    {
        return match ($etat) {
            'bon_etat' => 'Bon Etat',
            'neuf' => 'Neuf',
            'defectueux' => 'Defectueux',
            default => '-',
        };
    }

    public function genererRapportPDF(Inventaire $inventaire)
    {
        $data = $this->preparerDonneesRapport($inventaire);

        $pdf = Pdf::loadView('pdf.rapport-inventaire', $data);
        
        // Configuration PDF
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        // Nom du fichier
        $filename = 'rapport_inventaire_' . $inventaire->annee . '_' . now()->format('YmdHis') . '.pdf';
        $dir = 'rapports/' . $inventaire->annee;
        $path = $dir . '/' . $filename;

        // Créer le répertoire si nécessaire
        if (!Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->makeDirectory($dir);
        }

        // Sauvegarder
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Générer et retourner le PDF en flux direct (sans sauvegarde disque)
     * Évite les problèmes de chemin ou permissions sur le stockage
     *
     * @param Inventaire $inventaire
     * @return \Illuminate\Http\Response
     */
    public function streamRapportPDF(Inventaire $inventaire)
    {
        $data = $this->preparerDonneesRapport($inventaire);
        $pdf = Pdf::loadView('pdf.rapport-inventaire', $data);
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'rapport_inventaire_' . $inventaire->annee . '_' . now()->format('Y-m-d_His') . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Générer un rapport Excel complet d'un inventaire
     * 
     * @param Inventaire $inventaire
     * @return string Chemin du fichier Excel généré
     */
    public function genererRapportExcel(Inventaire $inventaire)
    {
        // Cette méthode utilisera Laravel Excel (Maatwebsite)
        // Sera implémentée avec les exports détaillés
        
        $filename = 'rapport_inventaire_' . $inventaire->annee . '_' . now()->format('YmdHis') . '.xlsx';
        $path = 'rapports/' . $inventaire->annee . '/' . $filename;

        // TODO: Implémenter avec Laravel Excel
        // Pour l'instant, retourner le chemin
        
        return $path;
    }

    /**
     * Récupérer les biens présents conformes
     * 
     * @param Inventaire $inventaire
     * @return \Illuminate\Support\Collection
     */
    public function getBiensPresents(Inventaire $inventaire)
    {
        return InventaireScan::where('inventaire_id', $inventaire->id)
            ->where('statut_scan', 'present')
            ->with(['bien.designation', 'bien.categorie', 'bien.emplacement.localisation', 'localisationReelle', 'agent'])
            ->get()
            ->map(function ($scan) {
                $locPrevue = $scan->bien?->emplacement?->idLocalisation;
                $isConforme = $locPrevue ? ($locPrevue === $scan->localisation_reelle_id) : true;
                return [
                    'code' => $scan->code_inventaire,
                    'designation' => $scan->designation,
                    'nature' => $scan->categorie,
                    'localisation' => $scan->localisationReelle?->CodeLocalisation ?? $scan->localisationReelle?->Localisation ?? $scan->localisation_code,
                    'service' => null,
                    'valeur' => 0,
                    'etat' => $scan->etat_constate,
                    'date_scan' => $scan->date_scan,
                    'agent' => $scan->agent->users ?? null,
                    'conforme' => $isConforme,
                ];
            });
    }

    /**
     * Récupérer les biens déplacés
     * 
     * @param Inventaire $inventaire
     * @return \Illuminate\Support\Collection
     */
    public function getBiensDeplaces(Inventaire $inventaire)
    {
        return InventaireScan::where('inventaire_id', $inventaire->id)
            ->where('statut_scan', 'deplace')
            ->with(['bien.emplacement.localisation', 'bien.designation', 'bien.categorie', 'localisationReelle', 'agent'])
            ->get()
            ->map(function ($scan) {
                $locPrevue = $scan->bien?->emplacement?->localisation?->CodeLocalisation ?? $scan->localisation_code;
                $locReelle = $scan->localisationReelle?->CodeLocalisation ?? $scan->localisationReelle?->Localisation ?? null;
                return [
                    'code' => $scan->code_inventaire,
                    'designation' => $scan->designation,
                    'nature' => $scan->categorie,
                    'localisation_prevue' => $locPrevue,
                    'localisation_reelle' => $locReelle,
                    'service' => null,
                    'valeur' => 0,
                    'date_scan' => $scan->date_scan,
                    'agent' => $scan->agent->users ?? null,
                    'commentaire' => $scan->commentaire,
                ];
            });
    }

    /**
     * Récupérer les biens absents
     * 
     * @param Inventaire $inventaire
     * @return \Illuminate\Support\Collection
     */
    public function getBiensAbsents(Inventaire $inventaire)
    {
        return InventaireScan::where('inventaire_id', $inventaire->id)
            ->where('statut_scan', 'absent')
            ->with(['bien.emplacement.localisation', 'bien.designation', 'bien.categorie', 'agent'])
            ->get()
            ->map(function ($scan) {
                return [
                    'code' => $scan->code_inventaire,
                    'designation' => $scan->designation,
                    'nature' => $scan->categorie,
                    'localisation' => $scan->localisation_code,
                    'service' => null,
                    'valeur' => 0,
                    'date_acquisition' => $scan->bien?->DateAcquisition,
                    'date_scan' => $scan->date_scan,
                    'agent' => $scan->agent->users ?? null,
                    'commentaire' => $scan->commentaire,
                ];
            });
    }

    /**
     * Récupérer les biens non scannés
     * 
     * @param Inventaire $inventaire
     * @return \Illuminate\Support\Collection
     */
    public function getBiensNonScannes(Inventaire $inventaire)
    {
        // Récupérer tous les NumOrdre de biens scannés
        $biensScannésIds = InventaireScan::where('inventaire_id', $inventaire->id)
            ->pluck('bien_id');

        // Récupérer les IDs de localisations inventoriées
        $localisationsIds = $inventaire->inventaireLocalisations()
            ->pluck('localisation_id');

        // Récupérer les emplacements de ces localisations
        $emplacementIds = Emplacement::whereIn('idLocalisation', $localisationsIds)
            ->pluck('idEmplacement');

        // Biens attendus mais non scannés (via gesimmo → emplacement → localisation)
        return Gesimmo::whereIn('idEmplacement', $emplacementIds)
            ->whereNotIn('NumOrdre', $biensScannésIds)
            ->with(['designation', 'categorie', 'emplacement.localisation'])
            ->get()
            ->map(function ($bien) {
                return [
                    'code' => 'GS' . $bien->NumOrdre,
                    'designation' => $bien->designation->designation ?? 'N/A',
                    'nature' => $bien->categorie?->Categorie,
                    'localisation' => $bien->emplacement?->localisation?->CodeLocalisation,
                    'service' => null,
                    'valeur' => 0,
                ];
            });
    }

    /**
     * Analyser la performance par localisation
     * 
     * @param Inventaire $inventaire
     * @return \Illuminate\Support\Collection
     */
    public function getPerformanceLocalisations(Inventaire $inventaire)
    {
        return $inventaire->inventaireLocalisations
            ->map(function ($invLoc) {
                $scans = $invLoc->inventaireScans;
                $presents = $scans->where('statut_scan', 'present')->count();
                $deplaces = $scans->where('statut_scan', 'deplace')->count();
                $absents = $scans->where('statut_scan', 'absent')->count();
                
                $tauxConformite = $invLoc->nombre_biens_attendus > 0
                    ? round(($presents / $invLoc->nombre_biens_attendus) * 100, 2)
                    : 0;

                $duree = null;
                if ($invLoc->date_debut_scan && $invLoc->date_fin_scan) {
                    $duree = $invLoc->date_debut_scan->diffInMinutes($invLoc->date_fin_scan);
                }

                return [
                    'code' => $invLoc->localisation->code ?? null,
                    'designation' => $invLoc->localisation->designation ?? null,
                    'attendus' => $invLoc->nombre_biens_attendus,
                    'scannes' => $invLoc->nombre_biens_scannes,
                    'presents' => $presents,
                    'deplaces' => $deplaces,
                    'absents' => $absents,
                    'taux_conformite' => $tauxConformite,
                    'duree_minutes' => $duree,
                    'agent' => $invLoc->agent ? $invLoc->agent->name : 'Non assigné',
                    'statut' => $invLoc->statut,
                ];
            })
            ->sortBy('taux_conformite');
    }

    /**
     * Analyser la performance par agent
     * 
     * @param Inventaire $inventaire
     * @return \Illuminate\Support\Collection
     */
    public function getPerformanceAgents(Inventaire $inventaire)
    {
        $scansParAgent = InventaireScan::where('inventaire_id', $inventaire->id)
            ->with('agent')
            ->get()
            ->groupBy('user_id');

        $localisationsParAgent = $inventaire->inventaireLocalisations()
            ->with('agent')
            ->get()
            ->groupBy('user_id');

        $performance = [];

        foreach ($scansParAgent as $userId => $scans) {
            $agent = $scans->first()->agent;
            
            if (!$agent) {
                continue;
            }

            $localisations = $localisationsParAgent->get($userId, collect());

            $dureeTotale = $localisations->reduce(function ($total, $invLoc) {
                if ($invLoc->date_debut_scan && $invLoc->date_fin_scan) {
                    return $total + $invLoc->date_debut_scan->diffInMinutes($invLoc->date_fin_scan);
                }
                return $total;
            }, 0);

            $performance[] = [
                'agent' => $agent->name,
                'localisations' => $localisations->count(),
                'localisations_terminees' => $localisations->where('statut', 'termine')->count(),
                'biens_scannes' => $scans->count(),
                'duree_totale_minutes' => $dureeTotale,
                'moyenne_par_localisation' => $localisations->count() > 0 
                    ? round($dureeTotale / $localisations->count(), 0) 
                    : 0,
                'moyenne_par_bien' => $scans->count() > 0 
                    ? round($dureeTotale / $scans->count(), 2) 
                    : 0,
            ];
        }

        return collect($performance)->sortByDesc('biens_scannes');
    }

    /**
     * Analyser les mouvements de biens
     * 
     * @param Inventaire $inventaire
     * @return array
     */
    public function getAnalyseMouvements(Inventaire $inventaire)
    {
        $biensDeplaces = $this->getBiensDeplaces($inventaire);

        // Flux : Localisation A → Localisation B (nombre de biens)
        $flux = [];
        foreach ($biensDeplaces as $bien) {
            $key = $bien['localisation_prevue'] . ' → ' . $bien['localisation_reelle'];
            
            if (!isset($flux[$key])) {
                $flux[$key] = [
                    'origine' => $bien['localisation_prevue'],
                    'destination' => $bien['localisation_reelle'],
                    'nombre_biens' => 0,
                    'valeur_totale' => 0,
                ];
            }
            
            $flux[$key]['nombre_biens']++;
            $flux[$key]['valeur_totale'] += $bien['valeur'];
        }

        return [
            'total_deplaces' => $biensDeplaces->count(),
            'flux' => collect($flux)->sortByDesc('nombre_biens')->values(),
        ];
    }

    /**
     * Générer des recommandations basées sur l'analyse
     * 
     * @param Inventaire $inventaire
     * @param array $statistiques
     * @param array $anomalies
     * @return array
     */
    private function genererRecommandations(Inventaire $inventaire, array $statistiques, array $anomalies)
    {
        $recommendations = [
            'corrections_immediates' => [],
            'ameliorations_organisationnelles' => [],
            'prochain_inventaire' => [],
        ];

        // Corrections immédiates
        if ($statistiques['biens_deplaces'] > 0) {
            $recommendations['corrections_immediates'][] = 
                "Mettre à jour les localisations de {$statistiques['biens_deplaces']} bien(s) déplacé(s) dans le système.";
        }

        if ($statistiques['biens_absents'] > 0) {
            $recommendations['corrections_immediates'][] = 
                "Investiguer {$statistiques['biens_absents']} bien(s) absent(s).";
        }

        if (count($anomalies['biens_deteriores'] ?? []) > 0) {
            $recommendations['corrections_immediates'][] = 
                "Planifier la réparation ou le remplacement de " . 
                count($anomalies['biens_deteriores']) . " bien(s) détérioré(s).";
        }

        // Améliorations organisationnelles
        $localisationsProblematiques = $this->getPerformanceLocalisations($inventaire)
            ->where('taux_conformite', '<', 80);

        if ($localisationsProblematiques->count() > 0) {
            $recommendations['ameliorations_organisationnelles'][] = 
                "Améliorer la gestion de " . $localisationsProblematiques->count() . 
                " localisation(s) avec un taux de conformité inférieur à 80%.";
        }

        $mouvements = $this->getAnalyseMouvements($inventaire);
        if (count($mouvements['flux']) > 5) {
            $recommendations['ameliorations_organisationnelles'][] = 
                "Analyser les flux de déplacement fréquents et optimiser l'organisation spatiale.";
        }

        // Prochain inventaire
        $duree = $inventaire->date_debut->diffInDays($inventaire->date_fin ?? now());
        $recommendations['prochain_inventaire'][] = 
            "Prévoir environ {$duree} jours pour le prochain inventaire basé sur cette expérience.";

        $nbAgents = $inventaire->inventaireLocalisations()
            ->distinct('user_id')
            ->count('user_id');
        
        $recommendations['prochain_inventaire'][] = 
            "Mobiliser au minimum {$nbAgents} agent(s) pour maintenir l'efficacité.";

        if ($statistiques['taux_conformite'] < 90) {
            $recommendations['prochain_inventaire'][] = 
                "Renforcer la formation des agents sur les procédures de scan et d'identification.";
        }

        return $recommendations;
    }

    /**
     * Générer un rapport par localisation (PDF)
     * 
     * @param Inventaire $inventaire
     * @param int $localisationId
     * @return string
     */
    public function genererRapportLocalisation(Inventaire $inventaire, $localisationId)
    {
        $inventaireLocalisation = $inventaire->inventaireLocalisations()
            ->where('localisation_id', $localisationId)
            ->with(['localisation', 'agent', 'inventaireScans.bien.designation'])
            ->firstOrFail();

        $data = [
            'inventaire' => $inventaire,
            'inventaireLocalisation' => $inventaireLocalisation,
        ];

        $pdf = Pdf::loadView('pdf.rapport-localisation', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'rapport_' . $inventaireLocalisation->localisation->code . '_' . 
                    $inventaire->annee . '_' . now()->format('YmdHis') . '.pdf';
        $path = 'rapports/' . $inventaire->annee . '/localisations/' . $filename;

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Générer un export Excel des biens par statut
     * 
     * @param Inventaire $inventaire
     * @param string $statut
     * @return string
     */
    public function exportBiensParStatut(Inventaire $inventaire, $statut)
    {
        // Utiliser Laravel Excel pour export
        // TODO: Implémenter avec Maatwebsite\Excel
        
        $filename = "biens_{$statut}_{$inventaire->annee}.xlsx";
        $path = 'exports/' . $inventaire->annee . '/' . $filename;

        return $path;
    }
}

