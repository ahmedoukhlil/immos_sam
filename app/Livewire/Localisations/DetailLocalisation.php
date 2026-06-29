<?php

namespace App\Livewire\Localisations;

use App\Models\LocalisationImmo;
use App\Models\Emplacement;
use App\Models\Gesimmo;
use App\Models\InventaireLocalisation;
use App\Models\InventaireScan;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class DetailLocalisation extends Component
{
    use WithPagination;

    /**
     * Instance de la localisation
     */
    public LocalisationImmo $localisation;

    /**
     * Toggle pour afficher/masquer la liste des emplacements
     */
    public $afficherEmplacements = true;

    /**
     * Toggle pour afficher/masquer la liste des biens
     */
    public $afficherBiens = false;

    /**
     * Recherche dans les emplacements
     */
    public $searchEmplacement = '';

    /**
     * Recherche dans les biens
     */
    public $searchBien = '';

    /**
     * Filtre par nature juridique
     */
    public $filterNature = '';

    /**
     * Initialisation du composant
     * 
     * @param LocalisationImmo $localisation
     */
    public function mount(LocalisationImmo $localisation): void
    {
        // Eager load des relations nécessaires
        $this->localisation = $localisation->load([
            'emplacements.affectation',
            'emplacements.immobilisations',
        ]);
    }

    /**
     * Propriété calculée : Retourne les emplacements de cette localisation, filtrés
     */
    public function getEmplacementsProperty()
    {
        $query = $this->localisation->emplacements()->with(['affectation', 'immobilisations']);

        // Recherche
        if (!empty($this->searchEmplacement)) {
            $query->where(function ($q) {
                $q->where('Emplacement', 'like', '%' . $this->searchEmplacement . '%')
                    ->orWhere('CodeEmplacement', 'like', '%' . $this->searchEmplacement . '%');
            });
        }

        return $query->orderBy('Emplacement')->paginate(10, ['*'], 'emplacementsPage');
    }

    /**
     * Propriété calculée : Retourne les statistiques de la localisation
     */
    public function getStatistiquesProperty(): array
    {
        $totalEmplacements = $this->localisation->emplacements()->count();
        
        // Compter les immobilisations via les emplacements
        $totalImmobilisations = Gesimmo::whereHas('emplacement', function ($q) {
            $q->where('idLocalisation', $this->localisation->idLocalisation);
        })->count();

        // Calculer la valeur totale (si disponible dans Gesimmo)
        // Note: Gesimmo n'a pas de champ valeur, donc on retourne 0
        $valeurTotale = 0;

        // Répartition par nature juridique
        $parNature = Gesimmo::whereHas('emplacement', function ($q) {
                $q->where('idLocalisation', $this->localisation->idLocalisation);
            })
            ->with('natureJuridique')
            ->get()
            ->groupBy(function ($gesimmo) {
                return $gesimmo->natureJuridique->NatJur ?? 'Non défini';
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        // Répartition par catégorie
        $parCategorie = Gesimmo::whereHas('emplacement', function ($q) {
                $q->where('idLocalisation', $this->localisation->idLocalisation);
            })
            ->with('categorie')
            ->get()
            ->groupBy(function ($gesimmo) {
                return $gesimmo->categorie->Categorie ?? 'Non défini';
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        // Répartition par état
        $parEtat = Gesimmo::whereHas('emplacement', function ($q) {
                $q->where('idLocalisation', $this->localisation->idLocalisation);
            })
            ->with('etat')
            ->get()
            ->groupBy(function ($gesimmo) {
                return $gesimmo->etat->Etat ?? 'Non défini';
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        return [
            'total_emplacements' => $totalEmplacements,
            'total_immobilisations' => $totalImmobilisations,
            'total_biens' => $totalImmobilisations, // Alias pour la vue
            'valeur_totale' => $valeurTotale,
            'par_nature' => $parNature,
            'par_categorie' => $parCategorie,
            'par_etat' => $parEtat,
        ];
    }

    /**
     * Toggle l'affichage de la liste des emplacements
     */
    public function toggleAfficherEmplacements(): void
    {
        $this->afficherEmplacements = !$this->afficherEmplacements;
        $this->resetPage(); // Réinitialiser la pagination
    }

    /**
     * Toggle l'affichage de la liste des biens
     */
    public function toggleAfficherBiens(): void
    {
        $this->afficherBiens = !$this->afficherBiens;
    }

    /**
     * Propriété calculée : Retourne les biens (immobilisations) de cette localisation, filtrés
     */
    public function getBiensProperty()
    {
        $query = Gesimmo::whereHas('emplacement', function ($q) {
            $q->where('idLocalisation', $this->localisation->idLocalisation);
        })
        ->with(['designation', 'categorie', 'etat', 'natureJuridique', 'emplacement']);

        // Recherche
        if (!empty($this->searchBien)) {
            $query->whereHas('designation', function ($q) {
                $q->where('designation', 'like', '%' . $this->searchBien . '%');
            });
        }

        // Filtre par nature
        if (!empty($this->filterNature)) {
            $query->whereHas('natureJuridique', function ($q) {
                $q->where('NatJur', 'like', '%' . $this->filterNature . '%');
            });
        }

        return $query->orderBy('NumOrdre')->paginate(20, ['*'], 'biensPage');
    }

    /**
     * Propriété calculée : Retourne les derniers inventaires pour cette localisation
     */
    public function getDerniersInventairesProperty()
    {
        return InventaireLocalisation::where('localisation_id', $this->localisation->idLocalisation)
            ->with(['inventaire', 'agent'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Propriété calculée : Retourne tous les inventaires pour cette localisation
     */
    public function getTousInventairesProperty()
    {
        return InventaireLocalisation::where('localisation_id', $this->localisation->idLocalisation)
            ->with(['inventaire', 'agent'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Propriété calculée : Retourne les mouvements récents (entrées et sorties)
     */
    public function getMouvementsRecentsProperty(): array
    {
        // Biens entrés : scans où le bien a été trouvé dans cette localisation
        // (localisation_reelle_id = idLocalisation)
        $entres = InventaireScan::where('localisation_reelle_id', $this->localisation->idLocalisation)
            ->with(['gesimmo.designation', 'inventaire', 'localisationReelle'])
            ->orderBy('date_scan', 'desc')
            ->limit(10)
            ->get();

        // Biens sortis : scans de biens qui étaient censés être dans cette localisation
        // mais qui ont été trouvés ailleurs (statut_scan = 'deplace' ou 'absent')
        // ou localisation_reelle_id différent
        $sortis = InventaireScan::whereHas('gesimmo.emplacement', function ($q) {
                $q->where('idLocalisation', $this->localisation->idLocalisation);
            })
            ->where(function ($q) {
                $q->where('statut_scan', 'deplace')
                    ->orWhere('statut_scan', 'absent')
                    ->orWhere(function ($subQ) {
                        $subQ->whereNotNull('localisation_reelle_id')
                            ->where('localisation_reelle_id', '!=', $this->localisation->idLocalisation);
                    });
            })
            ->with(['gesimmo.designation', 'inventaire', 'localisationReelle'])
            ->orderBy('date_scan', 'desc')
            ->limit(10)
            ->get();

        return [
            'entres' => $entres,
            'sortis' => $sortis,
        ];
    }

    /**
     * Supprime la localisation
     */
    public function supprimer()
    {
        // Vérifier que l'utilisateur est admin
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer une localisation.');
            return;
        }

        // Vérifier qu'aucun emplacement n'est associé
        $nombreEmplacements = $this->localisation->emplacements()->count();
        
        if ($nombreEmplacements > 0) {
            session()->flash('error', "Impossible de supprimer cette localisation : {$nombreEmplacements} emplacement(s) y sont associé(s).");
            return;
        }

        try {
            $this->localisation->delete();
            session()->flash('success', 'La localisation a été supprimée avec succès.');
            return redirect()->route('localisations.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Rendu du composant
     */
    public function render()
    {
        return view('livewire.localisations.detail-localisation');
    }
}

