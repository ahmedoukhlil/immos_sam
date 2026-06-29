<?php

namespace App\Services;

use App\Models\Inventaire;
use App\Models\InventaireLocalisation;
use App\Models\InventaireScan;
use App\Models\LocalisationImmo;
use App\Models\Emplacement;
use App\Models\Gesimmo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * Service pour centraliser la logique métier des inventaires
 * 
 * Ce service gère toutes les opérations liées aux inventaires :
 * - Création et démarrage d'inventaires
 * - Gestion des localisations et scans
 * - Calcul de statistiques
 * - Détection d'anomalies
 * - Génération de rapports
 */
class InventaireService
{
    /**
     * Crée un nouvel inventaire avec ses localisations
     * 
     * @param array $data Données de l'inventaire
     *   - 'annee' : Année de l'inventaire
     *   - 'date_debut' : Date de début
     *   - 'observation' : Observation optionnelle
     *   - 'localisations' : Array d'IDs de localisations
     *   - 'assignations' : Array [localisation_id => user_id]
     * 
     * @return Inventaire L'inventaire créé
     * @throws Exception Si un inventaire est déjà en cours ou en préparation
     */
    public function creerInventaire(array $data): Inventaire
    {
        return DB::transaction(function () use ($data) {
            // Vérifier qu'aucun inventaire en_cours ou en_preparation n'existe
            $inventaireExistant = Inventaire::whereIn('statut', ['en_cours', 'en_preparation'])->first();
            
            if ($inventaireExistant) {
                throw new Exception("Un inventaire est déjà en cours ou en préparation pour l'année {$inventaireExistant->annee}.");
            }

            // Créer l'inventaire avec statut 'en_preparation'
            $inventaire = Inventaire::create([
                'annee' => $data['annee'],
                'date_debut' => $data['date_debut'],
                'statut' => 'en_preparation',
                'created_by' => Auth::id(),
                'observation' => $data['observation'] ?? null,
            ]);

            // Pour chaque localisation, créer InventaireLocalisation
            foreach ($data['localisations'] as $localisationId) {
                $localisation = LocalisationImmo::find($localisationId);
                
                if (!$localisation) {
                    throw new Exception("Localisation {$localisationId} introuvable.");
                }

                // Calculer nombre_biens_attendus via les emplacements → gesimmo
                $nombreBiensAttendus = Gesimmo::whereHas('emplacement', fn ($q) => $q->where('idLocalisation', $localisationId))->count();
                
                // Récupérer l'agent assigné si fourni
                $userId = $data['assignations'][$localisationId] ?? null;

                InventaireLocalisation::create([
                    'inventaire_id' => $inventaire->id,
                    'localisation_id' => $localisationId,
                    'statut' => 'en_attente',
                    'user_id' => $userId,
                    'nombre_biens_attendus' => $nombreBiensAttendus,
                    'nombre_biens_scannes' => 0,
                ]);
            }

            Log::info("Inventaire créé", [
                'inventaire_id' => $inventaire->id,
                'annee' => $inventaire->annee,
                'localisations' => count($data['localisations']),
                'user_id' => Auth::id(),
            ]);

            return $inventaire->fresh(['inventaireLocalisations']);
        });
    }

    /**
     * Démarre un inventaire (passe de 'en_preparation' à 'en_cours')
     * 
     * @param Inventaire $inventaire L'inventaire à démarrer
     * @return bool True si succès
     * @throws Exception Si le statut n'est pas 'en_preparation' ou si aucune localisation n'est assignée
     */
    public function demarrerInventaire(Inventaire $inventaire): bool
    {
        return DB::transaction(function () use ($inventaire) {
            // Vérifier statut = 'en_preparation'
            if ($inventaire->statut !== 'en_preparation') {
                throw new Exception("Seuls les inventaires en préparation peuvent être démarrés. Statut actuel : {$inventaire->statut}");
            }

            // Vérifier qu'au moins une localisation est assignée
            $localisationsAssignees = $inventaire->inventaireLocalisations()
                ->whereNotNull('user_id')
                ->count();

            if ($localisationsAssignees === 0) {
                throw new Exception("Au moins une localisation doit être assignée à un agent avant de démarrer l'inventaire.");
            }

            // Changer statut à 'en_cours'
            $inventaire->demarrer();

            Log::info("Inventaire démarré", [
                'inventaire_id' => $inventaire->id,
                'annee' => $inventaire->annee,
                'user_id' => Auth::id(),
            ]);

            // Event : InventaireDemarre
            event(new \App\Events\InventaireDemarre($inventaire));

            return true;
        });
    }

