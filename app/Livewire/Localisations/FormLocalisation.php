<?php

namespace App\Livewire\Localisations;

use App\Models\LocalisationImmo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]

class FormLocalisation extends Component
{
    /**
     * Instance de la localisation (null si création)
     */
    public $localisation = null;

    /**
     * ID de la localisation pour l'édition
     */
    public $localisationId = null;

    /**
     * Propriétés du formulaire
     */
    public $Localisation = '';
    public $CodeLocalisation = '';

    /**
     * Initialisation du composant
     * 
     * @param LocalisationImmo|int|string|null $localisation Instance de la localisation pour l'édition, ID, ou null pour la création
     */
    public function mount($localisation = null): void
    {
        if ($localisation) {
            // Si c'est un ID (string ou int) et pas une instance de LocalisationImmo, charger la localisation
            if (!($localisation instanceof LocalisationImmo)) {
                if (is_numeric($localisation) || (is_string($localisation) && ctype_digit($localisation))) {
                    try {
                        $localisation = LocalisationImmo::findOrFail($localisation);
                    } catch (\Exception $e) {
                        Log::warning('Localisation introuvable pour édition:', ['id' => $localisation]);
                        // Si la localisation n'existe pas, traiter comme création
                        $localisation = null;
                    }
                } else {
                    // Si ce n'est ni un ID ni une instance, traiter comme création
                    $localisation = null;
                }
            }
            
            // Si on a une instance valide de LocalisationImmo
            if ($localisation instanceof LocalisationImmo) {
                // Mode édition : charger les valeurs de la localisation
                $this->localisation = $localisation;
                $this->localisationId = $localisation->idLocalisation;
                $this->Localisation = $localisation->Localisation;
                $this->CodeLocalisation = $localisation->CodeLocalisation ?? '';
            }
        }
    }

    /**
     * Génère une suggestion de code automatiquement
     */
    public function generateCodeSuggestion()
    {
        // Compter les localisations existantes pour générer un numéro unique
        $count = LocalisationImmo::count() + 1;
        
        // Générer un code basé sur le nom de la localisation
        $prefix = 'LOC';
        if (!empty($this->Localisation)) {
            // Extraire les premières lettres du nom
            $words = explode(' ', $this->Localisation);
            $initials = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $initials .= strtoupper(substr($word, 0, 1));
                }
            }
            if (strlen($initials) > 0) {
                $prefix = substr($initials, 0, 3);
            }
        }
        
        $this->CodeLocalisation = $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }


    /**
     * Propriété calculée : Vérifie si on est en mode édition
     */
    public function getIsEditProperty(): bool
    {
        return $this->localisation !== null;
    }

    /**
     * Règles de validation
     */
    protected function rules(): array
    {
        return [
            'Localisation' => 'required|string|max:255',
            'CodeLocalisation' => 'nullable|string|max:255',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    protected function messages(): array
    {
        return [
            'Localisation.required' => 'La localisation est obligatoire.',
            'Localisation.max' => 'La localisation ne peut pas dépasser 255 caractères.',
            'CodeLocalisation.max' => 'Le code de localisation ne peut pas dépasser 255 caractères.',
        ];
    }


    /**
     * Sauvegarde la localisation (création ou édition)
     */
    public function save()
    {
        // Normaliser les valeurs vides avant validation
        $this->Localisation = trim($this->Localisation ?? '');
        $this->CodeLocalisation = trim($this->CodeLocalisation ?? '') ?: null;

        // Valider les données
        try {
            $validated = $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Les erreurs de validation sont automatiquement affichées par Livewire
            throw $e;
        }

        try {
            // Préparer les données pour l'insertion
            $data = [
                'Localisation' => trim($validated['Localisation']),
                'CodeLocalisation' => $validated['CodeLocalisation'] ?? null,
            ];

            if ($this->isEdit && $this->localisation) {
                // Mode édition : mettre à jour la localisation existante
                $this->localisation->update($data);
                $localisation = $this->localisation->fresh();
                $message = 'Localisation modifiée avec succès';
            } else {
                // Mode création : créer une nouvelle localisation
                try {
                    DB::beginTransaction();
                    $localisation = LocalisationImmo::create($data);
                    DB::commit();
                    
                    // Vérifier que la création a réussi
                    if (!$localisation || !$localisation->idLocalisation) {
                        throw new \Exception('Échec de la création de la localisation - aucun ID retourné');
                    }
                    
                    $message = 'Localisation créée avec succès';
                } catch (\Illuminate\Database\QueryException $e) {
                    DB::rollBack();
                    Log::error('Erreur SQL lors de la création de localisation', [
                        'message' => $e->getMessage(),
                        'Localisation' => $data['Localisation'] ?? 'N/A'
                    ]);
                    throw new \Exception('Erreur lors de l\'insertion en base de données : ' . $e->getMessage());
                }
            }

            // Invalider le cache des statistiques
            \Illuminate\Support\Facades\Cache::forget('localisations_total_count');
            \Illuminate\Support\Facades\Cache::forget('emplacements_total_count');

            session()->flash('success', $message);

            // Rediriger vers la liste des localisations
            return $this->redirect(route('localisations.index'), navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde localisation', [
                'message' => $e->getMessage(),
                'Localisation' => $this->Localisation ?? 'N/A'
            ]);
            session()->flash('error', 'Une erreur est survenue lors de la sauvegarde : ' . $e->getMessage());
            return;
        }
    }

    /**
     * Annule et redirige vers la liste
     */
    public function cancel()
    {
        return redirect()->route('localisations.index');
    }


    /**
     * Rendu du composant
     */
    public function render()
    {
        return view('livewire.localisations.form-localisation');
    }
}

