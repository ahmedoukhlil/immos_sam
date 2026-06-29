<?php

namespace App\Livewire\Biens;

use App\Models\Gesimmo;
use App\Models\LocalisationImmo;
use App\Models\Emplacement;
use App\Models\Affectation;
use App\Models\Designation;
use App\Models\Categorie;
use App\Livewire\Traits\WithCachedOptions;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ListeBiens extends Component
{
    use WithPagination, WithCachedOptions;

    /**
     * Propriétés publiques pour les filtres et la recherche
     */
    public $search = '';
    public $filterDesignation = '';
    public $filterCategorie = '';
    public $filterLocalisation = '';
    public $filterAffectation = '';
    public $filterEmplacement = '';
    public $filterNumOrdreMin = '';
    public $filterNumOrdreMax = '';
    public $filterDateAcquisition = '';
    public $sortField = 'NumOrdre';
    public $sortDirection = 'asc';
    public $perPage = 20;
    public $selectedBiens = [];


    /**
     * Initialisation du composant
     */
    public function mount(): void
    {
        // Réinitialiser la pagination si nécessaire
        $this->resetPage();
    }

    /**
     * Propriété calculée : Retourne toutes les désignations avec leurs catégories
     * Cache 1h - table de référence rarement modifiée
     */
    public function getDesignationsProperty()
    {
        return cache()->remember('liste_biens_designations', 3600, function () {
            return Designation::with('categorie')
                ->orderBy('designation')
                ->get();
        });
    }

    /**
     * Propriété calculée : Retourne toutes les catégories
     * Cache 1h - table de référence rarement modifiée
     */
    public function getCategoriesProperty()
    {
        return cache()->remember('liste_biens_categories', 3600, function () {
            return Categorie::orderBy('Categorie')->get();
        });
    }

    /**
     * Propriété calculée : Retourne toutes les localisations
     * Utilise le cache partagé pour améliorer les performances
     */
    public function getLocalisationsProperty()
    {
        return cache()->remember('localisation_options_all_collection', 3600, function () {
            return LocalisationImmo::select('idLocalisation', 'Localisation', 'CodeLocalisation')
                ->orderBy('Localisation')
                ->get();
        });
    }

    /**
     * Propriété calculée : Retourne les affectations filtrées selon la localisation sélectionnée
     * Requête directe ultra-optimisée avec cache pour une réponse instantanée
     */
    public function getAffectationsProperty()
    {
        // Si aucune localisation n'est sélectionnée, retourner une collection vide
        if (empty($this->filterLocalisation)) {
            return collect();
        }
        
        // Utiliser le cache avec une clé basée sur la localisation
        $cacheKey = 'affectations_by_localisation_' . $this->filterLocalisation;
        
        return cache()->remember($cacheKey, 300, function () {
            return Affectation::select('idAffectation', 'Affectation', 'CodeAffectation', 'idLocalisation')
                ->whereHas('emplacements', function ($query) {
                    $query->where('idLocalisation', $this->filterLocalisation);
                })
                ->orderBy('Affectation')
                ->get();
        });
    }

    /**
     * Options pour SearchableSelect : Affectations
     * Filtrées selon la localisation sélectionnée
     * Utilise le cache partagé pour une réponse ultra-rapide (< 1ms)
     */
    public function getAffectationOptionsProperty()
    {
        $options = [[
            'value' => '',
            'text' => 'Toutes les affectations',
        ]];
        
        if (empty($this->filterLocalisation)) {
            return $options;
        }
        
        $affectations = $this->getCachedAffectationOptions($this->filterLocalisation);
        return array_merge($options, $affectations);
    }

    /**
     * Propriété calculée : Retourne les emplacements filtrés selon l'affectation
     * Requête directe ultra-optimisée avec cache pour une réponse instantanée
     */
    public function getEmplacementsProperty()
    {
        // Si aucune affectation n'est sélectionnée, retourner une collection vide
        if (empty($this->filterAffectation)) {
            return collect();
        }
        
        // Utiliser le cache avec une clé basée sur les filtres
        $cacheKey = 'emplacements_by_filters_' . ($this->filterLocalisation ?? 'all') . '_' . ($this->filterAffectation ?? 'all');
        
        return cache()->remember($cacheKey, 300, function () {
            // Sélectionner uniquement les colonnes nécessaires pour l'affichage
            $query = Emplacement::select(
                'idEmplacement',
                'Emplacement',
                'CodeEmplacement',
                'idLocalisation',
                'idAffectation'
            );
            
            // Filtrer par localisation si sélectionnée
            if (!empty($this->filterLocalisation)) {
                $query->where('idLocalisation', $this->filterLocalisation);
            }
            
            // Filtrer par affectation si sélectionnée
            if (!empty($this->filterAffectation)) {
                $query->where('idAffectation', $this->filterAffectation);
            }
            
            $emplacements = $query->orderBy('Emplacement')->get();
            
            // Charger les relations en une seule requête si nécessaire (seulement si on a des résultats)
            if ($emplacements->isNotEmpty()) {
                $localisationIds = $emplacements->pluck('idLocalisation')->unique()->filter();
                $affectationIds = $emplacements->pluck('idAffectation')->unique()->filter();
                
                $localisations = collect();
                $affectations = collect();
                
                if ($localisationIds->isNotEmpty()) {
                    $localisations = LocalisationImmo::select('idLocalisation', 'Localisation', 'CodeLocalisation')
                        ->whereIn('idLocalisation', $localisationIds)
                        ->get()
                        ->keyBy('idLocalisation');
                }
                
                if ($affectationIds->isNotEmpty()) {
                    $affectations = Affectation::select('idAffectation', 'Affectation', 'CodeAffectation')
                        ->whereIn('idAffectation', $affectationIds)
                        ->get()
                        ->keyBy('idAffectation');
                }
                
                // Ajouter les relations et le nom d'affichage
                return $emplacements->map(function ($emplacement) use ($localisations, $affectations) {
                    $emplacement->localisation = $localisations->get($emplacement->idLocalisation);
                    $emplacement->affectation = $affectations->get($emplacement->idAffectation);
                    $emplacement->display_name = $this->getEmplacementDisplayName($emplacement);
                    return $emplacement;
                });
            }
            
            return $emplacements;
        });
    }

    /**
     * Options pour SearchableSelect : Emplacements
     * Filtrés selon l'affectation sélectionnée
     * Utilise le cache partagé pour une réponse ultra-rapide (< 1ms)
     */
    public function getEmplacementOptionsProperty()
    {
        $options = [[
            'value' => '',
            'text' => 'Tous les emplacements',
        ]];
        
        if (empty($this->filterAffectation)) {
            return $options;
        }
        
        $emplacements = $this->getCachedEmplacementOptions(
            $this->filterLocalisation,
            $this->filterAffectation,
            300, // cache 5 minutes
            false // nom de l'emplacement uniquement
        );
        
        return array_merge($options, $emplacements);
    }
    
    /**
     * Génère le nom d'affichage d'un emplacement avec ses relations
     */
    private function getEmplacementDisplayName($emplacement): string
    {
        $parts = [];
        
        // Localisation
        if ($emplacement->localisation) {
            $parts[] = $emplacement->localisation->Localisation ?? '';
            if ($emplacement->localisation->CodeLocalisation) {
                $parts[] = '(' . $emplacement->localisation->CodeLocalisation . ')';
            }
        }
        
        // Affectation
        if ($emplacement->affectation) {
            $parts[] = '- ' . ($emplacement->affectation->Affectation ?? '');
        }
        
        // Emplacement
        $parts[] = '- ' . ($emplacement->Emplacement ?? '');
        
        return implode(' ', array_filter($parts));
    }

    /**
     * Requête légère pour récupérer uniquement les NumOrdre (sans eager loading)
     */
    protected function getBiensNumOrdreQuery()
    {
        $query = Gesimmo::query()->select('NumOrdre');

        if (!empty($this->search)) {
            $search = trim($this->search);
            if (is_numeric($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('NumOrdre', '=', (int)$search)
                        ->orWhere('NumOrdre', 'like', '%' . $search . '%')
                        ->orWhereHas('designation', fn($q2) => $q2->where('designation', 'like', '%' . $search . '%'))
                        ->orWhereHas('emplacement', fn($q2) => $q2->where('Emplacement', 'like', '%' . $search . '%'));
                });
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('NumOrdre', 'like', '%' . $search . '%')
                        ->orWhereHas('designation', fn($q2) => $q2->where('designation', 'like', '%' . $search . '%'))
                        ->orWhereHas('emplacement', fn($q2) => $q2->where('Emplacement', 'like', '%' . $search . '%'));
                });
            }
        }
        if (!empty($this->filterDesignation)) $query->where('idDesignation', $this->filterDesignation);
        if (!empty($this->filterCategorie)) $query->where('idCategorie', $this->filterCategorie);
        if (!empty($this->filterEmplacement)) {
            $query->where('idEmplacement', $this->filterEmplacement);
        } else {
            if (!empty($this->filterLocalisation)) $query->whereHas('emplacement', fn($q) => $q->where('idLocalisation', $this->filterLocalisation));
            if (!empty($this->filterAffectation)) $query->whereHas('emplacement', fn($q) => $q->where('idAffectation', $this->filterAffectation));
        }
        if ($this->filterNumOrdreMin !== '' && is_numeric($this->filterNumOrdreMin)) {
            $query->where('NumOrdre', '>=', (int) $this->filterNumOrdreMin);
        }
        if ($this->filterNumOrdreMax !== '' && is_numeric($this->filterNumOrdreMax)) {
            $query->where('NumOrdre', '<=', (int) $this->filterNumOrdreMax);
        }
        if (!empty($this->filterDateAcquisition)) $query->where('DateAcquisition', (int)$this->filterDateAcquisition);

        return $query->orderBy($this->sortField, $this->sortDirection);
    }

    /**
     * Propriété calculée : Vérifie si tous les biens sont sélectionnés
     */
    public function getAllSelectedProperty()
    {
        $allBiensIds = $this->getBiensNumOrdreQuery()->pluck('NumOrdre')->toArray();
        
        if (empty($allBiensIds)) {
            return false;
        }

        return count($this->selectedBiens) === count($allBiensIds) &&
               empty(array_diff($allBiensIds, $this->selectedBiens));
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
        $this->search = '';
        $this->filterDesignation = '';
        $this->filterCategorie = '';
        $this->filterLocalisation = '';
        $this->filterAffectation = '';
        $this->filterEmplacement = '';
        $this->filterNumOrdreMin = '';
        $this->filterNumOrdreMax = '';
        $this->filterDateAcquisition = '';
        $this->selectedBiens = [];
        $this->resetPage();
    }

    /**
     * Réinitialise les filtres dépendants quand la localisation change
     */
    public function updatedFilterLocalisation($value): void
    {
        // Réinitialiser l'affectation et l'emplacement si la localisation change
        $this->filterAffectation = '';
        $this->filterEmplacement = '';
        
        $this->resetPage();
    }

    /**
     * Réinitialise les filtres dépendants quand l'affectation change
     */
    public function updatedFilterAffectation($value): void
    {
        // Réinitialiser l'emplacement si l'affectation change
        $this->filterEmplacement = '';
        
        $this->resetPage();
    }

    /**
     * Sélectionne ou désélectionne tous les biens (toutes pages)
     */
    public function toggleSelectAll(): void
    {
        // Requête légère : uniquement NumOrdre, sans eager loading
        $allBiensIds = $this->getBiensNumOrdreQuery()->pluck('NumOrdre')->toArray();
        
        // Vérifier si tous les biens sont déjà sélectionnés
        $allSelected = !empty($allBiensIds) && 
                       count($this->selectedBiens) === count($allBiensIds) &&
                       empty(array_diff($allBiensIds, $this->selectedBiens));

        if ($allSelected) {
            // Tout désélectionner
            $this->selectedBiens = [];
        } else {
            // Tout sélectionner (fusionner avec les biens déjà sélectionnés)
            $this->selectedBiens = array_unique(array_merge($this->selectedBiens, $allBiensIds));
        }
    }

    /**
     * Supprime un bien
     */
    public function deleteBien($bienId): void
    {
        // Vérifier que l'utilisateur est admin
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer un bien.');
            return;
        }

        $bien = Gesimmo::find($bienId);

        if ($bien) {
            $bien->delete();
            session()->flash('success', 'L\'immobilisation a été supprimée avec succès.');
            
            // Retirer de la sélection si présent
            $this->selectedBiens = array_diff($this->selectedBiens, [$bienId]);
        } else {
            session()->flash('error', 'Immobilisation introuvable.');
        }
    }

    /**
     * Exporte les biens sélectionnés
     */
    public function exportSelected()
    {
        if (empty($this->selectedBiens)) {
            session()->flash('warning', 'Veuillez sélectionner au moins un bien à exporter.');
            return;
        }

        // Rediriger vers la route d'export avec les IDs sélectionnés en paramètre de requête
        $ids = implode(',', $this->selectedBiens);
        return redirect()->route('biens.export-excel', ['ids' => $ids]);
    }

    /**
     * Construit la requête de base pour les immobilisations
     */
    protected function getBiensQuery()
    {
        $query = Gesimmo::with([
            'designation',
            'categorie',
            'etat',
            'emplacement.localisation',
            'emplacement.affectation',
            'natureJuridique',
            'sourceFinancement'
        ]);

        // Recherche globale
        if (!empty($this->search)) {
            $search = trim($this->search);
            
            // Si la recherche est un nombre, prioriser la recherche exacte par NumOrdre
            if (is_numeric($search)) {
                $query->where(function ($q) use ($search) {
                    // Recherche exacte en priorité
                    $q->where('NumOrdre', '=', (int)$search)
                        // Puis recherche partielle sur NumOrdre
                        ->orWhere('NumOrdre', 'like', '%' . $search . '%')
                        // Et aussi sur les autres champs
                        ->orWhereHas('designation', function ($q2) use ($search) {
                            $q2->where('designation', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('emplacement', function ($q2) use ($search) {
                            $q2->where('Emplacement', 'like', '%' . $search . '%');
                        });
                });
                // Trier pour mettre les résultats exacts en premier
                $query->orderByRaw("CASE WHEN NumOrdre = ? THEN 0 ELSE 1 END", [(int)$search]);
            } else {
                // Recherche textuelle
                $query->where(function ($q) use ($search) {
                    $q->where('NumOrdre', 'like', '%' . $search . '%')
                        ->orWhereHas('designation', function ($q2) use ($search) {
                            $q2->where('designation', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('emplacement', function ($q2) use ($search) {
                            $q2->where('Emplacement', 'like', '%' . $search . '%');
                        });
                });
            }
        }

        // Filtre par désignation
        if (!empty($this->filterDesignation)) {
            $query->where('idDesignation', $this->filterDesignation);
        }

        // Filtre par catégorie
        if (!empty($this->filterCategorie)) {
            $query->where('idCategorie', $this->filterCategorie);
        }

        // Filtre par emplacement : idEmplacement suffit (implique localisation + affectation)
        if (!empty($this->filterEmplacement)) {
            $query->where('idEmplacement', $this->filterEmplacement);
        } else {
            // Filtre hiérarchique par localisation (seulement si pas d'emplacement)
            if (!empty($this->filterLocalisation)) {
                $query->whereHas('emplacement', fn($q) => $q->where('idLocalisation', $this->filterLocalisation));
            }
            // Filtre hiérarchique par affectation (seulement si pas d'emplacement)
            if (!empty($this->filterAffectation)) {
                $query->whereHas('emplacement', fn($q) => $q->where('idAffectation', $this->filterAffectation));
            }
        }

        // Filtre par intervalle de NumOrdre
        if ($this->filterNumOrdreMin !== '' && is_numeric($this->filterNumOrdreMin)) {
            $query->where('NumOrdre', '>=', (int) $this->filterNumOrdreMin);
        }
        if ($this->filterNumOrdreMax !== '' && is_numeric($this->filterNumOrdreMax)) {
            $query->where('NumOrdre', '<=', (int) $this->filterNumOrdreMax);
        }

        // Filtre par année d'acquisition
        if (!empty($this->filterDateAcquisition)) {
            $query->where('DateAcquisition', (int)$this->filterDateAcquisition);
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
        $biens = $this->getBiensQuery()->paginate($this->perPage);

        return view('livewire.biens.liste-biens', [
            'biens' => $biens,
        ]);
    }
}