    /**
     * Démarre le scan d'une localisation
     * 
     * @param InventaireLocalisation $invLoc La localisation à démarrer
     * @param User $user L'agent qui démarre le scan
     * @return bool True si succès
     * @throws Exception Si les conditions ne sont pas remplies
     */
    public function demarrerLocalisation(InventaireLocalisation $invLoc, User $user): bool
    {
        return DB::transaction(function () use ($invLoc, $user) {
            // Vérifier que inventaire parent est 'en_cours' ou 'en_preparation'
            if (!in_array($invLoc->inventaire->statut, ['en_cours', 'en_preparation'])) {
                throw new Exception("L'inventaire parent doit être en cours ou en préparation pour démarrer une localisation. Statut actuel : {$invLoc->inventaire->statut}");
            }

            // Vérifier que invLoc statut = 'en_attente'
            if ($invLoc->statut !== 'en_attente') {
                throw new Exception("Seules les localisations en attente peuvent être démarrées. Statut actuel : {$invLoc->statut}");
            }

            // Set statut = 'en_cours', date_debut_scan = now(), user_id = $user->idUser
            $invLoc->demarrer();
            $invLoc->update(['user_id' => $user->idUser]);

            Log::info("Localisation démarrée", [
                'inventaire_localisation_id' => $invLoc->id,
                'localisation_id' => $invLoc->localisation_id,
                'user_id' => $user->idUser,
            ]);

            return true;
        });
    }

    /**
     * Enregistre un scan d'inventaire
     * 
     * @param array $data Données du scan
     *   - 'inventaire_id' : ID de l'inventaire
     *   - 'inventaire_localisation_id' : ID de la localisation
     *   - 'bien_id' : ID du bien scanné
     *   - 'statut_scan' : Statut (present, deplace, absent, deteriore)
     *   - 'localisation_reelle_id' : ID de la localisation où le bien a été trouvé
     *   - 'etat_constate' : État constaté
     *   - 'commentaire' : Commentaire optionnel
     *   - 'photo_path' : Chemin de la photo optionnelle
     *   - 'user_id' : ID de l'agent qui a scanné
     * 
     * @return InventaireScan Le scan créé
     * @throws Exception Si les validations échouent
     */
    public function enregistrerScan(array $data): InventaireScan
    {
        return DB::transaction(function () use ($data) {
            // Validations — bien_id = NumOrdre dans la table gesimmo
            $bien = Gesimmo::find($data['bien_id']);
            if (!$bien) {
                throw new Exception("Bien (NumOrdre {$data['bien_id']}) introuvable dans gesimmo.");
            }

            // Vérifier que le bien n'a pas déjà été scanné dans cet inventaire
            $scanExistant = InventaireScan::where('inventaire_id', $data['inventaire_id'])
                ->where('bien_id', $data['bien_id'])
                ->first();

            if ($scanExistant) {
                throw new Exception("Le bien GS{$bien->NumOrdre} a déjà été scanné dans cet inventaire.");
            }

            // Vérifier que InventaireLocalisation est en_cours
            $invLoc = InventaireLocalisation::find($data['inventaire_localisation_id']);
            if (!$invLoc || $invLoc->statut !== 'en_cours') {
                throw new Exception("La localisation doit être en cours pour enregistrer un scan.");
            }

            // Créer InventaireScan avec date_scan = now()
            $scan = InventaireScan::create([
                'inventaire_id' => $data['inventaire_id'],
                'inventaire_localisation_id' => $data['inventaire_localisation_id'],
                'bien_id' => $data['bien_id'],
                'date_scan' => now(),
                'statut_scan' => $data['statut_scan'],
                'localisation_reelle_id' => $data['localisation_reelle_id'] ?? null,
                'etat_constate' => $data['etat_constate'] ?? null,
                'commentaire' => $data['commentaire'] ?? null,
                'photo_path' => $data['photo_path'] ?? null,
                'user_id' => $data['user_id'],
            ]);

            // Incrémenter nombre_biens_scannes dans InventaireLocalisation
            $invLoc->increment('nombre_biens_scannes');

            Log::info("Scan enregistré", [
                'scan_id' => $scan->id,
                'bien_id' => $scan->bien_id,
                'statut_scan' => $scan->statut_scan,
                'user_id' => $scan->user_id,
            ]);

            // Event : BienScanne
            event(new \App\Events\BienScanne($scan));

            return $scan->fresh(['bien', 'localisationReelle', 'agent']);
        });
    }

