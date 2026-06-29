<?php

namespace App\Livewire\Inventaires;

use App\Models\Inventaire;
use App\Models\InventaireLocalisation;
use App\Models\InventaireScan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardInventaire extends Component
{
    /**
     * Instance de l'inventaire
     */
    public Inventaire $inventaire;

    /**
     * Propriétés pour les filtres
     */
    public $filterStatutLoc = 'all'; // all, en_attente, en_cours, termine
    public $filterAgent = 'all';
    public $searchLoc = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';
    public $activeAgentDropdown = null;

    /**
     * Initialisation du composant
     */
    public function mount(Inventaire $inventaire): void
    {
        // Vérifier autorisation (admin ou agent assigné)
        $user = Auth::user();
        
        $hasAccess = $inventaire->inventaireLocalisations()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->idUser)
                  ->orWhereHas('agents', fn($sub) => $sub->where('users.idUser', $user->idUser));
            })->exists();

        if (!$user->isAdmin() && !$hasAccess) {
            abort(403, 'Vous n\'avez pas accès à cet inventaire.');
        }

        $this->inventaire = $inventaire->load([
            'inventaireLocalisations.localisation',
            'inventaireLocalisations.agent',
            'inventaireLocalisations.agents',
            'inventaireScans'
        ]);
    }

    /**
     * Propriété calculée : Retourne les statistiques complètes de l'inventaire
     * Note: Le refresh est géré par refreshStatistiques() pour éviter les appels multiples
     */
    public function getStatistiquesProperty(): array
    {
        $inventaireLocalisations = $this->inventaire->inventaireLocalisations;
        $scans = $this->inventaire->inventaireScans;

        $totalLocalisations = $inventaireLocalisations->count();
        $localisationsTerminees = $inventaireLocalisations->where('statut', 'termine')->count();
        $localisationsEnCours = $inventaireLocalisations->where('statut', 'en_cours')->count();
        $localisationsEnAttente = $inventaireLocalisations->where('statut', 'en_attente')->count();

        // Calculer le total de biens attendus
        $totalBiensAttendus = $inventaireLocalisations->sum('nombre_biens_attendus');
        
        // Si le total est 0, recalculer depuis les emplacements (une seule fois)
        if ($totalBiensAttendus == 0) {
            $this->recalculerBiensAttendus();
            // Recharger après mise à jour
            $this->inventaire->load('inventaireLocalisations');
            $inventaireLocalisations = $this->inventaire->inventaireLocalisations;
            $totalBiensAttendus = $inventaireLocalisations->sum('nombre_biens_attendus');
        }

        // Total de biens scannés : max entre localisations et scans uniques
        $totalBiensScannesFromLoc = $inventaireLocalisations->sum('nombre_biens_scannes');
        $totalBiensScannesFromScans = $scans->unique('bien_id')->count();
        $totalBiensScannes = max($totalBiensScannesFromLoc, $totalBiensScannesFromScans);
        
        if ($totalBiensScannes == 0) {
            $totalBiensScannes = $scans->count();
        }

        $biensPresents = $scans->where('statut_scan', 'present')->count();
        $biensDeplaces = $scans->where('statut_scan', 'deplace')->count();
        $biensAbsents = $scans->where('statut_scan', 'absent')->count();
        $biensDeteriores = $scans->where('statut_scan', 'deteriore')->count();
        $biensDefectueux = $scans->where('etat_constate', 'defectueux')->count();

        $progressionGlobale = $totalBiensAttendus > 0 
            ? round(($totalBiensScannes / $totalBiensAttendus) * 100, 1) 
            : 0;
        
        $progressionLocalisations = $totalLocalisations > 0 
            ? round(($localisationsTerminees / $totalLocalisations) * 100, 1) 
            : 0;

        // Conformité réelle = présents / total attendus (pas seulement scannés)
        $tauxConformite = $totalBiensAttendus > 0 
            ? round(($biensPresents / $totalBiensAttendus) * 100, 1) 
            : 0;
        
        // Non encore vérifiés = attendus - scannés
        $biensNonVerifies = max(0, $totalBiensAttendus - $totalBiensScannes);

        $dureeJours = $this->inventaire->duree ?? 0;

        // Scans effectués aujourd'hui
        $scansAujourdhui = $scans->filter(fn($scan) => $scan->date_scan && $scan->date_scan->isToday())->count();

        // Vitesse moyenne (scans par jour)
        $vitesseMoyenne = 0;
        if ($this->inventaire->date_debut && $totalBiensScannes > 0) {
            $joursEcoules = max(1, now()->diffInDays($this->inventaire->date_debut));
            $vitesseMoyenne = round($totalBiensScannes / $joursEcoules, 1);
        }

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
            'progression_globale' => $progressionGlobale,
            'progression_localisations' => $progressionLocalisations,
            'taux_conformite' => $tauxConformite,
            'biens_non_verifies' => $biensNonVerifies,
            'duree_jours' => $dureeJours,
            'scans_aujourdhui' => $scansAujourdhui,
            'vitesse_moyenne' => $vitesseMoyenne,
        ];
    }

    /**
     * Recalcule les biens attendus depuis les emplacements (opération coûteuse, exécutée une seule fois)
     */
    private function recalculerBiensAttendus(): void
    {
        foreach ($this->inventaire->inventaireLocalisations as $invLoc) {
            if ($invLoc->localisation && $invLoc->nombre_biens_attendus == 0) {
                $nombreBiensAttendus = $invLoc->localisation->emplacements()
                    ->withCount('immobilisations')
                    ->get()
                    ->sum('immobilisations_count');
                
                $invLoc->update(['nombre_biens_attendus' => $nombreBiensAttendus]);
            }
        }
    }

    /**
     * Propriété calculée : Retourne les localisations filtrées et triées
     */
    public function getInventaireLocalisationsProperty()
    {
        $query = $this->inventaire->inventaireLocalisations()
            ->with(['localisation', 'agent', 'agents']);

        // Filtre par statut
        if ($this->filterStatutLoc !== 'all') {
            $query->where('statut', $this->filterStatutLoc);
        }

        // Filtre par agent (vérifie user_id legacy ET la table pivot)
        if ($this->filterAgent !== 'all') {
            $agentId = $this->filterAgent;
            $query->where(function ($q) use ($agentId) {
                $q->where('user_id', $agentId)
                  ->orWhereHas('agents', fn($sub) => $sub->where('users.idUser', $agentId));
            });
        }

        // Recherche
        if (!empty($this->searchLoc)) {
            $query->whereHas('localisation', function ($q) {
                $q->where('CodeLocalisation', 'like', '%' . $this->searchLoc . '%')
                    ->orWhere('Localisation', 'like', '%' . $this->searchLoc . '%');
            });
        }

        $results = $query->get();

        $isDesc = $this->sortDirection === 'desc';

        if ($this->sortField === 'code') {
            $results = $results->sortBy(function ($invLoc) {
                return $invLoc->localisation->CodeLocalisation ?? $invLoc->localisation->Localisation ?? '';
            }, SORT_REGULAR, $isDesc);
        } elseif ($this->sortField === 'progression') {
            $results = $results->sortBy(function ($invLoc) {
                return $invLoc->nombre_biens_attendus > 0
                    ? ($invLoc->nombre_biens_scannes / $invLoc->nombre_biens_attendus)
                    : 0;
            }, SORT_NUMERIC, $isDesc);
        }

        return $results->values();
    }

    /**
     * Propriété calculée : Données pour le graphique progression temporelle
     */
    public function getScansGraphDataProperty()
    {
        return $this->inventaire->inventaireScans()->orderBy('date_scan')->get();
    }

    /**
     * Propriété calculée : Retourne la liste des agents assignés à cet inventaire (pour les filtres)
     */
    public function getAgentsProperty()
    {
        $invLocs = $this->inventaire->inventaireLocalisations()
            ->with(['agent', 'agents'])
            ->get();

        $allAgents = collect();

        foreach ($invLocs as $invLoc) {
            if ($invLoc->agent) {
                $allAgents->push($invLoc->agent);
            }
            foreach ($invLoc->agents as $ag) {
                $allAgents->push($ag);
            }
        }

        return $allAgents->unique('idUser')->values();
    }

    /**
     * Propriété calculée : Retourne tous les utilisateurs pouvant être assignés (pour le dropdown d'assignation)
     */
    public function getAgentsDisponiblesProperty()
    {
        return User::whereIn('role', ['agent', 'admin_stock', 'admin'])
            ->orderBy('users')
            ->get();
    }

    /**
     * Propriété calculée : Retourne les 5 derniers scans
     */
    public function getDerniersScansProperty()
    {
        return $this->inventaire->inventaireScans()
            ->with(['bien.designation', 'localisationReelle', 'agent'])
            ->orderBy('date_scan', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Propriété calculée : Retourne les alertes détectées
     */
    public function getAlertesProperty(): array
    {
        $alertes = [
            'localisations_non_demarrees' => [],
            'localisations_bloquees' => [],
            'biens_absents_valeur_haute' => [],
            'biens_defectueux' => [],
            'localisations_non_assignees' => [],
        ];

        // Localisations non démarrées
        $nonDemarrees = $this->inventaire->inventaireLocalisations()
            ->where('statut', 'en_attente')
            ->with('localisation')
            ->get();
        
        foreach ($nonDemarrees as $invLoc) {
            $alertes['localisations_non_demarrees'][] = [
                'id' => $invLoc->id,
                'code' => $invLoc->localisation->CodeLocalisation ?? $invLoc->localisation->Localisation ?? 'N/A',
                'designation' => $invLoc->localisation->Localisation ?? 'N/A',
            ];
        }

        // Localisations bloquées (pas de scan depuis 24h)
        $ilY24h = now()->subDay();
        $bloquees = $this->inventaire->inventaireLocalisations()
            ->where('statut', 'en_cours')
            ->where(function ($q) use ($ilY24h) {
                $q->whereNull('date_debut_scan')
                    ->orWhere('date_debut_scan', '<', $ilY24h);
            })
            ->with('localisation')
            ->get();
        
        foreach ($bloquees as $invLoc) {
            $dernierScan = $this->inventaire->inventaireScans()
                ->where('inventaire_localisation_id', $invLoc->id)
                ->orderBy('date_scan', 'desc')
                ->first();
            
            $joursSansScan = $dernierScan 
                ? $dernierScan->date_scan->diffInDays(now())
                : ($invLoc->date_debut_scan ? $invLoc->date_debut_scan->diffInDays(now()) : 0);
            
            $alertes['localisations_bloquees'][] = [
                'id' => $invLoc->id,
                'code' => $invLoc->localisation->CodeLocalisation ?? $invLoc->localisation->Localisation ?? 'N/A',
                'designation' => $invLoc->localisation->Localisation ?? 'N/A',
                'jours' => $joursSansScan,
            ];
        }

        // Biens absents (liste les 10 premiers)
        $biensAbsents = $this->inventaire->inventaireScans()
            ->where('statut_scan', 'absent')
            ->with('bien.designation')
            ->limit(10)
            ->get();
        
        foreach ($biensAbsents as $scan) {
            $alertes['biens_absents_valeur_haute'][] = [
                'bien_id' => $scan->bien_id,
                'code' => $scan->code_inventaire,
                'designation' => $scan->designation,
                'valeur' => 0,
            ];
        }

        // Biens défectueux (etat_constate = defectueux, signalés via PWA)
        $biensDefectueux = $this->inventaire->inventaireScans()
            ->where('etat_constate', 'defectueux')
            ->with(['gesimmo.designation', 'localisationReelle'])
            ->get();
        
        foreach ($biensDefectueux as $scan) {
            $alertes['biens_defectueux'][] = [
                'bien_id' => $scan->bien_id,
                'code' => $scan->code_inventaire,
                'designation' => $scan->designation,
                'localisation' => $scan->localisationReelle?->CodeLocalisation ?? $scan->localisationReelle?->Localisation ?? 'N/A',
                'photo_url' => $scan->photo_url,
                'commentaire' => $scan->commentaire,
            ];
        }

        // Localisations non assignées (ni via user_id legacy, ni via table pivot)
        $nonAssignees = $this->inventaire->inventaireLocalisations()
            ->whereNull('user_id')
            ->whereDoesntHave('agents')
            ->with('localisation')
            ->get();
        
        foreach ($nonAssignees as $invLoc) {
            $alertes['localisations_non_assignees'][] = [
                'id' => $invLoc->id,
                'code' => $invLoc->localisation->CodeLocalisation ?? $invLoc->localisation->Localisation ?? 'N/A',
                'designation' => $invLoc->localisation->Localisation ?? 'N/A',
            ];
        }

        return $alertes;
    }

    /**
     * Propriété calculée : Retourne le nombre total d'alertes
     * Note: Utilise les données déjà chargées pour éviter un double calcul
     */
    public function getTotalAlertesProperty(): int
    {
        // Compter rapidement sans recalculer toutes les alertes en détail
        $total = 0;
        
        $total += $this->inventaire->inventaireLocalisations()
            ->where('statut', 'en_attente')->count();
        
        $ilY24h = now()->subDay();
        $total += $this->inventaire->inventaireLocalisations()
            ->where('statut', 'en_cours')
            ->where(function ($q) use ($ilY24h) {
                $q->whereNull('date_debut_scan')
                    ->orWhere('date_debut_scan', '<', $ilY24h);
            })->count();
        
        $total += $this->inventaire->inventaireScans()
            ->where('statut_scan', 'absent')
            ->count();
        
        $total += $this->inventaire->inventaireScans()
            ->where('etat_constate', 'defectueux')->count();
        
        $total += $this->inventaire->inventaireLocalisations()
            ->whereNull('user_id')
            ->whereDoesntHave('agents')
            ->count();
        
        return $total;
    }

    /**
     * Recharge les statistiques (appelé par polling)
     */
    public function refresh(): void
    {
        $this->inventaire->refresh();
        // Les propriétés calculées seront recalculées automatiquement
    }

    /**
     * Rafraîchit uniquement les statistiques de manière légère
     * Émet un événement pour déclencher l'animation
     */
    public function refreshStatistiques(): void
    {
        // Recharger l'inventaire avec toutes ses relations
        $this->inventaire->refresh();
        $this->inventaire->load([
            'inventaireLocalisations.localisation',
            'inventaireLocalisations.agent',
            'inventaireScans'
        ]);
        
        // Émettre un événement pour l'indicateur visuel
        $this->dispatch('statistiques-updated');
    }

    /**
     * Passe l'inventaire en cours
     */
    public function passerEnCours(): void
    {
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Seuls les administrateurs peuvent démarrer un inventaire.');
            return;
        }

        if ($this->inventaire->statut !== 'en_preparation') {
            session()->flash('error', 'Seuls les inventaires en préparation peuvent être démarrés.');
            return;
        }

        try {
            $this->inventaire->demarrer();
            $this->inventaire->refresh();
            session()->flash('success', "L'inventaire {$this->inventaire->annee} a été démarré avec succès.");
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors du démarrage: ' . $e->getMessage());
        }
    }

    /**
     * Termine l'inventaire
     */
    public function terminerInventaire(): void
    {
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Seuls les administrateurs peuvent terminer un inventaire.');
            return;
        }

        // Vérifier que toutes les localisations sont terminées
        $localisationsNonTerminees = $this->inventaire->inventaireLocalisations()
            ->where('statut', '!=', 'termine')
            ->count();

        if ($localisationsNonTerminees > 0) {
            session()->flash('error', "Toutes les localisations doivent être terminées avant de terminer l'inventaire. {$localisationsNonTerminees} localisation(s) restante(s).");
            return;
        }

        try {
            $this->inventaire->update([
                'statut' => 'termine',
                'date_fin' => now(),
            ]);
            $this->inventaire->refresh();
            session()->flash('success', "L'inventaire {$this->inventaire->annee} a été terminé avec succès.");
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la finalisation: ' . $e->getMessage());
        }
    }

    /**
     * Clôture définitivement l'inventaire
     */
    public function cloturerInventaire()
    {
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Seuls les administrateurs peuvent clôturer un inventaire.');
            return;
        }

        if ($this->inventaire->statut !== 'termine') {
            session()->flash('error', 'Seuls les inventaires terminés peuvent être clôturés.');
            return;
        }

        try {
            $this->inventaire->cloturer();
            session()->flash('success', "L'inventaire {$this->inventaire->annee} a été clôturé définitivement.");
            return redirect()->route('inventaires.rapport', $this->inventaire);
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la clôture: ' . $e->getMessage());
        }
    }

    /**
     * Réassigne une localisation à un agent (legacy, remplacé par toggleAgentLocalisation)
     */
    public function reassignerLocalisation($invLocId, $userId): void
    {
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Seuls les administrateurs peuvent réassigner des localisations.');
            return;
        }

        $invLoc = InventaireLocalisation::find($invLocId);

        if (!$invLoc || $invLoc->inventaire_id !== $this->inventaire->id) {
            session()->flash('error', 'Localisation introuvable.');
            return;
        }

        try {
            $invLoc->update([
                'user_id' => $userId ?: null,
            ]);
            if ($userId) {
                $invLoc->agents()->syncWithoutDetaching([(int) $userId]);
            }
            session()->flash('success', 'Localisation réassignée avec succès.');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la réassignation: ' . $e->getMessage());
        }
    }

    /**
     * Ajoute/retire un agent d'une localisation (toggle multi-agent)
     */
    public function toggleAgentLocalisation($invLocId, $userId): void
    {
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Seuls les administrateurs peuvent modifier les assignations.');
            return;
        }

        $invLoc = InventaireLocalisation::with('agents')->find($invLocId);

        if (!$invLoc || $invLoc->inventaire_id !== $this->inventaire->id) {
            session()->flash('error', 'Localisation introuvable.');
            return;
        }

        try {
            $userId = (int) $userId;
            $isCurrentlyAssigned = $invLoc->agents->contains('idUser', $userId);

            if ($isCurrentlyAssigned) {
                $invLoc->agents()->detach($userId);
                if ($invLoc->user_id === $userId) {
                    $remaining = $invLoc->agents()->first();
                    $invLoc->update(['user_id' => $remaining?->idUser]);
                }
            } else {
                $invLoc->agents()->attach($userId);
                if (!$invLoc->user_id) {
                    $invLoc->update(['user_id' => $userId]);
                }
            }

            $this->inventaire->load([
                'inventaireLocalisations.localisation',
                'inventaireLocalisations.agent',
                'inventaireLocalisations.agents',
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Change le tri de la colonne
     */
    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleAgentDropdown($invLocId): void
    {
        $this->activeAgentDropdown = $this->activeAgentDropdown === $invLocId ? null : $invLocId;
    }

    public function closeAgentDropdown(): void
    {
        $this->activeAgentDropdown = null;
    }

    /**
     * Rendu du composant
     */
    public function render()
    {
        return view('livewire.inventaires.dashboard-inventaire');
    }
}


