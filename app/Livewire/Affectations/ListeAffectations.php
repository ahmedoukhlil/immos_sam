<?php

namespace App\Livewire\Affectations;

use App\Models\Affectation;
use App\Models\Emplacement;
use App\Models\LocalisationImmo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]

class ListeAffectations extends Component
{
    use WithPagination;

    /**
     * Propriétés publiques pour les filtres et la recherche
     */
    public $search = '';
    public $filterLocalisation = '';
    public $sortField = 'Affectation';
    public $sortDirection = 'asc';
    public $perPage = 20;
    public $selectedAffectations = [];

    /**
     * Initialisation du composant
     */
    public function mount(): void
    {
        $this->resetPage();
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
        $this->resetPage();
    }

    /**
     * Propriété calculée : Retourne la liste des localisations
     */
    public function getLocalisationsProperty()
    {
        return LocalisationImmo::orderBy('Localisation')->get();
    }

    /**
     * Options pour SearchableSelect : Localisations
     */
    public function getLocalisationOptionsProperty()
    {
        $options = [[
            'value' => '',
            'text' => 'Toutes les localisations',
        ]];

        $localisations = LocalisationImmo::orderBy('Localisation')
            ->get()
            ->map(function ($localisation) {
                return [
                    'value' => (string)$localisation->idLocalisation,
                    'text' => ($localisation->CodeLocalisation ? $localisation->CodeLocalisation . ' - ' : '') . $localisation->Localisation,
                ];
            })
            ->toArray();

        return array_merge($options, $localisations);
    }

    /**
     * Réinitialise tous les filtres
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterLocalisation = '';
        $this->selectedAffectations = [];
        $this->resetPage();
    }

    /**
     * Supprime une affectation
     */
    public function deleteAffectation($affectationId): void
    {
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer une affectation.');
            return;
        }

        $affectation = Affectation::find($affectationId);

        if (!$affectation) {
            session()->flash('error', 'Affectation introuvable.');
            return;
        }

        // Vérifier qu'aucun emplacement n'est associé à cette affectation
        $nombreEmplacements = $affectation->emplacements()->count();
        
        if ($nombreEmplacements > 0) {
            session()->flash('error', "Impossible de supprimer cette affectation : {$nombreEmplacements} emplacement(s) y sont associé(s).");
            return;
        }

        try {
            $affectation->delete();
            Cache::forget('emplacements_total_count');
            session()->flash('success', 'L\'affectation a été supprimée avec succès.');
            
            $this->selectedAffectations = array_diff($this->selectedAffectations, [$affectationId]);
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Construit la requête de base pour les affectations
     */
    protected function getAffectationsQuery()
    {
        $query = Affectation::withCount('emplacements')
            ->with(['localisation', 'emplacements.localisation']);

        // Recherche globale
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('Affectation', 'like', '%' . $this->search . '%')
                    ->orWhere('CodeAffectation', 'like', '%' . $this->search . '%');
            });
        }

        // Filtre par localisation
        if (!empty($this->filterLocalisation)) {
            $query->whereHas('emplacements', function ($q) {
                $q->where('idLocalisation', $this->filterLocalisation);
            });
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
        $affectations = $this->getAffectationsQuery()->paginate($this->perPage);

        // Statistiques pour le header (avec cache)
        $totalAffectations = Cache::remember('affectations_total_count', 300, function () {
            return Affectation::count();
        });

        return view('livewire.affectations.liste-affectations', [
            'affectations' => $affectations,
            'totalAffectations' => $totalAffectations,
        ]);
    }
}