    /**
     * Termine le scan d'une localisation
     * 
     * @param InventaireLocalisation $invLoc La localisation à terminer
     * @param bool $marquerBiensAbsents Si true, marque les biens non scannés comme 'absent'
     * @return bool True si succès
     * @throws Exception Si le statut n'est pas 'en_cours'
     */
    public function terminerLocalisation(InventaireLocalisation $invLoc, bool $marquerBiensAbsents = false): bool
    {
        return DB::transaction(function () use ($invLoc, $marquerBiensAbsents) {
            // Vérifier statut = 'en_cours'
            if ($invLoc->statut !== 'en_cours') {
                throw new Exception("Seules les localisations en cours peuvent être terminées. Statut actuel : {$invLoc->statut}");
            }

            // Marquer les biens non scannés comme 'absent' si demandé
            if ($marquerBiensAbsents) {
                $biensScannes = $invLoc->inventaireScans()->pluck('bien_id')->toArray();
                $biensNonScannes = Gesimmo::whereHas('emplacement', fn ($q) => $q->where('idLocalisation', $invLoc->localisation_id))
                    ->whereNotIn('NumOrdre', $biensScannes)
                    ->get();

                foreach ($biensNonScannes as $bien) {
                    InventaireScan::create([
                        'inventaire_id' => $invLoc->inventaire_id,
                        'inventaire_localisation_id' => $invLoc->id,
                        'bien_id' => $bien->NumOrdre,
                        'date_scan' => now(),
                        'statut_scan' => 'absent',
                        'user_id' => $invLoc->user_id,
                    ]);
                }

                // Mettre à jour le nombre de scans
                $invLoc->refresh();
                $invLoc->update(['nombre_biens_scannes' => $invLoc->inventaireScans()->count()]);
            }

            // Set statut = 'termine', date_fin_scan = now()
            $invLoc->terminer();

            Log::info("Localisation terminée", [
                'inventaire_localisation_id' => $invLoc->id,
                'localisation_id' => $invLoc->localisation_id,
                'biens_scannes' => $invLoc->nombre_biens_scannes,
                'biens_attendus' => $invLoc->nombre_biens_attendus,
            ]);

            // Vérifier si toutes les localisations sont terminées
            $toutesTerminees = $invLoc->inventaire->inventaireLocalisations()
                ->where('statut', '!=', 'termine')
                ->count() === 0;

            if ($toutesTerminees) {
                Log::info("Toutes les localisations sont terminées, l'inventaire peut être finalisé", [
                    'inventaire_id' => $invLoc->inventaire_id,
                ]);
            }

            return true;
        });
    }

    /**
     * Termine un inventaire
     * 
     * @param Inventaire $inventaire L'inventaire à terminer
     * @return bool True si succès
     * @throws Exception Si les conditions ne sont pas remplies
     */
    public function terminerInventaire(Inventaire $inventaire): bool
    {
        return DB::transaction(function () use ($inventaire) {
            // Vérifier statut = 'en_cours'
            if ($inventaire->statut !== 'en_cours') {
                throw new Exception("Seuls les inventaires en cours peuvent être terminés. Statut actuel : {$inventaire->statut}");
            }

            // Vérifier que toutes les localisations sont terminées
            $localisationsNonTerminees = $inventaire->inventaireLocalisations()
                ->where('statut', '!=', 'termine')
                ->count();

            if ($localisationsNonTerminees > 0) {
                throw new Exception("Toutes les localisations doivent être terminées avant de terminer l'inventaire. {$localisationsNonTerminees} localisation(s) restante(s).");
            }

            // Set statut = 'termine', date_fin = now()
            $inventaire->update([
                'statut' => 'termine',
                'date_fin' => now(),
            ]);

            Log::info("Inventaire terminé", [
                'inventaire_id' => $inventaire->id,
                'annee' => $inventaire->annee,
                'user_id' => Auth::id(),
            ]);

            // Event : InventaireTermine
            event(new \App\Events\InventaireTermine($inventaire));

            return true;
        });
    }

