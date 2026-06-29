<?php

namespace App\Livewire\Emplacements;

use App\Models\Emplacement;
use App\Models\LocalisationImmo;
use App\Models\Affectation;
use App\Livewire\Traits\WithCachedOptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]

class FormEmplacement extends Component
{
    use WithCachedOptions;
    
    /**
     * Instance de l'emplacement (null si création)
     */
    public $emplacement = null;

    /**
     * ID de l'emplacement pour l'édition
     */
    public $emplacementId = null;

    /**
     * Propriétés du formulaire
     */
    public $Emplacement = '';
    public $CodeEmplacement = '';
    public $idLocalisation = '';
    public $idAffectation = '';

    /**
     * Initialisation du composant
     * 
     * @param Emplacement|int|string|null $emplacement Instance de l'emplacement pour l'édition, ID, ou null pour la création
     */
    public function mount($emplacement = null): void
    {
        if ($emplacement) {
            // Si c'est un ID (string ou int) et pas une instance de Emplacement, charger l'emplacement
            if (!($emplacement instanceof Emplacement)) {
                if (is_numeric($emplacement) || (is_string($emplacement) && ctype_digit($emplacement))) {
                    try {
                        $emplacement = Emplacement::with(['localisation', 'affectation'])->findOrFail($emplacement);
                    } catch (\Exception $e) {
                        Log::warning('Emplacement introuvable pour édition:', ['id' => $emplacement]);
                        // Si l'emplacement n'existe pas, traiter comme création
                        $emplacement = null;
                    }
                } else {
                    // Si ce n'est ni un ID ni une instance, traiter comme création
                    $emplacement = null;
                }
            }
            
            // Si on a une instance valide de Emplacement
            if ($emplacement instanceof Emplacement) {
                // Mode édition : charger les valeurs de l'emplacement
                $this->emplacement = $emplacement;
                $this->emplacementId = $emplacement->idEmplacement;
                $this->Emplacement = $emplacement->Emplacement;
                $this->CodeEmplacement = $emplacement->CodeEmplacement ?? '';
                $this->idLocalisation = $emplacement->idLocalisation ?? '';
                $this->idAffectation = $emplacement->idAffectation ?? '';
            }
        }
    }

    /**
     * Propriété calculée : Retourne toutes les localisations
     */
    public function getLocalisationsProperty()
    {
        return LocalisationImmo::orderBy('Localisation')->get();
    }

    /**
     * Propriété calculée : Options pour SearchableSelect (localisations)
     * Optimisé avec cache pour des performances ultra-rapides
     */
    public function getLocalisationOptionsProperty()
    {
        return $this->getCachedLocalisationOptions();
    }

    /**
     * Propriété calculée : Retourne toutes les affectations
     */
    public function getAffectationsProperty()
    {
        return Affectation::orderBy('Affectation')->get();
    }

    /**
     * Propriété calculée : Options pour SearchableSelect (affectations)
     * Filtre les affectations selon la localisation sélectionnée
     * Optimisé avec cache pour des performances ultra-rapides
     */
    public function getAffectationOptionsProperty()
    {
        return $this->getCachedAffectationOptions($this->idLocalisation);
    }

    /**
     * Réagit au changement de localisation
     * Réinitialise l'affectation quand la localisation change
     */
    public function updatedIdLocalisation($value)
    {
        // Vérifier si l'affectation actuelle appartient toujours à la nouvelle localisation
        if (!empty($this->idAffectation)) {
            $affectation = Affectation::find($this->idAffectation);
            
            // Si l'affectation n'appartient pas à la nouvelle localisation, la réinitialiser
            if (!$affectation || $affectation->idLocalisation != $value) {
                $this->idAffectation = '';
            }
        }
    }

