<?php

namespace App\Livewire\Inventaires;

use App\Models\Inventaire;
use App\Models\InventaireLocalisation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ListeInventaires extends Component
{
    use WithPagination;

    /**
     * Propriétés publiques pour les filtres et le tri
     */
    public $filterStatut = 'all';
    public $filterAnnee = '';
    public $sortField = 'annee';
    public $sortDirection = 'desc';
    public $perPage = 10;

    public $showAgentsModal = false;
    public $modalInventaireId = null;
    public $modalInventaireAnnee = null;

    /**
     * Initialisation du composant
     */
    public function mount(): void
    {
        // Réinitialiser la pagination si nécessaire
        $this->resetPage();
    }

    /**
     * Propriété calculée : Retourne la liste des années disponibles
     */
    public function getAnneesProperty()
    {
        return Inventaire::query()
            ->distinct()
            ->orderBy('annee', 'desc')
            ->pluck('annee')
            ->values();
    }

    /**
     * Propriété calculée : Retourne l'inventaire actuellement en cours
     */
    public function getInventaireEnCoursProperty()
    {
        return Inventaire::whereIn('statut', ['en_cours', 'en_preparation'])
            ->with(['creator'])
            ->withCount([
                'inventaireLocalisations',
                'inventaireLocalisations as localisations_terminees_count' => function ($q) {
                    $q->where('statut', 'termine');
                },
                'inventaireScans',
                'inventaireScans as scans_presents_count' => function ($q) {
                    $q->where('statut_scan', 'present');
                },
                'inventaireScans as scans_deplaces_count' => function ($q) {
                    $q->where('statut_scan', 'deplace');
                },
                'inventaireScans as scans_absents_count' => function ($q) {
                    $q->where('statut_scan', 'absent');
                },
            ])
            ->first();
    }

    /**
     * Propriété calculée : Compteurs rapides pour le header
     */
    public function getCompteursProperty(): array
    {
        return [
            'total' => Inventaire::count(),
            'en_cours' => Inventaire::whereIn('statut', ['en_cours', 'en_preparation'])->count(),
            'termines' => Inventaire::where('statut', 'termine')->count(),
            'clotures' => Inventaire::where('statut', 'cloture')->count(),
        ];
    }

    /**
     * Change le tri de la colonne
     */
    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            // Inverser la direction si on clique sur la même colonne
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Nouvelle colonne, tri ascendant par défaut
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        // Réinitialiser la pagination lors du changement de tri
        $this->resetPage();
    }

    /**
     * Réinitialise tous les filtres
     */
    public function resetFilters(): void
    {
        $this->filterStatut = 'all';
        $this->filterAnnee = '';
        $this->resetPage();
    }

    /**
     * Archive un inventaire (change le statut à 'cloture' si 'termine')
     */
    public function archiverInventaire($inventaireId): void
    {
        // Vérifier que l'utilisateur est admin
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires pour clôturer un inventaire.');
            return;
        }

        $inventaire = Inventaire::find($inventaireId);

        if (!$inventaire) {
            session()->flash('error', 'Inventaire introuvable.');
            return;
        }

        if ($inventaire->statut !== 'termine') {
            session()->flash('error', 'Seuls les inventaires terminés peuvent être clôturés.');
            return;
        }

        try {
            $inventaire->cloturer();
            session()->flash('success', "L'inventaire {$inventaire->annee} a été clôturé avec succès.");
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la clôture: ' . $e->getMessage());
        }
    }

    /**
     * Supprime un inventaire
     */
    public function supprimerInventaire($inventaireId): void
    {
        // Vérifier que l'utilisateur est admin
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer un inventaire.');
            return;
        }

        $inventaire = Inventaire::find($inventaireId);

        if (!$inventaire) {
            session()->flash('error', 'Inventaire introuvable.');
            return;
        }

        try {
            // Cascade delete : les scans et localisations seront supprimés automatiquement
            $inventaire->delete();
            session()->flash('success', "L'inventaire {$inventaire->annee} a été supprimé avec succès.");
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Ouvre la modale de gestion des agents pour un inventaire
     */
    public function ouvrirModalAgents($inventaireId): void
    {
        if (!Auth::user()->isAdmin()) {
            return;
        }

        $inventaire = Inventaire::find($inventaireId);
        if (!$inventaire || !in_array($inventaire->statut, ['en_cours', 'en_preparation'])) {
            session()->flash('error', 'Inventaire introuvable ou non modifiable.');
            return;
        }

        $this->modalInventaireId = $inventaireId;
        $this->modalInventaireAnnee = $inventaire->annee;
        $this->showAgentsModal = true;
    }

    /**
     * Ferme la modale
     */
    public function fermerModalAgents(): void
    {
        $this->showAgentsModal = false;
        $this->modalInventaireId = null;
        $this->modalInventaireAnnee = null;
    }

    /**
     * Retourne les localisations de l'inventaire ouvert dans la modale
     */
    public function getModalLocalisationsProperty()
    {
        if (!$this->modalInventaireId) {
            return collect();
        }

        return InventaireLocalisation::where('inventaire_id', $this->modalInventaireId)
            ->with(['localisation', 'agents'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Retourne tous les agents disponibles
     */
    public function getAllAgentsProperty()
    {
        return User::whereIn('role', ['agent', 'admin'])
            ->orderBy('users')
            ->get();
    }

    /**
     * Toggle un agent sur une localisation d'inventaire
     */
    public function toggleAgentLoc($invLocId, $userId): void
    {
        if (!Auth::user()->isAdmin()) {
            return;
        }

        $invLoc = InventaireLocalisation::find($invLocId);
        if (!$invLoc || $invLoc->inventaire_id !== $this->modalInventaireId) {
            return;
        }

        $userId = (int) $userId;

        if ($invLoc->agents->contains('idUser', $userId)) {
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
    }

    /**
     * Ajoute un agent à toutes les localisations de l'inventaire
     */
    public function ajouterAgentPartout($userId): void
    {
        if (!Auth::user()->isAdmin() || !$this->modalInventaireId || !$userId) {
            return;
        }

        $userId = (int) $userId;
        $invLocs = InventaireLocalisation::where('inventaire_id', $this->modalInventaireId)->get();

        foreach ($invLocs as $invLoc) {
            $invLoc->agents()->syncWithoutDetaching([$userId]);
            if (!$invLoc->user_id) {
                $invLoc->update(['user_id' => $userId]);
            }
        }

        session()->flash('success', 'Agent ajouté à toutes les localisations.');
    }

    /**
     * Construit la requête de base pour les inventaires
     */
    protected function getInventairesQuery()
    {
        $query = Inventaire::with(['creator'])
            ->withCount([
                'inventaireLocalisations',
                'inventaireLocalisations as localisations_terminees_count' => function ($q) {
                    $q->where('statut', 'termine');
                },
                'inventaireScans',
                'inventaireScans as scans_presents_count' => function ($q) {
                    $q->where('statut_scan', 'present');
                },
                'inventaireScans as scans_deplaces_count' => function ($q) {
                    $q->where('statut_scan', 'deplace');
                },
                'inventaireScans as scans_absents_count' => function ($q) {
                    $q->where('statut_scan', 'absent');
                },
            ])
            // Sous-requête pour le total de biens attendus (somme des nombre_biens_attendus)
            ->withSum('inventaireLocalisations as total_biens_attendus', 'nombre_biens_attendus');

        // Filtre par statut
        if ($this->filterStatut !== 'all') {
            $query->where('statut', $this->filterStatut);
        }

        // Filtre par année
        if (!empty($this->filterAnnee)) {
            $query->where('annee', $this->filterAnnee);
        }

        // Tri
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    /**
     * Rendu du composant
     */
    public function render()
    {
        $inventaires = $this->getInventairesQuery()->paginate($this->perPage);

        return view('livewire.inventaires.liste-inventaires', [
            'inventaires' => $inventaires,
        ]);
    }
}