    /**
     * Clôture définitivement un inventaire
     * 
     * @param Inventaire $inventaire L'inventaire à clôturer
     * @param User $user L'utilisateur qui clôture
     * @return bool True si succès
     * @throws Exception Si le statut n'est pas 'termine'
     */
    public function cloturerInventaire(Inventaire $inventaire, User $user): bool
    {
        return DB::transaction(function () use ($inventaire, $user) {
            // Vérifier statut = 'termine'
            if ($inventaire->statut !== 'termine') {
                throw new Exception("Seuls les inventaires terminés peuvent être clôturés. Statut actuel : {$inventaire->statut}");
            }

            // Set statut = 'cloture', closed_by = $user->idUser
            $inventaire->cloturer($user->idUser);

            Log::info("Inventaire clôturé", [
                'inventaire_id' => $inventaire->id,
                'annee' => $inventaire->annee,
                'closed_by' => $user->idUser,
            ]);

            // Générer rapport final automatiquement (méthode à implémenter)
            // $this->genererRapportPDF($inventaire);

            // Event : InventaireCloture
            event(new \App\Events\InventaireCloture($inventaire, $user));

            return true;
        });
    }

    /**
     * Calcule les statistiques complètes d'un inventaire
     * 
     * @param Inventaire $inventaire L'inventaire à analyser
     * @return array Tableau avec toutes les statistiques
     */
    public function calculerStatistiques(Inventaire $inventaire): array
    {
        $inventaireLocalisations = $inventaire->inventaireLocalisations;
        $scans = $inventaire->inventaireScans;

        $totalLocalisations = $inventaireLocalisations->count();
        $localisationsTerminees = $inventaireLocalisations->where('statut', 'termine')->count();
        $localisationsEnCours = $inventaireLocalisations->where('statut', 'en_cours')->count();
        $localisationsEnAttente = $inventaireLocalisations->where('statut', 'en_attente')->count();

        $totalBiensAttendus = $inventaireLocalisations->sum('nombre_biens_attendus');
        $totalBiensScannes = $scans->count();

        $biensPresents = $scans->where('statut_scan', 'present')->count();
        $biensDeplaces = $scans->where('statut_scan', 'deplace')->count();
        $biensAbsents = $scans->where('statut_scan', 'absent')->count();
        $biensDeteriores = $scans->where('statut_scan', 'deteriore')->count();
        $biensDefectueux = $scans->where('etat_constate', 'defectueux')->count();

        // Répartition par état physique (Bon Etat, Neuf, Defectueux)
        $biensNeufs = $scans->where('etat_constate', 'neuf')->count();
        $biensBonEtat = $scans->where('etat_constate', 'bon_etat')->count();

        $progressionGlobale = $totalLocalisations > 0 
            ? round(($localisationsTerminees / $totalLocalisations) * 100, 2) 
            : 0;

        $tauxConformite = $totalBiensScannes > 0 
            ? round(($biensPresents / $totalBiensScannes) * 100, 2) 
            : 0;

        // Taux de couverture (% biens scannés vs attendus)
        $tauxCouverture = $totalBiensAttendus > 0 
            ? round(($totalBiensScannes / $totalBiensAttendus) * 100, 2) 
            : 0;

        // Taux d'absence (% biens absents parmi les scannés)
        $tauxAbsence = $totalBiensScannes > 0 
            ? round(($biensAbsents / $totalBiensScannes) * 100, 2) 
            : 0;

        // Biens non scannés (manquants)
        $biensNonScannes = max(0, $totalBiensAttendus - $totalBiensScannes);

        // Taux d'anomalies (déplacés + absents + défectueux)
        $totalAnomalies = $biensDeplaces + $biensAbsents + $biensDefectueux;
        $tauxAnomalies = $totalBiensScannes > 0 
            ? round(($totalAnomalies / $totalBiensScannes) * 100, 2) 
            : 0;

        // Nombre d'agents ayant participé
        $nombreAgents = $inventaireLocalisations->whereNotNull('user_id')->pluck('user_id')->unique()->count();

        // Valeur totale : gesimmo n'a pas de champ valeur_acquisition
        $valeurTotaleScannee = 0;
        $valeurAbsente = 0;

        $dureeJours = $inventaire->duree ?? 0;

        // Statistiques par emplacement (au lieu de par localisation)
        $localisationIds = $inventaireLocalisations->pluck('localisation_id')->unique()->toArray();
        $emplacements = Emplacement::whereIn('idLocalisation', $localisationIds)
            ->with('localisation')
            ->orderBy('CodeEmplacement')
            ->get();

        $parEmplacement = $emplacements->map(function ($emplacement) use ($inventaire) {
            $biensAttendus = $emplacement->immobilisations()->count();
            $biensScannes = $inventaire->inventaireScans()
                ->whereHas('gesimmo', fn ($q) => $q->where('idEmplacement', $emplacement->idEmplacement))
                ->count();
            $biensPresents = $inventaire->inventaireScans()
                ->whereHas('gesimmo', fn ($q) => $q->where('idEmplacement', $emplacement->idEmplacement))
                ->where('statut_scan', 'present')
                ->count();
            $progression = $biensAttendus > 0 ? round(($biensScannes / $biensAttendus) * 100, 2) : 0;
            $tauxConformite = $biensScannes > 0 ? round(($biensPresents / $biensScannes) * 100, 2) : 0;

            return [
                'emplacement_id' => $emplacement->idEmplacement,
                'code' => $emplacement->CodeEmplacement ?? $emplacement->Emplacement ?? 'N/A',
                'designation' => $emplacement->Emplacement ?? $emplacement->CodeEmplacement ?? 'N/A',
                'localisation' => $emplacement->localisation?->CodeLocalisation ?? $emplacement->localisation?->Localisation ?? 'N/A',
                'biens_attendus' => $biensAttendus,
                'biens_scannes' => $biensScannes,
                'biens_presents' => $biensPresents,
                'progression' => $progression,
                'taux_conformite' => $tauxConformite,
            ];
        })->toArray();

        // Garder par_localisation pour compatibilité (utilise par_emplacement agrégé par localisation si besoin)
        $parLocalisation = $inventaireLocalisations->map(function ($invLoc) use ($inventaire) {
            $loc = $invLoc->localisation;
            $biensPresents = $inventaire->inventaireScans()
                ->where('inventaire_localisation_id', $invLoc->id)
                ->where('statut_scan', 'present')
                ->count();
            return [
                'localisation_id' => $invLoc->localisation_id,
                'code' => $loc->CodeLocalisation ?? $loc->Localisation ?? 'N/A',
                'designation' => $loc->Localisation ?? $loc->CodeLocalisation ?? 'N/A',
                'statut' => $invLoc->statut,
                'biens_attendus' => $invLoc->nombre_biens_attendus,
                'biens_scannes' => $invLoc->nombre_biens_scannes,
                'biens_presents' => $biensPresents,
                'progression' => $invLoc->progression,
                'taux_conformite' => $invLoc->taux_conformite,
            ];
        })->toArray();

        // Statistiques par agent
        $parAgent = $inventaireLocalisations->whereNotNull('user_id')
            ->groupBy('user_id')
            ->map(function ($group, $userId) {
                $agent = $group->first()->agent;
                return [
                    'user_id' => $userId,
                    'agent_name' => $agent->name ?? 'N/A',
                    'localisations' => $group->count(),
                    'biens_scannes' => $group->sum('nombre_biens_scannes'),
                ];
            })->values()->toArray();

        // Statistiques par catégorie (via gesimmo → categorie)
        $parNature = $scans->groupBy(function ($scan) {
            return $scan->bien?->categorie?->Categorie ?? 'Non renseigné';
        })->map(function ($group, $categorie) {
            return [
                'nature' => $categorie,
                'total' => $group->count(),
                'presents' => $group->where('statut_scan', 'present')->count(),
                'deplaces' => $group->where('statut_scan', 'deplace')->count(),
                'absents' => $group->where('statut_scan', 'absent')->count(),
            ];
        })->toArray();

        return [
            'total_localisations' => $totalLocalisations,
            'localisations_terminees' => $localisationsTerminees,
            'localisations_en_cours' => $localisationsEnCours,
            'localisations_en_attente' => $localisationsEnAttente,
            'total_biens_attendus' => $totalBiensAttendus,
            'total_biens_scannes' => $totalBiensScannes,
            'biens_presents' => $biensPresents,
            'biens_deplaces' => $biensDeplaces,
            'biens_absents' => $biensAbsents,
            'biens_deteriores' => $biensDeteriores,
            'biens_defectueux' => $biensDefectueux,
            'biens_neufs' => $biensNeufs,
            'biens_bon_etat' => $biensBonEtat,
            'biens_non_scannes' => $biensNonScannes,
            'progression_globale' => $progressionGlobale,
            'taux_conformite' => $tauxConformite,
            'taux_couverture' => $tauxCouverture,
            'taux_absence' => $tauxAbsence,
            'taux_anomalies' => $tauxAnomalies,
            'nombre_agents' => $nombreAgents,
            'valeur_totale_scannee' => $valeurTotaleScannee,
            'valeur_absente' => $valeurAbsente,
            'duree_jours' => $dureeJours,
            'par_localisation' => $parLocalisation,
            'par_emplacement' => $parEmplacement,
            'par_agent' => $parAgent,
            'par_nature' => $parNature,
        ];
    }