    /**
     * Génère une suggestion de code automatiquement
     */
    public function generateCodeSuggestion()
    {
        // Compter les emplacements existants pour générer un numéro unique
        $count = Emplacement::count() + 1;
        
        // Générer un code basé sur le nom de l'emplacement
        $prefix = 'EMP';
        if (!empty($this->Emplacement)) {
            // Extraire les premières lettres du nom
            $words = explode(' ', $this->Emplacement);
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
        
        $this->CodeEmplacement = $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Propriété calculée : Vérifie si on est en mode édition
     */
    public function getIsEditProperty(): bool
    {
        return $this->emplacement !== null;
    }

    /**
     * Règles de validation
     */
    protected function rules(): array
    {
        return [
            'Emplacement' => 'required|string|max:255',
            'CodeEmplacement' => 'nullable|string|max:255',
            'idLocalisation' => 'required|exists:localisation,idLocalisation',
            'idAffectation' => 'required|exists:affectation,idAffectation',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    protected function messages(): array
    {
        return [
            'Emplacement.required' => 'L\'emplacement est obligatoire.',
            'Emplacement.max' => 'L\'emplacement ne peut pas dépasser 255 caractères.',
            'CodeEmplacement.max' => 'Le code d\'emplacement ne peut pas dépasser 255 caractères.',
            'idLocalisation.required' => 'La localisation est obligatoire.',
            'idLocalisation.exists' => 'La localisation sélectionnée n\'existe pas.',
            'idAffectation.required' => 'L\'affectation est obligatoire.',
            'idAffectation.exists' => 'L\'affectation sélectionnée n\'existe pas.',
        ];
    }

    /**
     * Sauvegarde l'emplacement (création ou édition)
     */
    public function save()
    {
        // Normaliser les valeurs vides avant validation
        $this->Emplacement = trim($this->Emplacement ?? '');
        $this->CodeEmplacement = trim($this->CodeEmplacement ?? '') ?: null;
        $this->idLocalisation = $this->idLocalisation ?: null;
        $this->idAffectation = $this->idAffectation ?: null;

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
                'Emplacement' => trim($validated['Emplacement']),
                'CodeEmplacement' => $validated['CodeEmplacement'] ?? null,
                'idLocalisation' => $validated['idLocalisation'],
                'idAffectation' => $validated['idAffectation'],
            ];

            if ($this->isEdit && $this->emplacement) {
                // Mode édition : mettre à jour l'emplacement existant
                $this->emplacement->update($data);
                $emplacement = $this->emplacement->fresh();
                $message = 'Emplacement modifié avec succès';
            } else {
                // Mode création : créer un nouvel emplacement
                try {
                    DB::beginTransaction();
                    $emplacement = Emplacement::create($data);
                    DB::commit();
                    
                    // Vérifier que la création a réussi
                    if (!$emplacement || !$emplacement->idEmplacement) {
                        throw new \Exception('Échec de la création de l\'emplacement - aucun ID retourné');
                    }
                    
                    $message = 'Emplacement créé avec succès';
                } catch (\Illuminate\Database\QueryException $e) {
                    DB::rollBack();
                    Log::error('Erreur SQL lors de la création d\'emplacement', [
                        'message' => $e->getMessage(),
                        'Emplacement' => $data['Emplacement'] ?? 'N/A'
                    ]);
                    throw new \Exception('Erreur lors de l\'insertion en base de données : ' . $e->getMessage());
                }
            }

            // Invalider le cache des statistiques
            \Illuminate\Support\Facades\Cache::forget('emplacements_total_count');

            session()->flash('success', $message);

            // Rediriger vers le dashboard
            return $this->redirect(route('dashboard'), navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde emplacement', [
                'message' => $e->getMessage(),
                'Emplacement' => $this->Emplacement ?? 'N/A'
            ]);
            session()->flash('error', 'Une erreur est survenue lors de la sauvegarde : ' . $e->getMessage());
            return;
        }
    }

    /**
     * Annule et redirige vers le dashboard
     */
    public function cancel()
    {
        return redirect()->route('dashboard');
    }

    /**
     * Rendu du composant
     */
    public function render()
    {
        return view('livewire.emplacements.form-emplacement');
    }
}
