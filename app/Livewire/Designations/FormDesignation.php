<?php

namespace App\Livewire\Designations;

use App\Models\Designation;
use App\Models\Categorie;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]

class FormDesignation extends Component
{
    /**
     * Instance de la désignation (null si création)
     */
    public $designation = null;

    /**
     * ID de la désignation pour l'édition
     */
    public $designationId = null;

    /**
     * Propriétés du formulaire
     */
    public $designation_name = '';
    public $CodeDesignation = '';
    public $idCat = '';

    /**
     * Initialisation du composant
     * 
     * @param Designation|int|string|null $designation Instance de la désignation pour l'édition, ID, ou null pour la création
     */
    public function mount($designation = null): void
    {
        if ($designation) {
            // Si c'est un ID (string ou int) et pas une instance de Designation, charger la désignation
            if (!($designation instanceof Designation)) {
                if (is_numeric($designation) || (is_string($designation) && ctype_digit($designation))) {
                    try {
                        $designation = Designation::findOrFail($designation);
                    } catch (\Exception $e) {
                        // Si la désignation n'existe pas, traiter comme création
                        $designation = null;
                    }
                } else {
                    // Si ce n'est ni un ID ni une instance, traiter comme création
                    $designation = null;
                }
            }
            
            // Si on a une instance valide de Designation
            if ($designation instanceof Designation) {
                // Mode édition : charger les valeurs
                $this->designation = $designation;
                $this->designationId = $designation->id;
                $this->designation_name = $designation->designation;
                $this->CodeDesignation = $designation->CodeDesignation ?? '';
                $this->idCat = $designation->idCat;
            }
        }
    }

    /**
     * Propriété calculée : Retourne toutes les catégories
     */
    public function getCategoriesProperty()
    {
        return Categorie::orderBy('Categorie')->get();
    }

    /**
     * Options pour SearchableSelect : Catégories
     */
    public function getCategorieOptionsProperty()
    {
        return Categorie::orderBy('Categorie')
            ->get()
            ->map(function ($categorie) {
                return [
                    'value' => (string)$categorie->idCategorie,
                    'text' => $categorie->Categorie,
                ];
            })
            ->toArray();
    }

    /**
     * Génère une suggestion de code basée sur le nom
     */
    public function generateCodeSuggestion()
    {
        if (empty($this->designation_name)) {
            return;
        }

        // Générer un code basé sur les premières lettres du nom
        $words = explode(' ', strtoupper($this->designation_name));
        $code = '';
        
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $code .= substr($word, 0, 1);
            }
        }

        // Limiter à 10 caractères
        $code = substr($code, 0, 10);

        // Vérifier si le code existe déjà
        $existing = Designation::where('CodeDesignation', $code)
            ->where('id', '!=', $this->designationId)
            ->exists();

        if ($existing) {
            // Ajouter un numéro si le code existe
            $counter = 1;
            while (Designation::where('CodeDesignation', $code . $counter)
                ->where('id', '!=', $this->designationId)
                ->exists()) {
                $counter++;
            }
            $code .= $counter;
        }

        $this->CodeDesignation = $code;
    }

    /**
     * Propriété calculée : Vérifie si on est en mode édition
     */
    public function getIsEditProperty(): bool
    {
        return $this->designation !== null;
    }

    /**
     * Règles de validation
     */
    protected function rules(): array
    {
        return [
            'designation_name' => 'required|string|max:255',
            'CodeDesignation' => 'nullable|string|max:50|unique:designation,CodeDesignation,' . ($this->designationId ?? 'NULL') . ',id',
            'idCat' => 'required|exists:categorie,idCategorie',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    protected function messages(): array
    {
        return [
            'designation_name.required' => 'Le nom de la désignation est obligatoire.',
            'designation_name.max' => 'Le nom de la désignation ne peut pas dépasser 255 caractères.',
            'CodeDesignation.max' => 'Le code ne peut pas dépasser 50 caractères.',
            'CodeDesignation.unique' => 'Ce code est déjà utilisé par une autre désignation.',
            'idCat.required' => 'La catégorie est obligatoire.',
            'idCat.exists' => 'La catégorie sélectionnée n\'existe pas.',
        ];
    }

    /**
     * Sauvegarde la désignation (création ou édition)
     */
    public function save()
    {
        // Valider les données
        $validated = $this->validate();

        try {
            if ($this->isEdit) {
                // Mode édition : mettre à jour la désignation existante
                $this->designation->update([
                    'designation' => $validated['designation_name'],
                    'CodeDesignation' => $validated['CodeDesignation'] ?: null,
                    'idCat' => $validated['idCat'],
                ]);

                $message = 'Désignation modifiée avec succès';
            } else {
                // Mode création : créer une nouvelle désignation
                Designation::create([
                    'designation' => $validated['designation_name'],
                    'CodeDesignation' => $validated['CodeDesignation'] ?: null,
                    'idCat' => $validated['idCat'],
                ]);

                $message = 'Désignation créée avec succès';
            }

            session()->flash('success', $message);

            // Rediriger vers la liste
            return redirect()->route('designations.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de la sauvegarde : ' . $e->getMessage());
        }
    }

    /**
     * Annule et redirige vers la liste
     */
    public function cancel()
    {
        return redirect()->route('designations.index');
    }

    /**
     * Rendu du composant
     */
    public function render()
    {
        return view('livewire.designations.form-designation');
    }
}