    /**
     * Détecte les anomalies dans un inventaire
     * 
     * @param Inventaire $inventaire L'inventaire à analyser
     * @return array Tableau des alertes détectées
     */
    public function detecterAnomalies(Inventaire $inventaire): array
    {
        $alertes = [
            'localisations_non_demarrees' => [],
            'localisations_bloquees' => [],
            'biens_absents_valeur_haute' => [],
            'localisations_non_assignees' => [],
            'biens_deteriores' => [],
            'taux_absence_eleve' => [],
        ];

        // Localisations non démarrées
        $nonDemarrees = $inventaire->inventaireLocalisations()
            ->where('statut', 'en_attente')
            ->with('localisation')
            ->get();
        
        foreach ($nonDemarrees as $invLoc) {
            $alertes['localisations_non_demarrees'][] = [
                'id' => $invLoc->id,
                'code' => $invLoc->localisation->code,
                'designation' => $invLoc->localisation->designation,
            ];
        }

        // Localisations bloquées (pas de scan depuis 24h)
        $ilY24h = now()->subDay();
        $bloquees = $inventaire->inventaireLocalisations()
            ->where('statut', 'en_cours')
            ->where(function ($q) use ($ilY24h) {
                $q->whereNull('date_debut_scan')
                    ->orWhere('date_debut_scan', '<', $ilY24h);
            })
            ->with('localisation')
            ->get();
        
        foreach ($bloquees as $invLoc) {
            $dernierScan = $inventaire->inventaireScans()
                ->where('inventaire_localisation_id', $invLoc->id)
                ->orderBy('date_scan', 'desc')
                ->first();
            
            $joursSansScan = $dernierScan 
                ? $dernierScan->date_scan->diffInDays(now())
                : ($invLoc->date_debut_scan ? $invLoc->date_debut_scan->diffInDays(now()) : 0);
            
            $alertes['localisations_bloquees'][] = [
                'id' => $invLoc->id,
                'code' => $invLoc->localisation->code,
                'designation' => $invLoc->localisation->designation,
                'jours' => $joursSansScan,
            ];
        }

        // Biens absents (gesimmo n'a pas de valeur d'acquisition, on liste tous les absents)
        $biensAbsents = $inventaire->inventaireScans()
            ->where('statut_scan', 'absent')
            ->with('bien.designation')
            ->limit(20)
            ->get();
        
        foreach ($biensAbsents as $scan) {
            $alertes['biens_absents_valeur_haute'][] = [
                'bien_id' => $scan->bien_id,
                'code' => $scan->code_inventaire,
                'designation' => $scan->designation,
                'valeur' => 0,
            ];
        }

        // Localisations non assignées
        $nonAssignees = $inventaire->inventaireLocalisations()
            ->whereNull('user_id')
            ->with('localisation')
            ->get();
        
        foreach ($nonAssignees as $invLoc) {
            $alertes['localisations_non_assignees'][] = [
                'id' => $invLoc->id,
                'code' => $invLoc->localisation->code,
                'designation' => $invLoc->localisation->designation,
            ];
        }

        // Biens détériorés (statut_scan = deteriore)
        $biensDeteriores = $inventaire->inventaireScans()
            ->where('statut_scan', 'deteriore')
            ->with(['bien', 'gesimmo.designation'])
            ->get();
        
        foreach ($biensDeteriores as $scan) {
            $alertes['biens_deteriores'][] = [
                'bien_id' => $scan->bien_id,
                'code' => $scan->code_inventaire,
                'designation' => $scan->designation,
                'etat_constate' => $scan->etat_constate,
            ];
        }

        // Biens défectueux (etat_constate = defectueux, signalés via PWA)
        $biensDefectueux = $inventaire->inventaireScans()
            ->where('etat_constate', 'defectueux')
            ->with(['bien', 'gesimmo.designation', 'localisationReelle'])
            ->get();
        
        foreach ($biensDefectueux as $scan) {
            $photoAbsolutePath = null;
            if ($scan->photo_path) {
                $fullPath = storage_path('app/public/' . ltrim(str_replace('\\', '/', $scan->photo_path), '/'));
                if (file_exists($fullPath)) {
                    $photoAbsolutePath = $fullPath;
                }
            }

            $alertes['biens_defectueux'][] = [
                'bien_id' => $scan->bien_id,
                'code' => $scan->code_inventaire,
                'designation' => $scan->designation,
                'localisation' => $scan->localisationReelle?->CodeLocalisation ?? $scan->localisationReelle?->Localisation ?? 'N/A',
                'photo_path' => $scan->photo_path,
                'photo_absolute' => $photoAbsolutePath,
            ];
        }

        // Taux d'absence élevé (>20% absents)
        $localisationsAvecAbsence = $inventaire->inventaireLocalisations()
            ->where('statut', '!=', 'en_attente')
            ->with('localisation')
            ->get()
            ->filter(function ($invLoc) use ($inventaire) {
                if ($invLoc->nombre_biens_attendus === 0) {
                    return false;
                }
                $scans = $inventaire->inventaireScans()
                    ->where('inventaire_localisation_id', $invLoc->id)
                    ->get();
                $absents = $scans->where('statut_scan', 'absent')->count();
                $tauxAbsence = ($absents / $invLoc->nombre_biens_attendus) * 100;
                return $tauxAbsence > 20;
            });
        
        foreach ($localisationsAvecAbsence as $invLoc) {
            $scans = $inventaire->inventaireScans()
                ->where('inventaire_localisation_id', $invLoc->id)
                ->get();
            $absents = $scans->where('statut_scan', 'absent')->count();
            $tauxAbsence = round(($absents / $invLoc->nombre_biens_attendus) * 100, 1);
            
            $alertes['taux_absence_eleve'][] = [
                'id' => $invLoc->id,
                'code' => $invLoc->localisation->code,
                'designation' => $invLoc->localisation->designation,
                'taux_absence' => $tauxAbsence,
                'biens_absents' => $absents,
            ];
        }

        return $alertes;
    }

