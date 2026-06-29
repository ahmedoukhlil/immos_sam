<?php

namespace App\Livewire\Biens;

use App\Models\Gesimmo;
use App\Services\AmortissementService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DetailBien extends Component
{
    /**
     * Instance de l'immobilisation
     */
    public Gesimmo $bien;

    /**
     * Mode edition inline
     */
    public bool $editing = false;
    public $editValeurAcquisition = '';
    public $editDateMiseEnService = '';
    public $editDateAcquisition = '';
    public $editObservations = '';

    /**
     * Initialisation du composant
     */
    public function mount(Gesimmo $bien): void
    {
        $this->bien = $bien->load([
            'designation.categorie',
            'categorie',
            'etat',
            'emplacement.localisation',
            'emplacement.affectation',
            'natureJuridique',
            'sourceFinancement',
        ]);
    }

    /**
     * Active le mode edition et charge les valeurs actuelles dans les champs editables.
     */
    public function startEditing(): void
    {
        if (!Auth::user()->isAdmin()) {
            return;
        }

        $this->editValeurAcquisition = $this->bien->valeur_acquisition ?? '';
        $this->editDateMiseEnService = $this->bien->date_mise_en_service ? $this->bien->date_mise_en_service->format('Y-m-d') : '';
        $this->editDateAcquisition = $this->bien->DateAcquisition ?? '';
        $this->editObservations = $this->bien->Observations ?? '';
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function saveDetails(): void
    {
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Permissions insuffisantes.');
            return;
        }

        $validated = $this->validate([
            'editValeurAcquisition' => 'nullable|numeric|min:0',
            'editDateMiseEnService' => 'nullable|date',
            'editDateAcquisition' => 'nullable|integer|min:1900|max:' . (now()->year + 1),
            'editObservations' => 'nullable|string|max:2000',
        ], [
            'editValeurAcquisition.numeric' => 'La valeur doit être un nombre.',
            'editValeurAcquisition.min' => 'La valeur ne peut pas être négative.',
            'editDateMiseEnService.date' => 'Date de mise en service invalide.',
            'editDateAcquisition.integer' => 'L\'année doit être un nombre entier.',
            'editDateAcquisition.min' => 'L\'année doit être >= 1900.',
            'editDateAcquisition.max' => 'L\'année ne peut pas être dans le futur.',
            'editObservations.max' => 'Les observations ne doivent pas dépasser 2000 caractères.',
        ]);

        $this->bien->update([
            'valeur_acquisition' => !empty($validated['editValeurAcquisition']) ? $validated['editValeurAcquisition'] : null,
            'date_mise_en_service' => !empty($validated['editDateMiseEnService']) ? $validated['editDateMiseEnService'] : null,
            'DateAcquisition' => !empty($validated['editDateAcquisition']) ? (int) $validated['editDateAcquisition'] : null,
            'Observations' => $validated['editObservations'] ?: null,
        ]);

        $this->bien->refresh();
        $this->bien->load([
            'designation.categorie',
            'categorie',
            'etat',
            'emplacement.localisation',
            'emplacement.affectation',
            'natureJuridique',
            'sourceFinancement',
        ]);

        $this->editing = false;
        session()->flash('success', 'Immobilisation mise à jour avec succès.');
    }

    /**
     * Propriété calculée : Calcule l'âge de l'immobilisation en années
     * 
     * @return int|null
     */
    public function getAgeProperty(): ?int
    {
        // DateAcquisition contient l'année (ex: 2019)
        if (!$this->bien->DateAcquisition || $this->bien->DateAcquisition <= 1970) {
            return null;
        }
        
        $age = now()->year - $this->bien->DateAcquisition;
        
        // Ne retourner que si l'âge est positif et raisonnable (< 100 ans)
        return ($age > 0 && $age < 100) ? $age : null;
    }

    /**
     * Propriété calculée : Retourne le code d'immobilisation formaté
     */
    public function getCodeFormateProperty(): string
    {
        return $this->bien->code_formate ?? '';
    }

    /**
     * Propriété calculée : Tableau complet d'amortissement
     */
    public function getTableauAmortissementProperty(): ?array
    {
        $service = app(AmortissementService::class);
        return $service->calculerTableau($this->bien);
    }

    /**
     * Propriété calculée : Résumé de l'amortissement
     */
    public function getResumeAmortissementProperty(): ?array
    {
        $service = app(AmortissementService::class);
        return $service->calculerResume($this->bien);
    }

    /**
     * Propriété calculée : Raison de non-amortissabilité
     */
    public function getRaisonNonAmortissableProperty(): ?string
    {
        $service = app(AmortissementService::class);
        return $service->raisonNonAmortissable($this->bien);
    }


    /**
     * Lance l'impression de l'étiquette
     */
    public function telechargerEtiquette()
    {
        // L'URL sera ouverte dans une nouvelle fenêtre avec JavaScript
        // et l'impression sera lancée automatiquement
        $this->dispatch('print-etiquette', url: route('biens.etiquette', $this->bien));
    }

    /**
     * Supprime l'immobilisation
     */
    public function supprimer()
    {
        // Vérifier que l'utilisateur est admin
        if (!Auth::user()->isAdmin()) {
            session()->flash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer une immobilisation.');
            return;
        }

        try {
            $this->bien->delete();
            session()->flash('success', 'L\'immobilisation a été supprimée avec succès.');
            return $this->redirect(route('biens.index'));
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Rendu du composant
     */
    public function render()
    {
        return view('livewire.biens.detail-bien');
    }
}