    /**
     * Réassigne une localisation à un nouvel agent
     * 
     * @param InventaireLocalisation $invLoc La localisation à réassigner
     * @param User|null $newAgent Le nouvel agent (null pour désassigner)
     * @return bool True si succès
     */
    public function reassignerLocalisation(InventaireLocalisation $invLoc, ?User $newAgent): bool
    {
        $ancienAgent = $invLoc->agent;
        
        $invLoc->update([
            'user_id' => $newAgent?->idUser,
        ]);

        Log::info("Localisation réassignée", [
            'inventaire_localisation_id' => $invLoc->id,
            'ancien_agent_id' => $ancienAgent?->idUser,
            'nouvel_agent_id' => $newAgent?->idUser,
            'user_id' => Auth::id(),
        ]);

        // Notifier nouvel agent (optionnel - à implémenter avec notifications Laravel)
        // if ($newAgent) {
        //     $newAgent->notify(new LocalisationReassignee($invLoc));
        // }

        return true;
    }

    /**
     * Génère un rapport PDF de l'inventaire
     * 
     * @param Inventaire $inventaire L'inventaire à documenter
     * @return string Chemin du fichier PDF généré
     * @todo Implémenter la génération PDF avec DOMPDF
     */
    public function genererRapportPDF(Inventaire $inventaire): string
    {
        // TODO: Implémenter avec DOMPDF
        // Utiliser une vue Blade pour le template
        // Sauvegarder dans storage/app/public/rapports/
        // Retourner le chemin relatif
        
        throw new Exception("Génération PDF non encore implémentée");
    }

    /**
     * Génère un rapport Excel de l'inventaire
     * 
     * @param Inventaire $inventaire L'inventaire à documenter
     * @return string Chemin du fichier Excel généré
     * @todo Implémenter la génération Excel avec Laravel Excel
     */
    public function genererRapportExcel(Inventaire $inventaire): string
    {
        // TODO: Implémenter avec Laravel Excel
        // Créer plusieurs onglets (Résumé, Localisations, Scans, Anomalies)
        // Sauvegarder dans storage/app/public/rapports/
        // Retourner le chemin relatif
        
        throw new Exception("Génération Excel non encore implémentée");
    }
}
